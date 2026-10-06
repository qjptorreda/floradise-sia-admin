<?php include 'header.php'; ?>

<div class="auth-form">
    <h2>Welcome Back</h2>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <form action="action.php" method="POST">
        <input type="hidden" name="action" value="login">
        
        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="you@example.com">
        </div>
        
        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>
    
    <div class="auth-links">
        Don't have an account? <a href="signup.php">Sign up</a>
    </div>
</div>

<?php include 'footer.php'; ?>
