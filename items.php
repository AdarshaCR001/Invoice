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
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$start_from = ($page - 1) * $records_per_page;

$result = [];
$total_records = 0;
$total_pages = 1;

try {
    // Retrieve items with count of linked bills
    $stmt = $conn->prepare("
        SELECT i.*, COUNT(b.invoice_number) AS invoices_count 
        FROM items i 
        LEFT JOIN bills b ON i.item_name = b.item_name 
        GROUP BY i.id 
        ORDER BY i.id ASC 
        LIMIT $start_from, $records_per_page
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total number of records
    $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM items");
    $stmt_count->execute();
    $row = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $total_records = isset($row['total']) ? intval($row['total']) : 0;

    // Calculate total number of pages
    $total_pages = max(1, ceil($total_records / $records_per_page));

} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Items</title>
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

        .btn-logout {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
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
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2) !important;
            transform: translateY(-2px);
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

        /* Buyer / Item ID Column Distinct Styling */
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

        .btn-danger {
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3) !important;
        }

        .btn-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(239, 68, 68, 0.5) !important;
            background: linear-gradient(135deg, #f87171 0%, #ef4444 100%) !important;
        }

        /* Modal / Overlay styles */
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
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .overlay-content {
            background-color: var(--modal-bg);
            margin: 5% auto;
            padding: 32px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            position: relative;
            color: var(--text-main);
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 24px;
            font-size: 28px;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .close-btn:hover {
            color: var(--text-main);
        }

        .form-control {
            background-color: var(--input-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            border-radius: 8px !important;
            height: 42px !important;
            padding: 10px 14px !important;
            font-size: 14px !important;
            transition: border-color 0.2s ease !important;
        }

        .form-control:focus {
            border-color: var(--primary) !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2) !important;
        }

        .form-group label {
            color: var(--text-main);
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 6px;
        }

        .pagination > li > a, .pagination > li > span {
            background: var(--card-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
            margin: 0 2px;
            border-radius: 6px !important;
        }

        .pagination > .active > a, .pagination > .active > span {
            background: var(--primary) !important;
            border-color: var(--primary) !important;
            color: #ffffff !important;
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
        
        <!-- Right Theme Button & Logout Button -->
        <div style="flex: 1; display: flex; justify-content: flex-end; gap: 12px; align-items: center;">
            <button id="themeToggle" class="btn">🌙 Theme</button>
            <a href="logout.php" class="btn btn-logout" style="text-decoration: none;">🚪 Logout</a>
        </div>
    </div>
    
    <div class="header-bottom-row" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 30px; flex-wrap: wrap; width: 100%;">
        <div class="tabs-container">
            <a href="index.php" class="tab-link">Dashboard</a>
            <a href="bills.php" class="tab-link">Bills</a>
            <a href="buyers.php" class="tab-link">Buyers</a>
            <a href="items.php" class="tab-link active">Items</a>
        </div>
        
        <button onclick="openForm()" class="btn btn-primary">Add Item</button>
    </div>
</div>

<!-- Table Card -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 80px; text-align: center;">#</th>
                <th>Item Description / HSN</th>
                <th style="width: 180px; text-align: center;">Invoices Linked</th>
                <th class="actions-header" style="width: 180px; text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($result) > 0) { 
                $count = $start_from + 1;
                foreach ($result as $row) { ?>
                <tr>
                    <td style="text-align: center;"><?php echo $count++; ?></td>
                    <td>
                        <span style="font-weight: 600; font-size: 14px; color: var(--text-main);"><?php echo htmlspecialchars($row['item_name']); ?></span>
                        <?php if ($count === $start_from + 2 && $start_from === 0) { ?>
                            <span class="badge" style="margin-left: 10px; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em;">Default Item</span>
                        <?php } ?>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($row['invoices_count']); ?> bills</span>
                    </td>
                    <td class="actions-cell">
                        <div style="display: flex; gap: 8px; justify-content: center; align-items: center;">
                            <button class="btn btn-warning" onclick='editItem(<?php echo json_encode($row); ?>)'>Edit</button>
                            <button class="btn btn-danger" onclick='deleteItem(<?php echo $row['id']; ?>, "<?php echo htmlspecialchars($row['item_name'], ENT_QUOTES); ?>")'>Delete</button>
                        </div>
                    </td>
                </tr>
            <?php } } else { ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px 20px;">No items found. Click "+ Add Item" to create one.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($total_pages > 1) { ?>
        <div style="text-align: center; margin-top: 20px;">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                    <li class="<?php if ($page == $i) echo 'active'; ?>">
                        <a href="items.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>
</div>

    <!-- Overlay Form for Adding / Editing Item -->
    <div id="overlayForm" class="overlay">
        <div class="overlay-content">
            <button type="button" class="close-btn" aria-label="Close" onclick="closeForm()" style="background: none; border: none; padding: 0;">&times;</button>
            <h1 id="modalTitle" style="font-size: 1.8rem; margin-bottom: 24px;">Add Item</h1>
            
            <form id="itemForm" novalidate>
                <input type="hidden" name="itemId" id="itemId">

                <div class="form-group">
                    <label for="itemName">Item Name / HSN Description: <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="itemName" id="itemName" class="form-control" placeholder="e.g. RAGI HSN:10082031" required>
                    <div class="invalid-feedback" style="display: none; color: #ef4444; font-size: 12px; margin-top: 4px;">Item name is required.</div>
                </div>

                <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-default" onclick="closeForm()" style="border-radius: 8px; height: 42px; padding: 0 20px;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

            // Real-time validation clear on input
            $('#itemForm input[required]').on('input change', function() {
                if ($(this).val().trim() !== '') {
                    $(this).css('border-color', '');
                    $(this).siblings('.invalid-feedback').hide();
                }
            });

            // Item form submit handler
            $('#itemForm').submit(function(event) {
                event.preventDefault();

                var isValid = true;
                var itemNameVal = $('#itemName').val().trim();

                if (itemNameVal === '') {
                    isValid = false;
                    $('#itemName').css('border-color', '#ef4444');
                    $('#itemName').siblings('.invalid-feedback').show();
                } else {
                    $('#itemName').css('border-color', '');
                    $('#itemName').siblings('.invalid-feedback').hide();
                }

                if (!isValid) return;

                var formData = {
                    id: $('#itemId').val(),
                    itemName: itemNameVal
                };

                var $submitBtn = $('#itemForm button[type="submit"]');
                var originalText = $submitBtn.text();
                $submitBtn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: 'save_item.php',
                    type: 'POST',
                    data: { data: formData },
                    success: function(response) {
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
                            text: 'Failed to save item details.'
                        });
                    }
                });
            });
        });

        function openForm() {
            clearForm();
            $('#modalTitle').text('Add Item');
            document.getElementById("overlayForm").style.display = "block";
        }

        function closeForm() {
            document.getElementById("overlayForm").style.display = "none";
        }

        function editItem(item) {
            $('#modalTitle').text('Edit Item');
            $('#itemId').val(item.id);
            $('#itemName').val(item.item_name);
            document.getElementById("overlayForm").style.display = "block";
        }

        function deleteItem(id, name) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to delete "' + name + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#4b5563',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete_item.php',
                        type: 'POST',
                        data: { id: id },
                        success: function(response) {
                            if (response.indexOf('Error:') === 0) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Failed',
                                    text: response.replace('Error:', '').trim()
                                });
                            } else {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response
                                }).then(function() {
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: 'Failed to delete item.'
                            });
                        }
                    });
                }
            });
        }

        function clearForm() {
            $('#itemId').val('');
            $('#itemName').val('');
            $('#itemName').css('border-color', '');
            $('.invalid-feedback').hide();
        }
    </script>
</body>
</html>
