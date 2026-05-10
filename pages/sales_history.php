<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'nics_db';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$sales = mysqli_query($conn, "SELECT * FROM sales ORDER BY sale_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <link rel="stylesheet" href="../resources/css/sales_history.css">
    <title>Sales History - NICS Agri Supply</title>
</head>
<body>
    <div class="logout-session">
        Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="logout.php">Logout</a>
    </div>
    <div class="header-header">
        <h1>NICS AGRI SUPPLY</h1>
        <h2>Sales History</h2>
    </div>
    <nav class="navbar">
        <ul>
            <li><a href="../index.php">Dashboard</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="sales.php">New Sale</a></li>
            <li><a href="sales_history.php">Sales History</a></li>
            <li><a href="reports.php">Reports</a></li>
        </ul>
    </nav>
    <hr>
    <div class="history-whole">
        <div class="sales-history-content">
            <h3>All Transactions</h3>
            <table class="sales-history-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Payment</th>
                        <th>Change</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($sales)): ?>
                    <tr>
                        <td><?php echo $row['invoice_number']; ?></td>
                        <td><?php echo $row['sale_date']; ?></td>
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>₱<?php echo number_format($row['payment_amount'], 2); ?></td>
                        <td>₱<?php echo number_format($row['change_amount'], 2); ?></td>
                        <td>
                            <button><a href="receipt.php?invoice=<?php echo $row['invoice_number']; ?>" target="_blank">View Receipt</a></button>
                            
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="receipt-view">
            <iframe id="receiptFrame" src="about:blank"></iframe>
        </div>
    </div>
    <script src="../resources/js/view.js"></script>
</body>
</html>