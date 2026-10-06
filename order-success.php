<?php

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/sms.php';


// ======================================================
// CHECK REQUEST
// ======================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}


// ======================================================
// USER
// ======================================================

$user_id = $_SESSION['user_id'] ?? null;


// ======================================================
// CUSTOMER INFORMATION
// ======================================================

$customerName  = trim($_POST['name'] ?? '');
$customerPhone = trim($_POST['phone'] ?? '');
$customerEmail = trim($_POST['email'] ?? '');

$deliveryAddress = trim($_POST['address'] ?? '');
$paymentMethod   = trim($_POST['payment_method'] ?? '');
$notes           = trim($_POST['notes'] ?? '');


// ======================================================
// VALIDATION
// ======================================================

if ($customerName === '') {
    die("Customer name is required.");
}

if ($customerPhone === '') {
    die("Phone number is required.");
}

if ($deliveryAddress === '') {
    die("Delivery address is required.");
}

if ($paymentMethod === '') {
    die("Payment method is required.");
}


// ======================================================
// CHECK CART
// ======================================================

if (
    !isset($_SESSION['cart']) ||
    !is_array($_SESSION['cart']) ||
    empty($_SESSION['cart'])
) {
    die("Your cart is empty. Please add some products first.");
}

$cart = $_SESSION['cart'];


// ======================================================
// GENERATE ORDER NUMBER
// ======================================================

$orderNumber =
    'EP-' .
    date('YmdHis') .
    '-' .
    rand(100, 999);


// ======================================================
// CALCULATE TOTAL FROM DATABASE
// ======================================================

$totalAmount = 0;

$orderItems = [];


foreach ($cart as $productId => $quantity) {

    $productId = (int)$productId;
    $quantity  = (int)$quantity;


    if ($productId <= 0 || $quantity <= 0) {
        continue;
    }


    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            price
        FROM products
        WHERE id = ?
        AND is_available = 1
        LIMIT 1
    ");


    if (!$stmt) {
        die("Database error: " . $conn->error);
    }


    $stmt->bind_param(
        "i",
        $productId
    );


    $stmt->execute();


    $result = $stmt->get_result();


    $product = $result->fetch_assoc();


    $stmt->close();


    if (!$product) {
        continue;
    }


    $price = (float)$product['price'];


    $lineTotal =
        $price *
        $quantity;


    $totalAmount += $lineTotal;


    $orderItems[] = [
        'id'       => $product['id'],
        'name'     => $product['name'],
        'price'    => $price,
        'quantity' => $quantity,
        'total'    => $lineTotal
    ];
}


// ======================================================
// VALID ORDER
// ======================================================

if (empty($orderItems)) {
    die("No valid products found in your cart.");
}


if ($totalAmount <= 0) {
    die("Invalid order total.");
}


// ======================================================
// SAVE ORDER
// ======================================================

$orderSaved = false;


$conn->begin_transaction();


try {


    // ==================================================
    // LOGGED-IN USER
    // ==================================================

    if ($user_id !== null) {


        $stmt = $conn->prepare("
            INSERT INTO orders
            (
                user_id,
                order_number,
                total_amount,
                delivery_address,
                phone,
                payment_method,
                payment_status,
                order_status,
                notes
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        if (!$stmt) {
            throw new Exception(
                $conn->error
            );
        }


        $paymentStatus = 'pending';

        $orderStatus = 'pending';


        $stmt->bind_param(
            "isdssssss",
            $user_id,
            $orderNumber,
            $totalAmount,
            $deliveryAddress,
            $customerPhone,
            $paymentMethod,
            $paymentStatus,
            $orderStatus,
            $notes
        );


    }

    // ==================================================
    // GUEST USER
    // ==================================================

    else {


        $stmt = $conn->prepare("
            INSERT INTO orders
            (
                user_id,
                order_number,
                total_amount,
                delivery_address,
                phone,
                payment_method,
                payment_status,
                order_status,
                notes
            )
            VALUES
            (
                NULL,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


        if (!$stmt) {
            throw new Exception(
                $conn->error
            );
        }


        $paymentStatus = 'pending';

        $orderStatus = 'pending';


        $stmt->bind_param(
            "sdssssss",
            $orderNumber,
            $totalAmount,
            $deliveryAddress,
            $customerPhone,
            $paymentMethod,
            $paymentStatus,
            $orderStatus,
            $notes
        );

    }


    // ==================================================
    // EXECUTE
    // ==================================================

    if (!$stmt->execute()) {

        throw new Exception(
            $stmt->error
        );

    }


    $orderId =
        $stmt->insert_id;


    $stmt->close();


    // ==================================================
    // COMMIT
    // ==================================================

    $conn->commit();


    $orderSaved = true;


} catch (Exception $e) {


    $conn->rollback();


    die(
        "Order could not be placed.<br><br>" .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}


// ======================================================
// SEND SMS
// ======================================================

$smsResult = null;


if ($orderSaved) {


    /*
     * Quick SMS test message.
     */

    $smsMessage =
        "Dear " .
        $customerName .
        ", your Ember & Plate order " .
        $orderNumber .
        " has been received successfully. Total: Rs " .
        number_format(
            $totalAmount,
            2
        ) .
        ". Thank you!";


    $smsResult =
        sendOrderSMS(
            $customerPhone,
            $smsMessage
        );

}


// ======================================================
// CLEAR CART
// ======================================================

unset(
    $_SESSION['cart']
);

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
        Order Successful | Ember & Plate
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 25px;

            background:

                radial-gradient(
                    circle at top left,
                    #3a1510,
                    transparent 40%
                ),

                radial-gradient(
                    circle at bottom right,
                    #1b0907,
                    transparent 40%
                ),

                #080606;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: #fff;
        }


        .success-card {

            width: 100%;

            max-width: 700px;

            padding: 50px 35px;

            text-align: center;

            border-radius: 28px;

            background:
                rgba(
                    25,
                    17,
                    15,
                    0.96
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            box-shadow:
                0 30px 100px
                rgba(
                    0,
                    0,
                    0,
                    0.55
                );
        }


        .check {

            width: 90px;

            height: 90px;

            margin:
                0 auto 25px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #ef5b48;

            font-size: 45px;

            box-shadow:
                0 15px 40px
                rgba(
                    239,
                    91,
                    72,
                    0.35
                );
        }


        h1 {

            font-size: 38px;

            margin-bottom: 15px;
        }


        .subtitle {

            color: #bbb;

            line-height: 1.7;

            margin-bottom: 30px;
        }


        .order-box {

            padding: 22px;

            border-radius: 18px;

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.05
                );

            text-align: left;

            margin-bottom: 25px;
        }


        .row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 11px 0;

            border-bottom:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    0.07
                );
        }


        .row:last-child {

            border-bottom: none;
        }


        .label {

            color: #999;
        }


        .value {

            font-weight: 700;

            text-align: right;
        }


        /* ==================================================
           SMS SUCCESS
        ================================================== */

        .sms-success {

            margin:
                20px 0;

            padding:
                16px 18px;

            border-radius:
                14px;

            background:
                rgba(
                    65,
                    180,
                    100,
                    0.12
                );

            border:
                1px solid
                rgba(
                    65,
                    180,
                    100,
                    0.30
                );

            color:
                #8de0a4;

            line-height: 1.6;
        }


        /* ==================================================
           SMS ERROR
        ================================================== */

        .sms-failed {

            margin:
                20px 0;

            padding:
                18px;

            border-radius:
                14px;

            background:
                rgba(
                    239,
                    91,
                    72,
                    0.12
                );

            border:
                1px solid
                rgba(
                    239,
                    91,
                    72,
                    0.35
                );

            color:
                #ffaaa0;

            text-align:
                left;

            line-height:
                1.6;
        }


        .sms-title {

            font-size:
                18px;

            font-weight:
                800;

            margin-bottom:
                12px;

            text-align:
                center;
        }


        .error-code {

            margin-top:
                10px;

            padding:
                10px;

            border-radius:
                8px;

            background:
                rgba(
                    0,
                    0,
                    0,
                    0.25
                );

            color:
                #fff;
        }


        .error-response {

            margin-top:
                12px;

            padding:
                14px;

            border-radius:
                10px;

            background:
                #080808;

            color:
                #ffb8b0;

            font-size:
                12px;

            white-space:
                pre-wrap;

            overflow-x:
                auto;

            font-family:
                Consolas,
                monospace;
        }


        /* ==================================================
           BUTTONS
        ================================================== */

        .buttons {

            display:
                flex;

            gap:
                12px;

            justify-content:
                center;

            flex-wrap:
                wrap;

            margin-top:
                25px;
        }


        .btn {

            display:
                inline-block;

            padding:
                14px 24px;

            border-radius:
                12px;

            text-decoration:
                none;

            font-weight:
                700;

            transition:
                0.25s;
        }


        .btn-primary {

            background:
                #ef5b48;

            color:
                #fff;
        }


        .btn-secondary {

            background:
                rgba(
                    255,
                    255,
                    255,
                    0.08
                );

            color:
                #fff;
        }


        .btn:hover {

            transform:
                translateY(-2px);
        }


        @media(max-width:600px) {

            .success-card {

                padding:
                    40px 20px;
            }


            h1 {

                font-size:
                    30px;
            }


            .row {

                flex-direction:
                    column;

                gap:
                    4px;
            }


            .value {

                text-align:
                    left;
            }

        }

    </style>

</head>


<body>


<div class="success-card">


    <!-- ==================================================
         SUCCESS ICON
    ================================================== -->

    <div class="check">
        ✓
    </div>


    <h1>
        Order Successful!
    </h1>


    <p class="subtitle">

        Thank you for ordering from
        Ember & Plate.

        Your order has been received
        successfully.

    </p>


    <!-- ==================================================
         ORDER DETAILS
    ================================================== -->

    <div class="order-box">


        <div class="row">

            <span class="label">
                Order Number
            </span>

            <span class="value">

                <?= htmlspecialchars(
                    $orderNumber
                ) ?>

            </span>

        </div>


        <div class="row">

            <span class="label">
                Customer
            </span>

            <span class="value">

                <?= htmlspecialchars(
                    $customerName
                ) ?>

            </span>

        </div>


        <div class="row">

            <span class="label">
                Phone
            </span>

            <span class="value">

                <?= htmlspecialchars(
                    $customerPhone
                ) ?>

            </span>

        </div>


        <div class="row">

            <span class="label">
                Total
            </span>

            <span class="value">

                ₹<?= number_format(
                    $totalAmount,
                    2
                ) ?>

            </span>

        </div>


        <div class="row">

            <span class="label">
                Payment
            </span>

            <span class="value">

                <?= htmlspecialchars(
                    ucwords(
                        str_replace(
                            '_',
                            ' ',
                            $paymentMethod
                        )
                    )
                ) ?>

            </span>

        </div>


    </div>


    <!-- ==================================================
         SMS RESULT
    ================================================== -->

    <?php if (
        $smsResult &&
        $smsResult['success']
    ): ?>


        <div class="sms-success">

            ✓ SMS API request was successful.

            <br>

            Order confirmation SMS was requested
            for:

            <strong>
                <?= htmlspecialchars(
                    $customerPhone
                ) ?>
            </strong>

        </div>


    <?php elseif ($smsResult): ?>


        <div class="sms-failed">


            <div class="sms-title">

                ⚠ SMS Sending Failed

            </div>


            <strong>
                Message:
            </strong>

            <?= htmlspecialchars(
                $smsResult['message']
                ?? 'Unknown error'
            ) ?>


            <?php if (
                isset(
                    $smsResult['http_code']
                )
            ): ?>

                <div class="error-code">

                    <strong>
                        HTTP Code:
                    </strong>

                    <?= htmlspecialchars(
                        (string)
                        $smsResult['http_code']
                    ) ?>

                </div>

            <?php endif; ?>


            <?php if (
                isset(
                    $smsResult['response']
                )
            ): ?>


                <div class="error-response">

<?php

$response = $smsResult['response'];

if (is_array($response)) {

    echo htmlspecialchars(
        json_encode(
            $response,
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE
        )
    );

} else {

    echo htmlspecialchars(
        (string)$response
    );

}

?>

                </div>


            <?php endif; ?>


        </div>


    <?php endif; ?>


    <!-- ==================================================
         BUTTONS
    ================================================== -->

    <div class="buttons">


        <a
            href="my-orders.php"
            class="btn btn-primary"
        >
            My Orders
        </a>


        <a
            href="menu.php"
            class="btn btn-secondary"
        >
            Continue Shopping
        </a>


    </div>


</div>


</body>

</html>