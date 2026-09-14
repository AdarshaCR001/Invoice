<?php

require_once('environment.php');

// Retrieve the JSON array
$itemData = isset($_POST['data']) ? $_POST['data'] : null;

if (!$itemData) {
    echo 'Error: No data provided.';
    exit;
}

// Database connection
try {
    $conn = getDbConnection();

    $item_id = isset($itemData['id']) ? intval($itemData['id']) : 0;
    $item_name = isset($itemData['itemName']) ? trim($itemData['itemName']) : '';

    if (empty($item_name)) {
        echo 'Error: Item Name is required.';
        exit;
    }

    if ($item_id > 0) {
        // Prepare SQL statement for updating record
        $stmt = $conn->prepare("UPDATE items SET item_name = :itemName WHERE id = :id");
        $stmt->bindParam(':id', $item_id);
        $stmt->bindParam(':itemName', $item_name);
        $stmt->execute();
        echo 'Item updated successfully!';
    } else {
        // Prepare SQL statement for inserting new record
        $stmt = $conn->prepare("INSERT INTO items (item_name) VALUES (:itemName)");
        $stmt->bindParam(':itemName', $item_name);
        $stmt->execute();
        echo 'Item created successfully!';
    }

} catch (PDOException $e) {
    if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
        echo 'Error: Item Name must be unique.';
    } else {
        echo 'Error: ' . $e->getMessage();
    }
}
?>
