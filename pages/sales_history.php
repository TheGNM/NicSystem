<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'nics_db';

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php

// Get all sales
$sales = mysqli_query($conn, "SELECT * FROM sales ORDER BY sale_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>Sales History - NICS Agri Supply</title>
</head>
<body>
    <h1>NICS AGRI SUPPLY</h1>
    <h2>Sales History</h2>
    
    <nav>
        <a href="../index.php">Dashboard</a> | 
        <a href="products.php">Products</a> | 
        <a href="sales.php">New Sale</a> | 
        <a href="sales_history.php">Sales History</a> | 
        <a href="reports.php">Reports</a>
    </nav>
    
    <hr>
    
    <h3>All Transactions</h3>
    <table border="1" cellpadding="10">
        <tr>
            <th>Invoice #</th>
            <th>Date</th>
            <th>Total Amount</th>
            <th>Payment</th>
            <th>Change</th>
            <th>Actions</th>
        </tr>
        <?php while($row = mysqli_fetch_assoc($sales)): ?>
        <tr>
            <td><?php echo $row['invoice_number']; ?></td>
            <td><?php echo $row['sale_date']; ?></td>
            <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
            <td>₱<?php echo number_format($row['payment_amount'], 2); ?></td>
            <td>₱<?php echo number_format($row['change_amount'], 2); ?></td>
            <td><a href="receipt.php?invoice=<?php echo $row['invoice_number']; ?>" target="_blank">View Receipt</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>