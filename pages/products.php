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

// Handle add product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $price = (int)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $low_stock_notif = (int)$_POST['low_stock_notif'];
    
    $query = "INSERT INTO products (product_name, price, quantity, low_stock_notif) 
              VALUES ('$product_name', $price, $quantity, $low_stock_notif)";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Product added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: products.php");
    exit();
}

// Handle update product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $price = (int)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $low_stock_notif = (int)$_POST['low_stock_notif'];
    
    $query = "UPDATE products SET 
              product_name='$product_name', 
              price=$price, 
              quantity=$quantity, 
              low_stock_notif=$low_stock_notif 
              WHERE product_id=$product_id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Product updated successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: products.php");
    exit();
}

// Handle delete product
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $query = "DELETE FROM products WHERE product_id=$product_id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Product deleted successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: products.php");
    exit();
}

// Get all products
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY product_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>Products - NICS Agri Supply</title>
</head>
<body>
    <h1>NICS AGRI SUPPLY</h1>
    <h2>Products Management</h2>
    
    <nav>
        <a href="../index.php">Dashboard</a> | 
        <a href="products.php">Products</a> | 
        <a href="sales.php">New Sale</a> | 
        <a href="sales_history.php">Sales History</a> | 
        <a href="reports.php">Reports</a>
    </nav>
    
    <hr>
    
    <?php if(isset($_SESSION['message'])): ?>
        <p style="color: green;"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>
    
    <h3>Add New Product</h3>
    <form method="POST" action="">
        <table>
            <tr>
                <td>Product Name:</td>
                <td><input type="text" name="product_name" required></td>
            </tr>
            <tr>
                <td>Price:</td>
                <td><input type="number" name="price" required></td>
            </tr>
            <tr>
                <td>Initial Quantity:</td>
                <td><input type="number" name="quantity" required></td>
            </tr>
            <tr>
                <td>Low Stock Alert (quantity):</td>
                <td><input type="number" name="low_stock_notif" value="5" required></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" name="add_product" value="Add Product"></td>
            </tr>
        </table>
    </form>
    
    <h3>Product List</h3>
    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Low Stock Alert</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php while($row = mysqli_fetch_assoc($products)): ?>
        <tr>
            <form method="POST" action="" style="display: inline;">
                <td><?php echo $row['product_id']; ?>
                    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                </td>
                <td><input type="text" name="product_name" value="<?php echo $row['product_name']; ?>" required></td>
                <td><input type="number" name="price" value="<?php echo $row['price']; ?>" required></td>
                <td><input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" required></td>
                <td><input type="number" name="low_stock_notif" value="<?php echo $row['low_stock_notif']; ?>" required></td>
                <td style="color: <?php echo $row['quantity'] <= $row['low_stock_notif'] ? 'red' : 'green'; ?>;">
                    <?php echo $row['quantity'] <= $row['low_stock_notif'] ? 'Low Stock' : 'OK'; ?>
                </td>
                <td>
                    <input type="submit" name="update_product" value="Update">
                    <a href="?delete=<?php echo $row['product_id']; ?>" onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>