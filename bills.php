<?php

require_once('environment.php');

// Database connection
$host = $_ENV['HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_password = $_ENV['DB_PASSWORD'];

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_from = ($page - 1) * $records_per_page;

$buyer_filter = isset($_GET['buyer_filter']) ? intval($_GET['buyer_filter']) : 0;
$balance_filter = isset($_GET['balance_filter']) ? $_GET['balance_filter'] : 'all';

$where_clauses = [];
$params = [];

if ($buyer_filter > 0) {
    $where_clauses[] = "b.buyer_id = :buyer_filter";
    $params[':buyer_filter'] = $buyer_filter;
}

if ($balance_filter === 'remaining') {
    $where_clauses[] = "b.balance > 0";
} elseif ($balance_filter === 'none') {
    $where_clauses[] = "b.balance = 0";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

try {
    // Retrieve list of buyers for the form dropdown selection and filter dropdown
    $stmt_buyers = $conn->prepare("SELECT * FROM buyers ORDER BY buyer_company ASC");
    $stmt_buyers->execute();
    $buyers = $stmt_buyers->fetchAll(PDO::FETCH_ASSOC);

    // Retrieve data from the database with active filters
    $query = "SELECT b.*, buy.buyer_name, buy.buyer_company, buy.buyer_address 
              FROM bills b 
              JOIN buyers buy ON b.buyer_id = buy.id 
              $where_sql 
              ORDER BY b.invoice_number DESC 
              LIMIT :start_from, :records_per_page";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':start_from', $start_from, PDO::PARAM_INT);
    $stmt->bindValue(':records_per_page', $records_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total number of records under active filter
    $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM bills b $where_sql");
    foreach ($params as $key => $val) {
        $stmt_count->bindValue($key, $val);
    }
    $stmt_count->execute();
    $row = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $total_records = $row['total'];

    // Calculate total number of pages
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}

function getPaginationLink($p, $buyer_filter, $balance_filter) {
    $params = ['page' => $p];
    if ($buyer_filter > 0) {
        $params['buyer_filter'] = $buyer_filter;
    }
    if ($balance_filter !== 'all') {
        $params['balance_filter'] = $balance_filter;
    }
    return '?' . http_build_query($params);
}
?>

<!-- HTML and CSS for displaying the data -->
<!DOCTYPE html>
<html>
<head>
    <title>Bills</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: radial-gradient(circle at 10% 20%, #15161e 0%, #0c0d12 90%);
            --card-bg: rgba(255, 255, 255, 0.03);
            --border-color: rgba(255, 255, 255, 0.08);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --primary: #4f46e5;
            --primary-hover: #6366f1;
            --accent-green: #10b981;
            --accent-green-hover: #34d399;
            --accent-orange: #f59e0b;
            --accent-orange-hover: #fbbf24;
            --accent-cyan: #06b6d4;
            --accent-cyan-hover: #22d3ee;
            --glass-glow: 0 8px 32px 0 rgba(0, 0, 0, 0.4);
            --modal-bg: #161722;
            --modal-overlay-bg: rgba(5, 6, 8, 0.85);
            --input-bg: #0c0d12;
            --heading-gradient: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            --row-hover: rgba(255, 255, 255, 0.02);
        }

        body.light-theme {
            --bg-main: radial-gradient(circle at 10% 20%, #f4f6f9 0%, #e5e7eb 90%);
            --card-bg: rgba(255, 255, 255, 0.7);
            --border-color: rgba(0, 0, 0, 0.08);
            --text-main: #1f2937;
            --text-muted: #4b5563;
            --primary: #4f46e5;
            --primary-hover: #6366f1;
            --accent-green: #10b981;
            --accent-green-hover: #059669;
            --accent-orange: #f59e0b;
            --accent-orange-hover: #d97706;
            --accent-cyan: #06b6d4;
            --accent-cyan-hover: #0891b2;
            --glass-glow: 0 8px 32px 0 rgba(31, 41, 55, 0.1);
            --modal-bg: #ffffff;
            --modal-overlay-bg: rgba(31, 41, 55, 0.4);
            --input-bg: #f9fafb;
            --heading-gradient: linear-gradient(135deg, #1f2937 0%, #4f46e5 100%);
            --row-hover: rgba(0, 0, 0, 0.02);
        }

        body {
            background: var(--bg-main) !important;
            color: var(--text-main) !important;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
        }

        h1 {
            font-weight: 700;
            font-size: 2.5rem;
            letter-spacing: -0.02em;
            margin-bottom: 0px;
            background: var(--heading-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .dashboard-header {
            margin-bottom: 40px;
        }

        .tabs-container {
            display: flex;
            gap: 4px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            padding: 4px;
            border-radius: 10px;
        }

        body.light-theme .tabs-container {
            background: rgba(0, 0, 0, 0.02);
        }

        .tab-link {
            color: var(--text-muted) !important;
            text-decoration: none !important;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            height: 34px;
            box-sizing: border-box;
        }

        .tab-link:hover {
            color: var(--text-main) !important;
            background: rgba(255, 255, 255, 0.04);
        }

        body.light-theme .tab-link:hover {
            background: rgba(0, 0, 0, 0.04);
        }

        .tab-link.active {
            color: #ffffff !important;
            background: var(--primary) !important;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.4);
        }

        #themeToggle {
            background: var(--card-bg) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--border-color) !important;
            font-weight: 600 !important;
            padding: 10px 18px !important;
            border-radius: 8px !important;
            transition: all 0.3s ease !important;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            height: 42px;
            box-sizing: border-box;
        }
        #themeToggle:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            transform: translateY(-2px);
        }
        body.light-theme #themeToggle:hover {
            background: rgba(0, 0, 0, 0.05) !important;
        }

        select.form-control option {
            background-color: var(--modal-bg) !important;
            color: var(--text-main) !important;
        }

        .form-control[readonly] {
            background-color: rgba(255, 255, 255, 0.02) !important;
            color: var(--text-muted) !important;
            cursor: not-allowed;
            border-style: dashed !important;
        }
        
        body.light-theme .form-control[readonly] {
            background-color: rgba(0, 0, 0, 0.03) !important;
            color: var(--text-muted) !important;
        }

        /* Upgrade add button */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%) !important;
            border: none !important;
            color: #ffffff !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            padding: 10px 24px !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6) !important;
            background: linear-gradient(135deg, var(--primary-hover) 0%, #60a5fa 100%) !important;
        }

        /* Glassmorphic Table Container */
        .table-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--glass-glow);
            overflow-x: auto;
            margin-bottom: 30px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            font-weight: 600;
            padding: 16px 12px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 16px 12px;
            font-size: 14px;
            color: var(--text-main);
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tr {
            transition: background-color 0.2s ease;
        }

        tr:hover {
            background-color: var(--row-hover);
        }

        /* Header Row Styling */
        thead tr {
            background-color: rgba(255, 255, 255, 0.015) !important;
        }

        body.light-theme thead tr {
            background-color: rgba(0, 0, 0, 0.015) !important;
        }

        th {
            color: var(--text-main) !important;
            font-weight: 700 !important;
        }

        /* Invoice Number Column Distinct Styling */
        th:first-child, td:first-child {
            font-family: 'Courier New', Courier, monospace !important;
            font-weight: 700 !important;
            color: #818cf8 !important; /* Soft indigo/blue for IDs */
            text-align: center !important;
            width: 80px;
        }

        body.light-theme th:first-child, body.light-theme td:first-child {
            color: #4f46e5 !important;
        }

        /* Actions Column Distinct Design */
        .actions-header, .actions-cell {
            background-color: rgba(99, 102, 241, 0.04) !important;
            border-left: 1px solid var(--border-color) !important;
            text-align: center !important;
        }
        
        .actions-header {
            color: #a5b4fc !important;
        }

        body.light-theme .actions-header {
            color: #4f46e5 !important;
        }
        
        body.light-theme .actions-header, body.light-theme .actions-cell {
            background-color: rgba(79, 70, 229, 0.03) !important;
        }

        /* Common Action Button Sizes & Core Layout */
        .file-download, .btn-warning, .btn-info {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 34px !important;
            line-height: 1 !important;
            padding: 0 16px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            border-radius: 6px !important;
            text-align: center !important;
            vertical-align: middle !important;
            box-sizing: border-box !important;
            border: none !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }

        .file-download {
            background: linear-gradient(135deg, var(--accent-green) 0%, #059669 100%);
            color: white !important;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }

        .file-download:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(16, 185, 129, 0.5);
            background: linear-gradient(135deg, var(--accent-green-hover) 0%, #10b981 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--accent-orange) 0%, #d97706 100%) !important;
            color: white !important;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3) !important;
            margin-left: 6px;
        }

        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(245, 158, 11, 0.5) !important;
            background: linear-gradient(135deg, var(--accent-orange-hover) 0%, #f59e0b 100%) !important;
        }

        .btn-info {
            background: linear-gradient(135deg, var(--accent-cyan) 0%, #0891b2 100%) !important;
            color: white !important;
            box-shadow: 0 4px 10px rgba(6, 182, 212, 0.3) !important;
            margin-left: 6px;
        }

        .btn-info:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(6, 182, 212, 0.5) !important;
            background: linear-gradient(135deg, var(--accent-cyan-hover) 0%, #06b6d4 100%) !important;
        }

        /* Overlay form modals */
        .overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: var(--modal-overlay-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .overlay-content {
            background-color: var(--modal-bg);
            margin: 8% auto;
            padding: 32px;
            border: 1px solid var(--border-color);
            width: 90%;
            max-width: 550px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            position: relative;
        }

        .overlay-content h1 {
            font-size: 1.8rem;
            margin-top: 0;
            margin-bottom: 24px;
            background: var(--heading-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 600;
        }

        .close-btn {
            color: var(--text-muted);
            position: absolute;
            top: 24px;
            right: 24px;
            font-size: 24px;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .close-btn:hover {
            color: var(--text-main);
        }

        /* Filter Container Styles */
        .filter-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 12px;
            padding: 16px 24px;
            box-shadow: var(--glass-glow);
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 12px;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 0px;
        }

        .filter-btn {
            height: 42px !important;
            padding: 0 24px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.2) !important;
        }

        .reset-btn {
            background: var(--card-bg) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--border-color) !important;
            font-weight: 600 !important;
            height: 42px !important;
            padding: 0 24px !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.3s ease !important;
            text-decoration: none !important;
        }

        .reset-btn:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            transform: translateY(-2px);
        }

        body.light-theme .reset-btn:hover {
            background: rgba(0, 0, 0, 0.05) !important;
        }

        /* Input Controls */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 13px;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            background-color: var(--input-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            height: auto !important;
            transition: all 0.3s ease !important;
            box-shadow: none !important;
        }

        .form-control:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
        }

        .form-control::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.6 !important;
        }

        .form-control::-webkit-input-placeholder {
            color: var(--text-muted) !important;
            opacity: 0.6 !important;
        }

        /* Pagination design */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 6px;
        }

        .pagination a {
            color: var(--text-main);
            background: var(--card-bg);
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .pagination a.active {
            background: var(--primary) !important;
            color: white !important;
            border-color: var(--primary) !important;
        }

        .pagination span {
            color: var(--text-muted);
            padding: 8px 6px;
            display: flex;
            align-items: center;
        }

        @media screen and (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            .overlay-content {
                margin: 15% auto;
                padding: 24px;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-header">
    <div class="header-top-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; position: relative;">
        <!-- Left spacer -->
        <div style="flex: 1; display: flex; justify-content: flex-start;"></div>
        
        <!-- Center Title -->
        <h1 style="text-align: center; margin: 0; background: var(--heading-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; display: inline-block;">Invoice Generator</h1>
        
        <!-- Right Theme Button -->
        <div style="flex: 1; display: flex; justify-content: flex-end;">
            <button id="themeToggle" class="btn">🌙 Theme</button>
        </div>
    </div>
    
    <div class="header-bottom-row" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 30px; flex-wrap: wrap; width: 100%;">
        <div class="tabs-container">
            <a href="index.php" class="tab-link">Dashboard</a>
            <a href="bills.php" class="tab-link active">Bills</a>
            <a href="buyers.php" class="tab-link">Buyers</a>
        </div>
        
        <button onclick="openForm()" class="btn btn-primary">Add Bill</button>
    </div>
</div>

<!-- Overlay form -->
<div id="overlayForm" class="overlay">
    <div class="overlay-content">
        <span class="close-btn" onclick="closeForm()">&times;</span>
        <h1 id="modalTitle">Add Bill</h1>
        <form id="billForm">

        <input type="hidden" name="invoiceNumber" id="invoiceNumber">

            <div class="form-group">
                <label for="buyerIdSelect">Select Buyer:</label>
                <select name="buyerIdSelect" id="buyerIdSelect" class="form-control" required>
                    <option value="">-- Select a Buyer --</option>
                    <?php foreach ($buyers as $b) { ?>
                        <option value="<?php echo htmlspecialchars($b['id']); ?>" 
                                data-name="<?php echo htmlspecialchars($b['buyer_name']); ?>"
                                data-company="<?php echo htmlspecialchars($b['buyer_company']); ?>"
                                data-address="<?php echo htmlspecialchars($b['buyer_address']); ?>">
                            <?php echo htmlspecialchars($b['buyer_company']); ?> (<?php echo htmlspecialchars($b['buyer_name']); ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="buyerName">Buyer Name:</label>
                <input type="text" name="buyerName" id="buyerName" class="form-control" readonly>
            </div>

            <div class="form-group">
                <label for="buyerCompany">Buyer Company:</label>
                <input type="text" name="buyerCompany" id="buyerCompany" class="form-control" required readonly>
            </div>

            <div class="form-group">
                <label for="buyerAddress">Buyer Address:</label>
                <input type="text" name="buyerAddress" id="buyerAddress" class="form-control" required readonly>
            </div>

            <div class="form-group">
                <label for="itemName">Item Name:</label>
                <input type="text" name="itemName" id="itemName" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity (KG):</label>
                <input type="number" value=0 step="0.01" name="quantity" id="quantity" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="bag">Bag:</label>
                <input type="number" value=0 step="0.01" name="bag" id="bag" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="price">Price (Per KG):</label>
                <input type="number" value=0 step="0.01" name="price" id="price" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="vehicleNumber">Vehicle Number:</label>
                <input type="text" name="vehicleNumber" id="vehicleNumber" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="vehicleFreight">Vehicle Freight:</label>
                <input type="number" value=0 step="0.01" value=0 name="vehicleFreight" id="vehicleFreight" class="form-control">
            </div>

            <div class="form-group">
                <label for="balance">Balance Amount:</label>
                <input type="number" value=0 step="0.01" name="balance" id="balance" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>

<!-- Overlay form for editing balance -->
<div id="balanceOverlayForm" class="overlay">
    <div class="overlay-content">
        <span class="close-btn" onclick="closeBalanceForm()">&times;</span>
        <h1>Edit Balance</h1>
        <form id="balanceForm">
            <input type="hidden" name="balanceInvoiceNumber" id="balanceInvoiceNumber">
            <div class="form-group">
                <label for="balanceAmount">Balance Amount:</label>
                <input type="number" step="0.01" name="balanceAmount" id="balanceAmount" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Save Balance</button>
        </form>
    </div>
</div>

<!-- Filter form -->
<div class="filter-container">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label for="buyer_filter">Filter by Buyer:</label>
            <select name="buyer_filter" id="buyer_filter" class="form-control">
                <option value="">-- All Buyers --</option>
                <?php foreach ($buyers as $b) { ?>
                    <option value="<?php echo htmlspecialchars($b['id']); ?>" <?php echo $buyer_filter == $b['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($b['buyer_company']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="balance_filter">Balance Status:</label>
            <select name="balance_filter" id="balance_filter" class="form-control">
                <option value="all" <?php echo $balance_filter === 'all' ? 'selected' : ''; ?>>All Bills</option>
                <option value="remaining" <?php echo $balance_filter === 'remaining' ? 'selected' : ''; ?>>Balance Remaining (> 0)</option>
                <option value="none" <?php echo $balance_filter === 'none' ? 'selected' : ''; ?>>No Balance (= 0)</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary filter-btn">Apply Filters</button>
            <a href="bills.php" class="btn btn-default reset-btn">Reset</a>
        </div>
    </form>
</div>

<!-- Table to display the data -->
<div class="table-container">
<table class="table">
<thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Invoice Date</th>
                    <th>Buyer Company</th>
                    <th>Buyer Address</th>
                    <th>Item Name</th>
                    <th>Bag</th>
                    <th>Quantity (KG)</th>
                    <th>Price (per KG)</th>
                    <th>Amount</th>
                    <th>Vehicle Number</th>
                    <th>Vehicle Freight</th>
                    <th>Balance</th>
                    <th class="actions-header">Actions</th>
                </tr>
            </thead>
            <tbody>
        <?php foreach ($result as $row) { ?>
            <tr>
                <td><?php echo $row['invoice_number']; ?></td>
                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['created_on']))); ?></td>
                <td><?php echo $row['buyer_company']; ?></td>
                <td><?php echo $row['buyer_address']; ?></td>
                <td><?php echo $row['item_name']; ?></td>
                <td><?php echo $row['bag']; ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo formatIndianCurrency($row['price']); ?></td>
                <td><?php echo formatIndianCurrency($row['price'] * $row['quantity']); ?></td>
                <td><?php echo $row['vehicle_number']; ?></td>
                <td><?php echo formatIndianCurrency($row['vehicle_freight']); ?></td>
                <td>
                    <div style="font-weight: 600; margin-bottom: 6px;"><?php echo formatIndianCurrency($row['balance'] !== null ? $row['balance'] : 0.00); ?></div>
                    <button class="btn btn-info" onclick="editBalance(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="padding: 2px 8px !important; height: 22px !important; font-size: 10px !important; font-weight: 600 !important; border-radius: 4px !important; margin: 0 !important; line-height: 1 !important; display: inline-flex !important; align-items: center !important;">Edit Balance</button>
                </td>
                <td class="actions-cell">
                    <a class="file-download" href="<?php echo $row['url']; ?>" target="_blank" download>Download</a>
                    <button class="btn btn-warning" onclick="editBill(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
        </div>

    <div class="pagination">
    <?php
    // Determine the range of page links to display
    $num_links = 10; // Number of page links to show
    $start = max(1, $page - floor($num_links / 2));
    $end = min($start + $num_links - 1, $total_pages);

    // Display the first page link
    if ($start > 1) {
        echo '<a href="' . getPaginationLink(1, $buyer_filter, $balance_filter) . '">1</a>';
        echo '<span>&hellip;</span>';
    }

    // Display the page links within the range
    for ($i = $start; $i <= $end; $i++) {
        echo '<a href="' . getPaginationLink($i, $buyer_filter, $balance_filter) . '"';
        if ($i == $page) {
            echo ' class="active"';
        }
        echo '>' . $i . '</a>';
    }

    // Display the last page link
    if ($end < $total_pages) {
        echo '<span>&hellip;</span>';
        echo '<a href="' . getPaginationLink($total_pages, $buyer_filter, $balance_filter) . '">' . $total_pages . '</a>';
    }
    ?>
</div>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var balanceManuallyEdited = false;

    $(document).ready(function() {
      // Handle buyer selection change
      $('#buyerIdSelect').change(function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val()) {
          $('#buyerName').val(selectedOption.data('name') || '');
          $('#buyerCompany').val(selectedOption.data('company') || '');
          $('#buyerAddress').val(selectedOption.data('address') || '');
        } else {
          $('#buyerName').val('');
          $('#buyerCompany').val('');
          $('#buyerAddress').val('');
        }
      });

      $('#quantity, #price, #vehicleFreight').on('input change', function() {
        if (!balanceManuallyEdited) {
          var qty = parseFloat($('#quantity').val()) || 0;
          var price = parseFloat($('#price').val()) || 0;
          var freight = parseFloat($('#vehicleFreight').val()) || 0;
          var calculatedBalance = (qty * price) + freight;
          $('#balance').val(calculatedBalance.toFixed(2));
        }
      });

      $('#balance').on('input change', function() {
        balanceManuallyEdited = true;
      });

  $('#billForm').submit(function(event) {
    event.preventDefault(); // Prevent the default form submission

    var invoiceNumber = $('input[name=invoiceNumber]').val(); // Check for invoice number (edit mode)
    console.log("InvoiceNumber: "+invoiceNumber);
    var vechicleFreight = $('input[name=vehicleFreight]').val();
    console.log("vehicleFreight: "+vechicleFreight);
    var billData = {
        invoiceNumber: invoiceNumber, // Include invoiceNumber if updating
        buyerId: $('#buyerIdSelect').val(),
      buyerName: $('input[name=buyerName]').val(),
      buyerCompany: $('input[name=buyerCompany]').val(),
      buyerAddress: $('input[name=buyerAddress]').val(),
      itemName: $('input[name=itemName]').val(),
      quantity: parseFloat($('input[name=quantity]').val()),
      price: parseFloat($('input[name=price]').val()),
      bag: parseFloat($('input[name=bag]').val()),
      vehicleNumber: $('input[name=vehicleNumber]').val(),
      vehicleFreight: Number.isNaN(parseFloat(vechicleFreight)) ? 0 : vechicleFreight,
      balance: parseFloat($('input[name=balance]').val()) || 0.00
      // Add more properties as needed
    };
    
    // Send the form data to the PHP script
    $.ajax({
      url: 'save_bill.php',
      type: 'POST',
      data: { data: billData },
      success: function(response) {
        // Handle the response from the server
        console.log(response);
        Swal.fire(
        response
        ).then(function(){ 
       location.reload();
   });
      },
      error: function(xhr, status, error) {
        // Handle errors
        console.error(error);
        Swal.fire(
        "Failed to save bill"
        )
      }
    });
  });

  $('#balanceForm').submit(function(event) {
    event.preventDefault();

    var invoiceNumber = $('input[name=balanceInvoiceNumber]').val();
    var balance = parseFloat($('input[name=balanceAmount]').val());

    $.ajax({
      url: 'save_balance.php',
      type: 'POST',
      data: { invoiceNumber: invoiceNumber, balance: balance },
      success: function(response) {
        console.log(response);
        Swal.fire(response).then(function() {
          location.reload();
        });
      },
      error: function(xhr, status, error) {
        console.error(error);
        Swal.fire("Failed to save balance");
      }
    });
  });

  // Theme toggle handler
  $('#themeToggle').click(function() {
    $('body').toggleClass('light-theme');
    if ($('body').hasClass('light-theme')) {
      localStorage.setItem('theme', 'light');
      $('#themeToggle').text('☀️ Theme');
    } else {
      localStorage.setItem('theme', 'dark');
      $('#themeToggle').text('🌙 Theme');
    }
  });

  // Restore theme preference
  var savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'light') {
    $('body').addClass('light-theme');
    $('#themeToggle').text('☀️ Theme');
  } else {
    $('body').removeClass('light-theme');
    $('#themeToggle').text('🌙 Theme');
  }
});
        // Function to open the form overlay
        function openForm() {
            clearForm();
            $('#modalTitle').text('Add Bill');
            document.getElementById("overlayForm").style.display = "block";
            balanceManuallyEdited = false;
        }

        // Function to close the form overlay
        function closeForm() {
            document.getElementById("overlayForm").style.display = "none";
        }

        // Function to open the form overlay for editing a bill with pre-filled details
        function editBill(bill) {
            $('#modalTitle').text('Edit Bill');
            document.getElementById("overlayForm").style.display = "block";
            balanceManuallyEdited = true;

            // Populate the form with existing bill data for editing
            $('input[name=invoiceNumber]').val(bill.invoice_number); // Hidden field for invoice number
            $('#buyerIdSelect').val(bill.buyer_id);
            $('#buyerIdSelect').trigger('change');
            $('input[name=itemName]').val(bill.item_name);
            $('input[name=quantity]').val(bill.quantity);
            $('input[name=price]').val(bill.price);
            $('input[name=bag]').val(bill.bag);
            $('input[name=vehicleNumber]').val(bill.vehicle_number);
            $('input[name=vehicleFreight]').val(bill.vehicle_freight);
            $('input[name=balance]').val(bill.balance);
        }

        // Function to clear the form inputs
        function clearForm() {
        $('input[name=invoiceNumber]').val('');
        $('#buyerIdSelect').val('');
        $('#buyerIdSelect').trigger('change');
        document.getElementById('itemName').value = '';
        document.getElementById('quantity').value = '';
        document.getElementById('price').value = '';
        document.getElementById('bag').value = '';
        document.getElementById('vehicleNumber').value = '';
        document.getElementById('vehicleFreight').value = '';
        document.getElementById('balance').value = '';
        balanceManuallyEdited = false;
        }

        // Function to open the balance form overlay with pre-filled balance
        function editBalance(bill) {
            document.getElementById("balanceOverlayForm").style.display = "block";
            $('input[name=balanceInvoiceNumber]').val(bill.invoice_number);
            $('input[name=balanceAmount]').val(bill.balance !== null && bill.balance !== undefined ? bill.balance : '0.00');
        }

        // Function to close the balance form overlay
        function closeBalanceForm() {
            document.getElementById("balanceOverlayForm").style.display = "none";
        }
    </script>
</html>