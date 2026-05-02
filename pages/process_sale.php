<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'nics_db';
$conn = mysqli_connect($host, $username, $password, $database);

if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    $payment_amount = (int)$_POST['payment_amount'];
    $total_amount = (int)$_POST['total_amount'];
    $change_amount = $payment_amount - $total_amount;
    
    if ($change_amount < 0) {
        echo json_encode(['success' => false, 'error' => 'Insufficient payment!']);
        exit();
    }
    
    $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $query = "INSERT INTO sales (invoice_number, total_amount, payment_amount, change_amount) 
              VALUES ('$invoice_number', $total_amount, $payment_amount, $change_amount)";
    
    if (mysqli_query($conn, $query)) {
        $sales_id = mysqli_insert_id($conn);
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
        echo json_encode(['success' => true, 'invoice' => $invoice_number]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}
?>