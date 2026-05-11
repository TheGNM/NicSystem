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

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //removes unnecessary things on the pass and username to avoid injection
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $query = "SELECT * FROM admin_users WHERE username = '$username' AND password = MD5('$password')";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <link rel="stylesheet" href="../resources/css/login.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Admin Login - NICS Agri Supply</title>
</head>
<body>
    <p class="background-container"></p>
    <div class="login-form">
        <div class="header-header">
            <h2>NICS AGRI SUPPLY</h2>
            <h3>Admin Login</h3>
        </div>
        
        <?php if($error): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>
        <section class="container">
            <div class="login-container">
                <h3 class="heading">Login</h3>
                <form method="POST" action="" class="form-self">
                    <div class="input field">
                        <input type="text" name="username" id="username" placeholder="Username" class="username" required>
                    </div>
                    <div class="input field">
                        <input type="password" name="password" id="password" placeholder="Password" class="password" required>
                        <i class='bx bx-hide hide-show'></i>
                    </div>
                    <div class="input field">
                        <button type="submit" name="Login">Login</button>
                    </div>
                    <p>
                        Default: admin / admin123
                    </p>
                </form>
            </div>
        </section>
    </div>
    <script src="../resources/js/passwordhide.js"></script>
</body>
</html>