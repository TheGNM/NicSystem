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
//holds and check if the report type is same date or ranged
$report_type = $_GET['report_type'] ?? 'daily';
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
//if daily
if ($report_type == 'daily') {
    //query to get the infos on the day the admin wanted.
    $query = "SELECT * FROM sales WHERE DATE(sale_date) = '$date_from' ORDER BY sale_date DESC";
    //holds the title for the browser presentation
    $title = "Daily Sales Report - " . date('F d, Y', strtotime($date_from));
    //same sa naunang query pero for cash payment
    $cash_sales_query = "SELECT SUM(total_amount) as total FROM sales WHERE payment_type = 'cash' AND DATE(sale_date) = '$date_from'";
    //same din but para sa credits naman
    $payment_query = "SELECT cp.*, s.invoice_number, s.customer_name FROM credit_payments cp 
                    JOIN sales s ON cp.sales_id = s.sales_id 
                    WHERE DATE(cp.payment_date) = '$date_from'
                    ORDER BY cp.payment_date DESC";
}

//if not daily or if ranged
else {
    //same lang din sa nauna bur ranged yung date
    $query = "SELECT * FROM sales WHERE DATE(sale_date) BETWEEN '$date_from' AND '$date_to' ORDER BY sale_date DESC";
    $title = "Sales Report - " . date('F d', strtotime($date_from)) . " to " . date('F d, Y', strtotime($date_to));
    //same lang din sa una but ranged and for cash only
    $cash_sales_query = "SELECT SUM(total_amount) as total FROM sales WHERE payment_type = 'cash' AND DATE(sale_date) BETWEEN '$date_from' AND '$date_to'";
    //same lang din sa una but for credits and ranged date
    $payment_query = "SELECT cp.*, s.invoice_number, s.customer_name FROM credit_payments cp
                    JOIN sales s ON cp.sales_id = s.sales_id 
                    WHERE DATE(cp.payment_date) BETWEEN '$date_from' AND '$date_to'
                    ORDER BY cp.payment_date DESC";
}
//holds the vaslue ng ff query
$cash_result = mysqli_query($conn, $cash_sales_query);
$cash_sales_total = mysqli_fetch_assoc($cash_result)['total'] ?? 0;
$payments_result = mysqli_query($conn, $payment_query);

$total_credit_payments = 0;
//holds the list for the list of credit paymnents
$payments_list = [];
if ($payments_result) {
    //calculate the total credit payments
    while($payment = mysqli_fetch_assoc($payments_result)) {
        $total_credit_payments += $payment['amount_paid'];
        $payments_list[] = $payment;
    }
}

$total_revenue = $cash_sales_total + $total_credit_payments;

//get all the sales
$sales = mysqli_query($conn, $query);
$total_sales_amount = 0;
//cahnages the query a lil bit
$total_sales_result = mysqli_query($conn, str_replace("*", "SUM(total_amount) as total", $query));
//get the sum of the sales in the databse
$total_sales_sum = mysqli_fetch_assoc($total_sales_result);
//holds the sum
$total_sales_amount = $total_sales_sum['total'] ?? 0;
//query to get the summary of credit payments in the db

$credit_summary_query = "SELECT
    COUNT(*) as total_credit_transactions,
    SUM(total_amount) as total_credit_amount,
    SUM(remaining_balance) as total_outstanding
    FROM sales WHERE payment_type = 'credit'";

if ($report_type == 'daily') {
    $credit_summary_query .= " AND DATE(sale_date) = '$date_from'";
}

else {
    $credit_summary_query .= " AND DATE(sale_date) BETWEEN '$date_from' AND '$date_to'";
}
//gets the credit summary on db
$credit_summary_result = mysqli_query($conn, $credit_summary_query);
//holds the summary
$credit_summary = mysqli_fetch_assoc($credit_summary_result);
//gets and hold the products on the inventory
$inventory = mysqli_query($conn, "SELECT * FROM products ORDER BY quantity ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <link rel="stylesheet" href="../resources/css/reports.css">
    <title>Reports - NICS Agri Supply</title>
</head>
<body>
    <div class="logout-session">
        Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="logout.php">Logout</a>
    </div>
    <div class="header-header">
        <h1>NICS AGRI SUPPLY</h1>
        <h2>Sales and Inventory Reports</h2>
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
    <div class="reports-content" id="printableTable">
        <div class="print-header">
            <h1>NICS AGRI SUPPLY</h1>
            <h2>Sales and Inventory Reports</h2>
            <hr>
        </div>
        
        <h3>Generate Report</h3>
        <form method="GET" action="">
            <table>
                <tr>
                    <td>Report Type: </td>
                    <td><select name="report_type" onchange="this.form.submit()">
                        <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Daily Report</option>
                        <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Date Range Report</option>
                    </select></td>
                </tr>
                <?php if($report_type == 'daily'): ?>
                <tr><td>Date:</td><td><input type="date" name="date_from" value="<?php echo $date_from; ?>" onchange="this.form.submit()"></td></tr>
                <?php else: ?>
                <tr><td>From Date:</td><td><input type="date" name="date_from" value="<?php echo $date_from; ?>" onchange="this.form.submit()"></td></tr>
                <tr><td>To Date:</td><td><input type="date" name="date_to" value="<?php echo $date_to; ?>" onchange="this.form.submit()"></td></tr>
                <?php endif; ?>
            </table>
        </form>
            
        <hr>
            
        <h3><?php echo $title; ?></h3>
        
        <h4>Revenue Summary (Actual Cash Collected)</h4>
        <table>
            <tr>
                <th>Source</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>Cash Sales Revenue</td>
                <td>₱<?php echo number_format($cash_sales_total, 2); ?></td>
            </tr>
            <tr>
                <td>Credit Payments Collected</td>
                <td>+ ₱<?php echo number_format($total_credit_payments, 2); ?></td>
            </tr>
            <tr>
                <td>TOTAL CASH COLLECTED</td>
                <td>₱<?php echo number_format($total_revenue, 2); ?></td>
            </tr>
        </table>
        
        <br>
        
        <h4>Sales Breakdown</h4>
        <table>
            <tr>
                <th>Type</th>
                <th>Total Amount</th>
                <th>Collected</th>
                <th>Outstanding</th>
            </tr>
            <tr>
                <td>Cash Sales</td>
                <td>₱<?php echo number_format($cash_sales_total, 2); ?></td>
                <td>₱<?php echo number_format($cash_sales_total, 2); ?></td>
                <td>₱0.00</td>
            </tr>
            <tr>
                <td>Credit Sales</td>
                <td>₱<?php echo number_format($credit_summary['total_credit_amount'] ?? 0, 2); ?></td>
                <td>₱<?php echo number_format(($credit_summary['total_credit_amount'] ?? 0) - ($credit_summary['total_outstanding'] ?? 0), 2); ?></td>
                <td style="color: red;">₱<?php echo number_format($credit_summary['total_outstanding'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <th>TOTAL</th>
                <th>₱<?php echo number_format($total_sales_amount, 2); ?></th>
                <th>₱<?php echo number_format($total_revenue, 2); ?></th>
                <th>₱<?php echo number_format($credit_summary['total_outstanding'] ?? 0, 2); ?></th>
            </tr>
        </table>
        
        <br>
        
        <?php if(count($payments_list) > 0): ?>
        <h4>Credit Payments Collected This Period</h4>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Amount Paid</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($payments_list as $payment): ?>
                <tr>
                    <td><?php echo $payment['payment_date']; ?></td>
                    <td><?php echo $payment['invoice_number']; ?></td>
                    <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                    <td style="color: green;">+ ₱<?php echo number_format($payment['amount_paid'], 2); ?></td>
                    <td><?php echo htmlspecialchars($payment['remarks']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">Total Credit Payments Collected:</th>
                    <th>₱<?php echo number_format($total_credit_payments, 2); ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        <br>
        <?php endif; ?>
        
        <h4>All Transactions</h4>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Total Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            mysqli_data_seek($sales, 0);
            if(mysqli_num_rows($sales) > 0): 
                while($row = mysqli_fetch_assoc($sales)):
            ?>
            <tr>
                <td><?php echo $row['invoice_number']; ?></td>
                <td><?php echo $row['sale_date']; ?></td>
                <td><?php echo htmlspecialchars($row['customer_name'] ?? 'Walk-in'); ?></td>
                <td><?php echo ucfirst($row['payment_type'] ?? 'cash'); ?></td>
                <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
                <td>₱<?php echo number_format($row['amount_paid'] ?? $row['payment_amount'], 2); ?></td>
                <td style="color: <?php echo ($row['remaining_balance'] ?? 0) > 0 ? 'red' : 'green'; ?>;">
                    ₱<?php echo number_format($row['remaining_balance'] ?? 0, 2); ?>
                </td>
                <td><?php echo ucfirst($row['status'] ?? 'paid'); ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8">No sales found for this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        
        <hr>
        
        <h4>Credit/Utang Summary for this Period</h4>
        <table>
            <tr>
                <th>Total Credit Transactions</th>
                <td><?php echo $credit_summary['total_credit_transactions'] ?? 0; ?></td>
            </tr>
            <tr>
                <th>Total Credit Amount</th>
                <td>₱<?php echo number_format($credit_summary['total_credit_amount'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <th>Collected from Credits</th>
                <td>₱<?php echo number_format(($credit_summary['total_credit_amount'] ?? 0) - ($credit_summary['total_outstanding'] ?? 0), 2); ?></td>
            </tr>
            <tr>
                <th>Outstanding Balance</th>
                <td>₱<?php echo number_format($credit_summary['total_outstanding'] ?? 0, 2); ?></td>
            </tr>
        </table>
            
        <hr>
            
        <h3>Current Inventory Status</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Current Stock</th>
                    <th>Low Stock Alert</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            mysqli_data_seek($inventory, 0);
            while($row = mysqli_fetch_assoc($inventory)):
            ?>
            <tr>
                <td><?php echo $row['product_name']; ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo $row['low_stock_notif']; ?></td>
                <td>
                    <?php echo $row['quantity'] <= $row['low_stock_notif'] ? '⚠️ Low Stock' : '✓ In Stock'; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        
        <hr>
        
        <h4>Final Summary</h4>
        <table>
            <tr>
                <th>Total Sales Revenue (All Sales)</th>
                <td>₱<?php echo number_format($total_sales_amount, 2); ?></td>
            </tr>
            <tr>
                <th>Total Credit Payments Collected</th>
                <td>+ ₱<?php echo number_format($total_credit_payments, 2); ?></td>
            </tr>
            <tr>
                <th>TOTAL CASH COLLECTED (REVENUE)</th>
                <th>₱<?php echo number_format($total_revenue, 2); ?></th>
            </tr>
        </table>
    </div>
    <input type="button" value="Print Report" onclick="window.print()" class="print-btn">
</body>
</html>