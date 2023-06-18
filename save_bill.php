<?php
// Retrieve the JSON array
$billData = $_POST['data'];

//Database connection
$host = $_ENV['host'];
$db_name = $_ENV['db_name'];
$db_user = $_ENV['db_user'];
$db_password = $_ENV['db_password'];

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO bills (buyer_name, buyer_company, buyer_address, item_name, quantity, price, bag, vehicle_number, vehicle_freight, created_on, updated_on) 
                           VALUES (:buyerName, :buyerCompany, :buyerAddress, :itemName, :quantity, :price, :bag, :vehicleNumber, :vehicleFreight, :createdOn, :updatedOn)");

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
    $stmt->bindParam(':createdOn', date('Y-m-d'));
    $stmt->bindParam(':updatedOn', date('Y-m-d'));

    // Execute the query
    $stmt->execute();

    // Return a success message
    echo 'Bill data saved successfully!';
} catch (PDOException $e) {
    // Return an error message
    echo 'Error: ' . $e->getMessage();
}
?>
