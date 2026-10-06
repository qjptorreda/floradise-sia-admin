<?php include 'header.php'; ?>

<?php
if(empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$total = 0;
foreach($_SESSION['cart'] as $id => $qty) {
    $total += $products[$id]['price'] * $qty;
}
?>

<div class="container" style="max-width: 600px;">
    <h1 class="page-title text-center">Checkout</h1>
    
    <div class="cart-container" style="padding: 2rem;">
        <h3 style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Payment Details</h3>
        <p style="margin-bottom: 1.5rem; color: var(--text-light);">Total Amount: <strong>₱<?php echo number_format($total, 2); ?></strong></p>
        
        <form action="action.php" method="POST">
            <input type="hidden" name="action" value="checkout">
            
            <div class="input-group">
                <label for="name">Full Name on Card</label>
                <input type="text" id="name" name="name" required placeholder="John Doe">
            </div>
            
            <div class="input-group">
                <label for="card">Card Number (Mock)</label>
                <input type="text" id="card" name="card" required placeholder="1234 5678 9101 1121" maxlength="19">
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <div class="input-group" style="flex: 1;">
                    <label for="exp">Expiry Date</label>
                    <input type="text" id="exp" name="exp" required placeholder="MM/YY" maxlength="5">
                </div>
                <div class="input-group" style="flex: 1;">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv" required placeholder="123" maxlength="3">
                </div>
            </div>
            
            <div class="input-group">
                <label for="address">Shipping Address</label>
                <input type="text" id="address" name="address" required placeholder="123 Main St, City, Country">
            </div>
            
            <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem; font-size: 1.1rem; padding: 0.8rem;">
                <i class="fas fa-lock"></i> Pay ₱<?php echo number_format($total, 2); ?>
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
