<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FloradiseShop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <a href="index.php" class="logo"><i class="fas fa-leaf" style="color: #4ade80;"></i> Floradise</a>
        <nav class="nav-links">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="about.php"><i class="fas fa-info-circle"></i> About Us</a>
            <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
            <a href="cart.php">
                <i class="fas fa-shopping-cart"></i> Cart 
                <span class="badge"><?php echo getCartCount(); ?></span>
            </a>
            <?php if(isset($_SESSION['user'])): ?>
                <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user']); ?></a>
                <a href="logout.php" class="btn btn-primary" style="padding: 0.4rem 1rem;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Login</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">
