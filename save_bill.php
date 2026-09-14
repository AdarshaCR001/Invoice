<?php

require_once('environment.php');
require_once('htmlPdfConverter.php');
require_once('aws_s3.php');

// Retrieve the JSON array
$billData = isset($_POST['data']) ? $_POST['data'] : null;

if (!$billData) {
    echo 'Error: No bill data provided.';
    exit;
}

$s3_base_url = $_ENV['S3_BASE_URL'];

try {
    $conn = getDbConnection();

    $billData['payment_received'] = isset($billData['payment_received']) ? floatval($billData['payment_received']) : 0.00;
    $buyer_id = isset($billData['buyerId']) ? intval($billData['buyerId']) : 0;

    if ($buyer_id <= 0) {
        echo 'Error: Buyer selection is required.';
        exit;
    }

    $itemsList = isset($billData['items']) && is_array($billData['items']) ? $billData['items'] : [];
    if (count($itemsList) === 0) {
        // Fallback for single item structure
        if (!empty($billData['itemName'])) {
            $itemsList[] = [
                'item_name' => $billData['itemName'],
                'bag' => isset($billData['bag']) ? floatval($billData['bag']) : 0,
                'quantity' => isset($billData['quantity']) ? floatval($billData['quantity']) : 0,
                'price' => isset($billData['price']) ? floatval($billData['price']) : 0
            ];
        } else {
            echo 'Error: At least one item is required.';
            exit;
        }
    }

    $primaryItemName = isset($itemsList[0]['item_name']) ? $itemsList[0]['item_name'] : '';
    if (count($itemsList) > 1) {
        $primaryItemName .= ' (+ ' . (count($itemsList) - 1) . ' items)';
    }

    $totalQuantity = 0;
    $totalBag = 0;
    $weightedPriceSum = 0;

    foreach ($itemsList as $it) {
        $q = isset($it['quantity']) ? floatval($it['quantity']) : 0;
        $p = isset($it['price']) ? floatval($it['price']) : 0;
        $b = isset($it['bag']) ? floatval($it['bag']) : 0;
        $totalQuantity += $q;
        $totalBag += $b;
        $weightedPriceSum += ($q * $p);
    }
    $avgPrice = $totalQuantity > 0 ? ($weightedPriceSum / $totalQuantity) : 0;

    $billData['itemName'] = $primaryItemName;
    $billData['quantity'] = $totalQuantity;
    $billData['price'] = $avgPrice;
    $billData['bag'] = $totalBag;
    $billData['items'] = $itemsList;

    $conn->beginTransaction();

    if (!empty($billData['invoiceNumber'])) {
        $billData['updatedOn'] = date('Y-m-d');

        $stmt = $conn->prepare("UPDATE bills 
                                SET buyer_id = :buyerId, 
                                    item_name = :itemName, 
                                    quantity = :quantity, 
                                    price = :price, 
                                    bag = :bag, 
                                    vehicle_number = :vehicleNumber, 
                                    vehicle_freight = :vehicleFreight, 
                                    payment_received = :paymentReceived,
                                    updated_on = :updatedOn 
                                WHERE invoice_number = :invoiceNumber");

        $stmt->bindParam(':buyerId', $buyer_id);
        $stmt->bindParam(':itemName', $billData['itemName']);
        $stmt->bindParam(':quantity', $billData['quantity']);
        $stmt->bindParam(':price', $billData['price']);
        $stmt->bindParam(':bag', $billData['bag']);
        $stmt->bindParam(':vehicleNumber', $billData['vehicleNumber']);
        $stmt->bindParam(':vehicleFreight', $billData['vehicleFreight']);
        $stmt->bindParam(':paymentReceived', $billData['payment_received']);
        $stmt->bindParam(':updatedOn', $billData['updatedOn']);
        $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);
        $stmt->execute();

        // Delete existing items for update
        $stmt_del = $conn->prepare("DELETE FROM bill_items WHERE invoice_number = :invoiceNumber");
        $stmt_del->bindParam(':invoiceNumber', $billData['invoiceNumber']);
        $stmt_del->execute();

    } else {
        $billData['createdOn'] = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO bills (buyer_id, item_name, quantity, price, bag, vehicle_number, vehicle_freight, payment_received, created_on, updated_on) 
                               VALUES (:buyerId, :itemName, :quantity, :price, :bag, :vehicleNumber, :vehicleFreight, :paymentReceived, :createdOn, :updatedOn)");

        $stmt->bindParam(':buyerId', $buyer_id);
        $stmt->bindParam(':itemName', $billData['itemName']);
        $stmt->bindParam(':quantity', $billData['quantity']);
        $stmt->bindParam(':price', $billData['price']);
        $stmt->bindParam(':bag', $billData['bag']);
        $stmt->bindParam(':vehicleNumber', $billData['vehicleNumber']);
        $stmt->bindParam(':vehicleFreight', $billData['vehicleFreight']);
        $stmt->bindParam(':paymentReceived', $billData['payment_received']);
        $stmt->bindParam(':createdOn', $billData['createdOn']);
        $stmt->bindParam(':updatedOn', $billData['createdOn']);
        $stmt->execute();

        $billData['invoiceNumber'] = $conn->lastInsertId();
    }

    // Insert line items into bill_items
    $stmt_item_ins = $conn->prepare("INSERT INTO bill_items (invoice_number, item_name, bag, quantity, price, amount) 
                                    VALUES (:invoiceNumber, :itemName, :bag, :quantity, :price, :amount)");

    foreach ($itemsList as $it) {
        $in = $billData['invoiceNumber'];
        $name = isset($it['item_name']) ? $it['item_name'] : (isset($it['itemName']) ? $it['itemName'] : '');
        $bg = isset($it['bag']) ? floatval($it['bag']) : 0;
        $qty = isset($it['quantity']) ? floatval($it['quantity']) : 0;
        $pr = isset($it['price']) ? floatval($it['price']) : 0;
        $amt = $qty * $pr;

        $stmt_item_ins->bindParam(':invoiceNumber', $in);
        $stmt_item_ins->bindParam(':itemName', $name);
        $stmt_item_ins->bindParam(':bag', $bg);
        $stmt_item_ins->bindParam(':quantity', $qty);
        $stmt_item_ins->bindParam(':price', $pr);
        $stmt_item_ins->bindParam(':amount', $amt);
        $stmt_item_ins->execute();
    }

    $conn->commit();

    $stmt = $conn->prepare("SELECT created_on FROM bills WHERE invoice_number = :invoiceNumber");
    $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);
    $stmt->execute();
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    $billData['createdOn'] = $record['created_on'];

    // Generate the PDF with the bill data
    $filePath = getUpdatedPdf($billData);
    $file = fopen($filePath, "r");
    if (!$file) {
        throw new Exception("Unable to open file!");
    }

    // Upload the PDF to AWS S3
    $awsUploader = new AWSUploader();
    $a = $awsUploader->uploadFile("bills", $file);
    $fileKey = $s3_base_url . "" . $a;

    // Update the URL in the bills table
    $stmt = $conn->prepare("UPDATE bills SET url = :url WHERE invoice_number = :invoiceNumber");
    $stmt->bindParam(':url', $fileKey);
    $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);
    $stmt->execute();

    fclose($file);
    unlink($filePath);

    echo !empty($_POST['data']['invoiceNumber']) ? 'Bill data updated successfully!' : 'Bill data inserted successfully!';

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo 'Error: ' . $e->getMessage();
}
?>