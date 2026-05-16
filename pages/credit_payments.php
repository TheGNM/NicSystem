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
//pag magbabayad ng otang
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    $sales_id = (int)$_POST['sales_id'];
    $payment_amount = (int)$_POST['payment_amount'];
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']);
    
    $sale_query = mysqli_query($conn, "SELECT * FROM sales WHERE sales_id = $sales_id");
    $sale = mysqli_fetch_assoc($sale_query);
    
    if ($payment_amount <= 0) {
        $_SESSION['error'] = "Payment amount must be greater than zero!";
        header("Location: credit_payments.php");
        exit();
    }
    
    if ($payment_amount > $sale['remaining_balance']) {
        $_SESSION['error'] = "Payment amount cannot exceed remaining balance!";
        header("Location: credit_payments.php");
        exit();
    }
    
    $new_balance = $sale['remaining_balance'] - $payment_amount;
    $new_amount_paid = $sale['amount_paid'] + $payment_amount;
    
    if ($new_balance <= 0) {
        $new_balance = 0;
        $status = 'paid';
    } else {
        $status = 'partial';
    }
    //updates databse to inform na nakapagbayad na ng utang or nakapagless na ng utang
    mysqli_query($conn, "UPDATE sales SET remaining_balance = $new_balance, amount_paid = $new_amount_paid, status = '$status' WHERE sales_id = $sales_id");
    //papasok din sa credit payments on databse to update
    mysqli_query($conn, "INSERT INTO credit_payments (sales_id, amount_paid, remarks) VALUES ($sales_id, $payment_amount, '$remarks')");
    //outputs message para malaman na pumasok na ang payment into the database
    $_SESSION['message'] = "Payment recorded successfully! Remaining balance: ₱" . number_format($new_balance, 2);
    header("Location: credit_payments.php");
    exit();
}
//gets all the utang o credits sa database na may remaining balance pa
$credit_sales = mysqli_query($conn, "SELECT * FROM sales WHERE payment_type = 'credit' AND remaining_balance > 0 ORDER BY due_date ASC");
//holds the things to display the history of payment ng credits
$payment_history = mysqli_query($conn, "SELECT cp.*, s.invoice_number, s.customer_name, s.remaining_balance FROM credit_payments cp JOIN sales s ON cp.sales_id = s.sales_id ORDER BY cp.payment_date DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>Credit Payments - NICS Agri Supply</title>

</head>
<body>
    <div class="logout-session">
        Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="logout.php">Logout</a>
    </div>
    <div class="header-header">
        <h1>NICS AGRI SUPPLY</h1>
        <h2>Credit/Utang Payments</h2>
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
    
    <?php if(isset($_SESSION['message'])): ?>
        <p><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>
    
    <div class="credit-content">
        <h3>Outstanding Credit Transactions</h3>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total Amount</th>
                    <th>Amount Paid</th>
                    <th>Remaining Balance</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Record Payment</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($credit_sales) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($credit_sales)): 
                        $is_overdue = ($row['due_date'] && strtotime($row['due_date']) < time());
                    ?>
                    <tr>
                        <td><?php echo $row['invoice_number']; ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo $row['sale_date']; ?></td>
                        <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>₱<?php echo number_format($row['amount_paid'], 2); ?></td>
                        <td style="color: red; font-weight: bold;">₱<?php echo number_format($row['remaining_balance'], 2); ?></td>
                        <td class="<?php echo $is_overdue ? 'status-overdue' : ''; ?>">
                            <?php echo $row['due_date'] ? date('Y-m-d', strtotime($row['due_date'])) : 'N/A'; ?>
                            <?php if($is_overdue): ?> ⚠️ OVERDUE<?php endif; ?>
                        </td>
                        <td><?php echo ucfirst($row['status']); ?></td>
                        <td>
                            <form method="POST" action="" class="payment-form">
                                <input type="hidden" name="sales_id" value="<?php echo $row['sales_id']; ?>">
                                <input type="number" name="payment_amount" placeholder="Amount" required min="1" max="<?php echo $row['remaining_balance']; ?>">
                                <input type="text" name="remarks" placeholder="Remarks">
                                <input type="submit" name="record_payment" value="Pay" class="btn-pay">
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No outstanding credit transactions.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <h3>Payment History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Amount Paid</th>
                    <th>Remaining Balance</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($payment_history) > 0): ?>
                    <?php while($payment = mysqli_fetch_assoc($payment_history)): ?>
                    <tr>
                        <td><?php echo $payment['payment_date']; ?></td>
                        <td><?php echo $payment['invoice_number']; ?></td>
                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                        <td>₱<?php echo number_format($payment['amount_paid'], 2); ?></td>
                        <td>₱<?php echo number_format($payment['remaining_balance'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['remarks']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No payment records yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="../resources/js/active.js"></script>
</body>
</html>