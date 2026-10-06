<?php
require_once 'config.php';

if (!isset($_COOKIE['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

// Handle logout
if (isset($_GET['tab']) && $_GET['tab'] === 'logout') {
    setcookie('admin_logged_in', '', time() - 3600, "/");
    header('Location: admin_login.php');
    exit;
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Handle add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name  = $conn->real_escape_string($_POST['name']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $image = $conn->real_escape_string($_POST['image']);
    $conn->query("INSERT INTO products (name, price, stock, image) VALUES ('$name', $price, $stock, '$image')");
    header("Location: admin.php?tab=products&success=1");
    exit;
}

// Handle delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $id = (int)$_POST['product_id'];
    $conn->query("DELETE FROM products WHERE id=$id");
    header("Location: admin.php?tab=products&deleted=1");
    exit;
}

// Handle update stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $id    = (int)$_POST['product_id'];
    $stock = (int)$_POST['new_stock'];
    $conn->query("UPDATE products SET stock=$stock WHERE id=$id");
    header("Location: admin.php?tab=products&updated=1");
    exit;
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = (int)$_POST['user_id'];
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: admin.php?tab=users&deleted=1");
    exit;
}

// Fetch core stats
$total_users    = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_products = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$low_stock      = $conn->query("SELECT COUNT(*) as c FROM products WHERE stock <= 10")->fetch_assoc()['c'];
$total_stock    = $conn->query("SELECT SUM(stock) as s FROM products")->fetch_assoc()['s'] ?? 0;

$users_result    = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$products_result = $conn->query("SELECT * FROM products ORDER BY id DESC");

// Chart: last 7 days new users
$chart_labels = []; $chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('M d', strtotime("-$i days"));
    $chart_labels[] = $d; $chart_data[$d] = 0;
}
if ($users_result) {
    mysqli_data_seek($users_result, 0);
    while ($row = $users_result->fetch_assoc()) {
        $d = date('M d', strtotime($row['created_at']));
        if (isset($chart_data[$d])) $chart_data[$d]++;
    }
}
$chart_labels_json = json_encode(array_values($chart_labels));
$chart_data_json   = json_encode(array_values($chart_data));

// Stock distribution chart
$stock_names = []; $stock_vals = [];
if ($products_result) {
    mysqli_data_seek($products_result, 0);
    $cnt = 0;
    while ($row = $products_result->fetch_assoc()) {
        if ($cnt++ >= 6) break;
        $stock_names[] = $row['name'];
        $stock_vals[]  = (int)$row['stock'];
    }
}
$stock_names_json = json_encode($stock_names);
$stock_vals_json  = json_encode($stock_vals);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Floradise</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 255px;
            --bg: #0d1117;
            --sidebar: #111827;
            --card: #161d2b;
            --card2: #1a2236;
            --border: rgba(255,255,255,0.07);
            --text: #e2e8f0;
            --muted: #64748b;
            --accent: #7c3aed;
            --accent2: #10b981;
            --danger: #ef4444;
            --warn: #f59e0b;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w); background: var(--sidebar);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
        }
        .sidebar-brand {
            padding: 28px 24px 20px;
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
            border-bottom: 1px solid var(--border);
        }
        .brand-icon-sm {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: white; flex-shrink: 0;
        }
        .brand-text { font-weight: 800; font-size: 1.1rem; color: #fff; letter-spacing: -0.3px; }
        .brand-text span { background: linear-gradient(90deg, #a78bfa, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .nav-section { padding: 16px 12px 8px; font-size: 0.68rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
        .nav-menu { padding: 0 12px; flex: 1; overflow-y: auto; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 14px; border-radius: 10px;
            text-decoration: none; color: var(--muted);
            font-size: 0.875rem; font-weight: 500;
            transition: all 0.2s; margin-bottom: 2px;
            position: relative;
        }
        .nav-link i { width: 18px; text-align: center; font-size: 0.95rem; }
        .nav-link:hover { background: rgba(255,255,255,0.05); color: var(--text); }
        .nav-link.active { background: rgba(124,58,237,0.15); color: #a78bfa; }
        .nav-link.active::before { content: ''; position: absolute; left: 0; top: 20%; bottom: 20%; width: 3px; background: var(--accent); border-radius: 0 3px 3px 0; }
        .nav-link.danger { color: #f87171; }
        .nav-link.danger:hover { background: rgba(239,68,68,0.1); }

        .badge-pill {
            margin-left: auto; background: rgba(239,68,68,0.2); color: #f87171;
            font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 20px;
        }

        .sidebar-footer {
            padding: 16px 16px;
            border-top: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }
        .admin-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; font-weight: 700; color: white; flex-shrink: 0;
        }
        .admin-info { flex: 1; }
        .admin-name { font-size: 0.82rem; font-weight: 600; color: var(--text); }
        .admin-role { font-size: 0.72rem; color: var(--muted); }

        /* ── Main ── */
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* Topbar */
        .topbar {
            background: var(--sidebar); border-bottom: 1px solid var(--border);
            padding: 0 32px; height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar-title { font-size: 1rem; font-weight: 700; color: var(--text); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-time { font-size: 0.8rem; color: var(--muted); }
        .status-dot { width: 8px; height: 8px; background: var(--accent2); border-radius: 50%; display: inline-block; margin-right: 6px; animation: pulse-dot 2s infinite; }
        @keyframes pulse-dot { 0%,100% { opacity:1; } 50% { opacity:0.4; } }

        /* Content area */
        .content { padding: 32px; flex: 1; }

        /* Page header */
        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .page-header p { color: var(--muted); font-size: 0.875rem; }

        /* Toast */
        .toast {
            display: flex; align-items: center; gap: 10px;
            background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3);
            color: #6ee7b7; padding: 12px 18px; border-radius: 10px;
            margin-bottom: 24px; font-size: 0.875rem; font-weight: 500;
        }
        .toast.danger { background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.3); color: #fca5a5; }

        /* Stat grid */
        .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
        .stat-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 16px; padding: 22px;
            display: flex; align-items: center; gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 14px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
        }
        .stat-icon.purple { background: rgba(124,58,237,0.15); color: #a78bfa; }
        .stat-icon.green  { background: rgba(16,185,129,0.15); color: #34d399; }
        .stat-icon.amber  { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .stat-icon.red    { background: rgba(239,68,68,0.15);  color: #f87171; }
        .stat-body h3 { font-size: 1.7rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 4px; }
        .stat-body p  { font-size: 0.78rem; color: var(--muted); font-weight: 500; }

        /* Card */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .card-header h2 { font-size: 1rem; font-weight: 700; color: #fff; }
        .card-header span { font-size: 0.78rem; color: var(--muted); }

        /* Charts */
        .chart-grid { display: grid; grid-template-columns: 3fr 2fr; gap: 20px; margin-bottom: 28px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        thead th {
            padding: 10px 16px; text-align: left;
            font-size: 0.72rem; font-weight: 700; color: var(--muted);
            text-transform: uppercase; letter-spacing: 0.6px;
            border-bottom: 1px solid var(--border);
        }
        tbody td { padding: 14px 16px; font-size: 0.875rem; border-bottom: 1px solid rgba(255,255,255,0.03); }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: rgba(255,255,255,0.02); }

        /* Tags / Badges */
        .tag {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700;
        }
        .tag.green { background: rgba(16,185,129,0.12); color: #34d399; }
        .tag.red   { background: rgba(239,68,68,0.12);  color: #f87171; }
        .tag.amber { background: rgba(245,158,11,0.12); color: #fbbf24; }

        /* Product avatar */
        .prod-img { width: 38px; height: 38px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border); }

        /* Forms */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px; }
        .form-input {
            width: 100%; padding: 11px 14px;
            background: rgba(255,255,255,0.05); border: 1px solid var(--border);
            border-radius: 10px; color: var(--text); font-family: 'Inter', sans-serif; font-size: 0.875rem;
            transition: all 0.2s;
        }
        .form-input:focus { outline: none; border-color: rgba(124,58,237,0.5); box-shadow: 0 0 0 3px rgba(124,58,237,0.1); }
        .form-input::placeholder { color: var(--muted); }

        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; border-radius: 10px; font-size: 0.875rem; font-weight: 600;
            border: none; cursor: pointer; transition: all 0.2s; font-family: 'Inter', sans-serif;
        }
        .btn-primary { background: linear-gradient(135deg, var(--accent), #5b21b6); color: #fff; }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(124,58,237,0.4); }
        .btn-danger  { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .btn-danger:hover  { background: rgba(239,68,68,0.25); }
        .btn-sm { padding: 6px 12px; font-size: 0.78rem; border-radius: 8px; }
        .btn-full { width: 100%; justify-content: center; }

        /* Product grid (inventory) */
        .layout-2col { display: grid; grid-template-columns: 3fr 2fr; gap: 20px; }

        /* Top products */
        .top-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); }
        .top-item:last-child { border-bottom: none; }
        .top-rank { width: 22px; height: 22px; border-radius: 6px; background: rgba(124,58,237,0.15); color: #a78bfa; font-size: 0.72rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .top-name { flex: 1; font-size: 0.875rem; font-weight: 600; }
        .top-price { font-size: 0.875rem; font-weight: 700; color: var(--accent2); }

        /* Search */
        .search-wrap { position: relative; margin-bottom: 16px; }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 0.85rem; }
        .search-input { width: 100%; padding: 10px 14px 10px 38px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-family: 'Inter', sans-serif; font-size: 0.875rem; }
        .search-input:focus { outline: none; border-color: rgba(124,58,237,0.4); }
        .search-input::placeholder { color: var(--muted); }

        /* Stock bar */
        .stock-bar { height: 5px; background: rgba(255,255,255,0.07); border-radius: 3px; width: 80px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 8px; }
        .stock-fill { height: 100%; border-radius: 3px; transition: width 0.5s; }

        /* Coming soon */
        .coming-soon { text-align: center; padding: 80px 20px; }
        .coming-soon i { font-size: 3rem; color: var(--muted); margin-bottom: 16px; opacity: 0.4; }
        .coming-soon h2 { font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; color: #fff; }
        .coming-soon p  { color: var(--muted); font-size: 0.875rem; }
    </style>
</head>
<body>

<!-- ══ Sidebar ══ -->
<aside class="sidebar">
    <a href="index.php" class="sidebar-brand">
        <div class="brand-icon-sm"><i class="fas fa-seedling"></i></div>
        <div class="brand-text">Flora<span>dise</span></div>
    </a>
    <div class="nav-menu">
        <div class="nav-section">Main</div>
        <a href="admin.php?tab=dashboard" class="nav-link <?php echo $tab=='dashboard'?'active':''; ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="admin.php?tab=products" class="nav-link <?php echo $tab=='products'?'active':''; ?>">
            <i class="fas fa-boxes-stacked"></i> Inventory
            <?php if($low_stock > 0): ?><span class="badge-pill"><?php echo $low_stock; ?></span><?php endif; ?>
        </a>
        <a href="admin.php?tab=users" class="nav-link <?php echo $tab=='users'?'active':''; ?>">
            <i class="fas fa-users"></i> Customers
        </a>
        <a href="admin.php?tab=orders" class="nav-link <?php echo $tab=='orders'?'active':''; ?>">
            <i class="fas fa-shopping-bag"></i> Orders
        </a>
        <div class="nav-section">System</div>
        <a href="index.php" class="nav-link">
            <i class="fas fa-store"></i> View Store
        </a>
        <a href="admin.php?tab=logout" class="nav-link danger">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </div>
    <div class="sidebar-footer">
        <div class="admin-avatar">A</div>
        <div class="admin-info">
            <div class="admin-name">Admin</div>
            <div class="admin-role">Super Administrator</div>
        </div>
    </div>
</aside>

<!-- ══ Main ══ -->
<div class="main">
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-title">
            <?php
            $titles = ['dashboard'=>'Dashboard Overview','products'=>'Inventory Management','users'=>'Customer Management','orders'=>'Orders'];
            echo $titles[$tab] ?? 'Admin Panel';
            ?>
        </div>
        <div class="topbar-right">
            <span class="topbar-time"><span class="status-dot"></span>Live &mdash; <?php echo date('D, M d Y'); ?></span>
        </div>
    </div>

    <div class="content">

    <?php if ($tab == 'dashboard'): ?>

        <div class="page-header">
            <h1>Welcome back, Admin 👋</h1>
            <p>Here's what's happening with your Floradise store today.</p>
        </div>

        <!-- Stats -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                <div class="stat-body"><h3><?php echo $total_users; ?></h3><p>Total Customers</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-boxes-stacked"></i></div>
                <div class="stat-body"><h3><?php echo $total_products; ?></h3><p>Products Listed</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon amber"><i class="fas fa-cubes"></i></div>
                <div class="stat-body"><h3><?php echo number_format($total_stock); ?></h3><p>Total Stock Units</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="stat-body"><h3><?php echo $low_stock; ?></h3><p>Low Stock Alerts</p></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="chart-grid">
            <div class="card">
                <div class="card-header">
                    <h2>New Customers (7 days)</h2>
                    <span><?php echo date('F Y'); ?></span>
                </div>
                <canvas id="userChart" style="max-height:240px;"></canvas>
            </div>
            <div class="card">
                <div class="card-header"><h2>Stock Levels</h2><span>Top 6 products</span></div>
                <canvas id="stockChart" style="max-height:240px;"></canvas>
            </div>
        </div>

        <!-- Top products + recent customers -->
        <div class="chart-grid">
            <div class="card">
                <div class="card-header"><h2>Recent Customers</h2><a href="admin.php?tab=users" style="font-size:0.78rem;color:var(--accent);text-decoration:none;">View all</a></div>
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php
                    mysqli_data_seek($users_result, 0);
                    $u = 0;
                    while ($row = $users_result->fetch_assoc()) {
                        if ($u++ >= 5) break;
                        echo "<tr>
                            <td><strong>".htmlspecialchars($row['name'])."</strong></td>
                            <td style='color:var(--muted)'>".htmlspecialchars($row['email'])."</td>
                            <td style='color:var(--muted)'>".date('M d, Y', strtotime($row['created_at']))."</td>
                        </tr>";
                    }
                    if ($u == 0) echo "<tr><td colspan='3' style='text-align:center;color:var(--muted);padding:24px'>No customers yet</td></tr>";
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <div class="card-header"><h2>Top Products</h2></div>
                <?php
                mysqli_data_seek($products_result, 0);
                $rank = 1;
                while ($row = $products_result->fetch_assoc()) {
                    if ($rank > 5) break;
                    echo "<div class='top-item'>
                        <div class='top-rank'>{$rank}</div>
                        <img src='".htmlspecialchars($row['image'])."' class='prod-img' alt=''>
                        <div class='top-name'>".htmlspecialchars($row['name'])."</div>
                        <div class='top-price'>₱".number_format($row['price'],2)."</div>
                    </div>";
                    $rank++;
                }
                ?>
            </div>
        </div>

        <script>
        // User chart
        const ctx = document.getElementById('userChart').getContext('2d');
        const grad = ctx.createLinearGradient(0,0,0,300);
        grad.addColorStop(0,'rgba(124,58,237,0.35)'); grad.addColorStop(1,'rgba(124,58,237,0)');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo $chart_labels_json; ?>,
                datasets: [{ label:'New Customers', data: <?php echo $chart_data_json; ?>,
                    borderColor:'#7c3aed', backgroundColor: grad, borderWidth:2.5, fill:true,
                    tension:0.4, pointBackgroundColor:'#fff', pointBorderColor:'#7c3aed', pointRadius:4 }]
            },
            options: { responsive:true, maintainAspectRatio:false,
                plugins:{ legend:{display:false} },
                scales:{
                    y:{beginAtZero:true, ticks:{color:'#64748b', font:{size:11}}, grid:{color:'rgba(255,255,255,0.04)'}, border:{display:false}},
                    x:{ticks:{color:'#64748b', font:{size:11}}, grid:{display:false}, border:{display:false}}
                }
            }
        });
        // Stock chart
        const ctx2 = document.getElementById('stockChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: <?php echo $stock_names_json; ?>,
                datasets: [{ data: <?php echo $stock_vals_json; ?>,
                    backgroundColor:['#7c3aed','#10b981','#f59e0b','#6366f1','#ef4444','#3b82f6'],
                    borderWidth: 0, hoverOffset: 8 }]
            },
            options: { responsive:true, maintainAspectRatio:false, cutout:'68%',
                plugins:{ legend:{ position:'bottom', labels:{ color:'#64748b', font:{size:10}, boxWidth:10, padding:10 } } }
            }
        });
        </script>

    <?php elseif ($tab == 'products'): ?>

        <div class="page-header">
            <h1>Inventory Management</h1>
            <p>Manage your product catalogue, stock levels, and listings.</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="toast"><i class="fas fa-circle-check"></i> Product added successfully!</div>
        <?php elseif (isset($_GET['deleted'])): ?>
        <div class="toast danger"><i class="fas fa-trash"></i> Product deleted.</div>
        <?php elseif (isset($_GET['updated'])): ?>
        <div class="toast"><i class="fas fa-pen"></i> Stock updated successfully!</div>
        <?php endif; ?>

        <div class="layout-2col">
            <!-- Product table -->
            <div class="card">
                <div class="card-header"><h2>Current Catalogue</h2><span><?php echo $total_products; ?> products</span></div>
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" id="productSearch" placeholder="Search products...">
                </div>
                <table id="productTable">
                    <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php
                    mysqli_data_seek($products_result, 0);
                    while ($row = $products_result->fetch_assoc()):
                        $pct = min(100, ($row['stock'] / 50) * 100);
                        $fillColor = $row['stock'] > 10 ? '#10b981' : '#ef4444';
                        $stockTag = $row['stock'] > 10
                            ? "<span class='tag green'><i class='fas fa-circle' style='font-size:0.5rem'></i> In Stock ({$row['stock']})</span>"
                            : "<span class='tag red'><i class='fas fa-circle' style='font-size:0.5rem'></i> Low ({$row['stock']})</span>";
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <img src="<?php echo htmlspecialchars($row['image']); ?>" class="prod-img" alt="">
                                <strong style="font-size:0.875rem;"><?php echo htmlspecialchars($row['name']); ?></strong>
                            </div>
                        </td>
                        <td style="font-weight:700;color:var(--accent2);">₱<?php echo number_format($row['price'],2); ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div class="stock-bar"><div class="stock-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $fillColor; ?>;"></div></div>
                                <?php echo $stockTag; ?>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <!-- Quick restock -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <input type="number" name="new_stock" value="<?php echo $row['stock']; ?>" min="0" style="width:60px;padding:5px 8px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:0.78rem;">
                                    <button type="submit" name="update_stock" class="btn btn-sm" style="background:rgba(99,102,241,0.15);color:#818cf8;border:1px solid rgba(99,102,241,0.2);">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <!-- Delete -->
                                <form method="POST" onsubmit="return confirm('Delete this product?');" style="display:inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add product form -->
            <div class="card">
                <div class="card-header"><h2>Add New Product</h2></div>
                <form method="POST" action="admin.php?tab=products">
                    <div class="form-group">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-input" placeholder="e.g. Red Tulip Bouquet" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" step="0.01" name="price" class="form-input" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Initial Stock</label>
                        <input type="number" name="stock" class="form-input" value="50" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Image URL</label>
                        <input type="url" name="image" class="form-input" placeholder="https://..." id="imgUrl" required>
                    </div>
                    <div id="imgPreviewWrap" style="margin-bottom:16px;display:none;">
                        <img id="imgPreview" style="width:100%;height:140px;object-fit:cover;border-radius:10px;border:1px solid var(--border);" alt="Preview">
                    </div>
                    <button type="submit" name="add_product" class="btn btn-primary btn-full">
                        <i class="fas fa-plus"></i> Add to Catalogue
                    </button>
                </form>
            </div>
        </div>
        <script>
        // Live image preview
        document.getElementById('imgUrl').addEventListener('input', function() {
            const wrap = document.getElementById('imgPreviewWrap');
            const img  = document.getElementById('imgPreview');
            if (this.value) { img.src = this.value; wrap.style.display = 'block'; }
            else { wrap.style.display = 'none'; }
        });
        // Table search
        document.getElementById('productSearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#productTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
        </script>

    <?php elseif ($tab == 'users'): ?>

        <div class="page-header">
            <h1>Customer Management</h1>
            <p><?php echo $total_users; ?> registered customers in your store.</p>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
        <div class="toast danger"><i class="fas fa-trash"></i> Customer removed.</div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h2>All Customers</h2></div>
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" id="userSearch" placeholder="Search by name or email...">
            </div>
            <table id="userTable">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Joined</th><th>Action</th></tr></thead>
                <tbody>
                <?php
                mysqli_data_seek($users_result, 0);
                while ($row = $users_result->fetch_assoc()):
                    $initials = strtoupper(substr($row['name'], 0, 1));
                ?>
                <tr>
                    <td style="color:var(--muted);font-weight:600;">#<?php echo $row['id']; ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#10b981);display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:#fff;flex-shrink:0;"><?php echo $initials; ?></div>
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                        </div>
                    </td>
                    <td style="color:var(--muted);"><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><span class="tag amber"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Remove this customer?');">
                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($total_users == 0): ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--muted);">No customers registered yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <script>
        document.getElementById('userSearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#userTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
        </script>

    <?php else: ?>
        <div class="page-header"><h1>Orders</h1><p>Track and manage your store orders.</p></div>
        <div class="card">
            <div class="coming-soon">
                <i class="fas fa-shopping-bag"></i>
                <h2>Orders Module Coming Soon</h2>
                <p>Order tracking and fulfilment management will appear here once integrated.</p>
            </div>
        </div>
    <?php endif; ?>

    </div><!-- /content -->
</div><!-- /main -->

</body>
</html>
