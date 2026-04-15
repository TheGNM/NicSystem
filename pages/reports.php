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

// Get filter parameters
$report_type = $_GET['report_type'] ?? 'daily';
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

if ($report_type == 'daily') {
    $query = "SELECT * FROM sales WHERE DATE(sale_date) = '$date_from' ORDER BY sale_date DESC";
    $title = "Daily Sales Report - " . date('F d, Y', strtotime($date_from));
} else {
    $query = "SELECT * FROM sales WHERE DATE(sale_date) BETWEEN '$date_from' AND '$date_to' ORDER BY sale_date DESC";
    $title = "Monthly Sales Report - " . date('F d', strtotime($date_from)) . " to " . date('F d, Y', strtotime($date_to));
}

$sales = mysqli_query($conn, $query);

// Get totals
$total_query = str_replace("*", "SUM(total_amount) as total", $query);
$total_result = mysqli_query($conn, $total_query);
$total_sales = mysqli_fetch_assoc($total_result)['total'] ?? 0;

// Get inventory report
$inventory = mysqli_query($conn, "SELECT * FROM products ORDER BY quantity ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>Reports - NICS Agri Supply</title>
</head>
<body>
    <h1>NICS AGRI SUPPLY</h1>
    <h2>Sales and Inventory Reports</h2>
    
    <nav>
        <a href="../index.php">Dashboard</a> | 
        <a href="products.php">Products</a> | 
        <a href="sales.php">New Sale</a> | 
        <a href="sales_history.php">Sales History</a> | 
        <a href="reports.php">Reports</a>
    </nav>
    
    <hr>
    
    <h3>Generate Report</h3>
    <form method="GET" action="">
        <table>
            <tr>
                <td>Report Type:</td>
                <td>
                    <select name="report_type" onchange="this.form.submit()">
                        <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Daily Report</option>
                        <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Date Range Report</option>
                    </select>
                </td>
            </tr>
            <?php if($report_type == 'daily'): ?>
            <tr>
                <td>Date:</td>
                <td><input type="date" name="date_from" value="<?php echo $date_from; ?>" onchange="this.form.submit()"></td>
            </tr>
            <?php else: ?>
            <tr>
                <td>From Date:</td>
                <td><input type="date" name="date_from" value="<?php echo $date_from; ?>" onchange="this.form.submit()"></td>
            </tr>
            <tr>
                <td>To Date:</td>
                <td><input type="date" name="date_to" value="<?php echo $date_to; ?>" onchange="this.form.submit()"></td>
            </tr>
            <?php endif; ?>
        </table>
    </form>
    
    <hr>
    
    <h3><?php echo $title; ?></h3>
    <p><strong>Total Sales: ₱<?php echo number_format($total_sales, 2); ?></strong></p>
    <p>Number of Transactions: <?php echo mysqli_num_rows($sales); ?></p>
    
    <table border="1" cellpadding="10">
        <tr>
            <th>Invoice #</th>
            <th>Date</th>
            <th>Total Amount</th>
            <th>Payment</th>
            <th>Change</th>
        </tr>
        <?php 
        mysqli_data_seek($sales, 0);
        if(mysqli_num_rows($sales) > 0): 
            while($row = mysqli_fetch_assoc($sales)):
        ?>
        <tr>
            <td><?php echo $row['invoice_number']; ?></td>
            <td><?php echo $row['sale_date']; ?></td>
            <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
            <td>₱<?php echo number_format($row['payment_amount'], 2); ?></td>
            <td>₱<?php echo number_format($row['change_amount'], 2); ?></td>
        </tr>
        <?php 
            endwhile;
        else:
        ?>
        <tr>
            <td colspan="5" style="text-align: center;">No sales found for this period.</td>
        </tr>
        <?php endif; ?>
    </table>
    
    <hr>
    
    <h3>Current Inventory Status</h3>
    <table border="1" cellpadding="10">
        <tr>
            <th>Product Name</th>
            <th>Current Stock</th>
            <th>Low Stock Alert</th>
            <th>Status</th>
        </tr>
        <?php 
        mysqli_data_seek($inventory, 0);
        while($row = mysqli_fetch_assoc($inventory)):
        ?>
        <tr>
            <td><?php echo $row['product_name']; ?></td>
            <td><?php echo $row['quantity']; ?></td>
            <td><?php echo $row['low_stock_notif']; ?></td>
            <td style="color: <?php echo $row['quantity'] <= $row['low_stock_notif'] ? 'red' : 'green'; ?>;">
                <?php echo $row['quantity'] <= $row['low_stock_notif'] ? '⚠️ Low Stock' : '✓ In Stock'; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <br>
    <input type="button" value="Print Report" onclick="window.print()">
</body>
</html>