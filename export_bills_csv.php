<?php

require_once('environment.php');

// Database connection
try {
    $conn = getDbConnection();
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$buyer_filter = isset($_GET['buyer_filter']) ? intval($_GET['buyer_filter']) : 0;
$balance_filter = isset($_GET['balance_filter']) ? $_GET['balance_filter'] : 'all';
$selected_month = isset($_GET['month']) ? $_GET['month'] : 'all';
$selected_year = isset($_GET['year']) ? $_GET['year'] : 'all';

$where_clauses = [];
$params = [];

if ($buyer_filter > 0) {
    $where_clauses[] = "b.buyer_id = :buyer_filter";
    $params[':buyer_filter'] = $buyer_filter;
}

if ($balance_filter === 'remaining') {
    $where_clauses[] = "((b.quantity * b.price) + b.vehicle_freight - b.payment_received) > 0";
} elseif ($balance_filter === 'none') {
    $where_clauses[] = "((b.quantity * b.price) + b.vehicle_freight - b.payment_received) = 0";
}

if ($selected_year !== 'all') {
    $where_clauses[] = "YEAR(b.created_on) = :year";
    $params[':year'] = intval($selected_year);
}

if ($selected_month !== 'all') {
    $where_clauses[] = "MONTH(b.created_on) = :month";
    $params[':month'] = intval($selected_month);
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

try {
    $query = "SELECT b.*, buy.buyer_name, buy.buyer_company, buy.buyer_address,
                     ((b.quantity * b.price) + b.vehicle_freight - b.payment_received) AS balance
              FROM bills b
              JOIN buyers buy ON b.buyer_id = buy.id
              $where_sql
              ORDER BY b.invoice_number DESC";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch line items for multi-item support
    $bill_ids = array_column($bills, 'invoice_number');
    $bill_items_map = [];
    if (count($bill_ids) > 0) {
        $in_clause = implode(',', array_map('intval', $bill_ids));
        $stmt_bi = $conn->query("SELECT * FROM bill_items WHERE invoice_number IN ($in_clause) ORDER BY id ASC");
        $items_rows = $stmt_bi->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items_rows as $bi_row) {
            $bill_items_map[$bi_row['invoice_number']][] = $bi_row;
        }
    }

    // Set headers for CSV download
    $filename = "bills_export_" . date('Y-m-d_H-i-s') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // CSV Header row
    fputcsv($output, [
        'Invoice Number',
        'Invoice Date',
        'Buyer Company',
        'Buyer Address',
        'Item Name',
        'Bag',
        'Quantity (KG)',
        'Price (per KG)',
        'Vehicle Number',
        'Vehicle Freight',
        'Total Amount',
        'Payment Received',
        'Balance'
    ], ",", '"', "\\");

    foreach ($bills as $row) {
        $invoiceDate = date('Y-m-d', strtotime($row['created_on']));
        $billItems = isset($bill_items_map[$row['invoice_number']]) ? $bill_items_map[$row['invoice_number']] : [];
        $balance = $row['balance'] !== null ? $row['balance'] : 0.00;

        if (count($billItems) > 0) {
            $first = true;
            foreach ($billItems as $item) {
                $itemTotal = floatval($item['quantity']) * floatval($item['price']);
                fputcsv($output, [
                    $row['invoice_number'],
                    $invoiceDate,
                    $row['buyer_company'],
                    $row['buyer_address'],
                    $item['item_name'],
                    $item['bag'],
                    $item['quantity'],
                    formatIndianCurrency($item['price']),
                    $row['vehicle_number'],
                    $first ? formatIndianCurrency($row['vehicle_freight']) : formatIndianCurrency(0),
                    formatIndianCurrency($itemTotal + ($first ? floatval($row['vehicle_freight']) : 0)),
                    $first ? formatIndianCurrency($row['payment_received'] !== null ? $row['payment_received'] : 0.00) : formatIndianCurrency(0),
                    $first ? formatIndianCurrency($balance) : formatIndianCurrency(0)
                ], ",", '"', "\\");
                $first = false;
            }
        } else {
            $totalAmount = ($row['price'] * $row['quantity']) + $row['vehicle_freight'];
            fputcsv($output, [
                $row['invoice_number'],
                $invoiceDate,
                $row['buyer_company'],
                $row['buyer_address'],
                $row['item_name'],
                $row['bag'],
                $row['quantity'],
                formatIndianCurrency($row['price']),
                $row['vehicle_number'],
                formatIndianCurrency($row['vehicle_freight']),
                formatIndianCurrency($totalAmount),
                formatIndianCurrency($row['payment_received'] !== null ? $row['payment_received'] : 0.00),
                formatIndianCurrency($balance)
            ], ",", '"', "\\");
        }
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("Export failed: " . $e->getMessage());
}
