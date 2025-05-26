<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once('environment.php');
require_once('htmlPdfConverter.php');
require_once('aws_s3.php');

// Retrieve the JSON array
$billData = $_POST['data'];

// --- CSRF Token Validation ---
if (!isset($billData['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $billData['csrf_token'])) {
    error_log("CSRF token validation failed. Submitted token: " . (isset($billData['csrf_token']) ? $billData['csrf_token'] : "NULL") . " Session token: " . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : "NULL"));
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request (CSRF token mismatch). Please refresh the page and try again.']);
    exit;
}
// Optional: Regenerate token after successful validation for this request to prevent reuse on next submission
// For this task, we are focusing on validation. If regeneration is desired after successful *processing*:
// unset($_SESSION['csrf_token']); // Then index.php would generate a new one on next load.
// Or generate a new one here and pass it back if the form is to be re-used immediately via AJAX without page reload.

// --- Input Sanitization and Validation ---
$errors = [];

// Sanitize string inputs
$billData['buyerName'] = isset($billData['buyerName']) ? htmlspecialchars(trim($billData['buyerName']), ENT_QUOTES, 'UTF-8') : '';
$billData['buyerCompany'] = isset($billData['buyerCompany']) ? htmlspecialchars(trim($billData['buyerCompany']), ENT_QUOTES, 'UTF-8') : '';
$billData['buyerAddress'] = isset($billData['buyerAddress']) ? htmlspecialchars(trim($billData['buyerAddress']), ENT_QUOTES, 'UTF-8') : '';
$billData['itemName'] = isset($billData['itemName']) ? htmlspecialchars(trim($billData['itemName']), ENT_QUOTES, 'UTF-8') : '';
$billData['vehicleNumber'] = isset($billData['vehicleNumber']) ? htmlspecialchars(trim($billData['vehicleNumber']), ENT_QUOTES, 'UTF-8') : '';
// invoiceNumber is typically an integer or a specific format, used in WHERE clause, prepared statements handle its safety.
// If it were to be displayed, htmlspecialchars would be good. For now, let's ensure it's what we expect if present.
$billData['invoiceNumber'] = isset($billData['invoiceNumber']) ? trim($billData['invoiceNumber']) : null;


// Sanitize and validate numeric inputs
$billData['quantity'] = isset($billData['quantity']) ? floatval($billData['quantity']) : 0;
$billData['price'] = isset($billData['price']) ? floatval($billData['price']) : 0;
$billData['bag'] = isset($billData['bag']) ? floatval($billData['bag']) : 0;
$billData['vehicleFreight'] = isset($billData['vehicleFreight']) ? floatval($billData['vehicleFreight']) : 0;

// Validation: Required fields
if (empty($billData['buyerCompany'])) {
    $errors[] = "Buyer Company is required.";
}
if (empty($billData['buyerAddress'])) {
    $errors[] = "Buyer Address is required.";
}
if (empty($billData['itemName'])) {
    $errors[] = "Item Name is required.";
}
if (empty($billData['vehicleNumber'])) {
    $errors[] = "Vehicle Number is required.";
}

// Validation: Numeric fields
if ($billData['quantity'] <= 0) {
    $errors[] = "Quantity must be a positive number.";
}
if ($billData['price'] <= 0) {
    $errors[] = "Price must be a positive number.";
}
if ($billData['bag'] <= 0) {
    $errors[] = "Bag must be a positive number.";
}
if ($billData['vehicleFreight'] < 0) {
    $errors[] = "Vehicle Freight cannot be negative.";
}

// If validation errors exist, send JSON error response and exit
if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// --- End of Input Sanitization and Validation ---

// Database connection
$host = $_ENV['HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_password = $_ENV['DB_PASSWORD'];
$s3_base_url = $_ENV['S3_BASE_URL'];

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!empty($billData['invoiceNumber'])) {
        // If invoiceNumber is present, perform an update
        $billData['updatedOn'] = date('Y-m-d H:i:s'); // Use H:i:s for datetime precision

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
        // Bind updatedOn for update
        $stmt->bindParam(':updatedOn', $billData['updatedOn']);
        $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);

    } else {
        // If no invoiceNumber is present, create a new record
        $billData['createdOn'] = date('Y-m-d H:i:s'); // Use H:i:s for datetime precision
        $billData['updatedOn'] = $billData['createdOn']; // Set updatedOn to createdOn for new records

        // Prepare the SQL statement for inserting a new record
        $stmt = $conn->prepare("INSERT INTO bills (buyer_name, buyer_company, buyer_address, item_name, quantity, price, bag, vehicle_number, vehicle_freight, created_on, updated_on) 
                               VALUES (:buyerName, :buyerCompany, :buyerAddress, :itemName, :quantity, :price, :bag, :vehicleNumber, :vehicleFreight, :createdOn, :updatedOn)");
        // Bind createdOn and updatedOn for insert
        $stmt->bindParam(':createdOn', $billData['createdOn']);
        $stmt->bindParam(':updatedOn', $billData['updatedOn']);
    }

    // Bind the common parameters
    $stmt->bindParam(':buyerName', $billData['buyerName']);
    $stmt->bindParam(':buyerCompany', $billData['buyerCompany']);
    $stmt->bindParam(':buyerAddress', $billData['buyerAddress']);
    $stmt->bindParam(':itemName', $billData['itemName']);
    $stmt->bindParam(':quantity', $billData['quantity']);
    $stmt->bindParam(':price', $billData['price']);
    $stmt->bindParam(':bag', $billData['bag']);
    $stmt->bindParam(':vehicleNumber', $billData['vehicleNumber']);
    $stmt->bindParam(':vehicleFreight', $billData['vehicleFreight']);

    // Execute the insert or update query
    $stmt->execute();

    $isInsert = false;
    if (empty($billData['invoiceNumber'])) {
        // Get the newly generated invoiceNumber after insert
        $billData['invoiceNumber'] = $conn->lastInsertId();
        $isInsert = true;
    }

    // For inserts, createdOn was just set. For updates, we need to fetch it if not already available or for consistency.
    // However, the PDF generation part might need the original created_on date if it's an update.
    // Let's ensure billData['createdOn'] is correctly populated for the PDF.
    if (!$isInsert) {
        // If it's an update, we might want to preserve the original created_on date for the PDF.
        // The current logic updates 'updatedOn' but 'createdOn' remains the same for an update.
        // The select query below fetches the original created_on date.
        $stmtCreatedOn = $conn->prepare("SELECT created_on FROM bills WHERE invoice_number = :invoiceNumber");
        $stmtCreatedOn->bindParam(':invoiceNumber', $billData['invoiceNumber']);
        $stmtCreatedOn->execute();
        $record = $stmtCreatedOn->fetch(PDO::FETCH_ASSOC);
        if ($record) {
            $billData['createdOn'] = $record['created_on']; // Use fetched original created_on for PDF
        }
        // If it was an update, updatedOn was already set.
    }
    // If it was an insert, billData['createdOn'] and billData['updatedOn'] were already set before the insert.


    // Generate the PDF with the bill data
    $filePath = getUpdatedPdf($billData);
    $file = fopen($filePath, "r") or die("Unable to open file!");

    // Upload the PDF to AWS S3
    $awsUploader = new AWSUploader();
    $s3ObjectKey = $awsUploader->uploadFile("bills", $file); // uploadFile now returns false on failure, or the object key on success

    fclose($file); // Close the file handle as soon as possible

    if ($s3ObjectKey === false) {
        // S3 upload failed
        error_log("Failed to upload PDF to S3 for invoice: " . $billData['invoiceNumber'] . ". Local filePath: " . $filePath);
        unlink($filePath); // Clean up the local temporary PDF file

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to save bill due to a storage error. Please try again.']);
        exit;
    }

    // S3 upload was successful, proceed to update the database
    $fullS3Url = $s3_base_url . $s3ObjectKey;

    // Update the URL in the bills table
    $stmt = $conn->prepare("UPDATE bills SET url = :url WHERE invoice_number = :invoiceNumber");
    $stmt->bindParam(':url', $fullS3Url);
    $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);
    
    // Execute the update query for the file URL
    $stmt->execute();

    // Delete the temporary local file as it's successfully uploaded
    unlink($filePath);

    // Regenerate CSRF token after successful processing
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Content-Type: application/json');
    if ($isInsert) {
        echo json_encode(['success' => true, 'message' => 'Bill data inserted successfully!', 'invoiceNumber' => $billData['invoiceNumber']]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Bill data updated successfully!', 'invoiceNumber' => $billData['invoiceNumber']]);
    }

} catch (PDOException $e) {
    // Log the detailed PDO error to the server's error log
    error_log("PDOException in save_bill.php: " . $e->getMessage() . " | SQL State: " . $e->getCode() . " | Bill data (invoiceNumber part): " . (isset($billData['invoiceNumber']) ? $billData['invoiceNumber'] : 'N/A') . " | All bill data: " . json_encode($billData));
    
    // Clean up local file if it exists and $filePath is set (error might occur before $filePath is defined)
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }
    if (isset($file) && is_resource($file)) { // Ensure $file is a resource before trying to close
        fclose($file);
    }


    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An error occurred while saving the bill.']);
} catch (Exception $e) { // Catch any other general exceptions
    error_log("General Exception in save_bill.php: " . $e->getMessage() . " | Bill data (invoiceNumber part): " . (isset($billData['invoiceNumber']) ? $billData['invoiceNumber'] : 'N/A') . " | All bill data: " . json_encode($billData));

    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }
    if (isset($file) && is_resource($file)) {
        fclose($file);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred. Please try again.']);
}


?>
