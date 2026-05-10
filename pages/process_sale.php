<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'nics_db';
$conn = mysqli_connect($host, $username, $password, $database);

if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    $payment_type = $_POST['payment_type'];
    $total_amount = (int)$_POST['total_amount'];
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
    
    if($payment_type == 'cash') {
        $payment_amount = (int)$_POST['payment_amount'];
        $change_amount = $payment_amount - $total_amount;
        
        if ($change_amount < 0) {
            $_SESSION['error'] = "Insufficient payment!";
            header("Location: sales.php");
            exit();
        }
        
        $amount_paid = $payment_amount;
        $remaining_balance = 0;
        $status = 'paid';
        $due_date = 'NULL';
    } else {
        $downpayment = isset($_POST['downpayment']) ? (int)$_POST['downpayment'] : 0;
        $due_date = $_POST['due_date'];
        $amount_paid = $downpayment;
        $remaining_balance = $total_amount - $downpayment;
        $payment_amount = $downpayment;
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
    
    $query = "INSERT INTO sales (invoice_number, total_amount, payment_amount, change_amount, payment_type, amount_paid, remaining_balance, due_date, status, customer_name) 
            VALUES ('$invoice_number', $total_amount, $payment_amount, $change_amount, '$payment_type', $amount_paid, $remaining_balance, " . ($due_date ? "'$due_date'" : "NULL") . ", '$status', '$customer_name')";
    
    if (mysqli_query($conn, $query)) {
        $sales_id = mysqli_insert_id($conn);
        
        if($payment_type == 'credit' && $downpayment > 0) {
            mysqli_query($conn, "INSERT INTO credit_payments (sales_id, amount_paid, remarks) VALUES ($sales_id, $downpayment, 'Downpayment')");
        }
        
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        
        for ($i = 0; $i < count($product_ids); $i++) {
            if (!empty($product_ids[$i]) && $quantities[$i] > 0) {
                $product_id = (int)$product_ids[$i];
                $quantity = (int)$quantities[$i];
                
                $price_query = mysqli_query($conn, "SELECT price FROM products WHERE product_id = $product_id");
                $price_row = mysqli_fetch_assoc($price_query);
                $price = $price_row['price'];
                $subtotal = $quantity * $price;
                
                mysqli_query($conn, "INSERT INTO sales_items (sales_id, product_id, quantity, price, subtotal) 
                                    VALUES ($sales_id, $product_id, $quantity, $price, $subtotal)");
                
                mysqli_query($conn, "UPDATE products SET quantity = quantity - $quantity WHERE product_id = $product_id");
            }
        }
        
        $_SESSION['message'] = "Sale completed! Invoice #: $invoice_number";
        $_SESSION['last_invoice'] = $invoice_number;
        header("Location: receipt.php?invoice=$invoice_number");
        exit();
    } else {
        $_SESSION['error'] = "Database error: " . mysqli_error($conn);
        header("Location: sales.php");
        exit();
    }
}
?>