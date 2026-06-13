<?php

require_once('environment.php');

// Database connection
try {
    $conn = getDbConnection();
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $records_per_page;

try {
    // Retrieve data from the database
    $stmt = $conn->prepare("
        SELECT buy.*, COUNT(b.invoice_number) AS invoices_count 
        FROM buyers buy 
        LEFT JOIN bills b ON buy.id = b.buyer_id 
        GROUP BY buy.id 
        ORDER BY buy.buyer_company ASC 
        LIMIT $start_from, $records_per_page
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total number of records
    $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM buyers");
    $stmt_count->execute();
    $row = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $total_records = $row['total'];

    // Calculate total number of pages
    $total_pages = ceil($total_records / $records_per_page);

    // Retrieve all buyers for the dropdown list in Merge modal
    $stmt_all_buyers = $conn->prepare("SELECT id, buyer_name, buyer_company FROM buyers ORDER BY buyer_company ASC");
    $stmt_all_buyers->execute();
    $all_buyers = $stmt_all_buyers->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Buyers</title>
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

        /* Top Bar Container */
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

        /* Buyer ID Column Distinct Styling */
        th:first-child, td:first-child {
            font-family: 'Courier New', Courier, monospace !important;
            font-weight: 700 !important;
            color: #818cf8 !important;
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
        .btn-warning {
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
            background: linear-gradient(135deg, var(--accent-orange) 0%, #d97706 100%) !important;
            color: white !important;
            box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3) !important;
        }

        .btn-warning:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(245, 158, 11, 0.5) !important;
            background: linear-gradient(135deg, var(--accent-orange-hover) 0%, #f59e0b 100%) !important;
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
            margin: 10% auto;
            padding: 32px;
            border: 1px solid var(--border-color);
            width: 90%;
            max-width: 500px;
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
            <a href="bills.php" class="tab-link">Bills</a>
            <a href="buyers.php" class="tab-link active">Buyers</a>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center;">
            <button onclick="openMergeForm()" class="btn btn-primary" style="background: linear-gradient(135deg, var(--accent-orange) 0%, #d97706 100%) !important; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4) !important;">Merge Buyers</button>
            <button onclick="openForm()" class="btn btn-primary">Add Buyer</button>
        </div>
    </div>
</div>

<!-- Merge Overlay form -->
<div id="mergeOverlayForm" class="overlay">
    <div class="overlay-content">
        <button type="button" class="close-btn" aria-label="Close" onclick="closeMergeForm()" style="background: none; border: none; padding: 0;">&times;</button>
        <h1>Merge Buyers</h1>
        <form id="mergeBuyersForm" novalidate>
            <div class="form-group">
                <label for="sourceBuyerId">Source Buyers (Duplicates - WILL BE DELETED - Hold Cmd/Ctrl to select multiple): <span style="color: #ef4444;">*</span></label>
                <select name="sourceBuyerId[]" id="sourceBuyerId" class="form-control" required multiple style="height: 120px !important; padding: 6px !important;">
                    <?php foreach ($all_buyers as $b) { ?>
                        <option value="<?php echo htmlspecialchars($b['id']); ?>" data-company="<?php echo htmlspecialchars($b['buyer_company']); ?>">
                            <?php echo htmlspecialchars($b['buyer_company']); ?> (<?php echo htmlspecialchars($b['buyer_name']); ?>)
                        </option>
                    <?php } ?>
                </select>
                <div class="invalid-feedback" style="display: none; color: #ef4444; font-size: 12px; margin-top: 4px;">Please select at least one source buyer.</div>
            </div>

            <div class="form-group">
                <label for="targetBuyerId">Target Buyer (Primary - Will Keep Linked Bills): <span style="color: #ef4444;">*</span></label>
                <select name="targetBuyerId" id="targetBuyerId" class="form-control" required>
                    <option value="">-- Select Target Buyer --</option>
                    <?php foreach ($all_buyers as $b) { ?>
                        <option value="<?php echo htmlspecialchars($b['id']); ?>" data-company="<?php echo htmlspecialchars($b['buyer_company']); ?>">
                            <?php echo htmlspecialchars($b['buyer_company']); ?> (<?php echo htmlspecialchars($b['buyer_name']); ?>)
                        </option>
                    <?php } ?>
                </select>
                <div class="invalid-feedback" style="display: none; color: #ef4444; font-size: 12px; margin-top: 4px;">Please select a target buyer.</div>
            </div>

            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--accent-orange) 0%, #d97706 100%) !important; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.4) !important;">Execute Merge</button>
        </form>
    </div>
</div>

<!-- Overlay form -->
<div id="overlayForm" class="overlay">
    <div class="overlay-content">
        <button type="button" class="close-btn" aria-label="Close" onclick="closeForm()" style="background: none; border: none; padding: 0;">&times;</button>
        <h1 id="modalTitle">Add Buyer</h1>
        <form id="buyerForm" novalidate>
            <input type="hidden" name="buyerId" id="buyerId">

            <div class="form-group">
                <label for="buyerCompany">Buyer Company (Unique ID): <span style="color: #ef4444;">*</span></label>
                <input type="text" name="buyerCompany" id="buyerCompany" class="form-control" required placeholder="e.g. Acme Corp">
                <div class="invalid-feedback" style="display: none; color: #ef4444; font-size: 12px; margin-top: 4px;">Buyer company is required.</div>
            </div>

            <div class="form-group">
                <label for="buyerName">Buyer Contact Name:</label>
                <input type="text" name="buyerName" id="buyerName" class="form-control" placeholder="e.g. John Doe">
            </div>

            <div class="form-group">
                <label for="buyerAddress">Buyer Address: <span style="color: #ef4444;">*</span></label>
                <input type="text" name="buyerAddress" id="buyerAddress" class="form-control" required placeholder="e.g. 123 Main St, New York">
                <div class="invalid-feedback" style="display: none; color: #ef4444; font-size: 12px; margin-top: 4px;">Buyer address is required.</div>
            </div>

            <button type="submit" class="btn btn-primary">Save Buyer</button>
        </form>
    </div>
</div>

<!-- Table to display data -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Buyer ID</th>
                <th>Buyer Company</th>
                <th>Buyer Name</th>
                <th>Buyer Address</th>
                <th style="text-align: center;">Linked Invoices</th>
                <th class="actions-header">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($result as $row) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['buyer_company']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_name'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_address']); ?></td>
                <td style="text-align: center;">
                    <span class="badge" style="background-color: var(--primary); font-size: 12px; font-weight: 600; padding: 4px 10px;"><?php echo htmlspecialchars($row['invoices_count']); ?></span>
                </td>
                <td class="actions-cell">
                    <button class="btn btn-warning" onclick="editBuyer(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<div class="pagination">
    <?php
    $num_links = 10;
    $start = max(1, $page - floor($num_links / 2));
    $end = min($start + $num_links - 1, $total_pages);

    if ($start > 1) {
        echo '<a href="?page=1">1</a>';
        echo '<span>&hellip;</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        echo '<a href="?page=' . $i . '"';
        if ($i == $page) {
            echo ' class="active"';
        }
        echo '>' . $i . '</a>';
    }

    if ($end < $total_pages) {
        echo '<span>&hellip;</span>';
        echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
    }
    ?>
</div>

</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {


        $('#buyerForm input[required]').on('input', function() {
            if ($(this).val()) {
                $(this).css('border-color', '');
                $(this).siblings('.invalid-feedback').hide();
            }
        });

        $('#buyerForm').submit(function(event) {
            event.preventDefault();

            var isValid = true;
            $(this).find('input[required]').each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).css('border-color', '#ef4444');
                    $(this).siblings('.invalid-feedback').show();
                } else {
                    $(this).css('border-color', '');
                    $(this).siblings('.invalid-feedback').hide();
                }
            });

            if (!isValid) return;

            var $submitBtn = $(this).find('button[type="submit"]');
            var originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Saving...');


            var buyerData = {
                id: $('#buyerId').val(),
                buyerName: $('#buyerName').val(),
                buyerCompany: $('#buyerCompany').val(),
                buyerAddress: $('#buyerAddress').val()
            };

            $.ajax({
                url: 'save_buyer.php',
                type: 'POST',
                data: { data: buyerData },
                success: function(response) {
                    console.log(response);
                    if (response.indexOf('Error:') === 0) {
                        $submitBtn.prop('disabled', false).text(originalText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: response.replace('Error:', '').trim()
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response
                        }).then(function() {
                            location.reload();
                        });
                    }
                },

                error: function(xhr, status, error) {
                    $submitBtn.prop('disabled', false).text(originalText);
                    console.error(error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Failed to save buyer details.'
                    });
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



        $('#mergeBuyersForm select[required]').on('change', function() {
            if ($(this).val() && $(this).val().length > 0) {
                $(this).css('border-color', '');
                $(this).siblings('.invalid-feedback').hide();
            }
        });

        $('#mergeBuyersForm').submit(function(event) {
            event.preventDefault();

            var isValid = true;
            $(this).find('select[required]').each(function() {
                if (!$(this).val() || $(this).val().length === 0) {
                    isValid = false;
                    $(this).css('border-color', '#ef4444');
                    $(this).siblings('.invalid-feedback').show();
                } else {
                    $(this).css('border-color', '');
                    $(this).siblings('.invalid-feedback').hide();
                }
            });

            if (!isValid) return;



            var sourceIds = $('#sourceBuyerId').val() || [];
            var targetId = $('#targetBuyerId').val();

            if (sourceIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Selection',
                    text: 'Please select at least one source buyer.'
                });
                return;
            }

            if (sourceIds.indexOf(targetId) !== -1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Selection',
                    text: 'Target buyer cannot be in the list of source buyers to merge.'
                });
                return;
            }

            var sourceTexts = [];
            $('#sourceBuyerId option:selected').each(function() {
                sourceTexts.push($(this).data('company'));
            });
            var targetText = $('#targetBuyerId option:selected').data('company');

            var htmlMessage = '<div style="text-align: left; font-size: 14px;">' +
                              '<p>You are about to merge the following duplicate buyers:</p>' +
                              '<ul style="margin-bottom: 16px; font-weight: 600; color: #ef4444; padding-left: 20px;">' +
                              sourceTexts.map(function(t) { return '<li>' + t + '</li>'; }).join('') +
                              '</ul>' +
                              '<p>Into the target buyer:</p>' +
                              '<p style="font-weight: 600; color: #10b981; margin-bottom: 16px; padding-left: 5px;">' + targetText + '</p>' +
                              '<p>All linked invoices will be moved, and the source buyers will be deleted permanently.</p>' +
                              '</div>';

            Swal.fire({
                title: 'Are you sure?',
                html: htmlMessage,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, merge them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    var $submitBtn = $('#mergeBuyersForm button[type="submit"]');
                    var originalText = $submitBtn.text();
                    $submitBtn.prop('disabled', true).text('Merging...');
                    $.ajax({
                        url: 'merge_buyers.php',
                        type: 'POST',
                        data: { sourceIds: sourceIds, targetId: targetId },
                        success: function(response) {
                            console.log(response);
                            if (response.indexOf('Error:') === 0) {
                                $submitBtn.prop('disabled', false).text(originalText);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed',
                                    text: response.replace('Error:', '').trim()
                                });
                            } else {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response
                                }).then(function() {
                                    location.reload();
                                });
                            }
                        },

                error: function(xhr, status, error) {
                    $submitBtn.prop('disabled', false).text(originalText);

                            console.error(error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: 'Failed to merge buyers.'
                            });
                        }
                    });

                } else {
                    // Do nothing, alert is dismissed
                }
            });

        });
    });

    function openMergeForm() {
        $('#sourceBuyerId').val('');
        $('#targetBuyerId').val('');
        document.getElementById("mergeOverlayForm").style.display = "block";
    }

    function closeMergeForm() {
        document.getElementById("mergeOverlayForm").style.display = "none";
    }

    function openForm() {
        clearForm();
        $('#modalTitle').text('Add Buyer');
        document.getElementById("overlayForm").style.display = "block";
    }

    function closeForm() {
        document.getElementById("overlayForm").style.display = "none";
    }

    function editBuyer(buyer) {
        $('#modalTitle').text('Edit Buyer');
        document.getElementById("overlayForm").style.display = "block";
        
        $('#buyerId').val(buyer.id);
        $('#buyerCompany').val(buyer.buyer_company);
        $('#buyerName').val(buyer.buyer_name);
        $('#buyerAddress').val(buyer.buyer_address);
    }

    function clearForm() {
        $('#buyerId').val('');
        $('#buyerCompany').val('');
        $('#buyerName').val('');
        $('#buyerAddress').val('');
    }
</script>
</html>
