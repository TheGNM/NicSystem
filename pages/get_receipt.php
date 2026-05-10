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

$invoice = mysqli_real_escape_string($conn, $_GET['invoice']);
$sale_result = mysqli_query($conn, "SELECT * FROM sales WHERE invoice_number = '$invoice'");
$sale = mysqli_fetch_assoc($sale_result);

if (!$sale) {
    die("Invoice not found!");
}

$items = mysqli_query($conn, "SELECT si.*, p.product_name FROM sales_items si JOIN products p ON si.product_id = p.product_id WHERE si.sales_id = " . $sale['sales_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <link rel="stylesheet" href="../resources/css/receipt.css">
    <title>Receipt - <?php echo $invoice; ?></title>
</head>
<body>
    <div class="receipt-content" id="printableTable">
        <div>
            <h2>NICS AGRI SUPPLY</h2>
            <p>Salapungan, San Rafael, Bulacan</p>
            <p>Tel: 09123456789</p>
            <hr>
            <p><strong>OFFICIAL RECEIPT</strong></p>
            <p>Invoice #: <?php echo $sale['invoice_number']; ?></p>
            <p>Date: <?php echo $sale['sale_date']; ?></p>
            <p>Customer: <?php echo htmlspecialchars($sale['customer_name']); ?></p>
            <p>Payment Type: <?php echo strtoupper($sale['payment_type']); ?></p>
            <hr>
        </div>

        <table>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
            <?php while($item = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td><?php echo $item['product_name']; ?></td>
                <td><?php echo $item['quantity']; ?></td>
                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr><td colspan="3"><strong>TOTAL:</strong></td><td><strong>₱<?php echo number_format($sale['total_amount'], 2); ?></strong></td></tr>
            
            <?php if($sale['payment_type'] == 'cash'): ?>
            <tr><td colspan="3">Payment:</td><td>₱<?php echo number_format($sale['payment_amount'], 2); ?></td></tr>
            <tr><td colspan="3">Change:</td><td>₱<?php echo number_format($sale['change_amount'], 2); ?></td></tr>
            <?php else: ?>
            <tr><td colspan="3">Amount Paid:</td><td>₱<?php echo number_format($sale['amount_paid'], 2); ?></td></tr>
            <tr><td colspan="3">Remaining Balance:</td><td style="color: <?php echo $sale['remaining_balance'] > 0 ? 'red' : 'green'; ?>;">₱<?php echo number_format($sale['remaining_balance'], 2); ?></td></tr>
            <?php if($sale['due_date']): ?>
            <tr><td colspan="3">Due Date:</td><td><?php echo date('F d, Y', strtotime($sale['due_date'])); ?></td></tr>
            <?php endif; ?>
            <tr><td colspan="3">Status:</td><td><?php echo ucfirst($sale['status']); ?></td></tr>
            <?php endif; ?>
        </table>
            
        <hr>
        <div>
            <p>Thank you for your purchase!</p>
            <p>Visit us again at NICS AGRI SUPPLY</p>
            <?php if($sale['payment_type'] == 'credit' && $sale['remaining_balance'] > 0): ?>
            <p style="color: red;">Please settle remaining balance on or before due date.</p>
            <?php endif; ?>
            <br><br>
            <p>_______________________</p>
            <p>Authorized Signature</p>
        </div>
    </div>
    <input type="button" value="Print Receipt" onclick="window.print()">
</body>
</html>