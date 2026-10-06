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
// UPDATE ORDER STATUS
// =====================================================

$statusMessage = '';
$statusError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['order_status'] ?? '');

    $allowedStatuses = [
        'pending',
        'confirmed',
        'preparing',
        'out_for_delivery',
        'delivered',
        'cancelled'
    ];

    if ($orderId <= 0) {

        $statusError = "Invalid order.";

    } elseif (!in_array($newStatus, $allowedStatuses, true)) {

        $statusError = "Invalid order status.";

    } else {

        $stmt = $conn->prepare("
            UPDATE orders
            SET order_status = ?
            WHERE id = ?
        ");

        if ($stmt) {

            $stmt->bind_param(
                "si",
                $newStatus,
                $orderId
            );

            if ($stmt->execute()) {

                $statusMessage =
                    "Order status updated successfully.";

            } else {

                $statusError =
                    "Could not update order status.";
            }

            $stmt->close();

        } else {

            $statusError =
                "Database error: " . $conn->error;
        }
    }
}


// =====================================================
// FILTER
// =====================================================

$filter = trim($_GET['status'] ?? 'all');

$allowedFilters = [
    'all',
    'pending',
    'confirmed',
    'preparing',
    'out_for_delivery',
    'delivered',
    'cancelled'
];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}


// =====================================================
// GET ORDERS
// =====================================================

$orders = [];

if ($filter === 'all') {

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
        ORDER BY o.created_at DESC
    ");

} else {

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
        WHERE o.order_status = ?
        ORDER BY o.created_at DESC
    ");

    $stmt->bind_param(
        "s",
        $filter
    );
}


if ($stmt) {

    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $orders[] = $row;
    }

    $stmt->close();
}


// =====================================================
// STATUS COUNTS
// =====================================================

$statusCounts = [
    'all' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'preparing' => 0,
    'out_for_delivery' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

$result = $conn->query("
    SELECT
        order_status,
        COUNT(*) AS total
    FROM orders
    GROUP BY order_status
");

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $status = $row['order_status'];

        $count = (int)$row['total'];

        $statusCounts['all'] += $count;

        if (isset($statusCounts[$status])) {
            $statusCounts[$status] = $count;
        }
    }
}


// =====================================================
// HELPERS
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
        Orders | Ember & Plate Admin
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

            transition: 0.2s;
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
           HEADER
        ================================================= */

        .topbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 30px;
        }


        .title h1 {

            font-family:
                Georgia,
                serif;

            font-size: 34px;

            font-weight: 500;
        }


        .title p {

            color: #777;

            margin-top: 7px;

            font-size: 13px;
        }


        .back-btn {

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
           ALERT
        ================================================= */

        .alert {

            padding: 14px 17px;

            border-radius: 11px;

            margin-bottom: 20px;

            font-size: 13px;
        }


        .success {

            background:
                rgba(
                    70,
                    190,
                    90,
                    0.10
                );

            border:
                1px solid
                rgba(
                    70,
                    190,
                    90,
                    0.25
                );

            color: #8ee19b;
        }


        .error {

            background:
                rgba(
                    239,
                    91,
                    72,
                    0.10
                );

            border:
                1px solid
                rgba(
                    239,
                    91,
                    72,
                    0.25
                );

            color: #ffaaa0;
        }


        /* =================================================
           FILTERS
        ================================================= */

        .filters {

            display: flex;

            gap: 9px;

            flex-wrap: wrap;

            margin-bottom: 25px;
        }


        .filter {

            padding:
                10px 14px;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            border-radius: 10px;

            color: #999;

            text-decoration: none;

            font-size: 12px;

            transition: 0.2s;
        }


        .filter:hover,
        .filter.active {

            color: #080808;

            background: #d9ad58;

            border-color: #d9ad58;
        }


        .filter strong {

            margin-left: 5px;
        }


        /* =================================================
           ORDER CARDS
        ================================================= */

        .orders {

            display: grid;

            gap: 18px;
        }


        .order-card {

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
        }


        .order-head {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            padding: 20px 23px;

            border-bottom:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );
        }


        .order-number {

            color: #fff;

            font-size: 16px;

            font-weight: 700;
        }


        .order-date {

            margin-top: 6px;

            color: #666;

            font-size: 11px;
        }


        .status {

            display: inline-block;

            padding:
                7px 11px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .5px;
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


        .order-body {

            padding: 23px;

            display: grid;

            grid-template-columns:
                1.4fr
                1fr
                1fr;

            gap: 22px;
        }


        .info-label {

            color: #666;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 7px;
        }


        .info-value {

            color: #ddd;

            font-size: 13px;

            line-height: 1.6;
        }


        .amount {

            color: #d9ad58;

            font-size: 22px;

            font-weight: 700;
        }


        .order-footer {

            padding:
                18px 23px;

            border-top:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            flex-wrap: wrap;
        }


        .payment {

            color: #999;

            font-size: 12px;
        }


        .payment strong {

            color: #ddd;
        }


        /* =================================================
           STATUS FORM
        ================================================= */

        .status-form {

            display: flex;

            align-items: center;

            gap: 8px;
        }


        .status-form select {

            min-width: 170px;

            padding:
                10px 12px;

            border:
                1px solid #333;

            border-radius: 9px;

            background: #080808;

            color: #ddd;

            outline: none;

            cursor: pointer;
        }


        .status-form select:focus {

            border-color:
                #d9ad58;
        }


        .update-btn {

            padding:
                10px 14px;

            border: none;

            border-radius: 9px;

            background: #d9ad58;

            color: #080808;

            font-weight: 800;

            cursor: pointer;
        }


        .update-btn:hover {

            opacity: .9;
        }


        /* =================================================
           EMPTY
        ================================================= */

        .empty {

            padding: 70px 20px;

            text-align: center;

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

            color: #666;
        }


        .empty-icon {

            font-size: 38px;

            margin-bottom: 12px;

            color: #d9ad58;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media(max-width: 1000px) {

            .order-body {

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

                text-align: center;
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

                justify-content: center;

                padding: 13px 5px;
            }


            .logout {

                left: 10px;

                right: 10px;
            }


            .main {

                margin-left: 70px;

                padding: 25px 18px;
            }

        }


        @media(max-width: 600px) {

            .topbar {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .title h1 {

                font-size: 28px;
            }


            .order-head {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .order-body {

                grid-template-columns:
                    1fr;
            }


            .status-form {

                width: 100%;

                flex-direction:
                    column;

                align-items:
                    stretch;
            }


            .status-form select,
            .update-btn {

                width: 100%;
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

        <span class="icon">
            ◈
        </span>

        <span>
            Dashboard
        </span>

    </a>


    <a
        href="orders.php"
        class="active"
    >

        <span class="icon">
            ▣
        </span>

        <span>
            Orders
        </span>

    </a>


    <a href="products.php">

        <span class="icon">
            ◉
        </span>

        <span>
            Products
        </span>

    </a>


    <a href="customers.php">

        <span class="icon">
            ♙
        </span>

        <span>
            Customers
        </span>

    </a>


    <a href="reviews.php">

        <span class="icon">
            ★
        </span>

        <span>
            Reviews
        </span>

    </a>


    <a href="contact-messages.php">

        <span class="icon">
            ✉
        </span>

        <span>
            Messages
        </span>

    </a>


    <div class="logout">

        <a href="logout.php">

            <span class="icon">
                ↪
            </span>

            <span>
                Logout
            </span>

        </a>

    </div>


</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


    <!-- HEADER -->

    <div class="topbar">


        <div class="title">

            <h1>
                Orders
            </h1>

            <p>
                Manage customer orders and update delivery status.
            </p>

        </div>


        <a
            href="dashboard.php"
            class="back-btn"
        >
            ← Dashboard
        </a>


    </div>


    <!-- =================================================
         ALERTS
    ================================================= -->

    <?php if ($statusMessage !== ''): ?>

        <div class="alert success">

            ✓
            <?= htmlspecialchars($statusMessage) ?>

        </div>

    <?php endif; ?>


    <?php if ($statusError !== ''): ?>

        <div class="alert error">

            ⚠
            <?= htmlspecialchars($statusError) ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         FILTERS
    ================================================= -->

    <div class="filters">


        <a
            href="orders.php?status=all"
            class="filter <?= $filter === 'all'
                ? 'active'
                : '' ?>"
        >

            All

            <strong>
                <?= $statusCounts['all'] ?>
            </strong>

        </a>


        <a
            href="orders.php?status=pending"
            class="filter <?= $filter === 'pending'
                ? 'active'
                : '' ?>"
        >

            Pending

            <strong>
                <?= $statusCounts['pending'] ?>
            </strong>

        </a>


        <a
            href="orders.php?status=confirmed"
            class="filter <?= $filter === 'confirmed'
                ? 'active'
                : '' ?>"
        >

            Confirmed

            <strong>
                <?= $statusCounts['confirmed'] ?>
            </strong>

        </a>


        <a
            href="orders.php?status=preparing"
            class="filter <?= $filter === 'preparing'
                ? 'active'
                : '' ?>"
        >

            Preparing

            <strong>
                <?= $statusCounts['preparing'] ?>
            </strong>

        </a>


        <a
            href="orders.php?status=out_for_delivery"
            class="filter <?= $filter === 'out_for_delivery'
                ? 'active'
                : '' ?>"
        >

            Out for Delivery

            <strong>
                <?= $statusCounts['out_for_delivery'] ?>
            </strong>

        </a>


        <a
            href="orders.php?status=delivered"
            class="filter <?= $filter === 'delivered'
                ? 'active'
                : '' ?>"
        >

            Delivered

            <strong>
                <?= $statusCounts['delivered'] ?>
            </strong>

        </a>


        <a
            href="orders.php?status=cancelled"
            class="filter <?= $filter === 'cancelled'
                ? 'active'
                : '' ?>"
        >

            Cancelled

            <strong>
                <?= $statusCounts['cancelled'] ?>
            </strong>

        </a>


    </div>


    <!-- =================================================
         ORDERS
    ================================================= -->

    <div class="orders">


        <?php if (!empty($orders)): ?>


            <?php foreach ($orders as $order): ?>


                <div class="order-card">


                    <!-- ORDER HEADER -->

                    <div class="order-head">


                        <div>

                            <div class="order-number">

                                #<?= htmlspecialchars(
                                    $order['order_number']
                                ) ?>

                            </div>


                            <div class="order-date">

                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>

                            </div>

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


                    <!-- ORDER BODY -->

                    <div class="order-body">


                        <!-- CUSTOMER -->

                        <div>

                            <div class="info-label">
                                Customer
                            </div>


                            <div class="info-value">

                                <strong>

                                    <?= htmlspecialchars(
                                        $order['customer_name']
                                        ?: 'Guest Customer'
                                    ) ?>

                                </strong>


                                <br>


                                <?= htmlspecialchars(
                                    $order['phone']
                                ) ?>


                                <?php if (
                                    !empty(
                                        $order['customer_email']
                                    )
                                ): ?>

                                    <br>

                                    <?= htmlspecialchars(
                                        $order['customer_email']
                                    ) ?>

                                <?php endif; ?>

                            </div>

                        </div>


                        <!-- ADDRESS -->

                        <div>

                            <div class="info-label">
                                Delivery Address
                            </div>


                            <div class="info-value">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $order[
                                            'delivery_address'
                                        ]
                                    )
                                ) ?>

                            </div>

                        </div>


                        <!-- TOTAL -->

                        <div>

                            <div class="info-label">
                                Order Total
                            </div>


                            <div class="amount">

                                ₹<?= number_format(
                                    (float)
                                    $order[
                                        'total_amount'
                                    ],
                                    2
                                ) ?>

                            </div>

                        </div>


                    </div>


                    <!-- FOOTER -->

                    <div class="order-footer">


                        <div class="payment">

                            Payment:

                            <strong>

                                <?= htmlspecialchars(
                                    readablePayment(
                                        $order[
                                            'payment_method'
                                        ]
                                    )
                                ) ?>

                            </strong>


                            &nbsp; | &nbsp;


                            Payment Status:

                            <strong>

                                <?= htmlspecialchars(
                                    ucfirst(
                                        $order[
                                            'payment_status'
                                        ]
                                    )
                                ) ?>

                            </strong>

                        </div>


                        <!-- STATUS UPDATE -->

                        <form
                            method="POST"
                            class="status-form"
                        >


                            <input
                                type="hidden"
                                name="order_id"
                                value="<?= (int)$order['id'] ?>"
                            >


                            <select
                                name="order_status"
                            >

                                <option
                                    value="pending"
                                    <?= $order['order_status']
                                        === 'pending'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Pending
                                </option>


                                <option
                                    value="confirmed"
                                    <?= $order['order_status']
                                        === 'confirmed'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Confirmed
                                </option>


                                <option
                                    value="preparing"
                                    <?= $order['order_status']
                                        === 'preparing'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Preparing
                                </option>


                                <option
                                    value="out_for_delivery"
                                    <?= $order['order_status']
                                        === 'out_for_delivery'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Out for Delivery
                                </option>


                                <option
                                    value="delivered"
                                    <?= $order['order_status']
                                        === 'delivered'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Delivered
                                </option>


                                <option
                                    value="cancelled"
                                    <?= $order['order_status']
                                        === 'cancelled'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Cancelled
                                </option>

                            </select>


                            <button
                                type="submit"
                                class="update-btn"
                            >
                                Update
                            </button>


                        </form>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php else: ?>


            <div class="empty">

                <div class="empty-icon">
                    ▣
                </div>

                No orders found for this status.

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>