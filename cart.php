<?php include 'header.php'; ?>

<h1 class="page-title">Shopping Cart</h1>

<div class="cart-container">
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart">
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added anything to your cart yet.</p>
            <br>
            <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                foreach ($_SESSION['cart'] as $id => $qty):
                    $product = $products[$id];
                    $subtotal = $product['price'] * $qty;
                    $total += $subtotal;
                    ?>
                    <tr>
                        <td>
                            <div class="cart-item-info">
                                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>"
                                    class="cart-item-img">
                                <strong><?php echo $product['name']; ?></strong>
                            </div>
                        </td>
                        <td>₱<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $qty; ?></td>
                        <td><strong>₱<?php echo number_format($subtotal, 2); ?></strong></td>
                        <td>
                            <form action="action.php" method="POST">
                                <input type="hidden" name="action" value="remove_from_cart">
                                <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 0.4rem 0.8rem;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-total-section">
            <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
            <div style="text-align: right;">
                <div class="cart-total">Total: ₱<?php echo number_format($total, 2); ?></div>
                <a href="checkout.php" class="btn btn-primary"
                    style="margin-top: 1rem; font-size: 1.1rem; padding: 0.8rem 2rem; display: inline-block;">
                    Proceed to Checkout <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>