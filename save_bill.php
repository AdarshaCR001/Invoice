<?php

require_once('environment.php');
require_once('htmlPdfConverter.php');
require_once('aws_s3.php');

// Retrieve the JSON array
$billData = $_POST['data'];

// Database connection
$host = $_ENV['HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_password = $_ENV['DB_PASSWORD'];
$s3_base_url = $_ENV['S3_BASE_URL'];

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!empty($billData['invoiceNumber'])) {
        // If invoiceNumber is present, perform an update
        $billData['updatedOn'] = date('Y-m-d');

        // Prepare the SQL statement for updating the record by invoice_number
        $stmt = $conn->prepare("UPDATE bills 
                                SET buyer_name = :buyerName, 
                                    buyer_company = :buyerCompany, 
                                    buyer_address = :buyerAddress, 
                                    item_name = :itemName, 
                                    quantity = :quantity, 
                                    price = :price, 
                                    bag = :bag, 
                                    vehicle_number = :vehicleNumber, 
                                    vehicle_freight = :vehicleFreight, 
                                    updated_on = :updatedOn 
                                WHERE invoice_number = :invoiceNumber");

        echo 'Bill data updated successfully!';
    } else {
        // If no invoiceNumber is present, create a new record
        $billData['createdOn'] = date('Y-m-d');

        // Prepare the SQL statement for inserting a new record
        $stmt = $conn->prepare("INSERT INTO bills (buyer_name, buyer_company, buyer_address, item_name, quantity, price, bag, vehicle_number, vehicle_freight, created_on, updated_on) 
                               VALUES (:buyerName, :buyerCompany, :buyerAddress, :itemName, :quantity, :price, :bag, :vehicleNumber, :vehicleFreight, :createdOn, :updatedOn)");

        echo 'Bill data inserted successfully!';
    }

    // Bind the parameters (same for both insert and update)
    $stmt->bindParam(':buyerName', $billData['buyerName']);
    $stmt->bindParam(':buyerCompany', $billData['buyerCompany']);
    $stmt->bindParam(':buyerAddress', $billData['buyerAddress']);
    $stmt->bindParam(':itemName', $billData['itemName']);
    $stmt->bindParam(':quantity', $billData['quantity']);
    $stmt->bindParam(':price', $billData['price']);
    $stmt->bindParam(':bag', $billData['bag']);
    $stmt->bindParam(':vehicleNumber', $billData['vehicleNumber']);
    $stmt->bindParam(':vehicleFreight', $billData['vehicleFreight']);

    if (!empty($billData['invoiceNumber'])) {
        $stmt->bindParam(':updatedOn', $billData['createdOn']);
        $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);
    } else {
        $stmt->bindParam(':createdOn', $billData['createdOn']);
        $stmt->bindParam(':updatedOn', $billData['createdOn']);
    }

    // Execute the insert or update query
    $stmt->execute();

    if (empty($billData['invoiceNumber'])) {
        // Get the newly generated invoiceNumber after insert
        $billData['invoiceNumber'] = $conn->lastInsertId();
    }

    $stmt = $conn->prepare("SELECT created_on FROM bills WHERE invoice_number = :invoiceNumber");
    $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);
    $stmt->execute();
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    $billData['createdOn'] = $record['created_on'];


    // Generate the PDF with the bill data
    $filePath = getUpdatedPdf($billData);
    $file = fopen($filePath, "r") or die("Unable to open file!");

    // Upload the PDF to AWS S3
    $awsUploader = new AWSUploader();
    $a = $awsUploader->uploadFile("bills", $file);
    $fileKey = $s3_base_url . "" . $a;

    // Update the URL in the bills table
    $stmt = $conn->prepare("UPDATE bills SET url = :url WHERE invoice_number = :invoiceNumber");
    $stmt->bindParam(':url', $fileKey);
    $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);
    
    // Execute the update query for the file URL
    $stmt->execute();

    // Close the file and delete the temporary file
    fclose($file);
    unlink($filePath);

} catch (PDOException $e) {
    // Return an error message
    echo 'Error: ' . $e->getMessage();
}

?>
