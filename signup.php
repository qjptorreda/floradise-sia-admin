<?php include 'header.php'; ?>

<div class="auth-form">
    <h2>Create an Account</h2>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <form action="action.php" method="POST">
        <input type="hidden" name="action" value="signup">
        
        <div class="input-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required placeholder="John Doe">
        </div>

        <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="you@example.com">
        </div>
        
        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Create a password">
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
    </form>
    
    <div class="auth-links">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

<?php include 'footer.php'; ?>
