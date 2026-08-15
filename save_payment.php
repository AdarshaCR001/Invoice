<?php
require_once('environment.php');

$invoiceNumber = isset($_POST['invoiceNumber']) ? intval($_POST['invoiceNumber']) : 0;
$paymentReceived = isset($_POST['payment_received']) ? floatval($_POST['payment_received']) : 0.0;

if ($invoiceNumber <= 0) {
    echo 'Error: Invalid Invoice Number';
    exit;
}

try {
    $conn = getDbConnection();

    $stmt = $conn->prepare("UPDATE bills SET payment_received = :paymentReceived WHERE invoice_number = :invoiceNumber");
    $stmt->bindParam(':paymentReceived', $paymentReceived);
    $stmt->bindParam(':invoiceNumber', $invoiceNumber);
    $stmt->execute();

    echo 'Payment updated successfully!';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
