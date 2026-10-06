<?php

session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$response = [
    'success' => false,
    'message' => '',
    'cart_count' => 0
];

/* =========================
   ONLY POST REQUEST
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    $response['message'] = 'Invalid request.';

    echo json_encode($response);
    exit;
}

/* =========================
   GET PRODUCT DATA
========================= */

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

$quantity = max(1, $quantity);

if ($product_id <= 0) {

    $response['message'] = 'Invalid product.';

    echo json_encode($response);
    exit;
}

/* =========================
   CHECK PRODUCT
========================= */

$stmt = $conn->prepare("
    SELECT id, name, price, image
    FROM products
    WHERE id = ?
    AND is_available = 1
    LIMIT 1
");

if (!$stmt) {

    $response['message'] = 'Database error.';

    echo json_encode($response);
    exit;
}

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $response['message'] = 'Product not found.';

    echo json_encode($response);
    exit;
}

$product = $result->fetch_assoc();

$stmt->close();

/* =========================
   CREATE CART
========================= */

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* =========================
   ADD PRODUCT
========================= */

if (isset($_SESSION['cart'][$product_id])) {

    $_SESSION['cart'][$product_id] += $quantity;

} else {

    $_SESSION['cart'][$product_id] = $quantity;
}

/* =========================
   CART COUNT
========================= */

$cart_count = 0;

foreach ($_SESSION['cart'] as $qty) {

    $cart_count += (int)$qty;
}

/* =========================
   SUCCESS RESPONSE
========================= */

$response['success'] = true;
$response['message'] = $product['name'] . ' added to cart.';
$response['cart_count'] = $cart_count;

echo json_encode($response);

exit;
?>