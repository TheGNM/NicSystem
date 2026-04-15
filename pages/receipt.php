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

$invoice = mysqli_real_escape_string($conn, $_GET['invoice']);
$sale_query = "SELECT * FROM sales WHERE invoice_number = '$invoice'";
$sale_result = mysqli_query($conn, $sale_query);
$sale = mysqli_fetch_assoc($sale_result);

if (!$sale) {
    die("Invoice not found!");
}

$items_query = "SELECT si.*, p.product_name 
                FROM sales_items si 
                JOIN products p ON si.product_id = p.product_id 
                WHERE si.sales_id = " . $sale['sales_id'];
$items = mysqli_query($conn, $items_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>Receipt - <?php echo $invoice; ?></title>
</head>
<body onload="window.print()">
    <div style="text-align: center;">
        <h2>NICS AGRI SUPPLY</h2>
        <p>Salapungan, San Rafael, Bulacan</p>
        <p>Tel: 09123456789</p>
        <hr>
        <p><strong>OFFICIAL RECEIPT</strong></p>
        <p>Invoice #: <?php echo $sale['invoice_number']; ?></p>
        <p>Date: <?php echo $sale['sale_date']; ?></p>
        <hr>
    </div>
    
    <table border="0" cellpadding="5" width="100%">
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>
        <?php while($item = mysqli_fetch_assoc($items)): ?>
        <tr>
            <td><?php echo $item['product_name']; ?></td>
            <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
            <td style="text-align: right;">₱<?php echo number_format($item['price'], 2); ?></td>
            <td style="text-align: right;">₱<?php echo number_format($item['subtotal'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="3" style="text-align: right;"><strong>TOTAL:</strong></td>
            <td style="text-align: right;"><strong>₱<?php echo number_format($sale['total_amount'], 2); ?></strong></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right;">Payment:</td>
            <td style="text-align: right;">₱<?php echo number_format($sale['payment_amount'], 2); ?></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: right;">Change:</td>
            <td style="text-align: right;">₱<?php echo number_format($sale['change_amount'], 2); ?></td>
        </tr>
    </table>
    
    <hr>
    <div style="text-align: center;">
        <p>Thank you for your purchase!</p>
        <p>Visit us again at NICS AGRI SUPPLY</p>
        <br><br>
        <p>_______________________</p>
        <p>Authorized Signature</p>
    </div>
</body>
</html>