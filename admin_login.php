<?php
require_once 'config.php';

// If already logged in, redirect to admin
if (isset($_COOKIE['admin_logged_in'])) {
    header('Location: admin.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $result = $conn->query("SELECT * FROM admins WHERE username = '$username'");
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            setcookie('admin_logged_in', 'true', time() + 86400, "/");
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Invalid password.';
        }
    } else {
        $error = 'Admin user not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Floradise</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1f2937; display: flex; align-items: center; justify-content: center; min-height: 100vh; color: white; }
        .login-box { background: #374151; padding: 40px; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; border-top: 5px solid #22c55e; }
        .login-box h1 { margin-top: 0; color: #22c55e; }
        .login-box p { color: #9ca3af; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #d1d5db; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #4b5563; border-radius: 6px; background: #1f2937; color: white; box-sizing: border-box; }
        .form-group input:focus { outline: none; border-color: #22c55e; }
        .btn { width: 100%; background: #22c55e; color: white; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 1.1rem; transition: 0.3s; }
        .btn:hover { background: #16a34a; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: left; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1><i class="fas fa-leaf"></i> Admin Portal</h1>
        <p>Secure System Access</p>
        
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div style="margin-top: 20px;">
            <a href="index.php" style="color: #9ca3af; text-decoration: none; font-size: 0.9rem;"><i class="fas fa-arrow-left"></i> Back to Store</a>
        </div>
    </div>
</body>
</html>
