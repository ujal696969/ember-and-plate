<?php

session_start();

require_once __DIR__ . '/../config/db.php';

if (!isset($conn)) {
    die("Database connection failed.");
}


/* =====================================================
   PRODUCT ID
===================================================== */

$productId = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($productId <= 0) {
    header("Location: menu.php");
    exit;
}


/* =====================================================
   FETCH PRODUCT
===================================================== */

$sql = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.image,
        p.category_id,
        p.is_available,
        p.is_featured,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database query failed.");
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $productId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =====================================================
   PRODUCT NOT FOUND
===================================================== */

if (!$product) {
    header("Location: menu.php");
    exit;
}


/* =====================================================
   IMAGE
===================================================== */

$imageName = trim(
    (string)($product['image'] ?? '')
);

/*
 * If database accidentally contains:
 * ../assets/images/biryani.png
 *
 * basename() will convert it to:
 * biryani.png
 */

$imageName = basename($imageName);

$imageUrl = '';

if ($imageName !== '') {

    $imageUrl =
        '../assets/images/' .
        rawurlencode($imageName);
}


/* =====================================================
   ADD TO CART
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_to_cart'])
) {

    /* Product ID coming from form */

    $postedProductId =
        (int)($_POST['product_id'] ?? 0);


    /* Selected quantity */

    $quantity =
        (int)($_POST['quantity'] ?? 1);


    /* Safety */

    if ($quantity < 1) {
        $quantity = 1;
    }

    if ($quantity > 20) {
        $quantity = 20;
    }


    /* Security check */

    if ($postedProductId !== $productId) {

        header(
            "Location: product.php?id=" .
            $productId
        );

        exit;
    }


    /* Product availability check */

    if ((int)$product['is_available'] !== 1) {

        header(
            "Location: product.php?id=" .
            $productId
        );

        exit;
    }


    /* =================================================
       CREATE CART SESSION
    ================================================= */

    if (
        !isset($_SESSION['cart']) ||
        !is_array($_SESSION['cart'])
    ) {

        $_SESSION['cart'] = [];

    }


    $found = false;


    /* =================================================
       CHECK EXISTING PRODUCT
    ================================================= */

    foreach ($_SESSION['cart'] as &$item) {

        if (
            is_array($item) &&
            isset($item['id']) &&
            (int)$item['id'] === $productId
        ) {

            /*
             * IMPORTANT
             *
             * Replace old quantity.
             *
             * Example:
             *
             * Old = 1
             * New = 3
             *
             * Result = 3
             *
             * NOT 4
             */

            $item['id'] =
                $productId;

            $item['name'] =
                $product['name'];

            $item['price'] =
                (float)$product['price'];

            $item['image'] =
                $imageName;

            $item['quantity'] =
                $quantity;

            $found = true;

            break;
        }
    }

    unset($item);


    /* =================================================
       ADD NEW PRODUCT
    ================================================= */

    if (!$found) {

        $_SESSION['cart'][] = [

            'id' =>
                $productId,

            'name' =>
                $product['name'],

            'price' =>
                (float)$product['price'],

            'image' =>
                $imageName,

            'quantity' =>
                $quantity

        ];
    }


    /*
     * Make sure PHP writes the session
     * before redirecting.
     */

    session_write_close();


    /* =================================================
       GO TO CART
    ================================================= */

    header("Location: cart.php");

    exit;
}


/* =====================================================
   CART COUNT
===================================================== */

$cartCount = 0;

if (
    isset($_SESSION['cart']) &&
    is_array($_SESSION['cart'])
) {

    foreach ($_SESSION['cart'] as $item) {

        if (!is_array($item)) {
            continue;
        }

        $cartCount +=
            (int)($item['quantity'] ?? 1);
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    <?= htmlspecialchars($product['name']) ?>
    | Ember & Plate
</title>


<!-- =====================================================
     GOOGLE FONTS
===================================================== -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =====================================================
   ROOT
===================================================== */

:root {

    --black: #070707;

    --dark: #0d0d0d;

    --card: #121212;

    --gold: #d6a84f;

    --gold-light: #f1d28a;

    --gold-dark: #956b23;

    --white: #ffffff;

    --muted: #999999;

    --border:
        rgba(214,168,79,.20);
}


/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


html {
    scroll-behavior: smooth;
}


body {

    min-height: 100vh;

    background:

        radial-gradient(
            circle at 10% 5%,
            rgba(214,168,79,.10),
            transparent 28%
        ),

        radial-gradient(
            circle at 90% 70%,
            rgba(214,168,79,.07),
            transparent 30%
        ),

        #070707;

    color: #ffffff;

    font-family:
        'DM Sans',
        sans-serif;

    overflow-x: hidden;
}


a {
    text-decoration: none;
    color: inherit;
}


/* =====================================================
   PAGE LOADER
===================================================== */

.page-loader {

    position: fixed;

    inset: 0;

    z-index: 99999;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #070707;

    animation:
        loaderHide
        .8s
        ease
        1s
        forwards;
}


.loader-text {

    color:
        var(--gold-light);

    font-family:
        'Playfair Display',
        serif;

    font-size: 28px;

    letter-spacing: 3px;

    animation:
        loaderPulse
        .8s
        ease
        infinite
        alternate;
}


@keyframes loaderPulse {

    from {

        opacity: .4;

        transform:
            scale(.96);
    }

    to {

        opacity: 1;

        transform:
            scale(1.04);
    }
}


@keyframes loaderHide {

    to {

        opacity: 0;

        visibility: hidden;
    }
}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {

    position: sticky;

    top: 0;

    z-index: 1000;

    background:
        rgba(7,7,7,.88);

    backdrop-filter:
        blur(18px);

    border-bottom:
        1px solid
        rgba(255,255,255,.06);
}


.nav-inner {

    max-width: 1250px;

    margin: auto;

    padding:
        15px 25px;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


.logo {

    display: flex;

    align-items: center;

    gap: 11px;
}


.logo-mark {

    width: 42px;

    height: 42px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            var(--gold-light),
            var(--gold-dark)
        );

    color: #080808;

    font-family:
        'Playfair Display',
        serif;

    font-weight: 700;

    box-shadow:
        0 8px 25px
        rgba(214,168,79,.15);
}


.logo-name {

    font-family:
        'Playfair Display',
        serif;

    font-size: 19px;

    font-weight: 700;
}


.logo-name span {

    color:
        var(--gold-light);
}


.nav-actions {

    display: flex;

    align-items: center;

    gap: 12px;
}


.menu-link {

    padding:
        10px 17px;

    border:
        1px solid
        rgba(255,255,255,.10);

    border-radius: 30px;

    color: #ccc;

    font-size: 13px;

    transition: .3s;
}


.menu-link:hover {

    color:
        var(--gold-light);

    border-color:
        var(--gold);

    transform:
        translateY(-2px);
}


.cart-link {

    position: relative;

    width: 44px;

    height: 44px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    border:
        1px solid
        var(--border);

    background:
        rgba(255,255,255,.03);

    font-size: 18px;

    transition: .3s;
}


.cart-link:hover {

    transform:
        translateY(-3px);

    border-color:
        var(--gold);
}


.cart-count {

    position: absolute;

    top: -4px;

    right: -4px;

    min-width: 19px;

    height: 19px;

    padding:
        0 5px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 20px;

    background:
        var(--gold);

    color: #080808;

    font-size: 10px;

    font-weight: 700;
}


/* =====================================================
   MAIN
===================================================== */

.main {

    max-width: 1250px;

    margin: auto;

    padding:
        55px 25px
        100px;
}


/* =====================================================
   BREADCRUMB
===================================================== */

.breadcrumb {

    display: flex;

    align-items: center;

    gap: 8px;

    color: #777;

    font-size: 12px;

    margin-bottom: 28px;

    animation:
        fadeDown
        .7s
        ease
        both;
}


.breadcrumb a {

    color:
        var(--gold-light);
}


@keyframes fadeDown {

    from {

        opacity: 0;

        transform:
            translateY(-15px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


/* =====================================================
   PRODUCT GRID
===================================================== */

.product-grid {

    display: grid;

    grid-template-columns:
        1.05fr
        .95fr;

    gap: 55px;

    align-items: center;
}


/* =====================================================
   IMAGE BOX
===================================================== */

.image-box {

    position: relative;

    min-height: 570px;

    overflow: hidden;

    border-radius: 32px;

    border:
        1px solid
        rgba(214,168,79,.20);

    background:
        #111;

    box-shadow:
        0 40px 100px
        rgba(0,0,0,.55);

    animation:
        imageEnter
        .9s
        cubic-bezier(.2,.8,.2,1)
        both;

    transition:
        transform .2s ease;
}


@keyframes imageEnter {

    from {

        opacity: 0;

        transform:
            translateX(-45px)
            scale(.96);
    }

    to {

        opacity: 1;

        transform:
            translateX(0)
            scale(1);
    }
}


.image-box::after {

    content: "";

    position: absolute;

    inset: 0;

    pointer-events: none;

    background:
        linear-gradient(
            to bottom,
            transparent 55%,
            rgba(0,0,0,.65)
        );
}


.product-image {

    width: 100%;

    height: 570px;

    object-fit: cover;

    display: block;

    transition:
        transform .8s
        cubic-bezier(.2,.8,.2,1);
}


.image-box:hover
.product-image {

    transform:
        scale(1.06);
}


/* =====================================================
   IMAGE FALLBACK
===================================================== */

.image-fallback {

    height: 570px;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-direction: column;

    gap: 12px;

    color: #777;
}


.image-fallback-icon {

    font-size: 60px;
}


.image-fallback strong {

    color: #bbb;

    font-size: 14px;
}


.image-fallback small {

    color: #666;

    max-width: 90%;

    text-align: center;

    word-break: break-all;
}


/* =====================================================
   FEATURED
===================================================== */

.featured {

    position: absolute;

    top: 22px;

    left: 22px;

    z-index: 10;

    padding:
        9px 14px;

    border-radius: 30px;

    background:
        rgba(7,7,7,.78);

    backdrop-filter:
        blur(12px);

    border:
        1px solid
        rgba(214,168,79,.45);

    color:
        var(--gold-light);

    font-size: 10px;

    font-weight: 700;

    letter-spacing: .8px;
}


/* =====================================================
   CATEGORY
===================================================== */

.image-category {

    position: absolute;

    left: 22px;

    bottom: 22px;

    z-index: 10;

    padding:
        8px 13px;

    border-radius: 30px;

    background:
        rgba(7,7,7,.75);

    backdrop-filter:
        blur(10px);

    color: white;

    font-size: 11px;
}


/* =====================================================
   DETAILS
===================================================== */

.details {

    animation:
        detailsEnter
        .9s
        .15s
        cubic-bezier(.2,.8,.2,1)
        both;
}


@keyframes detailsEnter {

    from {

        opacity: 0;

        transform:
            translateX(45px);
    }

    to {

        opacity: 1;

        transform:
            translateX(0);
    }
}


.category-name {

    color:
        var(--gold-light);

    font-size: 11px;

    text-transform:
        uppercase;

    letter-spacing: 2px;

    font-weight: 700;

    margin-bottom: 14px;
}


.product-name {

    font-family:
        'Playfair Display',
        serif;

    font-size:
        clamp(42px,5vw,68px);

    line-height: 1.04;

    margin-bottom: 18px;
}


.price {

    color:
        var(--gold-light);

    font-size: 31px;

    font-weight: 700;

    margin-bottom: 22px;
}


.description {

    color:
        #9c9c9c;

    max-width: 570px;

    font-size: 15px;

    line-height: 1.85;

    margin-bottom: 23px;
}


/* =====================================================
   AVAILABILITY
===================================================== */

.availability {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 24px;

    color:
        #6fe087;

    font-size: 13px;

    font-weight: 700;
}


.status-dot {

    width: 8px;

    height: 8px;

    border-radius: 50%;

    background:
        #61d77a;

    box-shadow:
        0 0 12px
        rgba(97,215,122,.75);

    animation:
        statusPulse
        1.5s
        infinite;
}


@keyframes statusPulse {

    50% {

        opacity: .45;

        transform:
            scale(.65);
    }
}


/* =====================================================
   ORDER BOX
===================================================== */

.order-box {

    padding:
        24px;

    border-radius: 22px;

    border:
        1px solid
        rgba(255,255,255,.08);

    background:
        rgba(255,255,255,.025);
}


/* =====================================================
   QUANTITY
===================================================== */

.quantity-label {

    display: block;

    margin-bottom: 10px;

    color: #ddd;

    font-size: 12px;

    font-weight: 700;
}


.quantity-control {

    width: 150px;

    height: 50px;

    display: flex;

    align-items: center;

    overflow: hidden;

    margin-bottom: 18px;

    border-radius: 14px;

    border:
        1px solid
        rgba(255,255,255,.12);

    background:
        #090909;
}


.quantity-control button {

    width: 48px;

    height: 100%;

    border: 0;

    background: transparent;

    color:
        var(--gold-light);

    font-size: 22px;

    cursor: pointer;

    transition: .25s;
}


.quantity-control button:hover {

    background:
        rgba(214,168,79,.10);
}


.quantity-input {

    width: 54px;

    height: 100%;

    border: 0;

    outline: 0;

    background: transparent;

    color: white;

    text-align: center;

    font-family: inherit;

    font-size: 15px;
}


/* =====================================================
   ADD CART
===================================================== */

.add-cart {

    width: 100%;

    min-height: 57px;

    border: 0;

    border-radius: 16px;

    cursor: pointer;

    color: #080808;

    font-family: inherit;

    font-size: 14px;

    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            var(--gold-light),
            var(--gold-dark)
        );

    box-shadow:
        0 15px 35px
        rgba(214,168,79,.17);

    transition:
        transform .3s,
        box-shadow .3s;
}


.add-cart:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 20px 45px
        rgba(214,168,79,.27);
}


.add-cart:active {

    transform:
        scale(.98);
}


/* =====================================================
   TRUST
===================================================== */

.trust-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 9px;

    margin-top: 17px;
}


.trust {

    padding:
        13px 6px;

    text-align: center;

    border-radius: 13px;

    border:
        1px solid
        rgba(255,255,255,.06);

    color: #777;

    font-size: 9px;
}


.trust strong {

    display: block;

    color: #ddd;

    font-size: 10px;

    margin-bottom: 4px;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:900px) {

    .product-grid {

        grid-template-columns: 1fr;

        gap: 35px;
    }

    .image-box {

        min-height: 450px;
    }

    .product-image {

        height: 450px;
    }

    .image-fallback {

        height: 450px;
    }
}


@media(max-width:600px) {

    .nav-inner {

        padding:
            13px 16px;
    }

    .logo-name {

        font-size: 16px;
    }

    .menu-link {

        padding:
            9px 12px;
    }

    .main {

        padding:
            35px 16px
            70px;
    }

    .image-box {

        min-height: 360px;

        border-radius: 23px;
    }

    .product-image {

        height: 360px;
    }

    .image-fallback {

        height: 360px;
    }

    .product-name {

        font-size: 42px;
    }

    .price {

        font-size: 27px;
    }

    .order-box {

        padding: 18px;
    }
}

</style>

</head>


<body>


<!-- =====================================================
     LOADER
===================================================== -->

<div class="page-loader">

    <div class="loader-text">
        EMBER & PLATE
    </div>

</div>



<!-- =====================================================
     NAVBAR
===================================================== -->

<header class="navbar">

    <div class="nav-inner">


        <a
            href="index.php"
            class="logo"
        >

            <div class="logo-mark">
                EP
            </div>

            <div class="logo-name">
                Ember <span>&</span> Plate
            </div>

        </a>


        <div class="nav-actions">


            <a
                href="menu.php"
                class="menu-link"
            >
                ← Menu
            </a>


            <a
                href="cart.php"
                class="cart-link"
                aria-label="Shopping Cart"
            >

                🛒

                <?php if ($cartCount > 0): ?>

                    <span class="cart-count">
                        <?= $cartCount ?>
                    </span>

                <?php endif; ?>

            </a>


        </div>

    </div>

</header>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- =================================================
         BREADCRUMB
    ================================================= -->

    <div class="breadcrumb">

        <a href="menu.php">
            Menu
        </a>

        <span>
            ›
        </span>

        <span>
            <?= htmlspecialchars(
                $product['name']
            ) ?>
        </span>

    </div>



    <!-- =================================================
         PRODUCT
    ================================================= -->

    <section class="product-grid">


        <!-- =================================================
             IMAGE
        ================================================= -->

        <div class="image-box">


            <?php if (
                (int)$product['is_featured'] === 1
            ): ?>

                <div class="featured">
                    ★ FEATURED
                </div>

            <?php endif; ?>


            <?php if ($imageUrl !== ''): ?>

                <img
                    src="<?= htmlspecialchars($imageUrl) ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>"
                    class="product-image"
                    onerror="imageFailed(this)"
                >


                <div
                    class="image-fallback"
                    style="display:none;"
                >

                    <div class="image-fallback-icon">
                        🍽️
                    </div>

                    <strong>
                        Image not available
                    </strong>

                    <small>
                        <?= htmlspecialchars(
                            $imageName
                        ) ?>
                    </small>

                </div>

            <?php else: ?>

                <div class="image-fallback">

                    <div class="image-fallback-icon">
                        🍽️
                    </div>

                    <strong>
                        Image coming soon
                    </strong>

                </div>

            <?php endif; ?>


            <?php if (
                !empty($product['category_name'])
            ): ?>

                <div class="image-category">

                    <?= htmlspecialchars(
                        $product['category_name']
                    ) ?>

                </div>

            <?php endif; ?>


        </div>



        <!-- =================================================
             DETAILS
        ================================================= -->

        <div class="details">


            <?php if (
                !empty($product['category_name'])
            ): ?>

                <div class="category-name">

                    <?= htmlspecialchars(
                        $product['category_name']
                    ) ?>

                </div>

            <?php endif; ?>


            <h1 class="product-name">

                <?= htmlspecialchars(
                    $product['name']
                ) ?>

            </h1>


            <div class="price">

                ₹<?= number_format(
                    (float)$product['price'],
                    2
                ) ?>

            </div>


            <p class="description">

                <?= htmlspecialchars(
                    $product['description']
                    ?: 'Freshly prepared with premium ingredients and served with care.'
                ) ?>

            </p>



            <!-- =================================================
                 AVAILABILITY
            ================================================= -->

            <?php if (
                (int)$product['is_available'] === 1
            ): ?>

                <div class="availability">

                    <span class="status-dot"></span>

                    Available Now

                </div>

            <?php else: ?>

                <div
                    class="availability"
                    style="color:#ff7777;"
                >

                    <span
                        class="status-dot"
                        style="
                            background:#ff5555;
                            box-shadow:
                            0 0 12px
                            rgba(255,85,85,.6);
                        "
                    ></span>

                    Currently Unavailable

                </div>

            <?php endif; ?>



            <!-- =================================================
                 ADD TO CART FORM
            ================================================= -->

            <?php if (
                (int)$product['is_available'] === 1
            ): ?>

                <form
                    method="POST"
                    action="product.php?id=<?= (int)$product['id'] ?>"
                    class="order-box"
                >


                    <!-- PRODUCT ID -->

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int)$product['id'] ?>"
                    >


                    <!-- QUANTITY -->

                    <label
                        class="quantity-label"
                        for="quantity"
                    >
                        Quantity
                    </label>


                    <div class="quantity-control">


                        <button
                            type="button"
                            onclick="changeQuantity(-1)"
                            aria-label="Decrease quantity"
                        >
                            −
                        </button>


                        <input
                            type="number"
                            name="quantity"
                            id="quantity"
                            class="quantity-input"
                            value="1"
                            min="1"
                            max="20"
                            readonly
                        >


                        <button
                            type="button"
                            onclick="changeQuantity(1)"
                            aria-label="Increase quantity"
                        >
                            +
                        </button>


                    </div>


                    <!-- ADD BUTTON -->

                    <button
                        type="submit"
                        name="add_to_cart"
                        value="1"
                        class="add-cart"
                    >

                        🛒
                        Add to Cart

                    </button>


                    <!-- TRUST -->

                    <div class="trust-grid">


                        <div class="trust">

                            <strong>
                                ✦ Fresh
                            </strong>

                            Quality Ingredients

                        </div>


                        <div class="trust">

                            <strong>
                                ♨ Freshly Made
                            </strong>

                            Prepared To Order

                        </div>


                        <div class="trust">

                            <strong>
                                ♥ Premium
                            </strong>

                            Restaurant Quality

                        </div>


                    </div>


                </form>

            <?php endif; ?>


        </div>


    </section>


</main>



<script>

/* =====================================================
   QUANTITY
===================================================== */

function changeQuantity(amount) {

    const input =
        document.getElementById('quantity');


    if (!input) {
        return;
    }


    let value =
        parseInt(input.value, 10);


    if (isNaN(value)) {
        value = 1;
    }


    value += amount;


    if (value < 1) {
        value = 1;
    }


    if (value > 20) {
        value = 20;
    }


    input.value = value;
}


/* =====================================================
   IMAGE ERROR
===================================================== */

function imageFailed(image) {

    image.style.display = 'none';


    const fallback =
        image.parentElement.querySelector(
            '.image-fallback'
        );


    if (fallback) {

        fallback.style.display = 'flex';

    }
}


/* =====================================================
   3D IMAGE EFFECT
===================================================== */

const imageBox =
    document.querySelector('.image-box');


if (imageBox) {


    imageBox.addEventListener(
        'mousemove',
        function(event) {

            const rect =
                imageBox.getBoundingClientRect();


            const x =
                (
                    event.clientX -
                    rect.left
                )
                /
                rect.width
                -
                0.5;


            const y =
                (
                    event.clientY -
                    rect.top
                )
                /
                rect.height
                -
                0.5;


            imageBox.style.transform =
                `
                perspective(1100px)
                rotateY(${x * 3}deg)
                rotateX(${y * -3}deg)
                `;

        }
    );


    imageBox.addEventListener(
        'mouseleave',
        function() {

            imageBox.style.transform =
                `
                perspective(1100px)
                rotateY(0deg)
                rotateX(0deg)
                `;

        }
    );

}

</script>


</body>

</html>