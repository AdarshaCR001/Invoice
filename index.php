<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

require_once('environment.php');

// Database connection
$host = $_ENV['HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_password = $_ENV['DB_PASSWORD'];

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database Connection Failed: " . $e->getMessage());
    die("An error occurred while connecting to the database. Please try again later.");
}

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
// Ensure $page is at least 1
if ($page < 1) {
    $page = 1;
}
$start_from = ($page - 1) * $records_per_page;

// Ensure $start_from and $records_per_page are integers for security
$start_from_int = intval($start_from);
$records_per_page_int = intval($records_per_page);

$database_error_message = null; // Initialize error message variable
$result = []; // Initialize $result to an empty array
$total_records = 0; // Initialize $total_records
$total_pages = 0; // Initialize $total_pages

try {
    // Retrieve data from the database
    $stmt = $conn->prepare("SELECT * FROM bills ORDER BY invoice_number DESC LIMIT :start_from, :records_per_page");
    $stmt->bindParam(':start_from', $start_from_int, PDO::PARAM_INT);
    $stmt->bindParam(':records_per_page', $records_per_page_int, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total number of records
    $stmt_count = $conn->prepare("SELECT COUNT(*) AS total FROM bills");
    $stmt_count->execute();
    $row_count = $stmt_count->fetch(PDO::FETCH_ASSOC);
    if ($row_count) {
        $total_records = $row_count['total'];
    }

    // Calculate total number of pages
    if ($records_per_page > 0) {
        $total_pages = ceil($total_records / $records_per_page);
    }

} catch (PDOException $e) {
    error_log("Database Query Failed: " . $e->getMessage());
    // $result is already initialized to [], and $total_records, $total_pages to 0
    $database_error_message = "An error occurred while retrieving data. Please try again later.";
}
?>

<!-- HTML and CSS for displaying the data -->
<!DOCTYPE html>
<html>
<head>
    <title>Bills</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        /* Styles for the overlay form */
        .overlay {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .overlay-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            text-align: left;
        }

        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }


        /* Styles for the page */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 5px;
        }

        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }

        .file-download {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .file-download:hover {
            background-color: #45a049;
        }

        @media screen and (max-width: 600px) {
            table {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
<h1>Bills</h1>

<?php if (!empty($database_error_message)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($database_error_message); ?>
    </div>
<?php endif; ?>

<!-- Button to open the form as an overlay -->
<button onclick="openForm()" class="btn btn-primary">Add Bill</button>

<!-- Overlay form -->
<div id="overlayForm" class="overlay">
    <div class="overlay-content">
        <span class="close-btn" onclick="closeForm()">&times;</span>
        <h1>Add Bill</h1>
        <form id="billForm">

        <input type="hidden" name="invoiceNumber" id="invoiceNumber">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <label for="buyerName">Buyer Name:</label>
                <input type="text" name="buyerName" id="buyerName" class="form-control">
            </div>

            <div class="form-group">
                <label for="buyerCompany">Buyer Company:</label>
                <input type="text" name="buyerCompany" id="buyerCompany" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="buyerAddress">Buyer Address:</label>
                <input type="text" name="buyerAddress" id="buyerAddress" class="form-control" required>
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

            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>

<!-- Table to display the data -->
<div class="table-container">
<table class="table">
<thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Buyer Name</th>
                    <th>Buyer Company</th>
                    <th>Buyer Address</th>
                    <th>Item Name</th>
                    <th>Bag</th>
                    <th>Quantity (KG)</th>
                    <th>Price (per KG)</th>
                    <th>Amount</th>
                    <th>Vehicle Number</th>
                    <th>Vehicle Freight</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
        <?php if (!empty($result)): foreach ($result as $row) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['invoice_number']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_name']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_company']); ?></td>
                <td><?php echo htmlspecialchars($row['buyer_address']); ?></td>
                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                <td><?php echo htmlspecialchars($row['bag']); ?></td>
                <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                <td><?php echo htmlspecialchars($row['price']); ?></td>
                <td><?php echo htmlspecialchars($row['price']*$row['quantity']); ?></td>
                <td><?php echo htmlspecialchars($row['vehicle_number']); ?></td>
                <td><?php echo htmlspecialchars($row['vehicle_freight']); ?></td>
                <td>
                    <a class="file-download" href="<?php echo htmlspecialchars($row['url']); ?>" target="_blank" download>Download</a>
                    <button class="btn btn-warning" onclick="editBill(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                </td>
            </tr>
        <?php } endif; ?>
        </tbody>
    </table>
        </div>

    <div class="pagination">
    <?php
    if ($total_pages > 0) { // Only display pagination if there are pages
        // Determine the range of page links to display
        $num_links = 10; // Number of page links to show
        $start = max(1, $page - floor($num_links / 2));
        $end = min($start + $num_links - 1, $total_pages);

        // Display the first page link
        if ($start > 1) {
            echo '<a href="?page=1">1</a>';
            if ($start > 2) { // Add ellipsis if there's a gap
                echo '<span>&hellip;</span>';
            }
        }

        // Display the page links within the range
        for ($i = $start; $i <= $end; $i++) {
            echo '<a href="?page=' . $i . '"';
            if ($i == $page) {
                echo ' class="active"';
            }
            echo '>' . $i . '</a>';
        }

        // Display the last page link
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) { // Add ellipsis if there's a gap
                echo '<span>&hellip;</span>';
            }
            echo '<a href="?page=' . $total_pages . '">' . $total_pages . '</a>';
        }
    }
    ?>
</div>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
  $('#billForm').submit(function(event) {
    event.preventDefault(); // Prevent the default form submission

    var invoiceNumber = $('input[name=invoiceNumber]').val(); // Check for invoice number (edit mode)
    console.log("InvoiceNumber: "+invoiceNumber);
    var vechicleFreight = $('input[name=vehicleFreight]').val();
    console.log("vehicleFreight: "+vechicleFreight);
    var billData = {
        csrf_token: $('input[name="csrf_token"]').val(), // Added CSRF token
        invoiceNumber: invoiceNumber, // Include invoiceNumber if updating
      buyerName: $('input[name=buyerName]').val(),
      buyerCompany: $('input[name=buyerCompany]').val(),
      buyerAddress: $('input[name=buyerAddress]').val(),
      itemName: $('input[name=itemName]').val(),
      quantity: parseFloat($('input[name=quantity]').val()),
      price: parseFloat($('input[name=price]').val()),
      bag: parseFloat($('input[name=bag]').val()),
      vehicleNumber: $('input[name=vehicleNumber]').val(),
      vehicleFreight: Number.isNaN(parseFloat(vechicleFreight)) ? 0 : vechicleFreight
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
});
        // Function to open the form overlay
        function openForm() {
            document.getElementById("overlayForm").style.display = "block";
        }

        // Function to close the form overlay
        function closeForm() {
            document.getElementById("overlayForm").style.display = "none";
        }

        // Function to open the form overlay for editing a bill with pre-filled details
        function editBill(bill) {
            document.getElementById("overlayForm").style.display = "block";

            // Populate the form with existing bill data for editing
            $('input[name=invoiceNumber]').val(bill.invoice_number); // Hidden field for invoice number
            $('input[name=buyerName]').val(bill.buyer_name);
            $('input[name=buyerCompany]').val(bill.buyer_company);
            $('input[name=buyerAddress]').val(bill.buyer_address);
            $('input[name=itemName]').val(bill.item_name);
            $('input[name=quantity]').val(bill.quantity);
            $('input[name=price]').val(bill.price);
            $('input[name=bag]').val(bill.bag);
            $('input[name=vehicleNumber]').val(bill.vehicle_number);
            $('input[name=vehicleFreight]').val(bill.vehicle_freight);
        }

        // Function to clear the form inputs
        function clearForm() {
        document.getElementById('buyerName').value = '';
        document.getElementById('buyerCompany').value = '';
        document.getElementById('buyerAddress').value = '';
        document.getElementById('itemName').value = '';
        document.getElementById('quantity').value = '';
        document.getElementById('price').value = '';
        document.getElementById('bag').value = '';
        document.getElementById('vehicleNumber').value = '';
        document.getElementById('vehicleFreight').value = '';
        }
    </script>
</html>