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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="resources/css/global.css">
    <link rel="stylesheet" href="resources/css/dashboard.css">
    <title>NICS Agri Supply - Sales & Inventory System</title>
</head>
<body>
    <div class="header">
        <h1>NICS AGRI SUPPLY</h1>
        <h2>Sales and Inventory Management System</h2>
    </div>
    
    <nav class="navbar">
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="pages/products.php">Products</a></li>
            <li><a href="pages/sales.php">New Sale</a></li>
            <li><a href="pages/sales_history.php">Sales History</a></li>
            <li><a href="pages/reports.php">Reports</a></li>
        </ul>
    </nav>
    
    <hr>
    <div class="dashboard-content">
        <h3>Dashboard</h3>

        <?php
        // Get total products
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
        $total_products = mysqli_fetch_assoc($result)['total'];

        // Get total sales today
        $result = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(total_amount) as total_sales FROM sales WHERE DATE(sale_date) = CURDATE()");
        $today_sales = mysqli_fetch_assoc($result);

        // Get low stock products
        $result = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= low_stock_notif");
        $low_stock = mysqli_num_rows($result);

        // Get total sales amount
        $result = mysqli_query($conn, "SELECT SUM(total_amount) as total_revenue FROM sales");
        $total_revenue = mysqli_fetch_assoc($result)['total_revenue'];
        ?>

        <div class="dashboard-table">
            <table border="1" cellpadding="10">
                <tr>
                    <th>Total Products</th>
                    <td><?php echo $total_products; ?></td>
                </tr>
                <tr>
                    <th>Today's Sales</th>
                    <td><?php echo $today_sales['total'] ?? 0; ?> transactions (₱<?php echo number_format($today_sales['total_sales'] ?? 0, 2); ?>)</td>
                </tr>
                <tr>
                    <th>Low Stock Items</th>
                    <td style="color: <?php echo $low_stock > 0 ? 'red' : 'green'; ?>"><?php echo $low_stock; ?> items</td>
                </tr>
                <tr>
                    <th>Total Revenue</th>
                    <td>₱<?php echo number_format($total_revenue ?? 0, 2); ?></td>
                </tr>
            </table>

            <?php if ($low_stock > 0): ?>
                <h3 style="color: red;">⚠️ Low Stock Alert!</h3>
                <table border="1" cellpadding="10">
                    <tr>
                        <th>Product Name</th>
                        <th>Current Stock</th>
                        <th>Low Stock Threshold</th>
                    </tr>
                    <?php 
                    $result = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= low_stock_notif");
                    while($row = mysqli_fetch_assoc($result)):
                    ?>
                    <tr>
                        <td><?php echo $row['product_name']; ?></td>
                        <td style="color: red;"><?php echo $row['quantity']; ?></td>
                        <td><?php echo $row['low_stock_notif']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>