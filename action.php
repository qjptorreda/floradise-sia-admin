<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        $product_id = $_POST['product_id'];
        
        $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];
        if (!is_array($cart)) $cart = [];

        if (isset($cart[$product_id])) {
            $cart[$product_id]++;
        } else {
            $cart[$product_id] = 1;
        }

        setcookie('cart', json_encode($cart), time() + 86400 * 30, "/");
        setcookie('message', "Product added to cart successfully!", time() + 5, "/");
        header('Location: index.php');
        exit;
    }

    if ($action === 'remove_from_cart') {
        $product_id = $_POST['product_id'];
        $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];
        if (!is_array($cart)) $cart = [];

        if (isset($cart[$product_id])) {
            unset($cart[$product_id]);
        }
        setcookie('cart', json_encode($cart), time() + 86400 * 30, "/");
        header('Location: cart.php');
        exit;
    }

    if ($action === 'login') {
        $email = $conn->real_escape_string($_POST['email']);
        $password = $_POST['password'];
        
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                setcookie('user', $user['name'], time() + 86400 * 30, "/");
                header('Location: index.php');
                exit;
            }
        }
        setcookie('error', "Invalid credentials!", time() + 5, "/");
        header('Location: login.php');
        exit;
    }

    if ($action === 'signup') {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Basic check if email exists
        $check_sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($check_sql);

        if ($result->num_rows > 0) {
            setcookie('error', "Email already registered!", time() + 5, "/");
            header('Location: signup.php');
            exit;
        } else {
            $insert_sql = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$password')";
            if ($conn->query($insert_sql) === TRUE) {
                setcookie('user', $name, time() + 86400 * 30, "/");
                header('Location: index.php');
                exit;
            } else {
                setcookie('error', "Database error: " . $conn->error, time() + 5, "/");
                header('Location: signup.php');
                exit;
            }
        }
    }

    if ($action === 'checkout') {
        // Mock payment processing
        setcookie('cart', '', time() - 3600, "/");
        setcookie('message', "Payment successful! Your flowers are on the way.", time() + 5, "/");
        header('Location: index.php');
        exit;
    }
}
