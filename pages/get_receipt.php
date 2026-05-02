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

if (isset($_GET['invoice'])) {
    $invoice = $_GET['invoice'];

    $stmt = $conn->prepare("SELECT * FROM sales WHERE invoice_number = ?");
    $stmt->bind_param("s", $invoice);
    $stmt->execute();
    $sale = $stmt->get_result()->fetch_assoc();

    if ($sale) {
        $item_stmt = $conn->prepare("SELECT si.*, p.product_name FROM sales_items si JOIN products p ON si.product_id = p.product_id WHERE si.sales_id = ?");
        $item_stmt->bind_param("i", $sale['sales_id']);
        $item_stmt->execute();
        $items = $item_stmt->get_result();
        ?>

        <div class="receipt-content" style="font-family: monospace; width: 300px; padding: 10px; border: 1px solid #ddd; background: #fff;">
            <div style="text-align: center;">
                <h2 style="margin-bottom: 0;">NICS AGRI SUPPLY</h2>
                <p style="font-size: 12px;">Salapungan, San Rafael, Bulacan<br>
                Tel: 09123456789</p>
                <hr style="border-top: 1px dashed #000;">
                <p><strong>OFFICIAL RECEIPT</strong></p>
            </div>
            
            <p>Invoice #: <?php echo htmlspecialchars($sale['invoice_number']); ?></p>
            <p>Date: <?php echo date('Y-m-d H:i', strtotime($sale['sale_date'])); ?></p>
            <hr style="border-top: 1px dashed #000;">
            
            <table style="width: 100%; font-size: 13px; text-align: left;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Sub</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <hr style="border-top: 1px dashed #000;">
            <table style="width: 100%;">
                <tr><td><strong>TOTAL:</strong></td><td style="text-align:right;"><strong>₱<?php echo number_format($sale['total_amount'], 2); ?></strong></td></tr>
                <tr><td>Payment:</td><td style="text-align:right;">₱<?php echo number_format($sale['payment_amount'], 2); ?></td></tr>
                <tr><td>Change:</td><td style="text-align:right;">₱<?php echo number_format($sale['change_amount'], 2); ?></td></tr>
            </table>
            
            <div style="text-align: center; margin-top: 20px;">
                <p>Thank you for your purchase!</p>
                <p>Visit us again at NICS AGRI SUPPLY</p>
                <br>
                <p>_______________________</p>
                <p>Authorized Signature</p>
            </div>
            
            <style>
                @media print {
                    button { display: none; }
                    body { background: none; }
                    .receipt-content { border: none; width: 100%; }
                }
            </style>
            <button onclick="window.print()" style="width: 100%; margin-top: 10px; cursor: pointer;">Print Receipt</button>
        </div>

        <?php
    } else {
        echo "<p style='color:red; font-family: sans-serif;'>Invoice [".htmlspecialchars($invoice)."] not found in the sales record.</p>";
    }
}
?>