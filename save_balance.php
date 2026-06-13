<?php
require_once('environment.php');

$invoiceNumber = isset($_POST['invoiceNumber']) ? intval($_POST['invoiceNumber']) : 0;
$balance = isset($_POST['balance']) ? floatval($_POST['balance']) : 0.0;

if ($invoiceNumber <= 0) {
    echo 'Error: Invalid Invoice Number';
    exit;
}

try {
    $conn = getDbConnection();

    $stmt = $conn->prepare("UPDATE bills SET balance = :balance WHERE invoice_number = :invoiceNumber");
    $stmt->bindParam(':balance', $balance);
    $stmt->bindParam(':invoiceNumber', $invoiceNumber);
    $stmt->execute();

    echo 'Balance updated successfully!';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
