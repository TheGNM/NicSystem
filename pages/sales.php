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

$products = mysqli_query($conn, "SELECT * FROM products WHERE quantity > 0 ORDER BY product_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    $payment_amount = (int)$_POST['payment_amount'];
    $total_amount = (int)$_POST['total_amount'];
    $change_amount = $payment_amount - $total_amount;
    
    if ($change_amount < 0) {
        $_SESSION['error'] = "Insufficient payment!";
        header("Location: sales.php");
        exit();
    }
    
    $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $query = "INSERT INTO sales (invoice_number, total_amount, payment_amount, change_amount) VALUES ('$invoice_number', $total_amount, $payment_amount, $change_amount)";
    
    if (mysqli_query($conn, $query)) {
        $sales_id = mysqli_insert_id($conn);
        
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        $prices = $_POST['price'];
        
        for ($x = 0; $x < count($product_ids); $x++) {
            if (!empty($product_ids[$x]) && $quantities[$x] > 0) {
                $product_id = (int)$product_ids[$x];
                $quantity = (int)$quantities[$x];
                $price = (int)$prices[$x];
                $subtotal = $quantity * $price;
                
                $item_query = "INSERT INTO sales_items (sales_id, product_id, quantity, price, subtotal) VALUES ($sales_id, $product_id, $quantity, $price, $subtotal)";
                mysqli_query($conn, $item_query);
                
                $update_stock = "UPDATE products SET quantity = quantity - $quantity WHERE product_id = $product_id";
                mysqli_query($conn, $update_stock);
            }
        }
        
        $_SESSION['message'] = "Sale completed! Invoice #: $invoice_number";
        $_SESSION['last_invoice'] = $invoice_number;
        header("Location: receipt.php?invoice=$invoice_number");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>New Sale - NICS Agri Supply</title>
    <?php

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

// Handle adding more items
$item_count = isset($_GET['items']) ? (int)$_GET['items'] : 1;
if (isset($_GET['add_item'])) {
    $item_count = (int)$_GET['items'] + 1;
    // Preserve existing POST data when adding item
    $redirect = "sales.php?items=" . $item_count;
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $redirect .= "&preserve=1";
    }
    header("Location: " . $redirect);
    exit();
}

// Handle removing an item
if (isset($_GET['remove_item'])) {
    $item_count = (int)$_GET['items'] - 1;
    if ($item_count < 1) $item_count = 1;
    header("Location: sales.php?items=" . $item_count);
    exit();
}

// Get products
$products = mysqli_query($conn, "SELECT * FROM products WHERE quantity > 0");

// Store POST data in session to preserve when adding/removing items
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['complete_sale'])) {
    $_SESSION['sale_data'] = $_POST;
}
if (isset($_GET['preserve']) && isset($_SESSION['sale_data'])) {
    $_POST = $_SESSION['sale_data'];
}

// Process sale submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    $payment_amount = (int)$_POST['payment_amount'];
    $total_amount = (int)$_POST['total_amount'];
    $change_amount = $payment_amount - $total_amount;
    
    if ($change_amount < 0) {
        $_SESSION['error'] = "Insufficient payment!";
        unset($_SESSION['sale_data']);
        header("Location: sales.php?items=" . $item_count);
        exit();
    }
    
    $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $query = "INSERT INTO sales (invoice_number, total_amount, payment_amount, change_amount) VALUES ('$invoice_number', $total_amount, $payment_amount, $change_amount)";
    
    if (mysqli_query($conn, $query)) {
        $sales_id = mysqli_insert_id($conn);
        
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        
        for ($i = 0; $i < count($product_ids); $i++) {
            if (!empty($product_ids[$i]) && $quantities[$i] > 0) {
                $product_id = (int)$product_ids[$i];
                $quantity = (int)$quantities[$i];
                
                // Get price from database
                $price_query = mysqli_query($conn, "SELECT price FROM products WHERE product_id = $product_id");
                $price_row = mysqli_fetch_assoc($price_query);
                $price = $price_row['price'];
                $subtotal = $quantity * $price;
                
                $item_query = "INSERT INTO sales_items (sales_id, product_id, quantity, price, subtotal) VALUES ($sales_id, $product_id, $quantity, $price, $subtotal)";
                mysqli_query($conn, $item_query);
                
                $update_stock = "UPDATE products SET quantity = quantity - $quantity WHERE product_id = $product_id";
                mysqli_query($conn, $update_stock);
            }
        }
        
        unset($_SESSION['sale_data']);
        $_SESSION['message'] = "Sale completed! Invoice #: $invoice_number";
        header("Location: receipt.php?invoice=$invoice_number");
        exit();
    }
}

// Calculate total
$total = 0;
$product_prices = [];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    for ($i = 0; $i < count($_POST['product_id']); $i++) {
        if (!empty($_POST['product_id'][$i]) && !empty($_POST['quantity'][$i]) && $_POST['quantity'][$i] > 0) {
            $pid = (int)$_POST['product_id'][$i];
            if (!isset($product_prices[$pid])) {
                $price_query = mysqli_query($conn, "SELECT price FROM products WHERE product_id = $pid");
                $price_row = mysqli_fetch_assoc($price_query);
                $product_prices[$pid] = $price_row['price'];
            }
            $price = $product_prices[$pid];
            $qty = (int)$_POST['quantity'][$i];
            $total += $qty * $price;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Sale - NICS Agri Supply</title>
</head>
<body>
    <div>
        Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="logout.php">Logout</a>
    </div>
    
    <h1>NICS AGRI SUPPLY</h1>
    <h2>New Sale Transaction</h2>
    
    <nav>
        <a href="../index.php">Dashboard</a> | 
        <a href="products.php">Products</a> | 
        <a href="sales.php">New Sale</a> | 
        <a href="sales_history.php">Sales History</a> | 
        <a href="reports.php">Reports</a>
    </nav>
    
    <hr>
    
    <?php if(isset($_SESSION['error'])): ?>
        <p style="color: red;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>
    
    <form method="POST" action="?items=<?php echo $item_count; ?>">
        <?php for($i = 1; $i <= $item_count; $i++): ?>
            <?php if($i > 1): ?>
                <hr>
            <?php endif; ?>
            
            <h4>Item <?php echo $i; ?></h4>
            
            <select name="product_id[]">
                <option value="">Select Product</option>
                <?php 
                mysqli_data_seek($products, 0);
                while($row = mysqli_fetch_assoc($products)): 
                    $selected = (isset($_POST['product_id'][$i-1]) && $_POST['product_id'][$i-1] == $row['product_id']) ? 'selected' : '';
                ?>
                <option value="<?php echo $row['product_id']; ?>" <?php echo $selected; ?>>
                    <?php echo $row['product_name']; ?> - ₱<?php echo number_format($row['price'], 2); ?> (Stock: <?php echo $row['quantity']; ?>)
                </option>
                <?php endwhile; ?>
            </select>
            
            Quantity: 
            <input type="number" name="quantity[]" min="1" value="<?php echo isset($_POST['quantity'][$i-1]) ? $_POST['quantity'][$i-1] : '1'; ?>">
            
            <?php if($i > 1): ?>
                <a href="?remove_item=<?php echo $i; ?>&items=<?php echo $item_count; ?>">Remove</a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <br><br>
        <a href="?add_item=1&items=<?php echo $item_count; ?>">+ Add Another Item</a>
        
        <hr>
        
        <h3>Total: ₱<?php echo number_format($total, 2); ?></h3>
        <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
        
        <table>
            <tr>
                <td>Payment Amount: </td>
                <td><input type="number" name="payment_amount" value="<?php echo isset($_POST['payment_amount']) ? $_POST['payment_amount'] : ''; ?>"></td>
            </tr>
            <tr>
                <td>Change: </td>
                <td>
                    <?php 
                    $payment = isset($_POST['payment_amount']) ? (int)$_POST['payment_amount'] : 0;
                    $change = $payment - $total;
                    if($payment > 0 && $change >= 0) {
                        echo '₱' . number_format($change, 2);
                    } elseif($payment > 0 && $change < 0) {
                        echo '<span style="color: red;">Insufficient payment! (Short by ₱' . number_format(abs($change), 2) . ')</span>';
                    } else {
                        echo '₱0';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <input type="submit" name="calculate" value="Update Total">
                    <input type="submit" name="complete_sale" value="Complete Sale" onclick="return confirm('Complete this sale?')">
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
</head>
</html>
