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
//if mag add ng product si admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $low_stock_notif = (int)$_POST['low_stock_notif'];
    $reorder_point = isset($_POST['reorder_point']) ? (int)$_POST['reorder_point'] : 5;
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    //ipasok sa database ang new product
    $query = "INSERT INTO products (product_name, category_id, price, quantity, low_stock_notif, reorder_point, unit, location) 
            VALUES ('$product_name', " . ($category_id ? $category_id : "NULL") . ", $price, $quantity, $low_stock_notif, $reorder_point, '$unit', '$location')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Product added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: products.php");
    exit();
}
//if mag update ng product si admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $product_id = (int)$_POST['product_id'];
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $price = (float)$_POST['price'];
    $quantity = (int)$_POST['quantity'];
    $low_stock_notif = (int)$_POST['low_stock_notif'];
    $reorder_point = (int)$_POST['reorder_point'];
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    //update the database as well
    $query = "UPDATE products SET product_name='$product_name', category_id=" . ($category_id ? $category_id : "NULL") . ", 
              price=$price, quantity=$quantity, low_stock_notif=$low_stock_notif, reorder_point=$reorder_point, 
              unit='$unit', location='$location' WHERE product_id=$product_id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Product updated successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: products.php");
    exit();
}
//if magdelete ng item or product si admin
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    //magdelete din sa database syempre
    $query = "DELETE FROM products WHERE product_id=$product_id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Product deleted successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: products.php");
    exit();
}

// Add category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name']);
    $query = "INSERT INTO categories (category_name) VALUES ('$category_name')";
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Category added successfully!";
    } else {
        $_SESSION['error'] = "Error: " . mysqli_error($conn);
    }
    header("Location: products.php");
    exit();
}

// Delete category
if (isset($_GET['delete_category'])) {
    $category_id = (int)$_GET['delete_category'];
    // Check if category has products
    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = $category_id");
    $has_products = mysqli_fetch_assoc($check)['count'] > 0;
    
    if ($has_products) {
        $_SESSION['error'] = "Cannot delete category - it has products assigned to it!";
    } else {
        $query = "DELETE FROM categories WHERE category_id = $category_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['message'] = "Category deleted successfully!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    header("Location: products.php");
    exit();
}

// Bulk stock update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_update'])) {
    foreach ($_POST['stock_quantity'] as $product_id => $quantity_change) {
        $product_id = (int)$product_id;
        $quantity_change = (int)$quantity_change;
        if ($quantity_change != 0) {
            mysqli_query($conn, "UPDATE products SET quantity = quantity + $quantity_change WHERE product_id = $product_id");
        }
    }
    $_SESSION['message'] = "Bulk stock update completed successfully!";
    header("Location: products.php");
    exit();
}

// Search and filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stock_filter = isset($_GET['stock_filter']) ? $_GET['stock_filter'] : 'all';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'product_id';
$sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] == 'ASC' ? 'ASC' : 'DESC';

// Build query with filters
$query = "SELECT p.*, c.category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        WHERE 1=1";

if ($search) {
    $query .= " AND p.product_name LIKE '%$search%'";
}
if ($category_filter > 0) {
    $query .= " AND p.category_id = $category_filter";
}
if ($stock_filter == 'low') {
    $query .= " AND p.quantity <= p.low_stock_notif AND p.quantity > 0";
} elseif ($stock_filter == 'critical') {
    $query .= " AND p.quantity <= p.reorder_point AND p.quantity > 0";
} elseif ($stock_filter == 'out') {
    $query .= " AND p.quantity = 0";
}

// Validate sort by to prevent SQL injection
$allowed_sort = ['product_id', 'product_name', 'price', 'quantity', 'category_name'];
if (!in_array($sort_by, $allowed_sort)) {
    $sort_by = 'product_id';
}
$query .= " ORDER BY $sort_by $sort_order";

//holds the products inside the database
$products = mysqli_query($conn, $query);

// Get categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name");

// Get inventory statistics
$stats_query = "SELECT 
    COUNT(*) as total_products,
    SUM(CASE WHEN quantity <= low_stock_notif AND quantity > 0 THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
    SUM(CASE WHEN quantity <= reorder_point AND quantity > 0 THEN 1 ELSE 0 END) as critical_stock,
    SUM(price * quantity) as total_value
    FROM products";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get action parameters
$show_add_modal = isset($_GET['action']) && $_GET['action'] == 'add';
$show_category_modal = isset($_GET['action']) && $_GET['action'] == 'categories';
$show_bulk_modal = isset($_GET['action']) && $_GET['action'] == 'bulk';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="../resources/css/global.css">
        <link rel="stylesheet" href="../resources/css/products.css">
        <title>Products - NICS Agri Supply</title>
    </head>
    <body>
        <div class="logout-session">
            Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="logout.php">Logout</a>
        </div>
        <div class="header-header">
            <h1>NICS AGRI SUPPLY</h1>
            <h2>Products Management</h2>
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
            <p class="success-message"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <p class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            <?php endif; ?>
            
        <div class="products-content">
            <div class="stats-dashboard">
                <ul>
                    <li>
                        <div class="stat-box total-products">
                            <div class="stat-label">Total Products</div>
                            <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="stat-box low-stock">
                            <div class="stat-label">Low Stock</div>
                            <div class="stat-number"><?php echo $stats['low_stock']; ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="stat-box critical-stock">
                            <div class="stat-label">Critical Stock</div>
                            <div class="stat-number"><?php echo $stats['critical_stock']; ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="stat-box out-stock">
                            <div class="stat-label">Out of Stock</div>
                            <div class="stat-number"><?php echo $stats['out_of_stock']; ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="stat-box inventory-value">
                            <div class="stat-label">Inventory Value</div>
                            <div class="stat-number">₱<?php echo number_format($stats['total_value'] ?? 0, 2); ?></div>
                        </div>
                    </li>
                </ul>
            </div>
            
            <div class="filter-bar">
                <form method="GET" action="" class="filter-form">
                    <ul>
                        <li>
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                        </li>
                        <li>
                            <select name="category" class="filter-select">
                                <option value="0">All Categories</option>
                                <?php 
                                mysqli_data_seek($categories, 0);
                                while($cat = mysqli_fetch_assoc($categories)): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </li>
                        <li>
                            <select name="stock_filter" class="filter-select">
                                <option value="all" <?php echo $stock_filter == 'all' ? 'selected' : ''; ?>>All Stock Levels</option>
                                <option value="critical" <?php echo $stock_filter == 'critical' ? 'selected' : ''; ?>>Critical (Below Reorder)</option>
                                <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                            </select>
                        </li>
                        <li>
                            <select name="sort_by" class="filter-select">
                                <option value="product_id" <?php echo $sort_by == 'product_id' ? 'selected' : ''; ?>>Sort by ID</option>
                                <option value="product_name" <?php echo $sort_by == 'product_name' ? 'selected' : ''; ?>>Sort by Name</option>
                                <option value="price" <?php echo $sort_by == 'price' ? 'selected' : ''; ?>>Sort by Price</option>
                                <option value="quantity" <?php echo $sort_by == 'quantity' ? 'selected' : ''; ?>>Sort by Stock</option>
                            </select>
                        </li>
                        <li>
                            <select name="sort_order" class="filter-select">
                                <option value="DESC" <?php echo $sort_order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                <option value="ASC" <?php echo $sort_order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            </select>
                        </li>
                        <li>
                            <button type="submit" class="filter-btn">Apply</button>
                        </li>
                        <li>
                            <a href="products.php" class="reset-btn">Reset</a>
                        </li>
                    </ul>
                </form>
            </div>
            
            <div class="action-buttons">
                <button class="action-btn add-btn"><a href="?action=add" class="action-btn add-btn">+ Add Product</a></button>
                <button class="action-btn category-btn"><a href="?action=categories" class="action-btn category-btn">Manage Categories</a></button>
                <button class="action-btn bulk-btn"><a href="?action=bulk" class="action-btn bulk-btn">Bulk Stock Update</a></button>
            </div>
            
            <h3>Product List</h3>
            <div class="table-content">
                <table class="product-list">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(mysqli_num_rows($products) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($products)): 
                        // Determine stock status
                        if($row['quantity'] <= 0) {
                            $status = 'Out of Stock';
                            $status_class = 'status-out';
                        } elseif($row['quantity'] <= $row['reorder_point']) {
                            $status = 'Critical';
                            $status_class = 'status-critical';
                        } elseif($row['quantity'] <= $row['low_stock_notif']) {
                            $status = 'Low Stock';
                            $status_class = 'status-low';
                        } else {
                            $status = 'In Stock';
                            $status_class = 'status-ok';
                        }
                    ?>
                    <form method="POST" action="" class="product-form">
                        <tr>
                            <td><?php echo $row['product_id']; ?><input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>"></td>
                            <td><input type="text" name="product_name" value="<?php echo htmlspecialchars($row['product_name']); ?>" required class="edit-input"></td>
                            <td>
                                <select name="category_id" class="edit-select">
                                    <option value="">None</option>
                                    <?php 
                                    mysqli_data_seek($categories, 0);
                                    while($cat = mysqli_fetch_assoc($categories)): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $row['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td><input type="text" name="unit" value="<?php echo htmlspecialchars($row['unit']); ?>" class="edit-input" style="width:60px;"></td>
                            <td><input type="number" name="price" value="<?php echo $row['price']; ?>" step="0.01" required class="edit-input" style="width:100px;"></td>
                            <td>
                                <input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" required class="edit-input" style="width:80px;">
                                <input type="hidden" name="low_stock_notif" value="<?php echo $row['low_stock_notif']; ?>">
                                <input type="hidden" name="reorder_point" value="<?php echo $row['reorder_point']; ?>">
                            </td>
                            <td><input type="text" name="location" value="<?php echo htmlspecialchars($row['location']); ?>" class="edit-input" style="width:80px;"></td>
                            <td class="<?php echo $status_class; ?>">
                                <?php echo $status; ?><br>
                                <small>Alert: <?php echo $row['low_stock_notif']; ?> | Reorder: <?php echo $row['reorder_point']; ?></small>
                            </td>
                            <td>
                                <input type="submit" name="update_product" value="Update" class="update-btn">
                                <a href="?delete=<?php echo $row['product_id']; ?>" class="delete-btn" onclick="return confirm('Delete this product? This action cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                    </form>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center;">No products found. Click "Add Product" to get started.</td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if($show_add_modal): ?>
        <div class="modal-overlay" style="display:flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Product</h2>
                </div>
                <form method="POST" action="">
                    <table class="modal-table">
                        <tr>
                            <td class="modal-label">Product Name:</td>
                            <td><input type="text" name="product_name" required class="modal-input"></td>
                        </tr>
                        <tr>
                            <td class="modal-label">Category:</td>
                            <td>
                                <select name="category_id" class="modal-select">
                                    <option value="">Select Category</option>
                                    <?php 
                                    mysqli_data_seek($categories, 0);
                                    while($cat = mysqli_fetch_assoc($categories)): ?>
                                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="modal-label">Unit:</td>
                            <td><input type="text" name="unit" placeholder="e.g., kg, pcs, bottle" required class="modal-input"></td>
                        </tr>
                        <tr>
                            <td class="modal-label">Price:</td>
                            <td><input type="number" name="price" step="0.01" required class="modal-input"></td>
                        </tr>
                        <tr>
                            <td class="modal-label">Initial Quantity:</td>
                            <td><input type="number" name="quantity" required class="modal-input"></td>
                        </tr>
                        <tr>
                            <td class="modal-label">Low Stock Alert:</td>
                            <td><input type="number" name="low_stock_notif" value="10" required class="modal-input"></td>
                        </tr>
                        <tr>
                            <td class="modal-label">Reorder Point:</td>
                            <td><input type="number" name="reorder_point" value="5" required class="modal-input">
                                <small>When stock falls below this, it's critical</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="modal-label">Storage Location:</td>
                            <td><input type="text" name="location" placeholder="e.g., Aisle A, Shelf 1" class="modal-input"></td>
                        </tr>
                    </table>
                    <div class="modal-buttons">
                        <input type="submit" name="add_product" value="Add Product" class="submit-btn">
                        <a href="products.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($show_category_modal): ?>
        <div class="modal-overlay" style="display:flex;">
            <div class="modal-content modal-medium">
                <div class="modal-header">
                    <h2>Manage Categories</h2>
                </div>
                <form method="POST" action="" class="modal-addcat-form">
                    <table class="modal-table">
                        <tr>
                            <td class="modal-label">New Category Name:</td>
                            <td><input type="text" name="category_name" required class="modal-input"></td>
                            <td><input type="submit" name="add_category" value="Add Category" class="submit-btn"></td>
                        </tr>
                    </table>
                </form>
                
                <h3>Existing Categories</h3>
                <table class="category-table">
                    <thead>
                        <tr><th>ID</th><th>Category Name</th><th>Products Count</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($categories, 0);
                        while($cat = mysqli_fetch_assoc($categories)):
                            $product_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = " . $cat['category_id']))['count'];
                        ?>
                        <tr>
                            <td><?php echo $cat['category_id']; ?></td>
                            <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                            <td><?php echo $product_count; ?> products</td>
                            <td>
                                <?php if($product_count == 0): ?>
                                    <a href="?delete_category=<?php echo $cat['category_id']; ?>" class="delete-btn" onclick="return confirm('Delete this category?')">Delete</a>
                                <?php else: ?>
                                    <span class="disabled-btn" title="Cannot delete - has <?php echo $product_count; ?> products">Cannot Delete</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="modal-buttons">
                    <a href="products.php" class="close-btn">Close</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($show_bulk_modal): ?>
        <div class="modal-overlay" style="display:flex;">
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Bulk Stock Update</h2>
                </div>
                <form method="POST" action="">
                    <table class="bulk-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Current Stock</th>
                                <th>Unit</th>
                                <th>Add (+) / Remove (-)</th>
                                <th>New Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($products, 0);
                            while($row = mysqli_fetch_assoc($products)): 
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                <td>
                                    <input type="number" name="stock_quantity[<?php echo $row['product_id']; ?>]" value="0" class="bulk-input" style="width:100px;">
                                    <small>(positive to add, negative to remove)</small>
                                </td>
                                <td><strong><?php echo $row['quantity']; ?></strong> → <strong id="new_<?php echo $row['product_id']; ?>"><?php echo $row['quantity']; ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="modal-buttons">
                        <input type="submit" name="bulk_update" value="Update All Stock" class="submit-btn" onclick="return confirm('Apply these stock changes?')">
                        <a href="products.php" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <script src="../resources/js/active.js"></script>
    </body>
</html>