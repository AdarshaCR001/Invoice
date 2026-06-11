<?php

require_once('environment.php');

$source_id = isset($_POST['sourceId']) ? intval($_POST['sourceId']) : 0;
$target_id = isset($_POST['targetId']) ? intval($_POST['targetId']) : 0;

if ($source_id <= 0 || $target_id <= 0) {
    echo 'Error: Invalid buyer selection.';
    exit;
}

if ($source_id === $target_id) {
    echo 'Error: Source and Target buyers must be different.';
    exit;
}

// Database connection
$host = $_ENV['HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_password = $_ENV['DB_PASSWORD'];

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start database transaction
    $conn->beginTransaction();

    // 1. Update all bills referencing the source buyer to reference the target buyer instead
    $stmt_update = $conn->prepare("UPDATE bills SET buyer_id = :targetId WHERE buyer_id = :sourceId");
    $stmt_update->bindParam(':targetId', $target_id, PDO::PARAM_INT);
    $stmt_update->bindParam(':sourceId', $source_id, PDO::PARAM_INT);
    $stmt_update->execute();

    // 2. Delete the source buyer record since it is now merged
    $stmt_delete = $conn->prepare("DELETE FROM buyers WHERE id = :sourceId");
    $stmt_delete->bindParam(':sourceId', $source_id, PDO::PARAM_INT);
    $stmt_delete->execute();

    // Commit transaction
    $conn->commit();

    echo 'Buyers merged successfully!';
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo 'Error: ' . $e->getMessage();
}
?>
