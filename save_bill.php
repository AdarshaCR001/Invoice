<?php

require_once('environment.php');
require_once('htmlPdfConverter.php');
require_once('aws_s3.php');

// Retrieve the JSON array
$billData = $_POST['data'];

//Database connection
$host = $_ENV['HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_password = $_ENV['DB_PASSWORD'];
$s3_base_url = $_ENV['S3_BASE_URL'];

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO bills (buyer_name, buyer_company, buyer_address, item_name, quantity, price, bag, vehicle_number, vehicle_freight, created_on, updated_on) 
                           VALUES (:buyerName, :buyerCompany, :buyerAddress, :itemName, :quantity, :price, :bag, :vehicleNumber, :vehicleFreight, :createdOn, :updatedOn)");

    $billData['createdOn'] = date('Y-m-d');
    // Bind the parameters
    $stmt->bindParam(':buyerName', $billData['buyerName']);
    $stmt->bindParam(':buyerCompany', $billData['buyerCompany']);
    $stmt->bindParam(':buyerAddress', $billData['buyerAddress']);
    $stmt->bindParam(':itemName', $billData['itemName']);
    $stmt->bindParam(':quantity', $billData['quantity']);
    $stmt->bindParam(':price', $billData['price']);
    $stmt->bindParam(':bag', $billData['bag']);
    $stmt->bindParam(':vehicleNumber', $billData['vehicleNumber']);
    $stmt->bindParam(':vehicleFreight', $billData['vehicleFreight']);
    $stmt->bindParam(':createdOn', $billData['createdOn']);
    $stmt->bindParam(':updatedOn', $billData['createdOn']);

    // Execute the query
    $stmt->execute();

    $billData['invoiceNumber'] = $conn->lastInsertId();

    $filePath = getUpdatedPdf($billData);
    $file = fopen($filePath, "r") or die("Unable to open file!");
    $awsUploader = new AWSUploader();
    $a=$awsUploader->uploadFile("bills", $file);
    $fileKey = $s3_base_url."".$a;

    $stmt  = $conn->prepare("UPDATE bills SET url = :url WHERE invoice_number = :invoiceNumber");
    $stmt->bindParam(':url', $fileKey);
    $stmt->bindParam(':invoiceNumber', $billData['invoiceNumber']);

    // Execute the query
    $stmt->execute();

    fclose($file);
    unlink($filePath);

    // Return a success message
    echo 'Bill data saved successfully!';
} catch (PDOException $e) {
    // Return an error message
    echo 'Error: ' . $e->getMessage();
}
?>
