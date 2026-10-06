<?php

session_start();

require_once __DIR__ . '/../config/db.php';


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


$userId = (int)$_SESSION['user_id'];

$userName = $_SESSION['user_name'] ?? 'Customer';


/*
|--------------------------------------------------------------------------
| CART COUNT
|--------------------------------------------------------------------------
*/

$cartCount = 0;

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int)$qty;
    }

}


/*
|--------------------------------------------------------------------------
| FETCH USER ORDERS
|--------------------------------------------------------------------------
*/

$orders = [];

$stmt = $conn->prepare("
    SELECT
        id,
        order_number,
        total_amount,
        delivery_address,
        phone,
        payment_method,
        payment_status,
        order_status,
        notes,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");


if ($stmt) {

    $stmt->bind_param("i", $userId);

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    $stmt->close();
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

function statusClass($status)
{
    $status = strtolower(trim((string)$status));

    switch ($status) {

        case 'delivered':
            return 'status-delivered';

        case 'confirmed':
            return 'status-confirmed';

        case 'preparing':
            return 'status-preparing';

        case 'out_for_delivery':
        case 'out for delivery':
            return 'status-delivery';

        case 'cancelled':
        case 'canceled':
            return 'status-cancelled';

        case 'pending':
        default:
            return 'status-pending';
    }
}


/*
|--------------------------------------------------------------------------
| PAYMENT STATUS CLASS
|--------------------------------------------------------------------------
*/

function paymentClass($status)
{
    $status = strtolower(trim((string)$status));

    if (
        $status === 'paid' ||
        $status === 'completed' ||
        $status === 'success'
    ) {
        return 'payment-paid';
    }

    if (
        $status === 'failed' ||
        $status === 'cancelled' ||
        $status === 'canceled'
    ) {
        return 'payment-failed';
    }

    return 'payment-pending';
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
        My Orders | Ember &amp; Plate
    </title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    background: #070707;

    color: #f5f1e8;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    overflow-x: hidden;
}

a {
    text-decoration: none;
    color: inherit;
}


/* =========================================================
   BACKGROUND
========================================================= */

body::before {

    content: "";

    position: fixed;

    width: 600px;
    height: 600px;

    left: -250px;
    top: -250px;

    background:
        radial-gradient(
            circle,
            rgba(190,145,50,0.11),
            transparent 70%
        );

    pointer-events: none;

    z-index: -1;
}

body::after {

    content: "";

    position: fixed;

    width: 500px;
    height: 500px;

    right: -220px;
    bottom: -220px;

    background:
        radial-gradient(
            circle,
            rgba(190,145,50,0.07),
            transparent 70%
        );

    pointer-events: none;

    z-index: -1;
}


/* =========================================================
   NAVBAR
========================================================= */

.navbar {

    position: fixed;

    top: 0;
    left: 0;

    width: 100%;

    min-height: 74px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 42px;

    background:
        rgba(5,5,5,0.92);

    backdrop-filter:
        blur(18px);

    border-bottom:
        1px solid
        rgba(255,255,255,0.08);

    z-index: 9999;
}


/* =========================================================
   BRAND
========================================================= */

.brand {

    display: flex;

    align-items: center;

    gap: 13px;
}

.brand-logo {

    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    border:
        1px solid
        #a98235;

    color: #d8b66d;

    font-family: Georgia, serif;

    font-size: 14px;
}

.brand-text {

    display: flex;

    flex-direction: column;
}

.brand-name {

    font-family: Georgia, serif;

    font-size: 20px;

    letter-spacing: 0.5px;
}

.brand-sub {

    font-size: 8px;

    letter-spacing: 4px;

    color: #a98235;

    margin-top: 2px;
}


/* =========================================================
   NAV
========================================================= */

.nav-links {

    display: flex;

    align-items: center;

    gap: 28px;
}

.nav-links a {

    position: relative;

    color: #999;

    font-size: 10px;

    letter-spacing: 1px;

    text-transform: uppercase;

    transition: 0.3s ease;
}

.nav-links a:hover,
.nav-links a.active {

    color: #d8b66d;
}

.nav-links a::after {

    content: "";

    position: absolute;

    left: 0;

    bottom: -8px;

    width: 0;

    height: 1px;

    background: #d8b66d;

    transition: 0.3s ease;
}

.nav-links a:hover::after,
.nav-links a.active::after {

    width: 100%;
}


/* =========================================================
   CART
========================================================= */

.cart-link {

    display: flex;

    align-items: center;

    gap: 7px;
}

.cart-count {

    min-width: 18px;

    height: 18px;

    padding: 0 5px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 50px;

    background: #b28a3d;

    color: #050505;

    font-size: 9px;

    font-weight: bold;
}


/* =========================================================
   LOGOUT
========================================================= */

.logout-btn {

    padding:
        10px
        17px;

    border:
        1px solid
        #9c762e;

    color: #d8b66d !important;
}

.logout-btn:hover {

    background: #b28a3d;

    color: #000 !important;
}


/* =========================================================
   HERO
========================================================= */

.hero {

    min-height: 48vh;

    display: flex;

    align-items: center;

    justify-content: center;

    text-align: center;

    padding:
        150px
        20px
        80px;
}

.hero-label {

    color: #b99755;

    font-size: 10px;

    letter-spacing: 5px;

    text-transform: uppercase;

    margin-bottom: 22px;

    animation:
        fadeUp 0.9s ease both;
}

.hero h1 {

    font-family: Georgia, serif;

    font-weight: 400;

    font-size:
        clamp(
            48px,
            8vw,
            90px
        );

    line-height: 0.95;

    letter-spacing: -3px;

    margin-bottom: 25px;

    animation:
        fadeUp 0.9s 0.1s ease both;
}

.hero h1 span {

    color: #b99650;

    font-style: italic;
}

.hero p {

    color: #858585;

    font-size: 13px;

    line-height: 1.8;

    animation:
        fadeUp 0.9s 0.2s ease both;
}


/* =========================================================
   CONTAINER
========================================================= */

.container {

    width:
        min(
            1180px,
            92%
        );

    margin: auto;
}


/* =========================================================
   ORDERS SECTION
========================================================= */

.orders-section {

    padding:
        30px
        0
        130px;
}


/* =========================================================
   HEADER
========================================================= */

.orders-header {

    display: flex;

    align-items: end;

    justify-content: space-between;

    margin-bottom: 35px;
}

.orders-header small {

    color: #b99755;

    font-size: 9px;

    letter-spacing: 4px;

    text-transform: uppercase;
}

.orders-header h2 {

    font-family: Georgia, serif;

    font-size: 40px;

    font-weight: 400;

    margin-top: 10px;
}

.orders-total {

    color: #777;

    font-size: 12px;
}


/* =========================================================
   ORDER CARD
========================================================= */

.order-card {

    position: relative;

    margin-bottom: 22px;

    background: #0b0b0b;

    border:
        1px solid
        rgba(255,255,255,0.07);

    padding: 30px;

    transition:
        transform 0.35s ease,
        border-color 0.35s ease,
        background 0.35s ease;

    overflow: hidden;
}

.order-card::before {

    content: "";

    position: absolute;

    top: 0;
    left: 0;

    width: 100%;

    height: 1px;

    background:
        linear-gradient(
            90deg,
            transparent,
            #b99650,
            transparent
        );
}

.order-card:hover {

    transform:
        translateY(-4px);

    border-color:
        rgba(185,145,65,0.28);

    background: #0e0e0e;
}


/* =========================================================
   ORDER TOP
========================================================= */

.order-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding-bottom: 22px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.06);
}

.order-number {

    font-family: Georgia, serif;

    font-size: 22px;

    color: #eee;
}

.order-date {

    color: #666;

    font-size: 10px;

    margin-top: 7px;
}


/* =========================================================
   STATUS
========================================================= */

.order-status {

    display: inline-flex;

    align-items: center;

    padding:
        8px
        13px;

    border-radius: 50px;

    font-size: 9px;

    letter-spacing: 1px;

    text-transform: uppercase;

    border: 1px solid;
}


/* status colours intentionally defaulted */

.status-pending {

    color: #d4ad5d;

    border-color:
        rgba(212,173,93,0.35);

    background:
        rgba(212,173,93,0.06);
}

.status-confirmed {

    color: #8fc6dc;

    border-color:
        rgba(100,180,210,0.35);

    background:
        rgba(100,180,210,0.06);
}

.status-preparing {

    color: #d9b36b;

    border-color:
        rgba(210,170,80,0.35);

    background:
        rgba(210,170,80,0.06);
}

.status-delivery {

    color: #b89ce0;

    border-color:
        rgba(170,130,220,0.35);

    background:
        rgba(170,130,220,0.06);
}

.status-delivered {

    color: #93d19e;

    border-color:
        rgba(90,180,105,0.35);

    background:
        rgba(90,180,105,0.06);
}

.status-cancelled {

    color: #e79a9a;

    border-color:
        rgba(210,80,80,0.35);

    background:
        rgba(210,80,80,0.06);
}


/* =========================================================
   ORDER DETAILS
========================================================= */

.order-details {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 20px;

    padding:
        25px
        0;
}

.detail-label {

    color: #666;

    font-size: 9px;

    letter-spacing: 1.5px;

    text-transform: uppercase;

    margin-bottom: 8px;
}

.detail-value {

    color: #ddd;

    font-size: 12px;

    line-height: 1.6;

    word-break: break-word;
}


/* =========================================================
   PAYMENT
========================================================= */

.payment-status {

    display: inline-block;

    margin-top: 5px;

    padding:
        5px
        9px;

    border-radius: 20px;

    font-size: 8px;

    text-transform: uppercase;

    letter-spacing: 1px;

    border: 1px solid;
}

.payment-paid {

    color: #94d29d;

    border-color:
        rgba(90,180,105,0.3);
}

.payment-pending {

    color: #d4ad5d;

    border-color:
        rgba(212,173,93,0.3);
}

.payment-failed {

    color: #e79a9a;

    border-color:
        rgba(210,80,80,0.3);
}


/* =========================================================
   TOTAL
========================================================= */

.order-bottom {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding-top: 20px;

    border-top:
        1px solid
        rgba(255,255,255,0.06);
}

.delivery {

    color: #777;

    font-size: 11px;

    max-width: 70%;

    line-height: 1.6;
}

.total-box {

    text-align: right;
}

.total-label {

    color: #666;

    font-size: 9px;

    letter-spacing: 1px;

    text-transform: uppercase;
}

.total-price {

    color: #d8b66d;

    font-family: Georgia, serif;

    font-size: 28px;

    margin-top: 4px;
}


/* =========================================================
   EMPTY
========================================================= */

.empty-orders {

    text-align: center;

    padding:
        90px
        20px;

    border:
        1px dashed
        #292929;
}

.empty-icon {

    color: #b99650;

    font-size: 45px;

    margin-bottom: 20px;
}

.empty-orders h3 {

    font-family: Georgia, serif;

    font-weight: 400;

    font-size: 32px;

    margin-bottom: 12px;
}

.empty-orders p {

    color: #777;

    font-size: 13px;

    margin-bottom: 28px;
}

.menu-btn {

    display: inline-block;

    padding:
        13px
        22px;

    border:
        1px solid
        #a98235;

    color: #d8b66d;

    font-size: 10px;

    letter-spacing: 2px;

    text-transform: uppercase;

    transition: 0.3s ease;
}

.menu-btn:hover {

    background: #b28a3d;

    color: #000;
}


/* =========================================================
   FOOTER
========================================================= */

footer {

    border-top:
        1px solid
        rgba(255,255,255,0.07);

    text-align: center;

    padding:
        45px
        20px;

    color: #666;

    font-size: 11px;

    letter-spacing: 1px;
}

footer strong {

    color: #b99650;

    font-family: Georgia, serif;

    font-size: 14px;
}


/* =========================================================
   ANIMATION
========================================================= */

@keyframes fadeUp {

    from {

        opacity: 0;

        transform:
            translateY(30px);
    }

    to {

        opacity: 1;

        transform:
            translateY(0);
    }
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 950px) {

    .navbar {

        padding:
            0
            20px;
    }

    .nav-links {

        gap: 17px;
    }

    .order-details {

        grid-template-columns:
            repeat(2, 1fr);
    }

}


@media (max-width: 760px) {

    .navbar {

        min-height: 68px;

        flex-direction: column;

        align-items: stretch;

        padding:
            10px
            12px;
    }

    .brand {

        justify-content: center;
    }

    .nav-links {

        width: 100%;

        overflow-x: auto;

        justify-content: flex-start;

        gap: 22px;

        padding:
            10px
            2px
            4px;

        scrollbar-width: none;
    }

    .nav-links::-webkit-scrollbar {
        display: none;
    }

    .nav-links a {

        flex:
            0 0 auto;

        font-size: 9px;
    }

    .hero {

        padding-top: 180px;
    }

    .orders-header {

        display: block;
    }

    .orders-total {

        margin-top: 10px;
    }

    .order-top {

        align-items: flex-start;

        flex-direction: column;
    }

    .order-details {

        grid-template-columns: 1fr 1fr;
    }

}


@media (max-width: 480px) {

    .hero h1 {

        font-size: 50px;

        letter-spacing: -2px;
    }

    .order-card {

        padding: 22px 18px;
    }

    .order-details {

        grid-template-columns: 1fr;

        gap: 18px;
    }

    .order-bottom {

        align-items: flex-start;

        flex-direction: column;

        gap: 20px;
    }

    .delivery {

        max-width: 100%;
    }

    .total-box {

        text-align: left;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar">

    <a
        href="index.php"
        class="brand"
    >

        <div class="brand-logo">
            EP
        </div>

        <div class="brand-text">

            <div class="brand-name">
                Ember &amp; Plate
            </div>

            <div class="brand-sub">
                Fine Dining
            </div>

        </div>

    </a>


    <div class="nav-links">

        <a href="index.php">
            Home
        </a>

        <a href="menu.php">
            Menu
        </a>

        <a href="about.php">
            About
        </a>

        <a href="reviews.php">
            Reviews
        </a>

        <a href="contact.php">
            Contact
        </a>

        <a
            href="my-orders.php"
            class="active"
        >
            My Orders
        </a>

        <a
            href="cart.php"
            class="cart-link"
        >

            Cart

            <span class="cart-count">
                <?= $cartCount ?>
            </span>

        </a>

        <a
            href="logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>

</nav>



<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">

    <div>

        <div class="hero-label">
            Your Dining Journey
        </div>

        <h1>
            My <span>orders.</span>
        </h1>

        <p>
            Keep track of your Ember &amp; Plate orders,
            payments and delivery details in one place.
        </p>

    </div>

</section>



<!-- =========================================================
     ORDERS
========================================================= -->

<section class="orders-section">

    <div class="container">


        <div class="orders-header">

            <div>

                <small>
                    Order History
                </small>

                <h2>
                    Your orders
                </h2>

            </div>

            <div class="orders-total">

                <?= count($orders) ?>

                <?= count($orders) == 1 ? 'order' : 'orders' ?>

            </div>

        </div>



        <?php if (!empty($orders)): ?>


            <?php foreach ($orders as $order): ?>

                <?php

                $orderStatus =
                    trim(
                        $order['order_status']
                        ?? 'pending'
                    );

                $paymentStatus =
                    trim(
                        $order['payment_status']
                        ?? 'pending'
                    );

                $createdDate =
                    !empty($order['created_at'])
                    ? date(
                        'd M Y, h:i A',
                        strtotime(
                            $order['created_at']
                        )
                    )
                    : '';

                ?>



                <article class="order-card">


                    <!-- ORDER TOP -->

                    <div class="order-top">

                        <div>

                            <div class="order-number">

                                #<?= e($order['order_number']) ?>

                            </div>

                            <div class="order-date">

                                <?= e($createdDate) ?>

                            </div>

                        </div>


                        <div>

                            <span
                                class="order-status <?= e(statusClass($orderStatus)) ?>"
                            >

                                <?= e(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $orderStatus
                                        )
                                    )
                                ) ?>

                            </span>

                        </div>

                    </div>



                    <!-- DETAILS -->

                    <div class="order-details">


                        <!-- TOTAL -->

                        <div>

                            <div class="detail-label">
                                Order Total
                            </div>

                            <div class="detail-value">

                                ₹<?= number_format(
                                    (float)$order['total_amount'],
                                    2
                                ) ?>

                            </div>

                        </div>


                        <!-- PAYMENT -->

                        <div>

                            <div class="detail-label">
                                Payment
                            </div>

                            <div class="detail-value">

                                <?= e(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $order['payment_method']
                                            ?? 'N/A'
                                        )
                                    )
                                ) ?>

                                <br>

                                <span
                                    class="payment-status <?= e(paymentClass($paymentStatus)) ?>"
                                >

                                    <?= e(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $paymentStatus
                                            )
                                        )
                                    ) ?>

                                </span>

                            </div>

                        </div>


                        <!-- PHONE -->

                        <div>

                            <div class="detail-label">
                                Phone
                            </div>

                            <div class="detail-value">

                                <?= e(
                                    $order['phone']
                                    ?? 'N/A'
                                ) ?>

                            </div>

                        </div>


                        <!-- ORDER ID -->

                        <div>

                            <div class="detail-label">
                                Order ID
                            </div>

                            <div class="detail-value">

                                #<?= e(
                                    $order['id']
                                ) ?>

                            </div>

                        </div>

                    </div>



                    <!-- BOTTOM -->

                    <div class="order-bottom">


                        <div class="delivery">

                            <strong>
                                Delivery:
                            </strong>

                            <?= e(
                                $order['delivery_address']
                                ?? 'Address not available'
                            ) ?>

                            <?php if (
                                !empty($order['notes'])
                            ): ?>

                                <br><br>

                                <strong>
                                    Note:
                                </strong>

                                <?= e(
                                    $order['notes']
                                ) ?>

                            <?php endif; ?>

                        </div>


                        <div class="total-box">

                            <div class="total-label">
                                Total Paid / Due
                            </div>

                            <div class="total-price">

                                ₹<?= number_format(
                                    (float)$order['total_amount'],
                                    2
                                ) ?>

                            </div>

                        </div>

                    </div>


                </article>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- EMPTY ORDERS -->

            <div class="empty-orders">

                <div class="empty-icon">
                    ◇
                </div>

                <h3>
                    No orders yet.
                </h3>

                <p>
                    Your next great meal is waiting for you.
                </p>

                <a
                    href="menu.php"
                    class="menu-btn"
                >
                    Explore Menu
                </a>

            </div>


        <?php endif; ?>


    </div>

</section>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <strong>
        Ember &amp; Plate
    </strong>

    <br><br>

    Fine food. Warm memories. Honest experiences.

    <br><br>

    &copy; <?= date('Y') ?>
    Ember &amp; Plate.
    All rights reserved.

</footer>


</body>

</html>