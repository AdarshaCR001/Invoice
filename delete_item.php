<?php

require_once('environment.php');

$item_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($item_id <= 0) {
    echo 'Error: Invalid Item ID.';
    exit;
}

try {
    $conn = getDbConnection();

    // Check if item exists
    $stmt_check = $conn->prepare("SELECT item_name FROM items WHERE id = :id");
    $stmt_check->bindParam(':id', $item_id);
    $stmt_check->execute();
    $item = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo 'Error: Item not found.';
        exit;
    }

    // Delete item
    $stmt_del = $conn->prepare("DELETE FROM items WHERE id = :id");
    $stmt_del->bindParam(':id', $item_id);
    $stmt_del->execute();

    echo 'Item deleted successfully!';

} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
