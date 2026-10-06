<?php
require_once 'config.php';

// Ensure Admin is logged in securely
if (!isset($_COOKIE['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle Admin Logout
if (isset($_GET['tab']) && $_GET['tab'] === 'logout') {
    setcookie('admin_logged_in', '', time() - 3600, "/");
    header('Location: admin_login.php');
    exit;
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Handle adding a product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $image = $conn->real_escape_string($_POST['image']);
    
    $conn->query("INSERT INTO products (name, price, stock, image) VALUES ('$name', $price, $stock, '$image')");
    header("Location: admin.php?tab=products");
    exit;
}

// Fetch Data for Dashboard
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$users_result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$products_result = $conn->query("SELECT * FROM products ORDER BY id DESC");

// Generate Chart Data (Last 7 Days)
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('M d', strtotime("-$i days"));
    $chart_labels[] = $date;
    $chart_data[$date] = 0;
}
if ($users_result) {
    mysqli_data_seek($users_result, 0);
    while($row = $users_result->fetch_assoc()) {
        $date = date('M d', strtotime($row['created_at']));
        if (isset($chart_data[$date])) {
            $chart_data[$date]++;
        }
    }
}
$chart_labels_json = json_encode(array_values($chart_labels));
$chart_data_json = json_encode(array_values($chart_data));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Floradise</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
        
        :root {
            --primary: #6366f1; /* Indigo */
            --primary-light: #e0e7ff;
            --sidebar-bg: #ffffff;
            --main-bg: #f4f7fb; 
            --card-bg: #ffffff;
            --text-dark: #1e1e2d;
            --text-gray: #7b809a;
            --border: #e2e8f0;
        }
        
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--main-bg); display: flex; min-height: 100vh; color: var(--text-dark); }
        
        /* Sidebar */
        .sidebar { width: 250px; background: var(--sidebar-bg); display: flex; flex-direction: column; border-right: 1px solid var(--border); z-index: 10; padding-top: 20px;}
        .sidebar .brand { padding: 0 25px 30px; font-size: 1.2rem; font-weight: 800; color: var(--text-dark); text-decoration: none; display: flex; align-items: center; gap: 10px; letter-spacing: 0.5px;}
        .sidebar .brand i { color: var(--primary); font-size: 1.5rem; }
        
        .nav-menu { flex: 1; padding: 0 15px; }
        .sidebar a { padding: 12px 15px; color: var(--text-gray); text-decoration: none; display: flex; align-items: center; border-radius: 8px; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .sidebar a i { width: 30px; font-size: 1.1rem; }
        .sidebar a:hover { background: #f8fafc; color: var(--text-dark); }
        .sidebar a.active { background: var(--primary-light); color: var(--primary); }
        
        /* Main Content */
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        
        .welcome-card { background: var(--card-bg); border-radius: 12px; padding: 30px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border: 1px solid var(--border); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .welcome-card h1 { margin: 0 0 5px 0; font-size: 1.8rem; font-weight: 800; color: var(--text-dark); }
        .welcome-card p { margin: 0; color: var(--text-gray); font-size: 0.95rem; }
        .demo-badge { background: #f3f4f6; color: var(--text-dark); font-weight: 600; padding: 8px 15px; border-radius: 20px; font-size: 0.85rem; border: 1px solid var(--border); }
        
        .section-title { font-size: 1.1rem; font-weight: 700; margin: 0 0 15px 0; color: var(--text-dark); }
        
        /* Quick Access */
        .quick-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .quick-card { background: var(--card-bg); border-radius: 12px; padding: 15px 20px; display: flex; align-items: center; gap: 15px; border: 1px solid var(--border); box-shadow: 0 2px 5px rgba(0,0,0,0.01); text-decoration: none; color: var(--text-dark); transition: transform 0.2s;}
        .quick-card:hover { transform: translateY(-2px); border-color: var(--primary); }
        .quick-icon { width: 40px; height: 40px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.2rem; }
        .quick-text h4 { margin: 0 0 2px 0; font-size: 0.95rem; font-weight: 700; }
        .quick-text p { margin: 0; font-size: 0.75rem; color: var(--text-gray); }
        
        /* Stat Grid */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); padding: 25px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 2px 5px rgba(0,0,0,0.01); }
        .stat-card h3 { margin: 0 0 15px 0; color: var(--text-gray); font-size: 0.85rem; font-weight: 600; }
        .stat-card p { margin: 0 0 10px 0; font-size: 2rem; font-weight: 800; color: var(--text-dark); }
        .stat-trend { font-size: 0.75rem; font-weight: 600; color: var(--primary); }
        
        /* Bottom Grid */
        .bottom-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: var(--card-bg); border-radius: 12px; padding: 25px; border: 1px solid var(--border); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h2 { margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-dark); }
        .card-header span { color: var(--text-gray); font-size: 0.85rem; font-weight: 500; }
        
        /* Top Products List */
        .top-product-item { display: flex; align-items: center; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
        .top-product-item:last-child { border-bottom: none; }
        .tp-left { display: flex; align-items: center; gap: 15px; }
        .tp-rank { color: var(--primary); font-weight: 700; font-size: 0.9rem; width: 15px; }
        .tp-name { font-weight: 600; font-size: 0.9rem; }
        .tp-price { font-weight: 700; font-size: 0.95rem; }
        
        /* Tables & Forms (For other tabs) */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem;}
        th { color: var(--text-gray); font-weight: 600; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; font-family: 'Inter'; }
        .btn { background: var(--primary); color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
    </style>
</head>
<body>

    <div class="sidebar">
        <a href="index.php" class="brand"><i class="fas fa-seedling"></i> Floradise</a>
        
        <div class="nav-menu">
            <a href="admin.php?tab=dashboard" class="<?php echo $tab == 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin.php?tab=orders" class="<?php echo $tab == 'orders' ? 'active' : ''; ?>"><i class="fas fa-shopping-bag"></i> Orders</a>
            <a href="admin.php?tab=products" class="<?php echo $tab == 'products' ? 'active' : ''; ?>"><i class="fas fa-box"></i> Inventory</a>
            <a href="admin.php?tab=users" class="<?php echo $tab == 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Customers</a>
            
            <div style="margin-top: 40px; margin-bottom: 10px; padding-left: 15px; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">System</div>
            <a href="index.php"><i class="fas fa-store"></i> Store Front</a>
            <a href="admin.php?tab=logout" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <?php if ($tab == 'dashboard'): ?>
            
            <div class="welcome-card">
                <div>
                    <h1>Welcome to your florist dashboard</h1>
                    <p>Sales, customers and operations in one connected view.</p>
                </div>
                <div class="demo-badge"><i class="fas fa-lock"></i> Secure Admin</div>
            </div>
            
            <h2 class="section-title">Quick access</h2>
            <div class="quick-grid">
                <a href="admin.php?tab=products" class="quick-card">
                    <div class="quick-icon"><i class="fas fa-plus"></i></div>
                    <div class="quick-text"><h4>Add product</h4><p>Keep the catalogue current</p></div>
                </a>
                <a href="#" class="quick-card">
                    <div class="quick-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="quick-text"><h4>Manage orders</h4><p>See every sale in one place</p></div>
                </a>
                <a href="admin.php?tab=products" class="quick-card">
                    <div class="quick-icon"><i class="fas fa-boxes"></i></div>
                    <div class="quick-text"><h4>View stock</h4><p>Check branch availability</p></div>
                </a>
                <a href="#" class="quick-card">
                    <div class="quick-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="quick-text"><h4>Sales reports</h4><p>Track daily performance</p></div>
                </a>
            </div>
            
            <div class="stat-grid">
                <div class="stat-card">
                    <h3>Total revenue</h3>
                    <p>$0.00</p>
                    <div class="stat-trend">+0.0% this month</div>
                </div>
                <div class="stat-card">
                    <h3>Online orders</h3>
                    <p>0</p>
                    <div class="stat-trend">0 awaiting fulfilment</div>
                </div>
                <div class="stat-card">
                    <h3>Customers</h3>
                    <p><?php echo $total_users; ?></p>
                    <div class="stat-trend">+<?php echo $total_users; ?> this month</div>
                </div>
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <p><?php echo $total_products; ?></p>
                    <div class="stat-trend">Live in store</div>
                </div>
            </div>
            
            <div class="bottom-grid">
                <div class="card">
                    <div class="card-header">
                        <h2>Sales overview</h2>
                        <span><?php echo date('F Y'); ?></span>
                    </div>
                    <canvas id="userChart" style="width:100%; max-height: 280px;"></canvas>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2>Top products</h2>
                    </div>
                    <?php 
                    $rank = 1;
                    mysqli_data_seek($products_result, 0);
                    while($row = $products_result->fetch_assoc()): 
                        if ($rank > 5) break;
                    ?>
                    <div class="top-product-item">
                        <div class="tp-left">
                            <span class="tp-rank"><?php echo $rank++; ?></span>
                            <span class="tp-name"><?php echo htmlspecialchars($row['name']); ?></span>
                        </div>
                        <span class="tp-price">₱<?php echo number_format($row['price'], 2); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <script>
            // Initialize Chart.js to match the purple style
            const ctx = document.getElementById('userChart').getContext('2d');
            
            // Create gradient
            let gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
            gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo $chart_labels_json; ?>,
                    datasets: [{
                        label: 'New Users',
                        data: <?php echo $chart_data_json; ?>,
                        borderColor: '#6366f1',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#6366f1',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { 
                        y: { beginAtZero: true, grid: { borderDash: [5, 5], color: '#f1f5f9' }, border: { display: false } },
                        x: { grid: { display: false }, border: { display: false } }
                    }
                }
            });
            </script>
            
        <?php elseif ($tab == 'users'): ?>
            <h2 class="section-title" style="font-size: 1.5rem;">Registered Customers</h2>
            <div class="card">
                <table>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Signup Date</th></tr>
                    <?php mysqli_data_seek($users_result, 0); while($row = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

        <?php elseif ($tab == 'products'): ?>
            <h2 class="section-title" style="font-size: 1.5rem;">Inventory Management</h2>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="card">
                    <div class="card-header"><h2>Current Stock</h2></div>
                    <table>
                        <tr><th>Product</th><th>Price</th><th>Stock Level</th></tr>
                        <?php mysqli_data_seek($products_result, 0); while($row = $products_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?php echo htmlspecialchars($row['image']); ?>" style="width: 35px; height: 35px; border-radius: 8px; object-fit: cover;">
                                        <strong style="font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></strong>
                                    </div>
                                </td>
                                <td style="font-weight: 600;">₱<?php echo number_format($row['price'], 2); ?></td>
                                <td>
                                    <?php if($row['stock'] > 10): ?>
                                        <span style="color: #059669; background: #d1fae5; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 700;">In Stock (<?php echo $row['stock']; ?>)</span>
                                    <?php else: ?>
                                        <span style="color: #dc2626; background: #fee2e2; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 700;">Low Stock (<?php echo $row['stock']; ?>)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
                
                <div class="card">
                    <div class="card-header"><h2>Add New Product</h2></div>
                    <form method="POST" action="admin.php?tab=products">
                        <div class="form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" required placeholder="e.g. Red Tulips">
                        </div>
                        <div class="form-group">
                            <label>Price (₱)</label>
                            <input type="number" step="0.01" name="price" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Initial Stock</label>
                            <input type="number" name="stock" value="10" required>
                        </div>
                        <div class="form-group">
                            <label>Image URL</label>
                            <input type="text" name="image" required placeholder="https://...">
                        </div>
                        <button type="submit" name="add_product" class="btn" style="width: 100%;"><i class="fas fa-plus"></i> Save Product</button>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
             <div class="welcome-card">
                 <div>
                     <h1>Feature coming soon</h1>
                     <p>This section is currently under development.</p>
                 </div>
             </div>
        <?php endif; ?>
    </div>
</body>
</html>
