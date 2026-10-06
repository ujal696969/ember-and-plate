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
// ADMIN INFO
// =====================================================

$adminName = $_SESSION['admin_name'] ?? 'Admin';


// =====================================================
// DASHBOARD STATISTICS
// =====================================================

// Total orders
$totalOrders = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($result) {
    $row = $result->fetch_assoc();
    $totalOrders = (int)$row['total'];
}


// Total revenue
$totalRevenue = 0;

$result = $conn->query("
    SELECT COALESCE(SUM(total_amount), 0) AS revenue
    FROM orders
    WHERE order_status != 'cancelled'
");

if ($result) {
    $row = $result->fetch_assoc();
    $totalRevenue = (float)$row['revenue'];
}


// Total customers
$totalCustomers = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM users
");

if ($result) {
    $row = $result->fetch_assoc();
    $totalCustomers = (int)$row['total'];
}


// Pending orders
$pendingOrders = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status = 'pending'
");

if ($result) {
    $row = $result->fetch_assoc();
    $pendingOrders = (int)$row['total'];
}


// Total products
$totalProducts = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {
    $row = $result->fetch_assoc();
    $totalProducts = (int)$row['total'];
}


// Total reviews
$totalReviews = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM reviews
    WHERE status = 'approved'
");

if ($result) {
    $row = $result->fetch_assoc();
    $totalReviews = (int)$row['total'];
}


// New contact messages
$newMessages = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM contact_messages
    WHERE status = 'new'
");

if ($result) {
    $row = $result->fetch_assoc();
    $newMessages = (int)$row['total'];
}


// =====================================================
// RECENT ORDERS
// =====================================================

$recentOrders = [];

$result = $conn->query("
    SELECT
        id,
        order_number,
        total_amount,
        phone,
        payment_method,
        payment_status,
        order_status,
        created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 8
");

if ($result) {

    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }

}


// =====================================================
// STATUS CLASS
// =====================================================

function orderStatusClass($status)
{
    return match ($status) {

        'pending' =>
            'status-pending',

        'confirmed' =>
            'status-confirmed',

        'preparing' =>
            'status-preparing',

        'out_for_delivery' =>
            'status-delivery',

        'delivered' =>
            'status-delivered',

        'cancelled' =>
            'status-cancelled',

        default =>
            'status-pending'
    };
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
        Dashboard | Ember & Plate
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
                0 12px
                25px;

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

            color:
                #d9ad58;

            font-family:
                Georgia,
                serif;

            font-size:
                24px;
        }


        .brand span {

            display:
                block;

            margin-top:
                5px;

            color:
                #666;

            font-size:
                11px;

            letter-spacing:
                2px;

            text-transform:
                uppercase;
        }


        .nav-title {

            color:
                #555;

            font-size:
                10px;

            letter-spacing:
                2px;

            text-transform:
                uppercase;

            padding:
                0 12px;

            margin-bottom:
                10px;
        }


        .sidebar a {

            display:
                flex;

            align-items:
                center;

            gap:
                12px;

            padding:
                13px 12px;

            margin-bottom:
                4px;

            border-radius:
                10px;

            color:
                #aaa;

            text-decoration:
                none;

            font-size:
                14px;

            transition:
                0.2s;
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

            color:
                #d9ad58;
        }


        .icon {

            width:
                24px;

            text-align:
                center;

            font-size:
                16px;
        }


        .logout {

            position:
                absolute;

            bottom:
                25px;

            left:
                18px;

            right:
                18px;

        }


        /* =================================================
           MAIN
        ================================================= */

        .main {

            margin-left:
                245px;

            min-height:
                100vh;

            padding:
                35px;
        }


        /* =================================================
           TOPBAR
        ================================================= */

        .topbar {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            margin-bottom:
                35px;
        }


        .welcome h1 {

            font-family:
                Georgia,
                serif;

            font-size:
                32px;

            font-weight:
                500;
        }


        .welcome p {

            margin-top:
                7px;

            color:
                #777;

            font-size:
                13px;
        }


        .admin-badge {

            padding:
                10px 15px;

            border:
                1px solid
                rgba(
                    217,
                    173,
                    88,
                    0.25
                );

            border-radius:
                12px;

            color:
                #d9ad58;

            font-size:
                13px;
        }


        /* =================================================
           STAT CARDS
        ================================================= */

        .stats {

            display:
                grid;

            grid-template-columns:
                repeat(
                    4,
                    1fr
                );

            gap:
                18px;

            margin-bottom:
                30px;
        }


        .stat-card {

            padding:
                23px;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

            border-radius:
                16px;

            background:
                #111;

            transition:
                0.25s;
        }


        .stat-card:hover {

            transform:
                translateY(-3px);

            border-color:
                rgba(
                    217,
                    173,
                    88,
                    0.25
                );
        }


        .stat-top {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            margin-bottom:
                18px;
        }


        .stat-icon {

            width:
                40px;

            height:
                40px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                10px;

            background:
                rgba(
                    217,
                    173,
                    88,
                    0.10
                );

            color:
                #d9ad58;

            font-size:
                18px;
        }


        .stat-label {

            color:
                #777;

            font-size:
                12px;
        }


        .stat-value {

            font-size:
                27px;

            font-weight:
                700;
        }


        /* =================================================
           SECOND STATS
        ================================================= */

        .small-stats {

            display:
                grid;

            grid-template-columns:
                repeat(
                    3,
                    1fr
                );

            gap:
                18px;

            margin-bottom:
                30px;
        }


        .small-card {

            padding:
                20px;

            border-radius:
                15px;

            background:
                #111;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );
        }


        .small-card-top {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            margin-bottom:
                12px;
        }


        .small-card-title {

            color:
                #999;

            font-size:
                13px;
        }


        .small-card-number {

            font-size:
                24px;

            font-weight:
                700;
        }


        .small-link {

            color:
                #d9ad58;

            text-decoration:
                none;

            font-size:
                12px;
        }


        /* =================================================
           ORDERS
        ================================================= */

        .panel {

            background:
                #111;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );

            border-radius:
                18px;

            overflow:
                hidden;
        }


        .panel-header {

            display:
                flex;

            justify-content:
                space-between;

            align-items:
                center;

            padding:
                22px 24px;

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

            font-size:
                21px;

            font-weight:
                500;
        }


        .view-all {

            color:
                #d9ad58;

            text-decoration:
                none;

            font-size:
                12px;
        }


        .table-wrapper {

            overflow-x:
                auto;
        }


        table {

            width:
                100%;

            border-collapse:
                collapse;

            min-width:
                750px;
        }


        th {

            padding:
                14px 20px;

            text-align:
                left;

            color:
                #666;

            font-size:
                11px;

            text-transform:
                uppercase;

            letter-spacing:
                1px;

            font-weight:
                600;
        }


        td {

            padding:
                17px 20px;

            border-top:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.05
                );

            color:
                #ccc;

            font-size:
                13px;
        }


        .order-number {

            color:
                #fff;

            font-weight:
                700;
        }


        .amount {

            color:
                #d9ad58;

            font-weight:
                700;
        }


        .status {

            display:
                inline-block;

            padding:
                6px 10px;

            border-radius:
                20px;

            font-size:
                10px;

            font-weight:
                700;

            text-transform:
                uppercase;
        }


        .status-pending {

            background:
                rgba(
                    217,
                    173,
                    88,
                    0.12
                );

            color:
                #d9ad58;
        }


        .status-confirmed {

            background:
                rgba(
                    80,
                    150,
                    255,
                    0.12
                );

            color:
                #80b5ff;
        }


        .status-preparing {

            background:
                rgba(
                    180,
                    100,
                    255,
                    0.12
                );

            color:
                #c49cff;
        }


        .status-delivery {

            background:
                rgba(
                    50,
                    190,
                    150,
                    0.12
                );

            color:
                #6edab8;
        }


        .status-delivered {

            background:
                rgba(
                    70,
                    190,
                    90,
                    0.12
                );

            color:
                #7ce08b;
        }


        .status-cancelled {

            background:
                rgba(
                    239,
                    91,
                    72,
                    0.12
                );

            color:
                #ff8f83;
        }


        .empty {

            padding:
                50px;

            text-align:
                center;

            color:
                #666;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media(max-width:1100px) {

            .stats {

                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );
            }

        }


        @media(max-width:800px) {

            .sidebar {

                width:
                    70px;

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

                font-size:
                    0;
            }


            .brand h2::after {

                content:
                    "E";

                font-size:
                    26px;
            }


            .brand span,
            .nav-title,
            .sidebar a span:not(.icon) {

                display:
                    none;
            }


            .sidebar a {

                justify-content:
                    center;

                padding:
                    13px 5px;
            }


            .logout {

                left:
                    10px;

                right:
                    10px;
            }


            .main {

                margin-left:
                    70px;

                padding:
                    25px 18px;
            }


            .small-stats {

                grid-template-columns:
                    1fr;
            }

        }


        @media(max-width:550px) {

            .stats {

                grid-template-columns:
                    1fr;
            }


            .topbar {

                align-items:
                    flex-start;

                gap:
                    15px;

                flex-direction:
                    column;
            }


            .welcome h1 {

                font-size:
                    27px;
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


    <a
        href="dashboard.php"
        class="active"
    >

        <span class="icon">
            ◈
        </span>

        <span>
            Dashboard
        </span>

    </a>


    <a href="orders.php">

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
     MAIN CONTENT
===================================================== -->

<main class="main">


    <!-- TOPBAR -->

    <div class="topbar">


        <div class="welcome">

            <h1>
                Good day, <?= htmlspecialchars($adminName) ?>
            </h1>

            <p>
                Here's what's happening with Ember & Plate today.
            </p>

        </div>


        <div class="admin-badge">

            ● Administrator

        </div>


    </div>


    <!-- =================================================
         MAIN STATISTICS
    ================================================= -->

    <div class="stats">


        <!-- ORDERS -->

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-label">
                    Total Orders
                </div>

                <div class="stat-icon">
                    ▣
                </div>

            </div>

            <div class="stat-value">
                <?= number_format($totalOrders) ?>
            </div>

        </div>


        <!-- REVENUE -->

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-label">
                    Total Revenue
                </div>

                <div class="stat-icon">
                    ₹
                </div>

            </div>

            <div class="stat-value">

                ₹<?= number_format(
                    $totalRevenue,
                    2
                ) ?>

            </div>

        </div>


        <!-- CUSTOMERS -->

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-label">
                    Customers
                </div>

                <div class="stat-icon">
                    ♙
                </div>

            </div>

            <div class="stat-value">
                <?= number_format($totalCustomers) ?>
            </div>

        </div>


        <!-- PENDING -->

        <div class="stat-card">

            <div class="stat-top">

                <div class="stat-label">
                    Pending Orders
                </div>

                <div class="stat-icon">
                    ◷
                </div>

            </div>

            <div class="stat-value">
                <?= number_format($pendingOrders) ?>
            </div>

        </div>


    </div>


    <!-- =================================================
         SMALL STATISTICS
    ================================================= -->

    <div class="small-stats">


        <div class="small-card">

            <div class="small-card-top">

                <span class="small-card-title">
                    Products
                </span>

                <span class="small-card-number">
                    <?= number_format($totalProducts) ?>
                </span>

            </div>

            <a
                href="products.php"
                class="small-link"
            >
                Manage Products →
            </a>

        </div>


        <div class="small-card">

            <div class="small-card-top">

                <span class="small-card-title">
                    Approved Reviews
                </span>

                <span class="small-card-number">
                    <?= number_format($totalReviews) ?>
                </span>

            </div>

            <a
                href="reviews.php"
                class="small-link"
            >
                Manage Reviews →
            </a>

        </div>


        <div class="small-card">

            <div class="small-card-top">

                <span class="small-card-title">
                    New Messages
                </span>

                <span class="small-card-number">
                    <?= number_format($newMessages) ?>
                </span>

            </div>

            <a
                href="contact-messages.php"
                class="small-link"
            >
                View Messages →
            </a>

        </div>


    </div>


    <!-- =================================================
         RECENT ORDERS
    ================================================= -->

    <div class="panel">


        <div class="panel-header">

            <h2>
                Recent Orders
            </h2>

            <a
                href="orders.php"
                class="view-all"
            >
                View All →
            </a>

        </div>


        <?php if (!empty($recentOrders)): ?>


            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Phone
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Payment
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Date
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $recentOrders
                        as $order
                    ): ?>


                        <tr>


                            <td>

                                <span class="order-number">

                                    <?= htmlspecialchars(
                                        $order['order_number']
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $order['phone']
                                ) ?>

                            </td>


                            <td>

                                <span class="amount">

                                    ₹<?= number_format(
                                        (float)$order['total_amount'],
                                        2
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $order['payment_method']
                                        )
                                    )
                                ) ?>

                            </td>


                            <td>

                                <span
                                    class="status
                                    <?= orderStatusClass(
                                        $order['order_status']
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $order['order_status']
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <td>

                                <?= date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty">

                No orders yet.

            </div>


        <?php endif; ?>


    </div>


</main>


</body>

</html>