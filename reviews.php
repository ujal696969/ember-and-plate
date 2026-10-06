<?php
session_start();

require_once __DIR__ . '/../config/db.php';

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/
$isLoggedIn = isset($_SESSION['user_id']);
$userId     = $_SESSION['user_id'] ?? 0;
$userName   = $_SESSION['user_name'] ?? 'Guest';


/*
|--------------------------------------------------------------------------
| REVIEW SUBMIT
|--------------------------------------------------------------------------
*/
$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!$isLoggedIn) {
        $errorMessage = "Please login first to submit a review.";
    } else {

        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $reviewText = trim($_POST['review_text'] ?? '');

        // Rating validation
        if ($rating < 1 || $rating > 5) {
            $errorMessage = "Please select a rating between 1 and 5 stars.";
        }

        // Review validation
        elseif ($reviewText === '') {
            $errorMessage = "Please write your review.";
        }

        elseif (strlen($reviewText) < 5) {
            $errorMessage = "Your review should contain at least 5 characters.";
        }

        else {

            /*
            |--------------------------------------------------------------------------
            | INSERT REVIEW
            |--------------------------------------------------------------------------
            */
            $stmt = $conn->prepare("
                INSERT INTO reviews
                (user_id, rating, review_text, status)
                VALUES (?, ?, ?, 'approved')
            ");

            if ($stmt) {

                $stmt->bind_param(
                    "iis",
                    $userId,
                    $rating,
                    $reviewText
                );

                if ($stmt->execute()) {

                    header("Location: reviews.php?success=1");
                    exit;

                } else {
                    $errorMessage = "Something went wrong while submitting your review.";
                }

                $stmt->close();

            } else {
                $errorMessage = "Unable to prepare review request.";
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $successMessage = "Thank you! Your review has been submitted successfully.";
}


/*
|--------------------------------------------------------------------------
| TOTAL REVIEWS + AVERAGE RATING
|--------------------------------------------------------------------------
*/
$totalReviews = 0;
$averageRating = 0;

$statsQuery = "
    SELECT
        COUNT(*) AS total_reviews,
        COALESCE(AVG(rating), 0) AS average_rating
    FROM reviews
    WHERE status = 'approved'
";

$statsResult = $conn->query($statsQuery);

if ($statsResult) {

    $stats = $statsResult->fetch_assoc();

    $totalReviews = (int)($stats['total_reviews'] ?? 0);
    $averageRating = (float)($stats['average_rating'] ?? 0);
}


/*
|--------------------------------------------------------------------------
| RATING BREAKDOWN
|--------------------------------------------------------------------------
| IMPORTANT:
| Only 1–5 stars are possible.
|--------------------------------------------------------------------------
*/

$ratingBreakdown = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 0
];

$ratingQuery = "
    SELECT rating, COUNT(*) AS total
    FROM reviews
    WHERE status = 'approved'
    GROUP BY rating
";

$ratingResult = $conn->query($ratingQuery);

if ($ratingResult) {

    while ($row = $ratingResult->fetch_assoc()) {

        $rating = (int)$row['rating'];
        $count  = (int)$row['total'];

        // Only accept valid 1–5 ratings
        if ($rating >= 1 && $rating <= 5) {
            $ratingBreakdown[$rating] = $count;
        }
    }
}


/*
|--------------------------------------------------------------------------
| FETCH CUSTOMER REVIEWS
|--------------------------------------------------------------------------
*/

$reviews = [];

$reviewQuery = "
    SELECT
        r.id,
        r.rating,
        r.review_text,
        r.created_at,
        u.name AS customer_name
    FROM reviews r
    INNER JOIN users u
        ON r.user_id = u.id
    WHERE r.status = 'approved'
    ORDER BY r.created_at DESC
";

$reviewResult = $conn->query($reviewQuery);

if ($reviewResult) {

    while ($row = $reviewResult->fetch_assoc()) {
        $reviews[] = $row;
    }
}


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
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/*
|--------------------------------------------------------------------------
| RATING PERCENTAGE
|--------------------------------------------------------------------------
*/

function ratingPercentage($count, $total)
{
    if ($total <= 0) {
        return 0;
    }

    return round(($count / $total) * 100);
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

    <title>Reviews | Ember & Plate</title>

    <meta
        name="description"
        content="Read real customer reviews and ratings at Ember & Plate."
    >

    <style>

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
            color: #f4f1e8;
            font-family: Arial, Helvetica, sans-serif;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        textarea {
            font-family: inherit;
        }


        /* =========================================================
           BACKGROUND
        ========================================================= */

        body::before {
            content: "";
            position: fixed;
            width: 500px;
            height: 500px;
            top: -200px;
            left: -150px;
            background: radial-gradient(
                circle,
                rgba(190, 145, 50, 0.12),
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
            right: -200px;
            bottom: -200px;
            background: radial-gradient(
                circle,
                rgba(190, 145, 50, 0.08),
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
            height: 74px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 42px;

            background: rgba(5, 5, 5, 0.88);
            backdrop-filter: blur(18px);

            border-bottom: 1px solid rgba(255, 255, 255, 0.08);

            z-index: 9999;
        }


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

            border: 1px solid #a98235;

            color: #d7b56d;
            font-family: Georgia, serif;
            font-size: 15px;
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


        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-links a {
            position: relative;

            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;

            color: #a8a8a8;

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
            bottom: -7px;

            width: 0;
            height: 1px;

            background: #d8b66d;

            transition: 0.3s ease;
        }

        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }


        .cart-link {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .cart-count {
            min-width: 18px;
            height: 18px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 0 5px;

            border-radius: 20px;

            background: #b28a3d;
            color: #050505;

            font-size: 9px;
            font-weight: bold;
        }


        .logout-btn {
            padding: 10px 18px;

            border: 1px solid #9c762e;

            color: #d8b66d !important;

            font-size: 10px !important;

            transition: 0.3s ease;
        }

        .logout-btn:hover {
            background: #b28a3d;
            color: #000 !important;
        }


        /* =========================================================
           HERO
        ========================================================= */

        .hero {
            min-height: 67vh;

            display: flex;
            align-items: center;
            justify-content: center;

            text-align: center;

            padding: 150px 20px 90px;

            position: relative;
        }


        .hero-content {
            max-width: 850px;
        }


        .hero-label {
            color: #b99755;

            font-size: 11px;
            letter-spacing: 5px;
            text-transform: uppercase;

            margin-bottom: 24px;

            animation: fadeUp 1s ease forwards;
        }


        .hero h1 {
            font-family: Georgia, "Times New Roman", serif;

            font-size: clamp(48px, 8vw, 96px);

            font-weight: 400;
            line-height: 0.95;

            letter-spacing: -3px;

            margin-bottom: 28px;

            animation: fadeUp 1s 0.15s ease both;
        }

        .hero h1 span {
            color: #b99650;
            font-style: italic;
        }


        .hero-description {
            max-width: 600px;
            margin: auto;

            color: #999;

            font-size: 14px;
            line-height: 1.9;

            animation: fadeUp 1s 0.3s ease both;
        }


        /* =========================================================
           MAIN
        ========================================================= */

        .container {
            width: min(1180px, 92%);
            margin: auto;
        }


        .section-title {
            margin-bottom: 40px;
        }

        .section-title small {
            color: #b99755;

            font-size: 10px;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .section-title h2 {
            font-family: Georgia, serif;

            font-size: clamp(32px, 5vw, 52px);

            font-weight: 400;

            margin-top: 12px;
        }


        /* =========================================================
           RATING OVERVIEW
        ========================================================= */

        .rating-section {
            padding: 80px 0 110px;
        }


        .rating-overview {
            display: grid;
            grid-template-columns: 0.8fr 1.2fr;

            gap: 70px;

            padding: 55px;

            border: 1px solid rgba(255, 255, 255, 0.08);

            background:
                linear-gradient(
                    135deg,
                    rgba(255, 255, 255, 0.035),
                    rgba(255, 255, 255, 0.01)
                );

            border-radius: 2px;
        }


        .average-box {
            display: flex;
            flex-direction: column;
            justify-content: center;

            text-align: center;

            border-right: 1px solid rgba(255, 255, 255, 0.08);

            padding-right: 60px;
        }


        .average-number {
            font-family: Georgia, serif;

            font-size: 78px;

            font-weight: 400;

            line-height: 1;

            color: #d8b66d;
        }


        .stars {
            color: #d8b66d;

            font-size: 19px;

            letter-spacing: 4px;

            margin: 18px 0;
        }


        .review-total {
            color: #888;

            font-size: 12px;

            letter-spacing: 1px;
        }


        /* =========================================================
           RATING BARS
        ========================================================= */

        .rating-bars {
            display: flex;
            flex-direction: column;

            justify-content: center;

            gap: 18px;
        }


        .rating-row {
            display: grid;

            grid-template-columns: 48px 1fr 45px;

            align-items: center;

            gap: 14px;
        }


        .rating-label {
            color: #bbb;

            font-size: 12px;

            white-space: nowrap;
        }


        .rating-bar {
            height: 6px;

            background: #222;

            overflow: hidden;

            border-radius: 10px;
        }


        .rating-fill {
            height: 100%;

            width: 0;

            background: linear-gradient(
                90deg,
                #70531e,
                #d8b66d
            );

            border-radius: 10px;

            transition: width 1.3s ease;
        }


        .rating-count {
            color: #777;

            font-size: 11px;

            text-align: right;
        }


        /* =========================================================
           REVIEW FORM
        ========================================================= */

        .write-section {
            padding: 30px 0 120px;
        }


        .review-form {
            max-width: 850px;

            margin: auto;

            padding: 50px;

            background: #0c0c0c;

            border: 1px solid rgba(255, 255, 255, 0.08);

            position: relative;

            overflow: hidden;
        }


        .review-form::before {
            content: "";

            position: absolute;

            top: 0;
            left: 0;

            width: 100%;
            height: 1px;

            background: linear-gradient(
                90deg,
                transparent,
                #c6a45b,
                transparent
            );
        }


        .form-title {
            text-align: center;

            margin-bottom: 35px;
        }

        .form-title h2 {
            font-family: Georgia, serif;

            font-size: 38px;

            font-weight: 400;

            margin-bottom: 10px;
        }

        .form-title p {
            color: #777;

            font-size: 13px;
        }


        /* =========================================================
           STAR SELECTOR
        ========================================================= */

        .star-selector {
            display: flex;

            justify-content: center;

            gap: 10px;

            margin-bottom: 30px;
        }


        .star-selector input {
            display: none;
        }


        .star-selector label {
            cursor: pointer;

            font-size: 34px;

            color: #333;

            transition: 0.25s ease;

            transform: scale(1);
        }


        .star-selector label:hover {
            color: #d8b66d;

            transform: scale(1.12);
        }


        .star-selector input:checked + label {
            color: #d8b66d;
        }


        .textarea-box {
            margin-bottom: 25px;
        }


        .textarea-box textarea {
            width: 100%;

            min-height: 150px;

            resize: vertical;

            background: #080808;

            border: 1px solid #252525;

            color: #eee;

            padding: 18px;

            outline: none;

            font-size: 14px;

            line-height: 1.7;

            transition: 0.3s ease;
        }


        .textarea-box textarea:focus {
            border-color: #a98235;

            box-shadow: 0 0 25px rgba(185, 145, 65, 0.08);
        }


        .submit-btn {
            width: 100%;

            padding: 16px;

            border: none;

            background: linear-gradient(
                90deg,
                #98712e,
                #d0ad62
            );

            color: #080808;

            font-weight: bold;

            letter-spacing: 2px;

            text-transform: uppercase;

            cursor: pointer;

            transition: 0.3s ease;
        }


        .submit-btn:hover {
            transform: translateY(-2px);

            box-shadow:
                0 12px 30px rgba(185, 145, 65, 0.18);
        }


        .login-message {
            text-align: center;

            padding: 25px;

            border: 1px solid rgba(185, 145, 65, 0.2);

            color: #999;

            background: rgba(185, 145, 65, 0.03);
        }


        .login-message a {
            color: #d8b66d;

            text-decoration: underline;
        }


        /* =========================================================
           ALERT
        ========================================================= */

        .alert {
            max-width: 850px;

            margin: 0 auto 35px;

            padding: 16px 20px;

            font-size: 13px;

            border: 1px solid;
        }


        .alert-success {
            border-color: rgba(90, 180, 110, 0.4);

            background: rgba(90, 180, 110, 0.06);

            color: #9ed6a7;
        }


        .alert-error {
            border-color: rgba(200, 80, 80, 0.4);

            background: rgba(200, 80, 80, 0.06);

            color: #e9a0a0;
        }


        /* =========================================================
           CUSTOMER REVIEWS
        ========================================================= */

        .reviews-section {
            padding: 20px 0 130px;
        }


        .reviews-header {
            display: flex;

            align-items: end;

            justify-content: space-between;

            margin-bottom: 45px;
        }


        .reviews-header p {
            color: #777;

            font-size: 12px;
        }


        .reviews-grid {
            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 20px;
        }


        .review-card {
            position: relative;

            padding: 30px;

            min-height: 250px;

            background: #0b0b0b;

            border: 1px solid rgba(255, 255, 255, 0.07);

            transition:
                transform 0.4s ease,
                border-color 0.4s ease,
                background 0.4s ease;

            overflow: hidden;
        }


        .review-card::before {
            content: "“";

            position: absolute;

            top: -15px;
            right: 15px;

            font-family: Georgia, serif;

            font-size: 120px;

            color: rgba(185, 145, 65, 0.08);
        }


        .review-card:hover {
            transform: translateY(-8px);

            border-color: rgba(185, 145, 65, 0.35);

            background: #0f0f0f;
        }


        .review-card-stars {
            color: #d8b66d;

            letter-spacing: 3px;

            font-size: 13px;

            margin-bottom: 20px;
        }


        .review-text {
            color: #c7c7c7;

            font-family: Georgia, serif;

            font-size: 16px;

            line-height: 1.8;

            position: relative;

            z-index: 1;

            margin-bottom: 30px;
        }


        .review-user {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            border-top: 1px solid rgba(255, 255, 255, 0.06);

            padding-top: 18px;
        }


        .customer-name {
            color: #e7e7e7;

            font-size: 12px;

            letter-spacing: 1px;

            text-transform: uppercase;
        }


        .review-date {
            color: #666;

            font-size: 10px;
        }


        /* =========================================================
           EMPTY STATE
        ========================================================= */

        .empty-state {
            text-align: center;

            padding: 70px 20px;

            border: 1px dashed #292929;

            color: #777;
        }


        .empty-state-icon {
            font-size: 42px;

            color: #a98235;

            margin-bottom: 20px;
        }


        .empty-state h3 {
            font-family: Georgia, serif;

            font-size: 28px;

            font-weight: 400;

            color: #ddd;

            margin-bottom: 10px;
        }


        /* =========================================================
           FOOTER
        ========================================================= */

        footer {
            border-top: 1px solid rgba(255, 255, 255, 0.07);

            padding: 45px 0;

            text-align: center;

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
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }


        .reveal {
            opacity: 0;

            transform: translateY(30px);

            transition:
                opacity 0.8s ease,
                transform 0.8s ease;
        }


        .reveal.show {
            opacity: 1;

            transform: translateY(0);
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .navbar {
                padding: 0 20px;
            }

            .nav-links {
                gap: 17px;
            }

            .rating-overview {
                grid-template-columns: 1fr;

                gap: 40px;
            }

            .average-box {
                border-right: none;

                border-bottom: 1px solid rgba(255,255,255,0.08);

                padding-right: 0;

                padding-bottom: 40px;
            }

            .reviews-grid {
                grid-template-columns: repeat(2, 1fr);
            }

        }


        @media (max-width: 760px) {

            .navbar {
                height: auto;

                min-height: 68px;

                flex-direction: column;

                align-items: stretch;

                padding: 10px 12px;
            }

            .brand {
                justify-content: center;
            }

            .nav-links {
                width: 100%;

                overflow-x: auto;

                justify-content: flex-start;

                padding: 10px 2px 4px;

                gap: 22px;

                scrollbar-width: none;
            }

            .nav-links::-webkit-scrollbar {
                display: none;
            }

            .nav-links a {
                flex: 0 0 auto;

                font-size: 9px;
            }

            .hero {
                padding-top: 180px;
            }

            .rating-overview {
                padding: 30px 20px;
            }

            .review-form {
                padding: 30px 20px;
            }

            .reviews-header {
                display: block;
            }

            .reviews-header p {
                margin-top: 10px;
            }

            .reviews-grid {
                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 480px) {

            .hero h1 {
                font-size: 50px;

                letter-spacing: -2px;
            }

            .hero-description {
                font-size: 13px;
            }

            .average-number {
                font-size: 64px;
            }

            .star-selector {
                gap: 5px;
            }

            .star-selector label {
                font-size: 29px;
            }

            .rating-row {
                grid-template-columns: 42px 1fr 35px;

                gap: 8px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar">

    <a href="index.php" class="brand">

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

        <a href="reviews.php" class="active">
            Reviews
        </a>

        <a href="contact.php">
            Contact
        </a>

        <a href="cart.php" class="cart-link">

            Cart

            <span class="cart-count">
                <?= $cartCount ?>
            </span>

        </a>

        <a href="logout.php" class="logout-btn">
            Logout
        </a>

    </div>

</nav>



<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">

    <div class="hero-content">

        <div class="hero-label">
            Guest Experiences
        </div>

        <h1>
            What our <span>guests</span> say.
        </h1>

        <p class="hero-description">
            Every plate tells a story.
            Every experience leaves a memory.
            Read what our guests have shared with Ember &amp; Plate.
        </p>

    </div>

</section>



<!-- =========================================================
     ALERT MESSAGES
========================================================= -->

<?php if ($successMessage): ?>

    <div class="container">

        <div class="alert alert-success">
            <?= e($successMessage) ?>
        </div>

    </div>

<?php endif; ?>


<?php if ($errorMessage): ?>

    <div class="container">

        <div class="alert alert-error">
            <?= e($errorMessage) ?>
        </div>

    </div>

<?php endif; ?>



<!-- =========================================================
     RATING OVERVIEW
========================================================= -->

<section class="rating-section">

    <div class="container">

        <div class="section-title reveal">

            <small>
                Real Guest Feedback
            </small>

            <h2>
                Our rating
            </h2>

        </div>


        <div class="rating-overview reveal">


            <!-- AVERAGE -->

            <div class="average-box">

                <div class="average-number">
                    <?= number_format($averageRating, 1) ?>
                </div>

                <div class="stars">

                    <?php

                    $roundedRating = round($averageRating);

                    for ($i = 1; $i <= 5; $i++) {

                        echo ($i <= $roundedRating)
                            ? '★'
                            : '☆';

                    }

                    ?>

                </div>

                <div class="review-total">

                    Based on
                    <?= $totalReviews ?>
                    <?= $totalReviews == 1 ? 'review' : 'reviews' ?>

                </div>

            </div>



            <!-- RATING BREAKDOWN -->

            <div class="rating-bars">

                <?php for ($star = 5; $star >= 1; $star--): ?>

                    <?php

                    /*
                    IMPORTANT:
                    ?? 0 prevents Undefined array key warning.
                    */

                    $count = $ratingBreakdown[$star] ?? 0;

                    $percentage = ratingPercentage(
                        $count,
                        $totalReviews
                    );

                    ?>

                    <div class="rating-row">

                        <div class="rating-label">
                            <?= $star ?> ★
                        </div>

                        <div class="rating-bar">

                            <div
                                class="rating-fill"
                                data-width="<?= $percentage ?>%"
                            ></div>

                        </div>

                        <div class="rating-count">
                            <?= $count ?>
                        </div>

                    </div>

                <?php endfor; ?>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     WRITE REVIEW
========================================================= -->

<section class="write-section">

    <div class="container">

        <div class="review-form reveal">

            <div class="form-title">

                <h2>
                    Share your experience
                </h2>

                <p>
                    Your honest feedback means a lot to us.
                </p>

            </div>


            <?php if ($isLoggedIn): ?>

                <form
                    method="POST"
                    action="reviews.php"
                >


                    <!-- STAR SELECTOR -->

                    <div class="star-selector">

                        <input
                            type="radio"
                            id="star5"
                            name="rating"
                            value="5"
                        >

                        <label for="star5">
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star4"
                            name="rating"
                            value="4"
                        >

                        <label for="star4">
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star3"
                            name="rating"
                            value="3"
                        >

                        <label for="star3">
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star2"
                            name="rating"
                            value="2"
                        >

                        <label for="star2">
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star1"
                            name="rating"
                            value="1"
                        >

                        <label for="star1">
                            ★
                        </label>

                    </div>



                    <!-- REVIEW TEXT -->

                    <div class="textarea-box">

                        <textarea
                            name="review_text"
                            placeholder="Tell us about your experience..."
                            required
                        ></textarea>

                    </div>



                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="submit-btn"
                    >
                        Submit Review
                    </button>

                </form>


            <?php else: ?>

                <div class="login-message">

                    Please
                    <a href="login.php">
                        login
                    </a>
                    to share your experience with us.

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>



<!-- =========================================================
     CUSTOMER REVIEWS
========================================================= -->

<section class="reviews-section">

    <div class="container">


        <div class="reviews-header reveal">

            <div class="section-title">

                <small>
                    From Our Guests
                </small>

                <h2>
                    Customer stories
                </h2>

            </div>

            <p>
                <?= $totalReviews ?>
                real
                <?= $totalReviews == 1 ? 'review' : 'reviews' ?>
            </p>

        </div>



        <?php if (!empty($reviews)): ?>

            <div class="reviews-grid">

                <?php foreach ($reviews as $review): ?>

                    <?php

                    $reviewRating = (int)$review['rating'];

                    $customerName =
                        trim($review['customer_name'] ?? 'Guest');

                    $reviewText =
                        trim($review['review_text'] ?? '');

                    $createdAt =
                        !empty($review['created_at'])
                        ? date(
                            'd M Y',
                            strtotime($review['created_at'])
                        )
                        : '';

                    ?>

                    <article class="review-card reveal">


                        <!-- STARS -->

                        <div class="review-card-stars">

                            <?php

                            for ($i = 1; $i <= 5; $i++) {

                                echo ($i <= $reviewRating)
                                    ? '★'
                                    : '☆';

                            }

                            ?>

                        </div>


                        <!-- REVIEW -->

                        <div class="review-text">

                            <?= nl2br(e($reviewText)) ?>

                        </div>


                        <!-- CUSTOMER -->

                        <div class="review-user">

                            <div class="customer-name">

                                <?= e($customerName) ?>

                            </div>

                            <div class="review-date">

                                <?= e($createdAt) ?>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>


        <?php else: ?>


            <div class="empty-state reveal">

                <div class="empty-state-icon">
                    ★
                </div>

                <h3>
                    Be our first storyteller.
                </h3>

                <p>
                    No customer reviews yet.
                    Your experience could be the first one here.
                </p>

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

    &copy; <?= date('Y') ?> Ember &amp; Plate.
    All rights reserved.

</footer>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener("DOMContentLoaded", function () {


    /* =========================================================
       SCROLL REVEAL
    ========================================================= */

    const revealElements =
        document.querySelectorAll(".reveal");


    const observer =
        new IntersectionObserver(
            function (entries) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        entry.target.classList.add("show");

                        observer.unobserve(entry.target);

                    }

                });

            },
            {
                threshold: 0.12
            }
        );


    revealElements.forEach(function (element) {

        observer.observe(element);

    });



    /* =========================================================
       RATING BAR ANIMATION
    ========================================================= */

    const ratingFills =
        document.querySelectorAll(".rating-fill");


    const barObserver =
        new IntersectionObserver(
            function (entries) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        const bar =
                            entry.target;

                        const targetWidth =
                            bar.getAttribute("data-width") || "0%";

                        setTimeout(function () {

                            bar.style.width =
                                targetWidth;

                        }, 150);

                        barObserver.unobserve(bar);

                    }

                });

            },
            {
                threshold: 0.5
            }
        );


    ratingFills.forEach(function (bar) {

        barObserver.observe(bar);

    });



    /* =========================================================
       STAR SELECTOR
    ========================================================= */

    const starInputs =
        document.querySelectorAll(
            '.star-selector input'
        );


    starInputs.forEach(function (input) {

        input.addEventListener(
            "change",
            function () {

                const selected =
                    parseInt(this.value);

                starInputs.forEach(function (item) {

                    const label =
                        document.querySelector(
                            'label[for="' + item.id + '"]'
                        );

                    if (!label) {
                        return;
                    }

                    const itemValue =
                        parseInt(item.value);

                    if (itemValue <= selected) {

                        label.style.color =
                            "#d8b66d";

                    } else {

                        label.style.color =
                            "#333";

                    }

                });

            }
        );

    });



    /* =========================================================
       NAVBAR SCROLL EFFECT
    ========================================================= */

    const navbar =
        document.querySelector(".navbar");


    window.addEventListener(
        "scroll",
        function () {

            if (window.scrollY > 30) {

                navbar.style.background =
                    "rgba(4,4,4,0.96)";

            } else {

                navbar.style.background =
                    "rgba(5,5,5,0.88)";

            }

        }
    );


});

</script>


</body>
</html>