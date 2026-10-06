<?php
require_once 'config.php';

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
            $error = 'Invalid password. Please try again.';
        }
    } else {
        $error = 'Admin account not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal — Floradise</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #060914;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; position: relative;
        }
        .bg-aura { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
        .aura-blob {
            position: absolute; border-radius: 50%;
            filter: blur(100px); opacity: 0.18;
            animation: drift 12s ease-in-out infinite alternate;
        }
        .aura-blob:nth-child(1) { width: 600px; height: 600px; background: #7c3aed; top: -150px; left: -150px; animation-duration: 14s; }
        .aura-blob:nth-child(2) { width: 500px; height: 500px; background: #10b981; bottom: -100px; right: -100px; animation-duration: 10s; animation-delay: -5s; }
        .aura-blob:nth-child(3) { width: 400px; height: 400px; background: #6366f1; top: 40%; left: 40%; animation-duration: 16s; animation-delay: -3s; }
        @keyframes drift { from { transform: translate(0,0) scale(1); } to { transform: translate(40px, 30px) scale(1.1); } }
        #particles { position: fixed; inset: 0; z-index: 1; pointer-events: none; }
        .login-wrapper { position: relative; z-index: 10; width: 100%; max-width: 420px; padding: 20px; }
        .login-card {
            background: rgba(255,255,255,0.04);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px; padding: 44px 40px 40px;
            box-shadow: 0 0 0 1px rgba(255,255,255,0.05) inset, 0 30px 80px rgba(0,0,0,0.6), 0 0 60px rgba(124,58,237,0.08);
            animation: cardIn 0.7s cubic-bezier(0.16,1,0.3,1) both;
        }
        @keyframes cardIn { from { opacity:0; transform:translateY(30px) scale(0.97); } to { opacity:1; transform:translateY(0) scale(1); } }
        .brand-area { text-align: center; margin-bottom: 36px; }
        .brand-icon {
            width: 68px; height: 68px;
            background: linear-gradient(135deg, #7c3aed, #10b981);
            border-radius: 20px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: white; margin-bottom: 18px;
            box-shadow: 0 8px 32px rgba(124,58,237,0.4);
            animation: iconPulse 3s ease-in-out infinite;
        }
        @keyframes iconPulse { 0%,100% { box-shadow: 0 8px 32px rgba(124,58,237,0.4); } 50% { box-shadow: 0 8px 48px rgba(124,58,237,0.7); } }
        .brand-title { font-size: 1.6rem; font-weight: 800; color: #fff; letter-spacing: -0.5px; margin-bottom: 6px; }
        .brand-title span { background: linear-gradient(90deg, #a78bfa, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .brand-sub { color: rgba(255,255,255,0.4); font-size: 0.85rem; min-height: 20px; }
        .error-box {
            background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5; padding: 12px 16px; border-radius: 10px;
            font-size: 0.85rem; margin-bottom: 22px;
            display: flex; align-items: center; gap: 10px;
            animation: shake 0.4s ease;
        }
        @keyframes shake { 0%,100% { transform: translateX(0); } 20%,60% { transform: translateX(-6px); } 40%,80% { transform: translateX(6px); } }
        .form-group { margin-bottom: 18px; position: relative; }
        .form-label { display: block; font-size: 0.78rem; font-weight: 600; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.25); font-size: 0.9rem; pointer-events: none; transition: color 0.2s; }
        .form-input {
            width: 100%; padding: 14px 16px 14px 44px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px; color: #fff;
            font-size: 0.95rem; font-family: 'Inter', sans-serif;
            transition: all 0.25s;
        }
        .form-input::placeholder { color: rgba(255,255,255,0.2); }
        .form-input:focus { outline: none; background: rgba(255,255,255,0.09); border-color: rgba(124,58,237,0.7); box-shadow: 0 0 0 3px rgba(124,58,237,0.15); }
        .toggle-pw { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.25); cursor: pointer; font-size: 0.9rem; transition: color 0.2s; background: none; border: none; padding: 4px; }
        .toggle-pw:hover { color: rgba(255,255,255,0.6); }
        .btn-login {
            width: 100%; padding: 15px;
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            color: white; font-weight: 700; font-size: 0.95rem;
            border: none; border-radius: 12px; cursor: pointer;
            transition: all 0.25s; position: relative; overflow: hidden;
            letter-spacing: 0.3px; margin-top: 6px;
        }
        .btn-login::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #8b5cf6, #6d28d9); opacity: 0; transition: opacity 0.25s; }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 10px 30px rgba(124,58,237,0.5); }
        .btn-login span { position: relative; z-index: 1; }
        .login-footer { text-align: center; margin-top: 24px; }
        .login-footer a { color: rgba(255,255,255,0.3); text-decoration: none; font-size: 0.82rem; transition: color 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .login-footer a:hover { color: rgba(255,255,255,0.6); }
        .security-badge { display: flex; align-items: center; justify-content: center; gap: 6px; color: rgba(255,255,255,0.2); font-size: 0.75rem; margin-top: 20px; }
        .security-badge i { color: #34d399; font-size: 0.7rem; }
    </style>
</head>
<body>
    <div class="bg-aura">
        <div class="aura-blob"></div>
        <div class="aura-blob"></div>
        <div class="aura-blob"></div>
    </div>
    <canvas id="particles"></canvas>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand-area">
                <div class="brand-icon"><i class="fas fa-seedling"></i></div>
                <div class="brand-title">Floradise <span>Admin</span></div>
                <div class="brand-sub" id="typewriter"></div>
            </div>
            <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-wrap">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="username" class="form-input" placeholder="Enter admin username" required autocomplete="username">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="toggle-pw" id="togglePw">
                            <i class="fas fa-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login" id="loginBtn">
                    <span id="loginBtnText"><i class="fas fa-arrow-right-to-bracket"></i>&nbsp; Sign In to Dashboard</span>
                </button>
            </form>
            <div class="login-footer">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Return to Store</a>
            </div>
            <div class="security-badge">
                <i class="fas fa-circle-check"></i> SSL Encrypted &nbsp;&middot;&nbsp;
                <i class="fas fa-circle-check"></i> Secure Session
            </div>
        </div>
    </div>
<script>
// Typewriter
const phrases = ['Secure System Access', 'Florist Management Portal', 'Welcome back, Admin'];
let pi = 0, ci = 0, deleting = false;
const el = document.getElementById('typewriter');
function type() {
    const cur = phrases[pi % phrases.length];
    el.textContent = deleting ? cur.substring(0, ci--) : cur.substring(0, ci++);
    if (!deleting && ci > cur.length) { deleting = true; setTimeout(type, 1800); return; }
    if (deleting && ci < 0) { deleting = false; pi++; ci = 0; }
    setTimeout(type, deleting ? 40 : 80);
}
type();
// Password toggle
document.getElementById('togglePw').addEventListener('click', () => {
    const pw = document.getElementById('password');
    const icon = document.getElementById('pwIcon');
    if (pw.type === 'password') { pw.type = 'text'; icon.className = 'fas fa-eye-slash'; }
    else { pw.type = 'password'; icon.className = 'fas fa-eye'; }
});
// Loading state
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    document.getElementById('loginBtnText').innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>&nbsp; Authenticating...';
    btn.disabled = true;
});
// Particles
const canvas = document.getElementById('particles');
const ctx2 = canvas.getContext('2d');
canvas.width = window.innerWidth; canvas.height = window.innerHeight;
window.addEventListener('resize', () => { canvas.width = window.innerWidth; canvas.height = window.innerHeight; });
const particles = Array.from({length: 55}, () => ({
    x: Math.random() * canvas.width, y: Math.random() * canvas.height,
    r: Math.random() * 1.5 + 0.3,
    dx: (Math.random() - 0.5) * 0.3, dy: -Math.random() * 0.4 - 0.1,
    o: Math.random() * 0.5 + 0.1
}));
function drawParticles() {
    ctx2.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
        ctx2.beginPath(); ctx2.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx2.fillStyle = `rgba(167,139,250,${p.o})`; ctx2.fill();
        p.x += p.dx; p.y += p.dy;
        if (p.y < -5) { p.y = canvas.height + 5; p.x = Math.random() * canvas.width; }
        if (p.x < 0 || p.x > canvas.width) p.dx *= -1;
    });
    requestAnimationFrame(drawParticles);
}
drawParticles();
</script>
</body>
</html>
