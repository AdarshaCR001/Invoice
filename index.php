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
    <link rel="stylesheet" href="css/custom_styles.css">
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

<?php include 'templates/bill_form_modal.php'; ?>

<?php include 'templates/bill_table.php'; ?>

</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/main.js"></script>
</html>