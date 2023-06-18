<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once realpath(__DIR__ . '/vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
$host = $_ENV['host'];
$db_name = $_ENV['db_name'];
$db_user = $_ENV['db_user'];
$db_password = $_ENV['db_password'];

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $db_user, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Pagination variables
$records_per_page = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page - 1) * $records_per_page;

try {
    // Retrieve data from the database
    $stmt = $conn->prepare("SELECT * FROM bills ORDER BY invoice_number DESC LIMIT $start_from, $records_per_page");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total number of records
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM bills");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_records = $row['total'];

    // Calculate total number of pages
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $e) {
    echo "Query failed: " . $e->getMessage();
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

<!-- Button to open the form as an overlay -->
<button onclick="openForm()" class="btn btn-primary">Add Bill</button>

<!-- Overlay form -->
<div id="overlayForm" class="overlay">
    <div class="overlay-content">
        <span class="close-btn" onclick="closeForm()">&times;</span>
        <h1>Add Bill</h1>
        <form id="billForm">

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
                <label for="quantity">Quantity:</label>
                <input type="number" name="quantity" id="quantity" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="price">Price:</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="bag">Bag:</label>
                <input type="number" name="bag" id="bag" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="vehicleNumber">Vehicle Number:</label>
                <input type="text" name="vehicleNumber" id="vehicleNumber" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="vehicleFreight">Vehicle Freight:</label>
                <input type="number" step="0.01" name="vehicleFreight" id="vehicleFreight" class="form-control">
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
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Bag</th>
                    <th>Vehicle Number</th>
                    <th>Vehicle Freight</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
        <?php foreach ($result as $row) { ?>
            <tr>
                <td><?php echo $row['invoice_number']; ?></td>
                <td><?php echo $row['buyer_name']; ?></td>
                <td><?php echo $row['buyer_company']; ?></td>
                <td><?php echo $row['buyer_address']; ?></td>
                <td><?php echo $row['item_name']; ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo $row['price']; ?></td>
                <td><?php echo $row['bag']; ?></td>
                <td><?php echo $row['vehicle_number']; ?></td>
                <td><?php echo $row['vehicle_freight']; ?></td>
                <td>
                    <a class="file-download" href="<?php echo $row['url']; ?>" target="_blank" download>Download</a>
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
        echo '<a href="?page=1">1</a>';
        echo '<span>&hellip;</span>';
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
  $('#billForm').submit(function(event) {
    event.preventDefault(); // Prevent the default form submission
    
    var billData = {
      buyerName: $('input[name=buyerName]').val(),
      buyerCompany: $('input[name=buyerCompany]').val(),
      buyerAddress: $('input[name=buyerAddress]').val(),
      itemName: $('input[name=itemName]').val(),
      quantity: parseFloat($('input[name=quantity]').val()),
      price: parseFloat($('input[name=price]').val()),
      bag: parseFloat($('input[name=bag]').val()),
      vehicleNumber: $('input[name=vehicleNumber]').val(),
      vehicleFreight: parseFloat($('input[name=vehicleFreight]').val())
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