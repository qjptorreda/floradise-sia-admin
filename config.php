<?php
session_start();

// Sync Cookies to Session for Vercel Compatibility
if (isset($_COOKIE['user']))
    $_SESSION['user'] = $_COOKIE['user'];
if (isset($_COOKIE['message'])) {
    $_SESSION['message'] = $_COOKIE['message'];
    setcookie('message', '', time() - 3600, "/");
}
if (isset($_COOKIE['error'])) {
    $_SESSION['error'] = $_COOKIE['error'];
    setcookie('error', '', time() - 3600, "/");
}
if (isset($_COOKIE['cart'])) {
    $_SESSION['cart'] = json_decode($_COOKIE['cart'], true);
}

// Database Connection (Aiven Cloud MySQL)
// Set these as environment variables in your hosting platform (e.g., Vercel, .env file).
// NEVER hardcode credentials here.
$db_host = getenv('DB_HOST') ?: 'mysql-1c6d9e73-torredajairus63-e18c.l.aivencloud.com';
$db_user = getenv('DB_USER') ?: 'avnadmin';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'defaultdb';
$db_port = (int)(getenv('DB_PORT') ?: 11993);

$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL); // Enable SSL for Aiven
$conn->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Automatically create the users table in Aiven if it doesn't exist yet!
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Automatically create the products table in Aiven if it doesn't exist yet!
$conn->query("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 50,
    image VARCHAR(500) NOT NULL
)");

// Automatically create Admin users table
$conn->query("CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)");

// Seed Admin database if empty
$admin_check = $conn->query("SELECT COUNT(*) as count FROM admins");
if ($admin_check && $admin_check->fetch_assoc()['count'] == 0) {
    $hash = password_hash('floradise_admin', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO admins (username, password) VALUES ('admin', '$hash')");
}

// Mock Products - Seed database if empty
$prod_check = $conn->query("SELECT COUNT(*) as count FROM products");
if ($prod_check && $prod_check->fetch_assoc()['count'] == 0) {
    $seed_products = [
        1 => ["name" => "Rose Bouquet", "price" => 1250.00, "image" => "https://images.unsplash.com/photo-1548811579-017fac2a00c1?q=80&w=500&auto=format&fit=crop"],
        2 => ["name" => "Sunflower Delight", "price" => 850.00, "image" => "https://images.unsplash.com/photo-1597848212624-a19eb35e2651?q=80&w=500&auto=format&fit=crop"],
        3 => ["name" => "Orchid Elegance", "price" => 2500.00, "image" => "https://images.unsplash.com/photo-1565011523534-747a8601f10a?q=80&w=500&auto=format&fit=crop"],
        4 => ["name" => "Tulip Dream", "price" => 999.00, "image" => "https://images.unsplash.com/photo-1520763185298-1b434c919102?q=80&w=500&auto=format&fit=crop"],
        5 => ["name" => "Lily Perfection", "price" => 1800.00, "image" => "https://images.unsplash.com/photo-1554631221-5634f681d420?q=80&w=500&auto=format&fit=crop"],
        6 => ["name" => "Potted Monstera", "price" => 1500.00, "image" => "https://images.unsplash.com/photo-1614594975525-e45190c55d0b?q=80&w=500&auto=format&fit=crop"],
        7 => ["name" => "Succulent Garden", "price" => 750.00, "image" => "https://images.unsplash.com/photo-1459156212016-c812468e2115?q=80&w=500&auto=format&fit=crop"],
        8 => ["name" => "Bridal Peonies", "price" => 3500.00, "image" => "https://images.unsplash.com/photo-1563241527-3004b7be0ffd?q=80&w=500&auto=format&fit=crop"],
        9 => ["name" => "Lavender Bliss", "price" => 600.00, "image" => "https://images.unsplash.com/photo-1508970058163-95d1052670dd?q=80&w=500&auto=format&fit=crop"],
        10 => ["name" => "Tropical Hibiscus", "price" => 850.00, "image" => "https://images.unsplash.com/photo-1560341753-4dcde6bcff84?q=80&w=500&auto=format&fit=crop"],
        11 => ["name" => "Fiddle Leaf Fig", "price" => 2200.00, "image" => "https://images.unsplash.com/photo-1597055958616-2c252eafa197?q=80&w=500&auto=format&fit=crop"],
        12 => ["name" => "Bonsai Tree", "price" => 3200.00, "image" => "https://images.unsplash.com/photo-1599598425947-3300262119ff?q=80&w=500&auto=format&fit=crop"],
        13 => ["name" => "Snake Plant", "price" => 950.00, "image" => "https://images.unsplash.com/photo-1598880940080-c9a518331db4?q=80&w=500&auto=format&fit=crop"],
        14 => ["name" => "Peace Lily", "price" => 1100.00, "image" => "https://images.unsplash.com/photo-1593696954577-09f19db1d6a7?q=80&w=500&auto=format&fit=crop"],
        15 => ["name" => "Cactus Collection", "price" => 1350.00, "image" => "https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?q=80&w=500&auto=format&fit=crop"],
        16 => ["name" => "Carnation Box", "price" => 1650.00, "image" => "https://images.unsplash.com/photo-1563241527-3004b7be0ffd?q=80&w=500&auto=format&fit=crop"],
        17 => ["name" => "Chrysanthemum Pot", "price" => 750.00, "image" => "https://images.unsplash.com/photo-1508898578281-774ac4893c0c?q=80&w=500&auto=format&fit=crop"],
        18 => ["name" => "Hydrangea Bouquet", "price" => 2100.00, "image" => "https://images.unsplash.com/photo-1503149779833-1de50ebe5f8a?q=80&w=500&auto=format&fit=crop"],
        19 => ["name" => "Aloe Vera", "price" => 450.00, "image" => "https://images.unsplash.com/photo-1554631221-5634f681d420?q=80&w=500&auto=format&fit=crop"],
        20 => ["name" => "Daisy Charm", "price" => 650.00, "image" => "https://images.unsplash.com/photo-1560790671-b76ca4de55ef?q=80&w=500&auto=format&fit=crop"]
    ];
    
    foreach ($seed_products as $p) {
        $name = $conn->real_escape_string($p['name']);
        $image = $conn->real_escape_string($p['image']);
        $conn->query("INSERT INTO products (name, price, stock, image) VALUES ('$name', {$p['price']}, 50, '$image')");
    }
}

// Global products variable for the frontend to use
$products = [];
$res = $conn->query("SELECT * FROM products");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $products[$row['id']] = $row;
    }
}

function getCartCount()
{
    $count = 0;
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $qty) {
            $count += $qty;
        }
    }
    return $count;
}
?>