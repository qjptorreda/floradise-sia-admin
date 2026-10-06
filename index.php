<?php include 'header.php'; ?>

<!-- Carousel -->
<div class="carousel-container">
    <div class="carousel">
        <div class="carousel-item active" style="background-image: url('https://images.unsplash.com/photo-1462275646964-a0e3386b89fa?q=80&w=2070&auto=format&fit=crop');">
            <div class="carousel-content">
                <h2>Spring Bloom Sale</h2>
                <p>Up to 30% off on all seasonal flowers!</p>
                <a href="#products" class="btn btn-primary">Shop Now</a>
            </div>
        </div>
        <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1490750967868-88cb44cb273f?q=80&w=2070&auto=format&fit=crop');">
            <div class="carousel-content">
                <h2>New Floral Arrangements</h2>
                <p>Check out our latest beautiful creations.</p>
                <a href="#products" class="btn btn-primary">Discover</a>
            </div>
        </div>
        <div class="carousel-item" style="background-image: url('https://images.unsplash.com/photo-1457530378978-8bac673b8062?q=80&w=2070&auto=format&fit=crop');">
            <div class="carousel-content">
                <h2>Exclusive Subscriptions</h2>
                <p>Get fresh flowers delivered weekly.</p>
                <a href="#products" class="btn btn-primary">Learn More</a>
            </div>
        </div>
    </div>
    <button class="carousel-btn prev-btn"><i class="fas fa-chevron-left"></i></button>
    <button class="carousel-btn next-btn"><i class="fas fa-chevron-right"></i></button>
    <div class="carousel-dots">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
</div>

<h1 class="page-title" id="products">Discover Our Products</h1>

<?php if(isset($_SESSION['message'])): ?>
    <div class="alert alert-success">
        <?php 
            echo $_SESSION['message']; 
            unset($_SESSION['message']);
        ?>
    </div>
<?php endif; ?>

<div class="products-grid">
    <?php foreach($products as $id => $product): ?>
        <div class="product-card">
            <div class="product-image-wrapper">
                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
            </div>
            <div class="product-info">
                <h3 class="product-title"><?php echo $product['name']; ?></h3>
                <div class="product-footer">
                    <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                    <form action="action.php" method="POST">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="product_id" value="<?php echo $id; ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cart-plus"></i> Add
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.querySelector('.carousel');
        const items = document.querySelectorAll('.carousel-item');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.querySelector('.prev-btn');
        const nextBtn = document.querySelector('.next-btn');
        let currentIndex = 0;
        let interval;

        function showItem(index) {
            items.forEach(item => item.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            if (index < 0) {
                currentIndex = items.length - 1;
            } else if (index >= items.length) {
                currentIndex = 0;
            } else {
                currentIndex = index;
            }
            
            items[currentIndex].classList.add('active');
            dots[currentIndex].classList.add('active');
        }

        function startAutoPlay() {
            interval = setInterval(() => {
                showItem(currentIndex + 1);
            }, 5000);
        }

        function resetAutoPlay() {
            clearInterval(interval);
            startAutoPlay();
        }

        prevBtn.addEventListener('click', () => {
            showItem(currentIndex - 1);
            resetAutoPlay();
        });

        nextBtn.addEventListener('click', () => {
            showItem(currentIndex + 1);
            resetAutoPlay();
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showItem(index);
                resetAutoPlay();
            });
        });

        startAutoPlay();
    });
</script>

<?php include 'footer.php'; ?>
