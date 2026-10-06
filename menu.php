<?php

session_start();

require_once __DIR__ . '/../config/db.php';

/* =========================================
   CART
========================================= */

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_count = 0;

foreach ($_SESSION['cart'] as $qty) {
    $cart_count += (int)$qty;
}


/* =========================================
   CATEGORIES
========================================= */

$categories = [];

$category_result = $conn->query("
    SELECT id, name
    FROM categories
    WHERE status = 1
    ORDER BY id ASC
");

if ($category_result) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row;
    }
}


/* =========================================
   SELECTED CATEGORY
========================================= */

$selected_category = (int)($_GET['category'] ?? 0);


/* =========================================
   PRODUCTS
========================================= */

$products = [];

if ($selected_category > 0) {

    $stmt = $conn->prepare("
        SELECT
            id,
            category_id,
            name,
            description,
            price,
            image
        FROM products
        WHERE category_id = ?
        AND is_available = 1
        ORDER BY id DESC
    ");

    $stmt->bind_param("i", $selected_category);
    $stmt->execute();

    $result = $stmt->get_result();

} else {

    $result = $conn->query("
        SELECT
            id,
            category_id,
            name,
            description,
            price,
            image
        FROM products
        WHERE is_available = 1
        ORDER BY id DESC
    ");
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Menu | Ember & Plate</title>


<style>

/* =========================================
   RESET
========================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family: Arial, Helvetica, sans-serif;

    background:
        radial-gradient(
            circle at top left,
            rgba(217,155,77,0.08),
            transparent 35%
        ),
        #0b0b0b;

    color: #fff;

    min-height: 100vh;
}


/* =========================================
   NAVBAR
========================================= */

.navbar {

    height: 76px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 6%;

    position: sticky;

    top: 0;

    z-index: 1000;

    background: rgba(10,10,10,0.92);

    backdrop-filter: blur(15px);

    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.logo {

    font-size: 24px;

    font-weight: 800;

    letter-spacing: 1px;
}

.logo span {
    color: #d99b4d;
}

.nav-links {

    display: flex;

    align-items: center;

    gap: 22px;
}

.nav-links a {

    color: #ddd;

    text-decoration: none;

    font-size: 14px;

    transition: 0.3s;
}

.nav-links a:hover {
    color: #d99b4d;
}


/* =========================================
   CART
========================================= */

.cart-link {

    position: relative;

    padding: 10px 15px;

    border: 1px solid rgba(255,255,255,0.15);

    border-radius: 22px;

    transition: 0.3s;
}

.cart-link:hover {
    border-color: #d99b4d;
}

.cart-count {

    position: absolute;

    top: -9px;

    right: -7px;

    min-width: 21px;

    height: 21px;

    padding: 0 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #d99b4d;

    color: #111;

    border-radius: 50%;

    font-size: 11px;

    font-weight: 800;
}

.cart-count.bump {
    animation: cartBump 0.45s ease;
}

@keyframes cartBump {

    0% {
        transform: scale(1);
    }

    50% {
        transform: scale(1.55);
    }

    100% {
        transform: scale(1);
    }
}


/* =========================================
   HEADER
========================================= */

.page-header {

    text-align: center;

    padding: 65px 20px 35px;
}

.page-header small {

    color: #d99b4d;

    text-transform: uppercase;

    letter-spacing: 4px;

    font-size: 11px;
}

.page-header h1 {

    font-size: clamp(40px, 6vw, 70px);

    margin-top: 12px;

    font-weight: 800;
}

.page-header p {

    max-width: 600px;

    margin: 15px auto 0;

    color: #999;

    line-height: 1.7;
}


/* =========================================
   CATEGORY
========================================= */

.categories {

    display: flex;

    justify-content: center;

    flex-wrap: wrap;

    gap: 10px;

    padding: 10px 20px 40px;
}

.category-btn {

    text-decoration: none;

    color: #bbb;

    border: 1px solid rgba(255,255,255,0.12);

    padding: 10px 18px;

    border-radius: 30px;

    font-size: 13px;

    transition: 0.3s;

    background: rgba(255,255,255,0.03);
}

.category-btn:hover,
.category-btn.active {

    background: #d99b4d;

    color: #111;

    border-color: #d99b4d;
}


/* =========================================
   MENU CONTAINER
========================================= */

.menu-container {

    width: 88%;

    max-width: 1400px;

    margin: auto;

    padding-bottom: 100px;
}

.product-grid {

    display: grid;

    grid-template-columns:
        repeat(auto-fill, minmax(270px, 1fr));

    gap: 25px;
}


/* =========================================
   PRODUCT CARD
========================================= */

.product-card {

    position: relative;

    overflow: hidden;

    background:
        linear-gradient(
            145deg,
            rgba(255,255,255,0.07),
            rgba(255,255,255,0.025)
        );

    border: 1px solid rgba(255,255,255,0.08);

    border-radius: 22px;

    padding: 12px;

    transition:
        transform 0.35s ease,
        border-color 0.35s ease,
        box-shadow 0.35s ease;
}

.product-card:hover {

    transform: translateY(-7px);

    border-color: rgba(217,155,77,0.5);

    box-shadow:
        0 25px 70px rgba(0,0,0,0.45);
}


/* =========================================
   PRODUCT IMAGE
========================================= */

.product-image {

    width: 100%;

    height: 230px;

    border-radius: 17px;

    overflow: hidden;

    background: #151515;

    position: relative;
}

.product-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

    transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.07);
}


/* =========================================
   PRODUCT INFO
========================================= */

.product-info {
    padding: 18px 8px 8px;
}

.product-info h3 {

    font-size: 20px;

    margin-bottom: 8px;
}

.product-description {

    color: #999;

    font-size: 13px;

    line-height: 1.6;

    min-height: 42px;

    margin-bottom: 15px;
}

.product-bottom {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 10px;
}

.price {

    font-size: 20px;

    font-weight: 800;

    color: #d99b4d;
}


/* =========================================
   ADD BUTTON
========================================= */

.add-btn {

    border: none;

    cursor: pointer;

    padding: 11px 16px;

    border-radius: 12px;

    background: #fff;

    color: #111;

    font-weight: 700;

    transition: 0.25s;

    position: relative;

    overflow: hidden;
}

.add-btn:hover {

    background: #d99b4d;

    transform: translateY(-2px);
}

.add-btn:active {
    transform: scale(0.95);
}

.add-btn.loading {

    pointer-events: none;

    opacity: 0.6;
}


/* =========================================
   3D PLATE
========================================= */

.plate-stage {

    position: fixed;

    right: 28px;

    top: 50%;

    transform: translateY(-50%);

    width: 190px;

    height: 190px;

    z-index: 900;

    pointer-events: none;

    display: flex;

    align-items: center;

    justify-content: center;
}

.plate {

    width: 165px;

    height: 165px;

    border-radius: 50%;

    position: relative;

    transform:
        perspective(700px)
        rotateX(57deg)
        rotateZ(-8deg);

    background:

        radial-gradient(
            circle at 50% 45%,
            #ffffff 0%,
            #eeeeee 47%,
            #cfcfcf 67%,
            #8d8d8d 72%,
            #eeeeee 78%,
            #888 82%,
            #dedede 87%,
            #777 100%
        );

    box-shadow:

        0 35px 35px rgba(0,0,0,0.55),

        inset 0 -10px 20px rgba(0,0,0,0.3),

        inset 0 8px 12px rgba(255,255,255,0.8);

    transition: transform 0.5s ease;
}

.plate::before {

    content: "";

    position: absolute;

    inset: 20px;

    border-radius: 50%;

    background:

        radial-gradient(
            circle,
            #f7f7f7 0%,
            #e4e4e4 55%,
            #c8c8c8 100%
        );

    box-shadow:
        inset 0 8px 12px rgba(0,0,0,0.15);
}

.plate::after {

    content: "";

    position: absolute;

    width: 95px;

    height: 95px;

    border-radius: 50%;

    left: 35px;

    top: 32px;

    border: 2px solid rgba(180,140,80,0.5);
}


/* =========================================
   DISH ON PLATE
========================================= */

.plate-dish {

    position: absolute;

    z-index: 10;

    width: 82px;

    height: 82px;

    left: 42px;

    top: 38px;

    object-fit: cover;

    border-radius: 50%;

    opacity: 0;

    transform:
        translateZ(15px)
        scale(0.3);

    transition:
        opacity 0.3s ease,
        transform 0.45s cubic-bezier(.2,1.5,.4,1);

    box-shadow:
        0 8px 14px rgba(0,0,0,0.4);
}

.plate-dish.show {

    opacity: 1;

    transform:
        translateZ(15px)
        scale(1);
}


/* =========================================
   PLATE BOUNCE
========================================= */

.plate-stage.bounce .plate {
    animation: plateBounce 0.7s ease;
}

@keyframes plateBounce {

    0% {
        transform:
            perspective(700px)
            rotateX(57deg)
            rotateZ(-8deg)
            scale(1);
    }

    35% {
        transform:
            perspective(700px)
            rotateX(57deg)
            rotateZ(-8deg)
            scale(1.12);
    }

    70% {
        transform:
            perspective(700px)
            rotateX(57deg)
            rotateZ(-8deg)
            scale(0.96);
    }

    100% {
        transform:
            perspective(700px)
            rotateX(57deg)
            rotateZ(-8deg)
            scale(1);
    }
}


/* =========================================
   FLYING DISH
========================================= */

.flying-dish {

    position: fixed;

    z-index: 99999;

    object-fit: cover;

    border-radius: 50%;

    pointer-events: none;

    box-shadow:
        0 15px 35px rgba(0,0,0,0.45);

    transition:

        left 0.75s cubic-bezier(.2,.8,.2,1),

        top 0.75s cubic-bezier(.2,.8,.2,1),

        width 0.75s ease,

        height 0.75s ease,

        opacity 0.75s ease,

        transform 0.75s cubic-bezier(.2,.8,.2,1);
}


/* =========================================
   TOAST
========================================= */

.cart-toast {

    position: fixed;

    right: 30px;

    bottom: 30px;

    z-index: 5000;

    background: rgba(20,20,20,0.96);

    border: 1px solid rgba(217,155,77,0.45);

    color: #fff;

    padding: 14px 20px;

    border-radius: 14px;

    box-shadow:
        0 15px 40px rgba(0,0,0,0.35);

    transform: translateY(30px);

    opacity: 0;

    pointer-events: none;

    transition: 0.35s ease;
}

.cart-toast.show {

    transform: translateY(0);

    opacity: 1;
}

.cart-toast span {

    color: #d99b4d;

    font-weight: bold;
}


/* =========================================
   EMPTY
========================================= */

.empty {

    text-align: center;

    padding: 80px 20px;

    color: #777;
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 800px) {

    .navbar {
        padding: 0 20px;
    }

    .nav-links {
        gap: 10px;
    }

    .nav-links a:not(.cart-link) {
        display: none;
    }

    .menu-container {
        width: 92%;
    }

    .plate-stage {

        width: 125px;

        height: 125px;

        right: 8px;

        top: auto;

        bottom: 70px;

        transform: none;
    }

    .plate {

        width: 110px;

        height: 110px;
    }

    .plate::before {
        inset: 14px;
    }

    .plate::after {

        width: 62px;

        height: 62px;

        left: 24px;

        top: 23px;
    }

    .plate-dish {

        width: 57px;

        height: 57px;

        left: 27px;

        top: 27px;
    }

    .cart-toast {

        right: 15px;

        bottom: 15px;
    }
}

</style>

</head>


<body>


<!-- =========================================
     NAVBAR
========================================= -->

<nav class="navbar">

    <div class="logo">
        Ember <span>&</span> Plate
    </div>


    <div class="nav-links">

        <a href="index.php">
            Home
        </a>

        <a href="about.php">
            About
        </a>

        <a href="contact.php">
            Contact
        </a>

        <a href="cart.php" class="cart-link">

            🛒 Cart

            <span
                class="cart-count"
                id="cartCount"
            >
                <?php echo $cart_count; ?>
            </span>

        </a>

    </div>

</nav>


<!-- =========================================
     HEADER
========================================= -->

<section class="page-header">

    <small>
        Ember & Plate
    </small>

    <h1>
        Our Menu
    </h1>

    <p>
        Crafted with passion, served with soul.
        Explore our signature dishes.
    </p>

</section>


<!-- =========================================
     CATEGORIES
========================================= -->

<div class="categories">

    <a
        href="menu.php"
        class="category-btn <?php echo ($selected_category === 0) ? 'active' : ''; ?>"
    >
        All
    </a>


    <?php foreach ($categories as $category): ?>

        <a
            href="menu.php?category=<?php echo (int)$category['id']; ?>"
            class="category-btn <?php echo ($selected_category === (int)$category['id']) ? 'active' : ''; ?>"
        >

            <?php echo htmlspecialchars($category['name']); ?>

        </a>

    <?php endforeach; ?>

</div>


<!-- =========================================
     PRODUCTS
========================================= -->

<div class="menu-container">

    <?php if (empty($products)): ?>

        <div class="empty">

            <h2>
                No dishes available
            </h2>

            <p>
                Please check another category.
            </p>

        </div>

    <?php else: ?>

        <div class="product-grid">


            <?php foreach ($products as $product): ?>

                <?php

                /*
                IMPORTANT IMAGE PATH

                Database:
                chicken-biryani.jpg

                Actual file:
                assets/images/chicken-biryani.jpg

                Browser from user/menu.php:
                ../assets/images/chicken-biryani.jpg
                */

                $image = trim($product['image'] ?? '');

                if ($image === '') {

                    $image = '../assets/images/food-placeholder.jpg';

                } elseif (
                    strpos($image, 'http://') === 0 ||
                    strpos($image, 'https://') === 0
                ) {

                    // Keep external image URL as it is

                } else {

                    $image = '../assets/images/' . ltrim($image, '/');
                }

                ?>


                <div
                    class="product-card"
                    data-product-id="<?php echo (int)$product['id']; ?>"
                >


                    <!-- IMAGE -->

                    <div class="product-image">

                        <img
                            src="<?php echo htmlspecialchars($image); ?>"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            onerror="this.style.display='none';"
                        >

                    </div>


                    <!-- INFO -->

                    <div class="product-info">


                        <h3>

                            <?php
                            echo htmlspecialchars($product['name']);
                            ?>

                        </h3>


                        <div class="product-description">

                            <?php

                            $description =
                                trim($product['description'] ?? '');

                            if ($description === '') {
                                $description =
                                    'Delicious signature dish.';
                            }

                            echo htmlspecialchars($description);

                            ?>

                        </div>


                        <div class="product-bottom">


                            <div class="price">

                                ₹<?php
                                echo number_format(
                                    (float)$product['price'],
                                    0
                                );
                                ?>

                            </div>


                            <button
                                type="button"
                                class="add-btn"
                                data-product-id="<?php echo (int)$product['id']; ?>"
                                data-image="<?php echo htmlspecialchars($image); ?>"
                            >

                                🛒 Add to Cart

                            </button>


                        </div>

                    </div>

                </div>


            <?php endforeach; ?>


        </div>

    <?php endif; ?>

</div>


<!-- =========================================
     3D PLATE
========================================= -->

<div
    class="plate-stage"
    id="plateStage"
>

    <div class="plate">

        <img
            src=""
            id="plateDish"
            class="plate-dish"
            alt=""
        >

    </div>

</div>


<!-- =========================================
     TOAST
========================================= -->

<div
    class="cart-toast"
    id="cartToast"
>

    ✓ <span>Added to Cart</span>

</div>


<!-- =========================================
     JAVASCRIPT
========================================= -->

<script>

const buttons =
    document.querySelectorAll('.add-btn');

const cartCount =
    document.getElementById('cartCount');

const plateStage =
    document.getElementById('plateStage');

const plateDish =
    document.getElementById('plateDish');

const cartToast =
    document.getElementById('cartToast');


/* =========================================
   ADD TO CART
========================================= */

buttons.forEach(button => {

    button.addEventListener('click', async function () {

        const productId =
            this.dataset.productId;

        const imageUrl =
            this.dataset.image;

        const originalText =
            this.innerHTML;


        /* Prevent double click */

        if (this.classList.contains('loading')) {
            return;
        }

        this.classList.add('loading');

        this.innerHTML = 'Adding...';


        /* =====================================
           PRODUCT IMAGE
        ===================================== */

        const productImage =
            this
            .closest('.product-card')
            .querySelector('.product-image img');


        const rect =
            productImage.getBoundingClientRect();


        /* =====================================
           CREATE FLYING IMAGE
        ===================================== */

        const flyingImage =
            document.createElement('img');

        flyingImage.src =
            imageUrl;

        flyingImage.className =
            'flying-dish';


        flyingImage.style.left =
            rect.left + 'px';

        flyingImage.style.top =
            rect.top + 'px';

        flyingImage.style.width =
            rect.width + 'px';

        flyingImage.style.height =
            rect.height + 'px';

        flyingImage.style.transform =
            'scale(1) rotate(0deg)';


        document.body.appendChild(
            flyingImage
        );


        /* =====================================
           AJAX
        ===================================== */

        try {

            const formData =
                new FormData();

            formData.append(
                'product_id',
                productId
            );

            formData.append(
                'quantity',
                '1'
            );


            const response =
                await fetch(
                    'add-to-cart.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                );


            const data =
                await response.json();


            if (!data.success) {

                flyingImage.remove();

                alert(
                    data.message ||
                    'Unable to add item.'
                );

                this.classList.remove(
                    'loading'
                );

                this.innerHTML =
                    originalText;

                return;
            }


            /* =================================
               PLATE TARGET
            ================================= */

            const plateRect =
                plateStage.getBoundingClientRect();


            const targetX =
                plateRect.left +
                (plateRect.width / 2) -
                41;


            const targetY =
                plateRect.top +
                (plateRect.height / 2) -
                41;


            /* =================================
               FLY
            ================================= */

            requestAnimationFrame(() => {

                flyingImage.style.left =
                    targetX + 'px';

                flyingImage.style.top =
                    targetY + 'px';

                flyingImage.style.width =
                    '82px';

                flyingImage.style.height =
                    '82px';

                flyingImage.style.transform =
                    'scale(0.65) rotate(360deg)';

            });


            /* =================================
               AFTER ANIMATION
            ================================= */

            setTimeout(() => {

                flyingImage.remove();


                /* Dish on plate */

                plateDish.src =
                    imageUrl;

                plateDish.classList.remove(
                    'show'
                );

                void plateDish.offsetWidth;

                plateDish.classList.add(
                    'show'
                );


                /* Plate bounce */

                plateStage.classList.remove(
                    'bounce'
                );

                void plateStage.offsetWidth;

                plateStage.classList.add(
                    'bounce'
                );


                /* Cart count */

                cartCount.textContent =
                    data.cart_count;


                cartCount.classList.remove(
                    'bump'
                );

                void cartCount.offsetWidth;

                cartCount.classList.add(
                    'bump'
                );


                /* Toast */

                cartToast.classList.add(
                    'show'
                );


                setTimeout(() => {

                    cartToast.classList.remove(
                        'show'
                    );

                }, 1800);


            }, 780);


        } catch (error) {

            console.error(error);

            flyingImage.remove();

            alert(
                'Something went wrong. Please try again.'
            );

        }


        /* =================================
           RESET BUTTON
        ================================= */

        setTimeout(() => {

            this.classList.remove(
                'loading'
            );

            this.innerHTML =
                originalText;

        }, 900);

    });

});

</script>


</body>

</html>