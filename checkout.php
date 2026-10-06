<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// =====================================================
// CART CHECK
// =====================================================

if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    header("Location: cart.php");
    exit;
}


// =====================================================
// CART DATA
// =====================================================

$cart = $_SESSION['cart'];

$cartItems = [];
$subtotal = 0;


// =====================================================
// GET PRODUCTS FROM DATABASE
// =====================================================

foreach ($cart as $productId => $quantity) {

    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            description,
            price,
            image
        FROM products
        WHERE id = ?
        AND is_available = 1
        LIMIT 1
    ");

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $productId);

    $stmt->execute();

    $result = $stmt->get_result();

    $product = $result->fetch_assoc();

    $stmt->close();


    if (!$product) {
        continue;
    }


    $price = (float)$product['price'];

    $itemTotal = $price * $quantity;

    $subtotal += $itemTotal;


    $cartItems[] = [
        'id' => (int)$product['id'],
        'name' => $product['name'],
        'description' => $product['description'] ?? '',
        'price' => $price,
        'quantity' => $quantity,
        'total' => $itemTotal,
        'image' => $product['image'] ?? ''
    ];
}


// =====================================================
// IF NO VALID PRODUCTS
// =====================================================

if (empty($cartItems)) {

    unset($_SESSION['cart']);

    header("Location: menu.php");
    exit;
}


// =====================================================
// DELIVERY
// =====================================================

$deliveryCharge = 0;


// =====================================================
// FINAL TOTAL
// =====================================================

$totalAmount = $subtotal + $deliveryCharge;


// =====================================================
// CART COUNT
// =====================================================

$cartCount = 0;

foreach ($cart as $qty) {
    $cartCount += (int)$qty;
}


// =====================================================
// USER DATA
// =====================================================

$userName = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$userPhone = $_SESSION['user_phone'] ?? '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout | Ember & Plate</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            background:
                radial-gradient(
                    circle at top left,
                    rgba(213, 166, 73, 0.08),
                    transparent 35%
                ),
                #080808;

            color: #fff;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            min-height: 100vh;
        }


        /* =================================================
           NAVBAR
        ================================================= */

        nav {

            height: 75px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 6%;

            background: rgba(10,10,10,0.95);

            border-bottom:
                1px solid
                rgba(255,255,255,0.08);

            position: sticky;

            top: 0;

            z-index: 100;
        }


        .logo {

            color: #d9ad58;

            font-family: Georgia, serif;

            font-size: 25px;

            font-weight: 700;

            text-decoration: none;
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

            transition: 0.25s;
        }


        .nav-links a:hover {

            color: #d9ad58;
        }


        .cart-link {

            color: #d9ad58 !important;

            font-weight: 700;
        }


        .cart-count {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 22px;

            height: 22px;

            padding: 0 6px;

            margin-left: 4px;

            border-radius: 50%;

            background: #d9ad58;

            color: #111;

            font-size: 11px;

            font-weight: 800;
        }


        /* =================================================
           MAIN
        ================================================= */

        .container {

            width: 92%;

            max-width: 1200px;

            margin: 55px auto 80px;
        }


        .page-title {

            margin-bottom: 35px;
        }


        .page-title span {

            color: #d9ad58;

            font-size: 13px;

            letter-spacing: 3px;

            text-transform: uppercase;
        }


        .page-title h1 {

            margin-top: 10px;

            font-family: Georgia, serif;

            font-size: 45px;

            font-weight: 500;
        }


        /* =================================================
           GRID
        ================================================= */

        .checkout-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1.4fr)
                minmax(320px, 0.8fr);

            gap: 28px;

            align-items: start;
        }


        .card {

            background: #111;

            border:
                1px solid
                rgba(217,173,88,0.25);

            border-radius: 20px;

            padding: 30px;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,0.25);
        }


        .card h2 {

            font-family: Georgia, serif;

            font-size: 27px;

            font-weight: 500;

            margin-bottom: 25px;
        }


        /* =================================================
           FORM
        ================================================= */

        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 18px;
        }


        .form-group {

            margin-bottom: 18px;
        }


        .full {

            grid-column: 1 / -1;
        }


        label {

            display: block;

            margin-bottom: 8px;

            color: #ddd;

            font-size: 14px;

            font-weight: 600;
        }


        .required {

            color: #d9ad58;
        }


        input,
        textarea,
        select {

            width: 100%;

            border: 1px solid #333;

            background: #090909;

            color: #fff;

            border-radius: 12px;

            padding: 15px;

            outline: none;

            font-size: 14px;

            transition: 0.25s;
        }


        input:focus,
        textarea:focus,
        select:focus {

            border-color: #d9ad58;

            box-shadow:
                0 0 0 3px
                rgba(217,173,88,0.08);
        }


        textarea {

            min-height: 120px;

            resize: vertical;
        }


        select {

            cursor: pointer;
        }


        select option {

            background: #111;

            color: #fff;
        }


        /* =================================================
           ORDER ITEMS
        ================================================= */

        .order-item {

            display: flex;

            gap: 15px;

            padding: 17px 0;

            border-bottom:
                1px solid
                rgba(255,255,255,0.08);
        }


        .order-item:first-child {

            padding-top: 0;
        }


        .order-item:last-child {

            border-bottom: none;
        }


        .food-image {

            width: 70px;

            height: 70px;

            flex-shrink: 0;

            border-radius: 12px;

            object-fit: cover;

            background: #222;
        }


        .item-info {

            flex: 1;
        }


        .item-name {

            font-size: 15px;

            font-weight: 700;

            margin-bottom: 7px;
        }


        .item-qty {

            color: #999;

            font-size: 13px;
        }


        .item-price {

            color: #d9ad58;

            font-weight: 700;

            white-space: nowrap;
        }


        /* =================================================
           TOTAL
        ================================================= */

        .summary-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            color: #ccc;

            font-size: 15px;
        }


        .summary-row.total {

            margin-top: 10px;

            padding-top: 20px;

            border-top:
                1px solid
                rgba(255,255,255,0.15);

            color: #fff;

            font-size: 21px;

            font-weight: 700;
        }


        .gold {

            color: #d9ad58 !important;
        }


        /* =================================================
           PLACE ORDER
        ================================================= */

        .place-order {

            width: 100%;

            margin-top: 25px;

            padding: 17px;

            border: none;

            border-radius: 12px;

            background: #d9ad58;

            color: #080808;

            font-size: 16px;

            font-weight: 800;

            cursor: pointer;

            transition: 0.25s;
        }


        .place-order:hover {

            transform: translateY(-2px);

            box-shadow:
                0 12px 30px
                rgba(217,173,88,0.25);
        }


        .secure-note {

            margin-top: 15px;

            text-align: center;

            color: #777;

            font-size: 12px;

            line-height: 1.6;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media(max-width: 900px) {

            .checkout-grid {

                grid-template-columns: 1fr;
            }

        }


        @media(max-width: 650px) {

            nav {

                height: auto;

                padding: 18px 5%;

                align-items: flex-start;

                gap: 15px;

                flex-direction: column;
            }


            .nav-links {

                width: 100%;

                overflow-x: auto;

                padding-bottom: 5px;

                white-space: nowrap;
            }


            .page-title h1 {

                font-size: 35px;
            }


            .form-grid {

                grid-template-columns: 1fr;
            }


            .full {

                grid-column: auto;
            }


            .card {

                padding: 22px;
            }
        }

    </style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav>

    <a
        href="index.php"
        class="logo"
    >
        Ember & Plate
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

        <a href="my-orders.php">
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

        <a href="logout.php">
            Logout
        </a>

    </div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="container">


    <div class="page-title">

        <span>
            Ember & Plate
        </span>

        <h1>
            Checkout
        </h1>

    </div>


    <div class="checkout-grid">


        <!-- =================================================
             CUSTOMER INFORMATION
        ================================================= -->

        <div class="card">

            <h2>
                Delivery Details
            </h2>


            <form
                action="order-success.php"
                method="POST"
                id="checkoutForm"
            >


                <div class="form-grid">


                    <!-- NAME -->

                    <div class="form-group">

                        <label>
                            Full Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="<?= htmlspecialchars($userName) ?>"
                            placeholder="Enter your full name"
                            required
                        >

                    </div>


                    <!-- PHONE -->

                    <div class="form-group">

                        <label>
                            Phone Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            value="<?= htmlspecialchars($userPhone) ?>"
                            placeholder="10 digit mobile number"
                            pattern="[6-9][0-9]{9}"
                            maxlength="10"
                            required
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group full">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="<?= htmlspecialchars($userEmail) ?>"
                            placeholder="your@email.com"
                        >

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group full">

                        <label>
                            Delivery Address
                            <span class="required">*</span>
                        </label>

                        <textarea
                            name="address"
                            placeholder="House / Flat, Street, Area, City, PIN"
                            required
                        ></textarea>

                    </div>


                    <!-- PAYMENT -->

                    <div class="form-group">

                        <label>
                            Payment Method
                        </label>

                        <select
                            name="payment_method"
                            required
                        >

                            <option value="cash_on_delivery">
                                Cash on Delivery
                            </option>

                            <option value="online">
                                Online Payment
                            </option>

                        </select>

                    </div>


                    <!-- NOTES -->

                    <div class="form-group">

                        <label>
                            Order Notes
                        </label>

                        <input
                            type="text"
                            name="notes"
                            placeholder="Any special instructions? (Optional)"
                        >

                    </div>


                </div>


                <button
                    type="submit"
                    class="place-order"
                >

                    Place Order —
                    ₹<?= number_format($totalAmount, 2) ?>

                </button>


                <div class="secure-note">

                    Your order total is calculated directly
                    from the current product prices.

                </div>


            </form>

        </div>


        <!-- =================================================
             ORDER SUMMARY
        ================================================= -->

        <div class="card">

            <h2>
                Your Order
            </h2>


            <?php foreach ($cartItems as $item): ?>

                <?php

                $image = trim($item['image']);

                if ($image === '') {

                    $image =
                        '../assets/images/chicken-biryani.jpg';

                } elseif (
                    strpos($image, 'http://') !== 0 &&
                    strpos($image, 'https://') !== 0
                ) {

                    $image =
                        '../assets/images/' .
                        ltrim($image, '/');
                }

                ?>


                <div class="order-item">


                    <img
                        src="<?= htmlspecialchars($image) ?>"
                        alt="<?= htmlspecialchars($item['name']) ?>"
                        class="food-image"
                        onerror="this.src='../assets/images/chicken-biryani.jpg'"
                    >


                    <div class="item-info">

                        <div class="item-name">

                            <?= htmlspecialchars($item['name']) ?>

                        </div>


                        <div class="item-qty">

                            × <?= $item['quantity'] ?>

                            × ₹<?= number_format($item['price'], 2) ?>

                        </div>

                    </div>


                    <div class="item-price">

                        ₹<?= number_format($item['total'], 2) ?>

                    </div>


                </div>


            <?php endforeach; ?>


            <!-- SUBTOTAL -->

            <div class="summary-row">

                <span>
                    Subtotal
                </span>

                <span>
                    ₹<?= number_format($subtotal, 2) ?>
                </span>

            </div>


            <!-- DELIVERY -->

            <div class="summary-row">

                <span>
                    Delivery
                </span>

                <span>
                    <?= $deliveryCharge > 0
                        ? '₹' . number_format($deliveryCharge, 2)
                        : 'Free'
                    ?>
                </span>

            </div>


            <!-- TOTAL -->

            <div class="summary-row total">

                <span>
                    Total
                </span>

                <span class="gold">

                    ₹<?= number_format($totalAmount, 2) ?>

                </span>

            </div>


        </div>


    </div>


</main>


</body>

</html>