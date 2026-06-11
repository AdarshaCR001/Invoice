<?php

require_once('environment.php');

// Retrieve the JSON array
$buyerData = isset($_POST['data']) ? $_POST['data'] : null;

if (!$buyerData) {
    echo 'Error: No data provided.';
    exit;
}

// Database connection
$host = $_ENV['HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_password = $_ENV['DB_PASSWORD'];

try {
    // Create a new PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $buyer_id = isset($buyerData['id']) ? intval($buyerData['id']) : 0;
    $buyer_name = isset($buyerData['buyerName']) ? trim($buyerData['buyerName']) : '';
    $buyer_company = isset($buyerData['buyerCompany']) ? trim($buyerData['buyerCompany']) : '';
    $buyer_address = isset($buyerData['buyerAddress']) ? trim($buyerData['buyerAddress']) : '';

    if (empty($buyer_company)) {
        echo 'Error: Buyer Company is required.';
        exit;
    }
    if (empty($buyer_address)) {
        echo 'Error: Buyer Address is required.';
        exit;
    }

    if ($buyer_id > 0) {
        // Prepare the SQL statement for updating the record
        $stmt = $conn->prepare("UPDATE buyers 
                                SET buyer_name = :buyerName, 
                                    buyer_company = :buyerCompany, 
                                    buyer_address = :buyerAddress 
                                WHERE id = :id");
        $stmt->bindParam(':id', $buyer_id);
        $stmt->bindParam(':buyerName', $buyer_name);
        $stmt->bindParam(':buyerCompany', $buyer_company);
        $stmt->bindParam(':buyerAddress', $buyer_address);
        $stmt->execute();
        echo 'Buyer updated successfully!';
    } else {
        // Prepare the SQL statement for inserting a new record
        $stmt = $conn->prepare("INSERT INTO buyers (buyer_name, buyer_company, buyer_address) 
                               VALUES (:buyerName, :buyerCompany, :buyerAddress)");
        $stmt->bindParam(':buyerName', $buyer_name);
        $stmt->bindParam(':buyerCompany', $buyer_company);
        $stmt->bindParam(':buyerAddress', $buyer_address);
        $stmt->execute();
        echo 'Buyer created successfully!';
    }

} catch (PDOException $e) {
    // Catch unique constraint violation error (Duplicate entry)
    if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
        echo 'Error: Buyer Company must be unique.';
    } else {
        echo 'Error: ' . $e->getMessage();
    }
}
?>
