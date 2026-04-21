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
    header("Location: pages/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="resources/css/global.css">
    <title>NICS Agri Supply - Dashboard</title>
</head>
<body>
    <div class="logout-session">
        Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="pages/logout.php">Logout</a>
    </div>
    
    <header class="header-header">
        <h1>NICS AGRI SUPPLY</h1>
        <h2>Sales and Inventory Management System</h2>
    </header>
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
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
        $total_products = mysqli_fetch_assoc($result)['total'];

        $result = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(total_amount) as total_sales FROM sales WHERE DATE(sale_date) = CURDATE()");
        $today_sales = mysqli_fetch_assoc($result);

        $result = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= low_stock_notif");
        $low_stock = mysqli_num_rows($result);

        $result = mysqli_query($conn, "SELECT SUM(total_amount) as total_revenue FROM sales");
        $total_revenue = mysqli_fetch_assoc($result)['total_revenue'];
        ?>

        <table>
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
        <?php endif; ?>
    </div>
</body>
</html>