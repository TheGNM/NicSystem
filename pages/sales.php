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
//if nagcomplete sale ka na
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    $payment_type = $_POST['payment_type'];
    $total_amount = (int)$_POST['total_amount'];
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    //pag cash payment
    if($payment_type == 'cash') {
        $payment_amount = (int)$_POST['payment_amount'];
        $change_amount = $payment_amount - $total_amount;
        //output if less yung bayad ni customer
        if ($change_amount < 0) {
            $_SESSION['error'] = "Insufficient payment!";
            unset($_SESSION['sale_data']);
            header("Location: sales.php");
            exit();
        }
        
        $amount_paid = $payment_amount;
        $remaining_balance = 0;
        $status = 'paid';
        $due_date = NULL;
        $downpayment = 0;
        $payment_amount_db = $payment_amount;
    }
    //if credit or utang mode of payment
    else {
    
        $downpayment = isset($_POST['downpayment']) ? (int)$_POST['downpayment'] : 0;
        $due_date = $_POST['due_date'];
        $amount_paid = $downpayment;
        $remaining_balance = $total_amount - $downpayment;
        $payment_amount_db = $downpayment;
        $change_amount = 0;
        
        if($remaining_balance <= 0) {
            $status = 'paid';
        } elseif($downpayment > 0 && $remaining_balance > 0) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }
    }
    
    $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    //if credits papasok database with due date
    if($due_date) {
        $query = "INSERT INTO sales (invoice_number, total_amount, payment_amount, change_amount, payment_type, amount_paid, remaining_balance, due_date, status, customer_name)
                VALUES ('$invoice_number', $total_amount, $payment_amount_db, $change_amount, '$payment_type', $amount_paid, $remaining_balance, '$due_date', '$status', '$customer_name')";
    }
    //if cash, null ang due dat ofc
    else {
        $query = "INSERT INTO sales (invoice_number, total_amount, payment_amount, change_amount, payment_type, amount_paid, remaining_balance, due_date, status, customer_name)
                VALUES ('$invoice_number', $total_amount, $payment_amount_db, $change_amount, '$payment_type', $amount_paid, $remaining_balance, NULL, '$status', '$customer_name')";
    }
    
    if (mysqli_query($conn, $query)) {
        $sales_id = mysqli_insert_id($conn);
        
        if($payment_type == 'credit' && $downpayment > 0) {
            mysqli_query($conn, "INSERT INTO credit_payments (sales_id, amount_paid, remarks) VALUES ($sales_id, $downpayment, 'Downpayment')");
        }
        
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        //loop for quantity, total price ng mga nabili ng costumer
        for ($x = 0; $x < count($product_ids); $x++) {
            if (!empty($product_ids[$x]) && $quantities[$x] > 0) {
                $product_id = (int)$product_ids[$x];
                $quantity = (int)$quantities[$x];
                // pasok databasse para macheck mga prices ng items
                $price_query = mysqli_query($conn, "SELECT price FROM products WHERE product_id = $product_id");
                $price_row = mysqli_fetch_assoc($price_query);
                $price = $price_row['price'];
                $subtotal = $quantity * $price;
                //papasok ang sales sa database after macalculate lahat
                mysqli_query($conn, "INSERT INTO sales_items (sales_id, product_id, quantity, price, subtotal) 
                                    VALUES ($sales_id, $product_id, $quantity, $price, $subtotal)");
                //para maupdate yung quantity at ibawas na ang nabili
                mysqli_query($conn, "UPDATE products SET quantity = quantity - $quantity WHERE product_id = $product_id");
            }
        }
        //shoqw that sale is completred
        unset($_SESSION['sale_data']);
        $_SESSION['message'] = "Sale completed! Invoice #: $invoice_number";
        header("Location: receipt.php?invoice=$invoice_number");
        exit();
    }
    //if somehow may error sa transaction, output den syempre
    else {
        $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        header("Location: sales.php");
        exit();
    }
}
//get lahat ng available na items sa inv
$products = mysqli_query($conn, "SELECT * FROM products WHERE quantity > 0 ORDER BY product_name");
//item number
$item_count = isset($_GET['items']) ? (int)$_GET['items'] : 1;
//add item kapag bibili ng higit sa isa ang customer
if (isset($_GET['add_item'])) {
    $item_count = (int)$_GET['items'] + 1;
    $redirect = "sales.php?items=" . $item_count;

    header("Location: " . $redirect);
    exit();
}
//remove item kapag nagbago isip ng customer
if (isset($_GET['remove_item'])) {
    $item_count = (int)$_GET['items'] - 1;
    if ($item_count < 1) $item_count = 1;
    header("Location: sales.php?items=" . $item_count);
    exit();
}



$total = 0;
$product_prices = [];
//if update total 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id']) && !isset($_POST['complete_sale'])) {
    //calculate the product quantity, prices, total
    for ($x = 0; $x < count($_POST['product_id']); $x++) {
        if (!empty($_POST['product_id'][$x]) && !empty($_POST['quantity'][$x]) && $_POST['quantity'][$x] > 0) {
            $pid = (int)$_POST['product_id'][$x];
            if (!isset($product_prices[$pid])) {
                $price_query = mysqli_query($conn, "SELECT price FROM products WHERE product_id = $pid");
                $price_row = mysqli_fetch_assoc($price_query);
                $product_prices[$pid] = $price_row['price'];
            }
            $price = $product_prices[$pid];
            $qty = (int)$_POST['quantity'][$x];
            $total += $qty * $price;
        }
    }
}
//if cash ang payment, this is the output
$selected_payment_type = isset($_POST['payment_type']) ? $_POST['payment_type'] : 'cash';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <link rel="stylesheet" href="../resources/css/sales.css">
    <title>New Sale - NICS Agri Supply</title>
</head>
<body>
    <div class="logout-session">
        Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="logout.php">Logout</a>
    </div>
    <div class="header-header">
        <h1>NICS AGRI SUPPLY</h1>
        <h2>New Sale Transaction</h2>
    </div>
    <nav class="navbar">
        <ul>
            <li><a href="../index.php">Dashboard</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="sales.php">New Sale</a></li>
            <li><a href="sales_history.php">Sales History</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="credit_payments.php">Credit Payments</a></li>
        </ul>
    </nav>
    <hr>

    <?php if(isset($_SESSION['error'])): ?>
        <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>
    
    <div class="sales-content">
    <form method="POST" action="">
        <div class="customer-section">
            <label>Customer Name: </label>
            <input type="text" name="customer_name" value="<?php echo isset($_POST['customer_name']) ? $_POST['customer_name'] : ''; ?>" required>
        </div>
        
        <div class="payment-type-section">
            <label>Payment Type: </label>
            <select name="payment_type" id="payment_type" onchange="this.form.submit()">
                <option value="cash" <?php echo $selected_payment_type == 'cash' ? 'selected' : ''; ?>>Cash</option>
                <option value="credit" <?php echo $selected_payment_type == 'credit' ? 'selected' : ''; ?>>Credit/Utang</option>
            </select>
        </div>
        
        <?php for($i = 1; $i <= $item_count; $i++): ?>
            <?php if($i > 1): ?>
                <hr class="item-divider">
            <?php endif; ?>
            
            <div class="item-row">
            <h4 class="item-label">Item <?php echo $i; ?></h4>
            
                <div class="item-fields">
                <select name="product_id[]" class="product-select">
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
            
            <div class="qty-wrap">
            <label class="qty-label">Quantity: </label>
            <input type="number" name="quantity[]" min="1" class="qty-input" value="<?php echo isset($_POST['quantity'][$i-1]) ? $_POST['quantity'][$i-1] : '1'; ?>">
            </div>

            <?php if($i > 1): ?>
                <a href="?remove_item=1&items=<?php echo $item_count; ?>" class="remove-link" onclick="return confirm('Remove this item?')">Remove</a>
            <?php endif; ?>
            </div>
        </div>
        <?php endfor; ?>
        
        <br><br>
        <a href="?add_item=1&items=<?php echo $item_count; ?>" class="add-item-link">+ Add Another Item</a>
        
        <hr class="sale-divider">
        
    <div class="sale-summary">
        <h3>Total: ₱<?php echo number_format($total, 2); ?></h3>
        <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
        
        <?php if($selected_payment_type == 'cash'): ?>
        <table class="payment-table">
            <tr>
                <td class="pay-label">Payment Amount: </td>
                <td><input type="number" name="payment_amount" class="payment-input" value="<?php echo isset($_POST['payment_amount']) ? $_POST['payment_amount'] : ''; ?>" required></td>
            </tr>
            <tr>
                <td class="pay-label">Change: </td>
                <td class="change-value">
                    <?php 
                    $payment = isset($_POST['payment_amount']) ? (int)$_POST['payment_amount'] : 0;
                    $change = $payment - $total;
                    if($payment > 0 && $change >= 0) {
                        echo '₱' . number_format($change, 2);
                    } elseif($payment > 0 && $change < 0) {
                        echo '<span style="color: red;">Insufficient payment! (Short by ₱' . number_format(abs($change), 2) . ')</span>';
                    } else {
                        echo '₱0.00';
                    }
                    ?>
                </td>
            </tr>
        <?php 
    
    //if credits, this is the output
    else: ?>
        <table class="payment-table">
            <tr>
                <td class="pay-label">Downpayment (Optional): </td>
                <td><input type="number" name="downpayment" class="payment-input" value="<?php echo isset($_POST['downpayment']) ? $_POST['downpayment'] : '0'; ?>"></td>
            </tr>
            <tr>
                <td class="pay-label">Due Date: </td>
                <td><input type="date" name="due_date" class="payment-input" value="<?php echo isset($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+30 days')); ?>" required></td>
            </tr>
            <tr>
                <td class="pay-label">Remaining Balance: </td>
                <td class="change-value">
                    <?php 
                    $downpayment_val = isset($_POST['downpayment']) ? (int)$_POST['downpayment'] : 0;
                    $remaining = $total - $downpayment_val;
                    if($remaining > 0) {
                        echo '<span style="color: red;">₱' . number_format($remaining, 2) . '</span>';
                    } elseif($remaining <= 0) {
                        echo '₱0.00 (Fully Paid)';
                    } else {
                        echo '₱0.00';
                    }
                    ?>
                </td>
            </tr>
        <?php endif; ?>
            <tr>
                <td colspan="2" class="sale-actions">
                    <input type="submit" name="calculate" value="Update Total" class="btn-update">
                    <input type="submit" name="complete_sale" value="Complete Sale" class="btn-complete" onclick="return confirm('Complete this sale?');">
                </td>
            </tr>
        </table>
        </div>
    </form>
        </div>
</body>
</html>