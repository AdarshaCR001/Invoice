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

// Month and Year Filters
$selected_month = isset($_GET['month']) ? $_GET['month'] : 'all';
$selected_year = isset($_GET['year']) ? $_GET['year'] : 'all';

$where_clauses = [];
$params = [];

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
    // 1. Core aggregates
    $query_agg = "
        SELECT 
            COUNT(b.invoice_number) AS total_invoices,
            SUM((b.quantity * b.price) + b.vehicle_freight) AS total_billing,
            SUM(b.balance) AS total_balance,
            SUM(b.quantity) AS total_qty
        FROM bills b
        $where_sql
    ";
    $stmt_agg = $conn->prepare($query_agg);
    foreach ($params as $key => $val) {
        $stmt_agg->bindValue($key, $val);
    }
    $stmt_agg->execute();
    $aggregates = $stmt_agg->fetch(PDO::FETCH_ASSOC);

    $total_invoices = $aggregates['total_invoices'] ?: 0;
    $total_billing = $aggregates['total_billing'] ?: 0.00;
    $total_balance = $aggregates['total_balance'] ?: 0.00;
    $total_qty = $aggregates['total_qty'] ?: 0.00;
    $total_received = $total_billing - $total_balance;

    // 2. Top Buyer by Revenue
    $query_top_buyer = "
        SELECT buy.buyer_company, buy.buyer_name, SUM((b.quantity * b.price) + b.vehicle_freight) AS revenue
        FROM bills b
        JOIN buyers buy ON b.buyer_id = buy.id
        $where_sql
        GROUP BY b.buyer_id
        ORDER BY revenue DESC
        LIMIT 1
    ";
    $stmt_top = $conn->prepare($query_top_buyer);
    foreach ($params as $key => $val) {
        $stmt_top->bindValue($key, $val);
    }
    $stmt_top->execute();
    $top_buyer_row = $stmt_top->fetch(PDO::FETCH_ASSOC);
    $top_buyer_name = $top_buyer_row ? $top_buyer_row['buyer_company'] . " (" . ($top_buyer_row['buyer_name'] ?: '-') . ")" : "N/A";
    $top_buyer_revenue = $top_buyer_row ? $top_buyer_row['revenue'] : 0.00;

    // 3. Highest Balance Holder
    $query_top_balance = "
        SELECT buy.buyer_company, buy.buyer_name, SUM(b.balance) AS balance_sum
        FROM bills b
        JOIN buyers buy ON b.buyer_id = buy.id
        $where_sql
        GROUP BY b.buyer_id
        ORDER BY balance_sum DESC
        LIMIT 1
    ";
    $stmt_bal = $conn->prepare($query_top_balance);
    foreach ($params as $key => $val) {
        $stmt_bal->bindValue($key, $val);
    }
    $stmt_bal->execute();
    $top_bal_row = $stmt_bal->fetch(PDO::FETCH_ASSOC);
    $top_bal_name = $top_bal_row ? $top_bal_row['buyer_company'] . " (" . ($top_bal_row['buyer_name'] ?: '-') . ")" : "N/A";
    $top_bal_amount = $top_bal_row ? $top_bal_row['balance_sum'] : 0.00;

    // 4. Available Years in database for filtering
    $stmt_years = $conn->prepare("SELECT DISTINCT YEAR(created_on) AS yr FROM bills WHERE created_on IS NOT NULL ORDER BY yr DESC");
    $stmt_years->execute();
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);

    // 5. Top 5 Buyers list by billing in the period
    $query_buyers_list = "
        SELECT buy.buyer_company, buy.buyer_name, 
               SUM((b.quantity * b.price) + b.vehicle_freight) AS total_spent,
               SUM(b.balance) AS total_outstanding,
               COUNT(b.invoice_number) AS invoices_count
        FROM bills b
        JOIN buyers buy ON b.buyer_id = buy.id
        $where_sql
        GROUP BY b.buyer_id
        ORDER BY total_spent DESC
        LIMIT 5
    ";
    $stmt_blist = $conn->prepare($query_buyers_list);
    foreach ($params as $key => $val) {
        $stmt_blist->bindValue($key, $val);
    }
    $stmt_blist->execute();
    $top_buyers_list = $stmt_blist->fetchAll(PDO::FETCH_ASSOC);

    // 6. Recent 5 bills in the period
    $query_recent_bills = "
        SELECT b.*, buy.buyer_company, buy.buyer_name 
        FROM bills b
        JOIN buyers buy ON b.buyer_id = buy.id
        $where_sql
        ORDER BY b.invoice_number DESC
        LIMIT 5
    ";
    $stmt_rlist = $conn->prepare($query_recent_bills);
    foreach ($params as $key => $val) {
        $stmt_rlist->bindValue($key, $val);
    }
    $stmt_rlist->execute();
    $recent_bills_list = $stmt_rlist->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
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
            
            /* HSL gradients for cards */
            --g-blue: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            --g-green: linear-gradient(135deg, #10b981 0%, #047857 100%);
            --g-red: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            --g-purple: linear-gradient(135deg, #8b5cf6 0%, #5d3fd3 100%);
            --g-orange: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --g-cyan: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
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

            --g-blue: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            --g-green: linear-gradient(135deg, #34d399 0%, #10b981 100%);
            --g-red: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
            --g-purple: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
            --g-orange: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            --g-cyan: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
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

        /* Filter Controls */
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

        select.form-control option {
            background-color: var(--modal-bg) !important;
            color: var(--text-main) !important;
        }

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
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6) !important;
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            border-radius: 16px;
            padding: 24px;
            color: #ffffff;
            box-shadow: 0 10px 24px rgba(0,0,0,0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 140px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 32px rgba(0,0,0,0.4);
        }

        body.light-theme .stat-card {
            box-shadow: 0 10px 24px rgba(31, 41, 55, 0.1);
        }
        body.light-theme .stat-card:hover {
            box-shadow: 0 16px 32px rgba(31, 41, 55, 0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            pointer-events: none;
        }

        .stat-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            opacity: 0.85;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 8px 0;
            letter-spacing: -0.01em;
            word-break: break-all;
        }

        .stat-subtext {
            font-size: 12px;
            opacity: 0.75;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Section Title */
        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 40px;
            margin-bottom: 20px;
            background: var(--heading-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.01em;
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

        /* Secondary Grid layout for dashboard tables */
        .dashboard-tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 24px;
        }

        @media screen and (max-width: 768px) {
            .dashboard-tables-grid {
                grid-template-columns: 1fr;
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
            <a href="index.php" class="tab-link active">Dashboard</a>
            <a href="bills.php" class="tab-link">Bills</a>
            <a href="buyers.php" class="tab-link">Buyers</a>
        </div>
    </div>
</div>

<!-- Date filter form -->
<div class="filter-container">
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label for="month">Month:</label>
            <select name="month" id="month" class="form-control">
                <option value="all" <?php echo $selected_month === 'all' ? 'selected' : ''; ?>>All Months</option>
                <?php
                $months = [
                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                ];
                foreach ($months as $num => $name) {
                    $sel = ($selected_month !== 'all' && intval($selected_month) === $num) ? 'selected' : '';
                    echo "<option value=\"$num\" $sel>$name</option>";
                }
                ?>
            </select>
        </div>
        <div class="filter-group">
            <label for="year">Year:</label>
            <select name="year" id="year" class="form-control">
                <option value="all" <?php echo $selected_year === 'all' ? 'selected' : ''; ?>>All Years</option>
                <?php
                foreach ($available_years as $yr) {
                    $sel = ($selected_year !== 'all' && intval($selected_year) === intval($yr)) ? 'selected' : '';
                    echo "<option value=\"$yr\" $sel>$yr</option>";
                }
                ?>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary filter-btn">Apply Filters</button>
            <a href="index.php" class="btn btn-default reset-btn">Reset</a>
        </div>
    </form>
</div>

<!-- Stats Card Deck -->
<div class="stats-grid">
    <!-- Billing Card -->
    <div class="stat-card" style="background: var(--g-blue);">
        <div class="stat-label">Total Billing</div>
        <div class="stat-value"><?php echo formatCurrency($total_billing); ?></div>
        <div class="stat-subtext">Sales for the selected period</div>
    </div>

    <!-- Received Card -->
    <div class="stat-card" style="background: var(--g-green);">
        <div class="stat-label">Total Received</div>
        <div class="stat-value"><?php echo formatCurrency($total_received); ?></div>
        <div class="stat-subtext">Received payments in period</div>
    </div>

    <!-- Outstanding Balance Card -->
    <div class="stat-card" style="background: var(--g-red);">
        <div class="stat-label">Total Balance</div>
        <div class="stat-value"><?php echo formatCurrency($total_balance); ?></div>
        <div class="stat-subtext">Outstanding balances in period</div>
    </div>

    <!-- Top Buyer Card -->
    <div class="stat-card" style="background: var(--g-purple);">
        <div class="stat-label">Top Buyer</div>
        <div class="stat-value" style="font-size: 1.6rem; margin: 14px 0 6px 0; font-weight: 700;"><?php echo htmlspecialchars($top_buyer_name); ?></div>
        <div class="stat-subtext">Total Spent: <?php echo formatCurrency($top_buyer_revenue); ?></div>
    </div>

    <!-- Highest Balance Card -->
    <div class="stat-card" style="background: var(--g-orange);">
        <div class="stat-label">Highest Balance Holder</div>
        <div class="stat-value" style="font-size: 1.6rem; margin: 14px 0 6px 0; font-weight: 700;"><?php echo htmlspecialchars($top_bal_name); ?></div>
        <div class="stat-subtext">Remaining Balance: <?php echo formatCurrency($top_bal_amount); ?></div>
    </div>

    <!-- Other Stats Card -->
    <div class="stat-card" style="background: var(--g-cyan);">
        <div class="stat-label">Other Metrics</div>
        <div style="margin: 6px 0;">
            <div style="font-size: 12px; margin-bottom: 2px;">Invoices Generated: <strong><?php echo $total_invoices; ?></strong></div>
            <div style="font-size: 12px; margin-bottom: 2px;">Avg Invoice: <strong><?php echo formatCurrency($total_invoices > 0 ? $total_billing / $total_invoices : 0); ?></strong></div>
            <div style="font-size: 12px;">Quantity Sold: <strong><?php echo number_format($total_qty, 2); ?> KG</strong></div>
        </div>
        <div class="stat-subtext">General business summary</div>
    </div>
</div>

<div class="dashboard-tables-grid">
    <!-- Top 5 Buyers list -->
    <div>
        <div class="section-title">Top 5 Buyers</div>
        <div class="table-container">
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Buyer Company</th>
                        <th>Contact Name</th>
                        <th style="text-align: right;">Total Spent</th>
                        <th style="text-align: right;">Outstanding</th>
                        <th style="text-align: center;">Bills</th>
                    </tr>
                </thead>
                <tbody>
                                <?php if (count($top_buyers_list) > 0) { ?>
                    <?php foreach ($top_buyers_list as $row) { ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['buyer_company']); ?></td>
                            <td><?php echo htmlspecialchars($row['buyer_name'] ?: '-'); ?></td>
                            <td style="text-align: right; font-weight: 500;"><?php echo formatCurrency($row['total_spent']); ?></td>
                            <td style="text-align: right; color: <?php echo $row['total_outstanding'] > 0 ? '#f87171' : 'inherit'; ?>;"><?php echo formatCurrency($row['total_outstanding']); ?></td>
                            <td style="text-align: center;">
                                <span class="badge" style="background-color: var(--primary); font-size: 11px; padding: 2px 6px;"><?php echo htmlspecialchars($row['invoices_count']); ?></span>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            <div style="font-size: 40px; margin-bottom: 10px;">👥</div>
                            <div style="font-weight: 500; font-size: 16px; margin-bottom: 5px;">No buyers found</div>
                            <div style="font-size: 13px;">Try adjusting your filters or <a href="index.php" style="color: var(--primary);">clear them</a> to see all records.</div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top 5 Recent Bills list -->
    <div>
        <div class="section-title">Recent Invoices</div>
        <div class="table-container">
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="text-align: center; width: 60px;">Inv #</th>
                        <th>Date</th>
                        <th>Buyer Company</th>
                        <th>Item</th>
                        <th style="text-align: right;">Amount</th>
                        <th style="text-align: right;">Balance</th>
                    </tr>
                </thead>
                <tbody>
                                <?php if (count($recent_bills_list) > 0) { ?>
                    <?php foreach ($recent_bills_list as $row) { ?>
                        <tr>
                            <td style="font-family: 'Courier New', Courier, monospace; font-weight: 700; color: #818cf8; text-align: center;">
                                <?php echo htmlspecialchars($row['invoice_number']); ?>
                            </td>
                            <td style="font-size: 12px;"><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['created_on']))); ?></td>
                            <td style="font-weight: 600;"><?php echo htmlspecialchars($row['buyer_company']); ?></td>
                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td style="text-align: right; font-weight: 500;"><?php echo formatCurrency($row['quantity'] * $row['price'] + $row['vehicle_freight']); ?></td>
                            <td style="text-align: right; font-weight: 500; color: <?php echo $row['balance'] > 0 ? '#f87171' : 'inherit'; ?>;">
                                <?php echo formatCurrency($row['balance']); ?>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            <div style="font-size: 40px; margin-bottom: 10px;">📭</div>
                            <div style="font-weight: 500; font-size: 16px; margin-bottom: 5px;">No invoices found</div>
                            <div style="font-size: 13px;">Try adjusting your filters or <a href="index.php" style="color: var(--primary);">clear them</a> to see all records.</div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
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
</script>
</html>
