<?php

session_start();

require_once __DIR__ . '/../config/db.php';


/* =====================================================
   INITIALIZE CART
===================================================== */

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


/* =====================================================
   CART ACTIONS
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /* =================================================
       UPDATE QUANTITY
    ================================================= */

    if (isset($_POST['update_quantity'])) {

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($productId > 0) {

            if ($quantity < 1) {
                $quantity = 1;
            }

            if ($quantity > 20) {
                $quantity = 20;
            }

            if (isset($_SESSION['cart'][$productId])) {

                $_SESSION['cart'][$productId] = $quantity;

            }
        }

        header("Location: cart.php");
        exit;
    }


    /* =================================================
       REMOVE ITEM
    ================================================= */

    if (isset($_POST['remove_item'])) {

        $productId = (int)$_POST['remove_item'];

        if ($productId > 0) {

            unset($_SESSION['cart'][$productId]);

        }

        header("Location: cart.php");
        exit;
    }


    /* =================================================
       CLEAR CART
    ================================================= */

    if (isset($_POST['clear_cart'])) {

        $_SESSION['cart'] = [];

        header("Location: cart.php");
        exit;
    }

}


/* =====================================================
   CART PRODUCT DATA
===================================================== */

$cartItems = [];

$subtotal = 0;

$cartCount = 0;


/*
 * Session format:
 *
 * $_SESSION['cart'] = [
 *     15 => 2,
 *     20 => 1,
 *     28 => 3
 * ];
 */


$productIds = [];

foreach ($_SESSION['cart'] as $productId => $quantity) {

    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId > 0 && $quantity > 0) {

        $productIds[] = $productId;

    }
}


/* =====================================================
   GET PRODUCTS FROM DATABASE
===================================================== */

if (!empty($productIds)) {

    $idList = implode(',', $productIds);

    $sql = "
        SELECT
            id,
            name,
            description,
            price,
            image
        FROM products
        WHERE id IN ($idList)
        AND is_available = 1
    ";

    $result = $conn->query($sql);


    if ($result) {

        while ($product = $result->fetch_assoc()) {

            $productId = (int)$product['id'];

            $quantity = (int)(
                $_SESSION['cart'][$productId] ?? 1
            );


            if ($quantity < 1) {
                $quantity = 1;
            }

            if ($quantity > 20) {
                $quantity = 20;
            }


            $price = (float)$product['price'];

            $itemTotal = $price * $quantity;


            $subtotal += $itemTotal;

            $cartCount += $quantity;


            /* =========================================
               IMAGE PATH
            ========================================= */

            $image = trim($product['image'] ?? '');

            if ($image === '') {

                $image =
                    '../assets/images/food-placeholder.jpg';

            } elseif (
                strpos($image, 'http://') === 0 ||
                strpos($image, 'https://') === 0
            ) {

                // External image

            } else {

                $image =
                    '../assets/images/' .
                    ltrim($image, '/');
            }


            $cartItems[] = [

                'id' => $productId,

                'name' =>
                    $product['name'],

                'description' =>
                    $product['description'] ?? '',

                'price' =>
                    $price,

                'quantity' =>
                    $quantity,

                'image' =>
                    $image,

                'total' =>
                    $itemTotal
            ];

        }

    }

}


/* =====================================================
   DELIVERY
===================================================== */

$delivery = 0;


/* =====================================================
   GRAND TOTAL
===================================================== */

$grandTotal = $subtotal + $delivery;

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
    Cart | Ember & Plate
</title>


<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =====================================================
   BODY
===================================================== */

body {

    background: #080808;

    color: #ffffff;

    font-family: "DM Sans", sans-serif;

    min-height: 100vh;
}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    width: min(1180px, 92%);

    margin: auto;
}


/* =====================================================
   HEADER
===================================================== */

header {

    border-bottom:
        1px solid rgba(255,255,255,.08);

    padding: 24px 0;

    background: #080808;
}


.navbar {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}


.logo {

    font-family:
        "Playfair Display",
        serif;

    font-size: 27px;

    font-weight: 700;

    color: #d6ad5b;

    text-decoration: none;
}


.logo span {

    color: #ffffff;
}


.back-menu {

    color: #bbbbbb;

    text-decoration: none;

    font-size: 14px;

    transition: .2s;
}


.back-menu:hover {

    color: #d6ad5b;
}


/* =====================================================
   PAGE
===================================================== */

.page {

    padding: 55px 0 80px;
}


.page-title {

    display: flex;

    align-items: end;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 35px;
}


.page-title h1 {

    font-family:
        "Playfair Display",
        serif;

    font-size: 45px;

    line-height: 1.1;
}


.page-title p {

    color: #8d8d8d;

    margin-top: 8px;
}


/* =====================================================
   CART LAYOUT
===================================================== */

.cart-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1.5fr)
        minmax(320px, .7fr);

    gap: 30px;

    align-items: start;
}


/* =====================================================
   CART CARD
===================================================== */

.cart-card {

    background: #111111;

    border:
        1px solid rgba(214,173,91,.15);

    border-radius: 18px;

    overflow: hidden;
}


/* =====================================================
   CART HEADER
===================================================== */

.cart-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 22px 25px;

    border-bottom:
        1px solid #242424;
}


.cart-header h2 {

    font-family:
        "Playfair Display",
        serif;

    font-size: 23px;
}


.cart-header span {

    color: #999;

    font-size: 14px;
}


/* =====================================================
   CART ITEM
===================================================== */

.cart-item {

    display: grid;

    grid-template-columns:
        110px
        minmax(0,1fr)
        auto;

    gap: 20px;

    align-items: center;

    padding: 22px 25px;

    border-bottom:
        1px solid #222222;
}


/* =====================================================
   PRODUCT IMAGE
===================================================== */

.product-image {

    width: 110px;

    height: 100px;

    border-radius: 12px;

    overflow: hidden;

    background: #181818;
}


.product-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;
}


/* =====================================================
   PRODUCT INFO
===================================================== */

.product-info {

    min-width: 0;
}


.product-info h3 {

    font-size: 17px;

    margin-bottom: 7px;

    font-weight: 600;
}


.product-price {

    color: #d6ad5b;

    font-size: 15px;

    font-weight: 600;
}


/* =====================================================
   QUANTITY
===================================================== */

.quantity-form {

    display: flex;

    align-items: center;

    width: fit-content;

    margin-top: 14px;
}


.qty-btn {

    width: 38px;

    height: 38px;

    border:
        1px solid #343434;

    background: #171717;

    color: #ffffff;

    cursor: pointer;

    font-size: 21px;

    display: flex;

    align-items: center;

    justify-content: center;

    transition: .2s;

    user-select: none;
}


.qty-btn:first-of-type {

    border-radius:
        8px 0 0 8px;
}


.qty-btn:last-of-type {

    border-radius:
        0 8px 8px 0;
}


.qty-btn:hover {

    background: #d6ad5b;

    color: #080808;

    border-color: #d6ad5b;
}


.qty-input {

    width: 50px;

    height: 38px;

    background: #0d0d0d;

    color: #ffffff;

    border-top:
        1px solid #343434;

    border-bottom:
        1px solid #343434;

    border-left: none;

    border-right: none;

    text-align: center;

    font-size: 15px;

    outline: none;

    appearance: textfield;
}


.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {

    -webkit-appearance: none;

    margin: 0;
}


/* =====================================================
   RIGHT SIDE
===================================================== */

.item-right {

    display: flex;

    flex-direction: column;

    align-items: flex-end;

    gap: 13px;
}


.item-total {

    color: #ffffff;

    font-size: 17px;

    font-weight: 700;

    white-space: nowrap;
}


/* =====================================================
   REMOVE
===================================================== */

.remove-form {
    margin: 0;
}


.remove-btn {

    background: transparent;

    border: none;

    color: #777777;

    cursor: pointer;

    font-size: 13px;

    padding: 4px 0;

    transition: .2s;
}


.remove-btn:hover {
    color: #ff7777;
}


/* =====================================================
   CLEAR
===================================================== */

.clear-area {

    padding: 18px 25px;

    display: flex;

    justify-content: flex-end;
}


.clear-btn {

    border: none;

    background: transparent;

    color: #777777;

    cursor: pointer;

    font-size: 13px;

    transition: .2s;
}


.clear-btn:hover {
    color: #ff7777;
}


/* =====================================================
   SUMMARY
===================================================== */

.summary-card {

    background: #111111;

    border:
        1px solid rgba(214,173,91,.18);

    border-radius: 18px;

    padding: 27px;

    position: sticky;

    top: 20px;
}


.summary-card h2 {

    font-family:
        "Playfair Display",
        serif;

    font-size: 25px;

    margin-bottom: 25px;
}


.summary-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 12px 0;

    color: #aaaaaa;

    font-size: 15px;
}


.summary-row span:last-child {
    color: #eeeeee;
}


.summary-divider {

    height: 1px;

    background: #2b2b2b;

    margin: 12px 0;
}


.total-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding-top: 18px;

    font-size: 21px;

    font-weight: 700;
}


.total-row strong {
    color: #d6ad5b;
}


/* =====================================================
   CHECKOUT
===================================================== */

.checkout-btn {

    display: block;

    width: 100%;

    margin-top: 25px;

    padding: 16px;

    border-radius: 10px;

    background: #d6ad5b;

    color: #080808;

    text-align: center;

    text-decoration: none;

    font-size: 16px;

    font-weight: 700;

    transition: .2s;
}


.checkout-btn:hover {

    background: #e7c476;

    transform: translateY(-1px);
}


.continue-shopping {

    display: block;

    text-align: center;

    margin-top: 17px;

    color: #999999;

    text-decoration: none;

    font-size: 14px;
}


.continue-shopping:hover {
    color: #d6ad5b;
}


/* =====================================================
   EMPTY CART
===================================================== */

.empty-cart {

    text-align: center;

    padding: 80px 25px;
}


.empty-icon {

    width: 75px;

    height: 75px;

    border-radius: 50%;

    background: #181818;

    border:
        1px solid #303030;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 20px;

    font-size: 30px;
}


.empty-cart h2 {

    font-family:
        "Playfair Display",
        serif;

    font-size: 30px;

    margin-bottom: 10px;
}


.empty-cart p {

    color: #888888;

    margin-bottom: 25px;
}


.menu-btn {

    display: inline-block;

    padding: 13px 22px;

    background: #d6ad5b;

    color: #080808;

    border-radius: 9px;

    text-decoration: none;

    font-weight: 700;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:850px) {

    .cart-layout {
        grid-template-columns: 1fr;
    }

    .summary-card {
        position: static;
    }

}


@media(max-width:650px) {

    .page {
        padding-top: 35px;
    }

    .page-title {

        align-items: flex-start;

        flex-direction: column;
    }

    .page-title h1 {
        font-size: 36px;
    }

    .cart-item {

        grid-template-columns:
            80px
            minmax(0,1fr);

        gap: 15px;

        padding: 18px;
    }

    .product-image {

        width: 80px;

        height: 80px;
    }

    .item-right {

        grid-column: 2;

        align-items: flex-start;

        flex-direction: row;

        justify-content: space-between;

        width: 100%;
    }

    .cart-header {
        padding: 18px;
    }

    .clear-area {
        padding: 15px 18px;
    }

}


@media(max-width:430px) {

    .product-info h3 {
        font-size: 15px;
    }

    .quantity-form {
        margin-top: 10px;
    }

    .qty-btn {

        width: 34px;

        height: 34px;
    }

    .qty-input {

        width: 42px;

        height: 34px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header>

<div class="container">

<div class="navbar">

<a
    href="index.php"
    class="logo"
>
    Ember <span>&</span> Plate
</a>


<a
    href="menu.php"
    class="back-menu"
>
    ← Continue Shopping
</a>

</div>

</div>

</header>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="container page">


<div class="page-title">

<div>

<h1>
    Your Cart
</h1>

<p>
    <?= $cartCount ?>
    item<?= $cartCount !== 1 ? 's' : '' ?>
    in your cart
</p>

</div>

</div>



<?php if (empty($cartItems)): ?>


<!-- =====================================================
     EMPTY CART
===================================================== -->

<div class="cart-card">

<div class="empty-cart">

<div class="empty-icon">
    🛒
</div>

<h2>
    Your cart is empty
</h2>

<p>
    Looks like you haven't added anything yet.
</p>

<a
    href="menu.php"
    class="menu-btn"
>
    Explore Our Menu
</a>

</div>

</div>


<?php else: ?>


<!-- =====================================================
     CART + SUMMARY
===================================================== -->

<div class="cart-layout">


<!-- =====================================================
     CART ITEMS
===================================================== -->

<div class="cart-card">


<div class="cart-header">

<h2>
    Cart Items
</h2>

<span>
    <?= $cartCount ?>
    item<?= $cartCount !== 1 ? 's' : '' ?>
</span>

</div>



<?php foreach ($cartItems as $item): ?>


<div class="cart-item">


<!-- IMAGE -->

<div class="product-image">

<img
    src="<?= htmlspecialchars($item['image']) ?>"
    alt="<?= htmlspecialchars($item['name']) ?>"
    onerror="this.src='../assets/images/chicken-biryani.jpg'"
>

</div>



<!-- PRODUCT INFO -->

<div class="product-info">

<h3>
    <?= htmlspecialchars($item['name']) ?>
</h3>


<div class="product-price">

₹<?= number_format($item['price'], 2) ?>

</div>


<!-- QUANTITY -->

<form
    method="POST"
    action="cart.php"
    class="quantity-form"
>

<input
    type="hidden"
    name="product_id"
    value="<?= (int)$item['id'] ?>"
>


<input
    type="hidden"
    name="update_quantity"
    value="1"
>


<button
    type="button"
    class="qty-btn"
    onclick="changeCartQuantity(this, -1)"
>
    −
</button>


<input
    type="number"
    name="quantity"
    class="qty-input"
    value="<?= (int)$item['quantity'] ?>"
    min="1"
    max="20"
    readonly
>


<button
    type="button"
    class="qty-btn"
    onclick="changeCartQuantity(this, 1)"
>
    +
</button>

</form>

</div>



<!-- RIGHT SIDE -->

<div class="item-right">


<div class="item-total">

₹<?= number_format($item['total'], 2) ?>

</div>


<form
    method="POST"
    action="cart.php"
    class="remove-form"
>

<button
    type="submit"
    name="remove_item"
    value="<?= (int)$item['id'] ?>"
    class="remove-btn"
>
    Remove
</button>

</form>

</div>


</div>


<?php endforeach; ?>


<!-- CLEAR CART -->

<div class="clear-area">

<form
    method="POST"
    action="cart.php"
>

<button
    type="submit"
    name="clear_cart"
    value="1"
    class="clear-btn"
    onclick="return confirm('Are you sure you want to clear your cart?');"
>
    Clear Cart
</button>

</form>

</div>


</div>



<!-- =====================================================
     SUMMARY
===================================================== -->

<div class="summary-card">

<h2>
    Order Summary
</h2>


<div class="summary-row">

<span>
    Subtotal
</span>

<span>
    ₹<?= number_format($subtotal, 2) ?>
</span>

</div>


<div class="summary-row">

<span>
    Delivery
</span>

<span>
    Free
</span>

</div>


<div class="summary-divider"></div>


<div class="total-row">

<span>
    Total
</span>

<strong>
    ₹<?= number_format($grandTotal, 2) ?>
</strong>

</div>


<a
    href="checkout.php"
    class="checkout-btn"
>
    Proceed to Checkout
</a>


<a
    href="menu.php"
    class="continue-shopping"
>
    ← Continue Shopping
</a>

</div>


</div>


<?php endif; ?>


</main>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

function changeCartQuantity(button, amount) {

    const form =
        button.closest('.quantity-form');

    if (!form) {
        return;
    }


    const input =
        form.querySelector('.qty-input');

    if (!input) {
        return;
    }


    let quantity =
        parseInt(input.value, 10) || 1;


    quantity += amount;


    if (quantity < 1) {
        quantity = 1;
    }


    if (quantity > 20) {
        quantity = 20;
    }


    input.value = quantity;


    clearTimeout(form.updateTimer);


    form.updateTimer = setTimeout(function() {

        form.submit();

    }, 150);

}

</script>


</body>

</html><?php

session_start();

require_once __DIR__ . '/../config/db.php';


/* =====================================================
   INITIALIZE CART
===================================================== */

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


/* =====================================================
   CART ACTIONS
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /* =================================================
       UPDATE QUANTITY
    ================================================= */

    if (isset($_POST['update_quantity'])) {

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($productId > 0) {

            if ($quantity < 1) {
                $quantity = 1;
            }

            if ($quantity > 20) {
                $quantity = 20;
            }

            if (isset($_SESSION['cart'][$productId])) {

                $_SESSION['cart'][$productId] = $quantity;

            }
        }

        header("Location: cart.php");
        exit;
    }


    /* =================================================
       REMOVE ITEM
    ================================================= */

    if (isset($_POST['remove_item'])) {

        $productId = (int)$_POST['remove_item'];

        if ($productId > 0) {

            unset($_SESSION['cart'][$productId]);

        }

        header("Location: cart.php");
        exit;
    }


    /* =================================================
       CLEAR CART
    ================================================= */

    if (isset($_POST['clear_cart'])) {

        $_SESSION['cart'] = [];

        header("Location: cart.php");
        exit;
    }

}


/* =====================================================
   CART PRODUCT DATA
===================================================== */

$cartItems = [];

$subtotal = 0;

$cartCount = 0;


/*
 * Session format:
 *
 * $_SESSION['cart'] = [
 *     15 => 2,
 *     20 => 1,
 *     28 => 3
 * ];
 */


$productIds = [];

foreach ($_SESSION['cart'] as $productId => $quantity) {

    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId > 0 && $quantity > 0) {

        $productIds[] = $productId;

    }
}


/* =====================================================
   GET PRODUCTS FROM DATABASE
===================================================== */

if (!empty($productIds)) {

    $idList = implode(',', $productIds);

    $sql = "
        SELECT
            id,
            name,
            description,
            price,
            image
        FROM products
        WHERE id IN ($idList)
        AND is_available = 1
    ";

    $result = $conn->query($sql);


    if ($result) {

        while ($product = $result->fetch_assoc()) {

            $productId = (int)$product['id'];

            $quantity = (int)(
                $_SESSION['cart'][$productId] ?? 1
            );


            if ($quantity < 1) {
                $quantity = 1;
            }

            if ($quantity > 20) {
                $quantity = 20;
            }


            $price = (float)$product['price'];

            $itemTotal = $price * $quantity;


            $subtotal += $itemTotal;

            $cartCount += $quantity;


            /* =========================================
               IMAGE PATH
            ========================================= */

            $image = trim($product['image'] ?? '');

            if ($image === '') {

                $image =
                    '../assets/images/food-placeholder.jpg';

            } elseif (
                strpos($image, 'http://') === 0 ||
                strpos($image, 'https://') === 0
            ) {

                // External image

            } else {

                $image =
                    '../assets/images/' .
                    ltrim($image, '/');
            }


            $cartItems[] = [

                'id' => $productId,

                'name' =>
                    $product['name'],

                'description' =>
                    $product['description'] ?? '',

                'price' =>
                    $price,

                'quantity' =>
                    $quantity,

                'image' =>
                    $image,

                'total' =>
                    $itemTotal
            ];

        }

    }

}


/* =====================================================
   DELIVERY
===================================================== */

$delivery = 0;


/* =====================================================
   GRAND TOTAL
===================================================== */

$grandTotal = $subtotal + $delivery;

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
    Cart | Ember & Plate
</title>


<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
    crossorigin
>

<link
    href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =====================================================
   RESET
===================================================== */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =====================================================
   BODY
===================================================== */

body {

    background: #080808;

    color: #ffffff;

    font-family: "DM Sans", sans-serif;

    min-height: 100vh;
}


/* =====================================================
   CONTAINER
===================================================== */

.container {

    width: min(1180px, 92%);

    margin: auto;
}


/* =====================================================
   HEADER
===================================================== */

header {

    border-bottom:
        1px solid rgba(255,255,255,.08);

    padding: 24px 0;

    background: #080808;
}


.navbar {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}


.logo {

    font-family:
        "Playfair Display",
        serif;

    font-size: 27px;

    font-weight: 700;

    color: #d6ad5b;

    text-decoration: none;
}


.logo span {

    color: #ffffff;
}


.back-menu {

    color: #bbbbbb;

    text-decoration: none;

    font-size: 14px;

    transition: .2s;
}


.back-menu:hover {

    color: #d6ad5b;
}


/* =====================================================
   PAGE
===================================================== */

.page {

    padding: 55px 0 80px;
}


.page-title {

    display: flex;

    align-items: end;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 35px;
}


.page-title h1 {

    font-family:
        "Playfair Display",
        serif;

    font-size: 45px;

    line-height: 1.1;
}


.page-title p {

    color: #8d8d8d;

    margin-top: 8px;
}


/* =====================================================
   CART LAYOUT
===================================================== */

.cart-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1.5fr)
        minmax(320px, .7fr);

    gap: 30px;

    align-items: start;
}


/* =====================================================
   CART CARD
===================================================== */

.cart-card {

    background: #111111;

    border:
        1px solid rgba(214,173,91,.15);

    border-radius: 18px;

    overflow: hidden;
}


/* =====================================================
   CART HEADER
===================================================== */

.cart-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 22px 25px;

    border-bottom:
        1px solid #242424;
}


.cart-header h2 {

    font-family:
        "Playfair Display",
        serif;

    font-size: 23px;
}


.cart-header span {

    color: #999;

    font-size: 14px;
}


/* =====================================================
   CART ITEM
===================================================== */

.cart-item {

    display: grid;

    grid-template-columns:
        110px
        minmax(0,1fr)
        auto;

    gap: 20px;

    align-items: center;

    padding: 22px 25px;

    border-bottom:
        1px solid #222222;
}


/* =====================================================
   PRODUCT IMAGE
===================================================== */

.product-image {

    width: 110px;

    height: 100px;

    border-radius: 12px;

    overflow: hidden;

    background: #181818;
}


.product-image img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;
}


/* =====================================================
   PRODUCT INFO
===================================================== */

.product-info {

    min-width: 0;
}


.product-info h3 {

    font-size: 17px;

    margin-bottom: 7px;

    font-weight: 600;
}


.product-price {

    color: #d6ad5b;

    font-size: 15px;

    font-weight: 600;
}


/* =====================================================
   QUANTITY
===================================================== */

.quantity-form {

    display: flex;

    align-items: center;

    width: fit-content;

    margin-top: 14px;
}


.qty-btn {

    width: 38px;

    height: 38px;

    border:
        1px solid #343434;

    background: #171717;

    color: #ffffff;

    cursor: pointer;

    font-size: 21px;

    display: flex;

    align-items: center;

    justify-content: center;

    transition: .2s;

    user-select: none;
}


.qty-btn:first-of-type {

    border-radius:
        8px 0 0 8px;
}


.qty-btn:last-of-type {

    border-radius:
        0 8px 8px 0;
}


.qty-btn:hover {

    background: #d6ad5b;

    color: #080808;

    border-color: #d6ad5b;
}


.qty-input {

    width: 50px;

    height: 38px;

    background: #0d0d0d;

    color: #ffffff;

    border-top:
        1px solid #343434;

    border-bottom:
        1px solid #343434;

    border-left: none;

    border-right: none;

    text-align: center;

    font-size: 15px;

    outline: none;

    appearance: textfield;
}


.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {

    -webkit-appearance: none;

    margin: 0;
}


/* =====================================================
   RIGHT SIDE
===================================================== */

.item-right {

    display: flex;

    flex-direction: column;

    align-items: flex-end;

    gap: 13px;
}


.item-total {

    color: #ffffff;

    font-size: 17px;

    font-weight: 700;

    white-space: nowrap;
}


/* =====================================================
   REMOVE
===================================================== */

.remove-form {
    margin: 0;
}


.remove-btn {

    background: transparent;

    border: none;

    color: #777777;

    cursor: pointer;

    font-size: 13px;

    padding: 4px 0;

    transition: .2s;
}


.remove-btn:hover {
    color: #ff7777;
}


/* =====================================================
   CLEAR
===================================================== */

.clear-area {

    padding: 18px 25px;

    display: flex;

    justify-content: flex-end;
}


.clear-btn {

    border: none;

    background: transparent;

    color: #777777;

    cursor: pointer;

    font-size: 13px;

    transition: .2s;
}


.clear-btn:hover {
    color: #ff7777;
}


/* =====================================================
   SUMMARY
===================================================== */

.summary-card {

    background: #111111;

    border:
        1px solid rgba(214,173,91,.18);

    border-radius: 18px;

    padding: 27px;

    position: sticky;

    top: 20px;
}


.summary-card h2 {

    font-family:
        "Playfair Display",
        serif;

    font-size: 25px;

    margin-bottom: 25px;
}


.summary-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 12px 0;

    color: #aaaaaa;

    font-size: 15px;
}


.summary-row span:last-child {
    color: #eeeeee;
}


.summary-divider {

    height: 1px;

    background: #2b2b2b;

    margin: 12px 0;
}


.total-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding-top: 18px;

    font-size: 21px;

    font-weight: 700;
}


.total-row strong {
    color: #d6ad5b;
}


/* =====================================================
   CHECKOUT
===================================================== */

.checkout-btn {

    display: block;

    width: 100%;

    margin-top: 25px;

    padding: 16px;

    border-radius: 10px;

    background: #d6ad5b;

    color: #080808;

    text-align: center;

    text-decoration: none;

    font-size: 16px;

    font-weight: 700;

    transition: .2s;
}


.checkout-btn:hover {

    background: #e7c476;

    transform: translateY(-1px);
}


.continue-shopping {

    display: block;

    text-align: center;

    margin-top: 17px;

    color: #999999;

    text-decoration: none;

    font-size: 14px;
}


.continue-shopping:hover {
    color: #d6ad5b;
}


/* =====================================================
   EMPTY CART
===================================================== */

.empty-cart {

    text-align: center;

    padding: 80px 25px;
}


.empty-icon {

    width: 75px;

    height: 75px;

    border-radius: 50%;

    background: #181818;

    border:
        1px solid #303030;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 20px;

    font-size: 30px;
}


.empty-cart h2 {

    font-family:
        "Playfair Display",
        serif;

    font-size: 30px;

    margin-bottom: 10px;
}


.empty-cart p {

    color: #888888;

    margin-bottom: 25px;
}


.menu-btn {

    display: inline-block;

    padding: 13px 22px;

    background: #d6ad5b;

    color: #080808;

    border-radius: 9px;

    text-decoration: none;

    font-weight: 700;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:850px) {

    .cart-layout {
        grid-template-columns: 1fr;
    }

    .summary-card {
        position: static;
    }

}


@media(max-width:650px) {

    .page {
        padding-top: 35px;
    }

    .page-title {

        align-items: flex-start;

        flex-direction: column;
    }

    .page-title h1 {
        font-size: 36px;
    }

    .cart-item {

        grid-template-columns:
            80px
            minmax(0,1fr);

        gap: 15px;

        padding: 18px;
    }

    .product-image {

        width: 80px;

        height: 80px;
    }

    .item-right {

        grid-column: 2;

        align-items: flex-start;

        flex-direction: row;

        justify-content: space-between;

        width: 100%;
    }

    .cart-header {
        padding: 18px;
    }

    .clear-area {
        padding: 15px 18px;
    }

}


@media(max-width:430px) {

    .product-info h3 {
        font-size: 15px;
    }

    .quantity-form {
        margin-top: 10px;
    }

    .qty-btn {

        width: 34px;

        height: 34px;
    }

    .qty-input {

        width: 42px;

        height: 34px;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header>

<div class="container">

<div class="navbar">

<a
    href="index.php"
    class="logo"
>
    Ember <span>&</span> Plate
</a>


<a
    href="menu.php"
    class="back-menu"
>
    ← Continue Shopping
</a>

</div>

</div>

</header>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="container page">


<div class="page-title">

<div>

<h1>
    Your Cart
</h1>

<p>
    <?= $cartCount ?>
    item<?= $cartCount !== 1 ? 's' : '' ?>
    in your cart
</p>

</div>

</div>



<?php if (empty($cartItems)): ?>


<!-- =====================================================
     EMPTY CART
===================================================== -->

<div class="cart-card">

<div class="empty-cart">

<div class="empty-icon">
    🛒
</div>

<h2>
    Your cart is empty
</h2>

<p>
    Looks like you haven't added anything yet.
</p>

<a
    href="menu.php"
    class="menu-btn"
>
    Explore Our Menu
</a>

</div>

</div>


<?php else: ?>


<!-- =====================================================
     CART + SUMMARY
===================================================== -->

<div class="cart-layout">


<!-- =====================================================
     CART ITEMS
===================================================== -->

<div class="cart-card">


<div class="cart-header">

<h2>
    Cart Items
</h2>

<span>
    <?= $cartCount ?>
    item<?= $cartCount !== 1 ? 's' : '' ?>
</span>

</div>



<?php foreach ($cartItems as $item): ?>


<div class="cart-item">


<!-- IMAGE -->

<div class="product-image">

<img
    src="<?= htmlspecialchars($item['image']) ?>"
    alt="<?= htmlspecialchars($item['name']) ?>"
    onerror="this.src='../assets/images/chicken-biryani.jpg'"
>

</div>



<!-- PRODUCT INFO -->

<div class="product-info">

<h3>
    <?= htmlspecialchars($item['name']) ?>
</h3>


<div class="product-price">

₹<?= number_format($item['price'], 2) ?>

</div>


<!-- QUANTITY -->

<form
    method="POST"
    action="cart.php"
    class="quantity-form"
>

<input
    type="hidden"
    name="product_id"
    value="<?= (int)$item['id'] ?>"
>


<input
    type="hidden"
    name="update_quantity"
    value="1"
>


<button
    type="button"
    class="qty-btn"
    onclick="changeCartQuantity(this, -1)"
>
    −
</button>


<input
    type="number"
    name="quantity"
    class="qty-input"
    value="<?= (int)$item['quantity'] ?>"
    min="1"
    max="20"
    readonly
>


<button
    type="button"
    class="qty-btn"
    onclick="changeCartQuantity(this, 1)"
>
    +
</button>

</form>

</div>



<!-- RIGHT SIDE -->

<div class="item-right">


<div class="item-total">

₹<?= number_format($item['total'], 2) ?>

</div>


<form
    method="POST"
    action="cart.php"
    class="remove-form"
>

<button
    type="submit"
    name="remove_item"
    value="<?= (int)$item['id'] ?>"
    class="remove-btn"
>
    Remove
</button>

</form>

</div>


</div>


<?php endforeach; ?>


<!-- CLEAR CART -->

<div class="clear-area">

<form
    method="POST"
    action="cart.php"
>

<button
    type="submit"
    name="clear_cart"
    value="1"
    class="clear-btn"
    onclick="return confirm('Are you sure you want to clear your cart?');"
>
    Clear Cart
</button>

</form>

</div>


</div>



<!-- =====================================================
     SUMMARY
===================================================== -->

<div class="summary-card">

<h2>
    Order Summary
</h2>


<div class="summary-row">

<span>
    Subtotal
</span>

<span>
    ₹<?= number_format($subtotal, 2) ?>
</span>

</div>


<div class="summary-row">

<span>
    Delivery
</span>

<span>
    Free
</span>

</div>


<div class="summary-divider"></div>


<div class="total-row">

<span>
    Total
</span>

<strong>
    ₹<?= number_format($grandTotal, 2) ?>
</strong>

</div>


<a
    href="checkout.php"
    class="checkout-btn"
>
    Proceed to Checkout
</a>


<a
    href="menu.php"
    class="continue-shopping"
>
    ← Continue Shopping
</a>

</div>


</div>


<?php endif; ?>


</main>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

function changeCartQuantity(button, amount) {

    const form =
        button.closest('.quantity-form');

    if (!form) {
        return;
    }


    const input =
        form.querySelector('.qty-input');

    if (!input) {
        return;
    }


    let quantity =
        parseInt(input.value, 10) || 1;


    quantity += amount;


    if (quantity < 1) {
        quantity = 1;
    }


    if (quantity > 20) {
        quantity = 20;
    }


    input.value = quantity;


    clearTimeout(form.updateTimer);


    form.updateTimer = setTimeout(function() {

        form.submit();

    }, 150);

}

</script>


</body>

</html>