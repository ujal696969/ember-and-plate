<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// =====================================================
// ADMIN LOGIN CHECK
// =====================================================

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}


// =====================================================
// GET ORDER ID
// =====================================================

$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}


// =====================================================
// GET ORDER
// =====================================================

$stmt = $conn->prepare("
    SELECT
        o.id,
        o.order_number,
        o.user_id,
        o.total_amount,
        o.delivery_address,
        o.phone,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.notes,
        o.created_at,
        u.name AS customer_name,
        u.email AS customer_email
    FROM orders o
    LEFT JOIN users u
        ON o.user_id = u.id
    WHERE o.id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "i",
    $orderId
);

$stmt->execute();

$result = $stmt->get_result();

$order = $result->fetch_assoc();

$stmt->close();


if (!$order) {
    header("Location: orders.php");
    exit;
}


// =====================================================
// GET ORDER ITEMS
// =====================================================

$items = [];

$stmt = $conn->prepare("
    SELECT
        id,
        order_id,
        product_id,
        product_name,
        quantity,
        price,
        subtotal
    FROM order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "i",
    $orderId
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$stmt->close();


// =====================================================
// CALCULATE ITEM TOTAL
// =====================================================

$itemsTotal = 0;

foreach ($items as $item) {

    $itemsTotal +=
        (float)$item['subtotal'];
}


// =====================================================
// STATUS CLASS
// =====================================================

function statusClass($status)
{
    return match ($status) {

        'pending' =>
            'pending',

        'confirmed' =>
            'confirmed',

        'preparing' =>
            'preparing',

        'out_for_delivery' =>
            'delivery',

        'delivered' =>
            'delivered',

        'cancelled' =>
            'cancelled',

        default =>
            'pending'
    };
}


function readableStatus($status)
{
    return ucwords(
        str_replace(
            '_',
            ' ',
            $status
        )
    );
}


function readablePayment($payment)
{
    return ucwords(
        str_replace(
            '_',
            ' ',
            $payment
        )
    );
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
        Order Details | Ember & Plate
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            min-height: 100vh;

            background:
                radial-gradient(
                    circle at top left,
                    rgba(217,173,88,0.07),
                    transparent 30%
                ),
                #080808;

            color: #fff;

            font-family:
                Arial,
                Helvetica,
                sans-serif;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;

            width: 245px;
            height: 100vh;

            padding: 28px 18px;

            background: #111;

            border-right:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            z-index: 100;
        }


        .brand {

            padding:
                0 12px 25px;

            border-bottom:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            margin-bottom: 25px;
        }


        .brand h2 {

            color: #d9ad58;

            font-family:
                Georgia,
                serif;

            font-size: 24px;
        }


        .brand span {

            display: block;

            margin-top: 5px;

            color: #666;

            font-size: 11px;

            letter-spacing: 2px;

            text-transform: uppercase;
        }


        .nav-title {

            color: #555;

            font-size: 10px;

            letter-spacing: 2px;

            text-transform: uppercase;

            padding: 0 12px;

            margin-bottom: 10px;
        }


        .sidebar a {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 12px;

            margin-bottom: 4px;

            border-radius: 10px;

            color: #aaa;

            text-decoration: none;

            font-size: 14px;

            transition: .2s;
        }


        .sidebar a:hover,
        .sidebar a.active {

            background:
                rgba(
                    217,
                    173,
                    88,
                    0.10
                );

            color: #d9ad58;
        }


        .icon {

            width: 24px;

            text-align: center;

            font-size: 16px;
        }


        .logout {

            position: absolute;

            bottom: 25px;

            left: 18px;
            right: 18px;
        }


        /* =================================================
           MAIN
        ================================================= */

        .main {

            margin-left: 245px;

            min-height: 100vh;

            padding: 35px;
        }


        /* =================================================
           TOP
        ================================================= */

        .topbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
        }


        .title h1 {

            font-family:
                Georgia,
                serif;

            font-size: 32px;

            font-weight: 500;
        }


        .title p {

            color: #777;

            margin-top: 7px;

            font-size: 13px;
        }


        .back-btn {

            display: inline-block;

            padding:
                11px 16px;

            border:
                1px solid
                rgba(
                    217,
                    173,
                    88,
                    0.25
                );

            border-radius: 10px;

            color: #d9ad58;

            text-decoration: none;

            font-size: 13px;
        }


        /* =================================================
           ORDER HEADER CARD
        ================================================= */

        .order-summary {

            display: grid;

            grid-template-columns:
                1.5fr
                1fr
                1fr;

            gap: 18px;

            margin-bottom: 20px;
        }


        .summary-card {

            padding: 22px;

            background: #111;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

            border-radius: 16px;
        }


        .label {

            color: #666;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 8px;
        }


        .value {

            color: #eee;

            font-size: 14px;

            line-height: 1.7;
        }


        .order-number {

            color: #fff;

            font-size: 18px;

            font-weight: 700;
        }


        .date {

            color: #666;

            font-size: 11px;

            margin-top: 5px;
        }


        .amount {

            color: #d9ad58;

            font-size: 27px;

            font-weight: 700;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status {

            display: inline-block;

            padding:
                7px 11px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;
        }


        .status.pending {

            color: #d9ad58;

            background:
                rgba(
                    217,
                    173,
                    88,
                    0.12
                );
        }


        .status.confirmed {

            color: #80b5ff;

            background:
                rgba(
                    80,
                    150,
                    255,
                    0.12
                );
        }


        .status.preparing {

            color: #c49cff;

            background:
                rgba(
                    180,
                    100,
                    255,
                    0.12
                );
        }


        .status.delivery {

            color: #6edab8;

            background:
                rgba(
                    50,
                    190,
                    150,
                    0.12
                );
        }


        .status.delivered {

            color: #7ce08b;

            background:
                rgba(
                    70,
                    190,
                    90,
                    0.12
                );
        }


        .status.cancelled {

            color: #ff8f83;

            background:
                rgba(
                    239,
                    91,
                    72,
                    0.12
                );
        }


        /* =================================================
           CUSTOMER
        ================================================= */

        .customer-card {

            display: grid;

            grid-template-columns:
                1fr 1fr 1fr;

            gap: 20px;

            padding: 23px;

            margin-bottom: 20px;

            background: #111;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

            border-radius: 16px;
        }


        /* =================================================
           ITEMS
        ================================================= */

        .panel {

            background: #111;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

            border-radius: 18px;

            overflow: hidden;

            margin-bottom: 20px;
        }


        .panel-header {

            padding:
                21px 23px;

            border-bottom:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );
        }


        .panel-header h2 {

            font-family:
                Georgia,
                serif;

            font-size: 21px;

            font-weight: 500;
        }


        .table-wrap {

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse:
                collapse;

            min-width: 650px;
        }


        th {

            padding:
                14px 22px;

            text-align: left;

            color: #666;

            font-size: 10px;

            text-transform:
                uppercase;

            letter-spacing:
                1px;
        }


        td {

            padding:
                18px 22px;

            border-top:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.05
                );

            color: #ccc;

            font-size: 13px;
        }


        .food-name {

            color: #fff;

            font-weight: 700;

            font-size: 14px;
        }


        .food-id {

            color: #666;

            font-size: 10px;

            margin-top: 4px;
        }


        .price {

            color: #d9ad58;

            font-weight: 700;
        }


        .subtotal {

            color: #fff;

            font-weight: 700;
        }


        /* =================================================
           TOTAL
        ================================================= */

        .total-box {

            display: flex;

            justify-content:
                flex-end;

            padding:
                20px 23px;
        }


        .total-inner {

            width: 320px;
        }


        .total-row {

            display: flex;

            justify-content:
                space-between;

            padding:
                10px 0;

            color: #999;

            font-size: 13px;
        }


        .total-row.final {

            margin-top: 7px;

            padding-top: 16px;

            border-top:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.10
                );

            color: #fff;

            font-size: 19px;

            font-weight: 700;
        }


        .total-row.final span:last-child {

            color: #d9ad58;
        }


        /* =================================================
           EXTRA INFO
        ================================================= */

        .extra-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 20px;
        }


        .extra-card {

            padding: 23px;

            background: #111;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

            border-radius: 16px;
        }


        .extra-value {

            color: #ddd;

            font-size: 13px;

            line-height: 1.7;
        }


        .empty {

            padding:
                50px 20px;

            text-align:
                center;

            color: #666;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media(max-width: 900px) {

            .order-summary {

                grid-template-columns:
                    1fr 1fr;
            }


            .customer-card {

                grid-template-columns:
                    1fr 1fr;
            }

        }


        @media(max-width: 800px) {

            .sidebar {

                width: 70px;

                padding:
                    20px 10px;
            }


            .brand {

                padding:
                    0 0 20px;

                text-align:
                    center;
            }


            .brand h2 {

                font-size: 0;
            }


            .brand h2::after {

                content: "E";

                font-size: 26px;
            }


            .brand span,
            .nav-title,
            .sidebar a span:not(.icon) {

                display: none;
            }


            .sidebar a {

                justify-content:
                    center;

                padding:
                    13px 5px;
            }


            .logout {

                left: 10px;
                right: 10px;
            }


            .main {

                margin-left: 70px;

                padding:
                    25px 18px;
            }

        }


        @media(max-width: 600px) {

            .topbar {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .order-summary {

                grid-template-columns:
                    1fr;
            }


            .customer-card {

                grid-template-columns:
                    1fr;
            }


            .extra-grid {

                grid-template-columns:
                    1fr;
            }


            .main {

                padding:
                    22px 12px;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">


    <div class="brand">

        <h2>
            Ember & Plate
        </h2>

        <span>
            Admin Panel
        </span>

    </div>


    <div class="nav-title">
        Management
    </div>


    <a href="dashboard.php">

        <span class="icon">◈</span>

        <span>Dashboard</span>

    </a>


    <a
        href="orders.php"
        class="active"
    >

        <span class="icon">▣</span>

        <span>Orders</span>

    </a>


    <a href="products.php">

        <span class="icon">◉</span>

        <span>Products</span>

    </a>


    <a href="customers.php">

        <span class="icon">♙</span>

        <span>Customers</span>

    </a>


    <a href="reviews.php">

        <span class="icon">★</span>

        <span>Reviews</span>

    </a>


    <a href="contact-messages.php">

        <span class="icon">✉</span>

        <span>Messages</span>

    </a>


    <div class="logout">

        <a href="logout.php">

            <span class="icon">↪</span>

            <span>Logout</span>

        </a>

    </div>


</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <div class="topbar">


        <div class="title">

            <h1>
                Order Details
            </h1>

            <p>
                Complete information about this customer order.
            </p>

        </div>


        <a
            href="orders.php"
            class="back-btn"
        >
            ← Back to Orders
        </a>


    </div>


    <!-- =================================================
         ORDER SUMMARY
    ================================================= -->

    <div class="order-summary">


        <div class="summary-card">

            <div class="label">
                Order Number
            </div>

            <div class="order-number">

                #<?= htmlspecialchars(
                    $order['order_number']
                ) ?>

            </div>

            <div class="date">

                <?= date(
                    'd M Y, h:i A',
                    strtotime(
                        $order['created_at']
                    )
                ) ?>

            </div>

        </div>


        <div class="summary-card">

            <div class="label">
                Order Status
            </div>

            <span
                class="status
                <?= statusClass(
                    $order['order_status']
                ) ?>"
            >

                <?= htmlspecialchars(
                    readableStatus(
                        $order['order_status']
                    )
                ) ?>

            </span>

        </div>


        <div class="summary-card">

            <div class="label">
                Order Total
            </div>

            <div class="amount">

                ₹<?= number_format(
                    (float)$order['total_amount'],
                    2
                ) ?>

            </div>

        </div>


    </div>


    <!-- =================================================
         CUSTOMER INFORMATION
    ================================================= -->

    <div class="customer-card">


        <div>

            <div class="label">
                Customer
            </div>

            <div class="value">

                <strong>

                    <?= htmlspecialchars(
                        $order['customer_name']
                        ?: 'Guest Customer'
                    ) ?>

                </strong>

            </div>

        </div>


        <div>

            <div class="label">
                Phone
            </div>

            <div class="value">

                <?= htmlspecialchars(
                    $order['phone']
                ) ?>

            </div>

        </div>


        <div>

            <div class="label">
                Email
            </div>

            <div class="value">

                <?= htmlspecialchars(
                    $order['customer_email']
                    ?: 'Not available'
                ) ?>

            </div>

        </div>


    </div>


    <!-- =================================================
         DELIVERY + PAYMENT
    ================================================= -->

    <div class="extra-grid">


        <div class="extra-card">

            <div class="label">
                Delivery Address
            </div>

            <div class="extra-value">

                <?= nl2br(
                    htmlspecialchars(
                        $order[
                            'delivery_address'
                        ]
                    )
                ) ?>

            </div>

        </div>


        <div class="extra-card">

            <div class="label">
                Payment
            </div>

            <div class="extra-value">

                <strong>

                    <?= htmlspecialchars(
                        readablePayment(
                            $order[
                                'payment_method'
                            ]
                        )
                    ) ?>

                </strong>

                <br>

                Payment Status:

                <?= htmlspecialchars(
                    ucfirst(
                        $order[
                            'payment_status'
                        ]
                    )
                ) ?>

            </div>

        </div>


    </div>


    <br>


    <!-- =================================================
         ORDER ITEMS
    ================================================= -->

    <div class="panel">


        <div class="panel-header">

            <h2>
                Ordered Items
            </h2>

        </div>


        <?php if (!empty($items)): ?>


            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Item
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Quantity
                            </th>

                            <th>
                                Subtotal
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $items
                        as $item
                    ): ?>


                        <tr>


                            <td>

                                <div class="food-name">

                                    <?= htmlspecialchars(
                                        $item[
                                            'product_name'
                                        ]
                                    ) ?>

                                </div>


                                <div class="food-id">

                                    Product ID:
                                    <?= (int)
                                        $item[
                                            'product_id'
                                        ] ?>

                                </div>

                            </td>


                            <td>

                                <span class="price">

                                    ₹<?= number_format(
                                        (float)
                                        $item['price'],
                                        2
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                ×
                                <?= (int)
                                    $item['quantity'] ?>

                            </td>


                            <td>

                                <span class="subtotal">

                                    ₹<?= number_format(
                                        (float)
                                        $item['subtotal'],
                                        2
                                    ) ?>

                                </span>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


            <!-- TOTAL -->

            <div class="total-box">


                <div class="total-inner">


                    <div class="total-row">

                        <span>
                            Items Total
                        </span>

                        <span>

                            ₹<?= number_format(
                                $itemsTotal,
                                2
                            ) ?>

                        </span>

                    </div>


                    <div class="total-row">

                        <span>
                            Delivery
                        </span>

                        <span>
                            Free
                        </span>

                    </div>


                    <div class="total-row final">

                        <span>
                            Total
                        </span>

                        <span>

                            ₹<?= number_format(
                                (float)
                                $order[
                                    'total_amount'
                                ],
                                2
                            ) ?>

                        </span>

                    </div>


                </div>

            </div>


        <?php else: ?>


            <div class="empty">

                No order items found.

            </div>


        <?php endif; ?>


    </div>


    <!-- =================================================
         NOTES
    ================================================= -->

    <?php if (
        !empty(
            trim(
                $order['notes'] ?? ''
            )
        )
    ): ?>


        <div class="extra-card">

            <div class="label">
                Order Notes
            </div>

            <div class="extra-value">

                <?= nl2br(
                    htmlspecialchars(
                        $order['notes']
                    )
                ) ?>

            </div>

        </div>


    <?php endif; ?>


</main>


</body>

</html>