<?php

require_once('environment.php');

$source_ids = isset($_POST['sourceIds']) ? $_POST['sourceIds'] : [];
$target_id = isset($_POST['targetId']) ? intval($_POST['targetId']) : 0;

if (!is_array($source_ids)) {
    $source_ids = [$source_ids];
}

$source_ids = array_map('intval', $source_ids);
$source_ids = array_filter($source_ids, function($id) {
    return $id > 0;
});

if (empty($source_ids) || $target_id <= 0) {
    echo 'Error: Invalid buyer selection.';
    exit;
}

if (in_array($target_id, $source_ids)) {
    echo 'Error: Target buyer cannot be in the list of source buyers to merge.';
    exit;
}

// Database connection
try {
    $conn = getDbConnection();

    // Start database transaction
    $conn->beginTransaction();

    $in_placeholder = implode(',', array_fill(0, count($source_ids), '?'));

    // 1. Update all bills referencing any of the source buyers to reference the target buyer instead
    $stmt_update = $conn->prepare("UPDATE bills SET buyer_id = ? WHERE buyer_id IN ($in_placeholder)");
    $params = array_merge([$target_id], $source_ids);
    $stmt_update->execute($params);

    // 2. Delete the source buyer records since they are now merged
    $stmt_delete = $conn->prepare("DELETE FROM buyers WHERE id IN ($in_placeholder)");
    $stmt_delete->execute($source_ids);

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
