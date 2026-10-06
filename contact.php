<?php

session_start();

require_once __DIR__ . '/../config/db.php';


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$successMessage = '';
$errorMessage = '';

$userName  = $_SESSION['user_name'] ?? '';
$userEmail = $_SESSION['user_email'] ?? '';
$userPhone = $_SESSION['user_phone'] ?? '';


/*
|--------------------------------------------------------------------------
| CONTACT FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $errorMessage = "Please enter your name.";

    } elseif ($email === '') {

        $errorMessage = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errorMessage = "Please enter a valid email address.";

    } elseif ($message === '') {

        $errorMessage = "Please write your message.";

    } elseif (strlen($message) < 5) {

        $errorMessage = "Your message should contain at least 5 characters.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | SAVE MESSAGE
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            INSERT INTO contact_messages
            (
                name,
                email,
                phone,
                subject,
                message,
                status
            )
            VALUES (?, ?, ?, ?, ?, 'new')
        ");


        if ($stmt) {

            $stmt->bind_param(
                "sssss",
                $name,
                $email,
                $phone,
                $subject,
                $message
            );


            if ($stmt->execute()) {

                $successMessage =
                    "Thank you, " . $name .
                    ". Your message has been received. We will get back to you soon.";

                $userName = '';
                $userEmail = '';
                $userPhone = '';

            } else {

                $errorMessage =
                    "Something went wrong. Please try again.";

            }


            $stmt->close();

        } else {

            $errorMessage =
                "Unable to process your message right now.";

        }

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
| ESCAPE FUNCTION
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
        Contact | Ember &amp; Plate
    </title>

    <meta
        name="description"
        content="Contact Ember & Plate for reservations, questions and dining enquiries."
    >


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
    color: inherit;
    text-decoration: none;
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
            rgba(190,145,50,0.12),
            transparent 70%
        );

    pointer-events: none;

    z-index: -1;
}

body::after {

    content: "";

    position: fixed;

    width: 550px;
    height: 550px;

    right: -250px;
    bottom: -250px;

    background:
        radial-gradient(
            circle,
            rgba(190,145,50,0.08),
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
        rgba(5,5,5,0.90);

    backdrop-filter:
        blur(18px);

    border-bottom:
        1px solid
        rgba(255,255,255,0.08);

    z-index: 9999;

    transition: 0.3s ease;
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
   NAV LINKS
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

    min-height: 66vh;

    display: flex;

    align-items: center;

    justify-content: center;

    text-align: center;

    padding:
        150px
        20px
        90px;

    position: relative;
}

.hero-content {

    max-width: 850px;
}

.hero-label {

    color: #b99755;

    font-size: 10px;

    letter-spacing: 5px;

    text-transform: uppercase;

    margin-bottom: 24px;

    animation:
        fadeUp 1s ease forwards;
}

.hero h1 {

    font-family:
        Georgia,
        "Times New Roman",
        serif;

    font-weight: 400;

    font-size:
        clamp(
            50px,
            8vw,
            95px
        );

    line-height: 0.95;

    letter-spacing: -3px;

    margin-bottom: 28px;

    animation:
        fadeUp 1s 0.15s ease both;
}

.hero h1 span {

    color: #b99650;

    font-style: italic;
}

.hero p {

    max-width: 620px;

    margin: auto;

    color: #929292;

    font-size: 14px;

    line-height: 1.9;

    animation:
        fadeUp 1s 0.3s ease both;
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
   CONTACT SECTION
========================================================= */

.contact-section {

    padding:
        40px
        0
        130px;
}

.contact-grid {

    display: grid;

    grid-template-columns:
        0.75fr
        1.25fr;

    gap: 70px;

    align-items: start;
}


/* =========================================================
   LEFT INFO
========================================================= */

.contact-info {

    padding:
        15px
        0;
}

.info-title {

    font-family: Georgia, serif;

    font-size: 38px;

    font-weight: 400;

    margin-bottom: 18px;
}

.info-description {

    color: #858585;

    font-size: 13px;

    line-height: 1.9;

    margin-bottom: 40px;
}


/* =========================================================
   INFO ITEMS
========================================================= */

.info-item {

    display: flex;

    gap: 18px;

    padding:
        22px
        0;

    border-bottom:
        1px solid
        rgba(255,255,255,0.07);
}

.info-icon {

    width: 42px;
    height: 42px;

    flex: 0 0 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    border:
        1px solid
        rgba(185,145,65,0.45);

    color: #c4a15a;

    font-size: 15px;
}

.info-item h4 {

    font-family: Georgia, serif;

    font-size: 16px;

    font-weight: 400;

    margin-bottom: 7px;
}

.info-item p {

    color: #777;

    font-size: 12px;

    line-height: 1.7;
}


/* =========================================================
   FORM
========================================================= */

.contact-form {

    position: relative;

    background: #0c0c0c;

    border:
        1px solid
        rgba(255,255,255,0.08);

    padding: 45px;

    overflow: hidden;
}

.contact-form::before {

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
            #c6a45b,
            transparent
        );
}

.form-heading {

    margin-bottom: 30px;
}

.form-heading small {

    color: #b99755;

    font-size: 9px;

    letter-spacing: 4px;

    text-transform: uppercase;
}

.form-heading h2 {

    font-family: Georgia, serif;

    font-size: 36px;

    font-weight: 400;

    margin-top: 10px;
}


/* =========================================================
   FORM ROW
========================================================= */

.form-row {

    display: grid;

    grid-template-columns:
        1fr
        1fr;

    gap: 18px;
}

.form-group {

    margin-bottom: 20px;
}

.form-group label {

    display: block;

    color: #aaa;

    font-size: 10px;

    letter-spacing: 1.5px;

    text-transform: uppercase;

    margin-bottom: 9px;
}

.form-group input,
.form-group textarea {

    width: 100%;

    border:
        1px solid
        #252525;

    outline: none;

    background: #080808;

    color: #eee;

    padding:
        15px
        16px;

    font-size: 13px;

    transition: 0.3s ease;
}

.form-group input {

    height: 50px;
}

.form-group textarea {

    min-height: 150px;

    resize: vertical;

    line-height: 1.7;
}

.form-group input:focus,
.form-group textarea:focus {

    border-color: #a98235;

    box-shadow:
        0 0 25px
        rgba(185,145,65,0.07);
}


/* =========================================================
   SEND BUTTON
========================================================= */

.send-btn {

    width: 100%;

    padding: 16px;

    border: none;

    cursor: pointer;

    background:
        linear-gradient(
            90deg,
            #98712e,
            #d0ad62
        );

    color: #070707;

    font-weight: bold;

    letter-spacing: 2px;

    text-transform: uppercase;

    transition: 0.3s ease;
}

.send-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 30px
        rgba(185,145,65,0.17);
}


/* =========================================================
   ALERT
========================================================= */

.alert {

    margin-bottom: 30px;

    padding:
        16px
        20px;

    font-size: 13px;

    border: 1px solid;
}

.alert-success {

    color: #a6d8ae;

    border-color:
        rgba(80,170,95,0.35);

    background:
        rgba(80,170,95,0.06);
}

.alert-error {

    color: #e8a1a1;

    border-color:
        rgba(200,80,80,0.35);

    background:
        rgba(200,80,80,0.06);
}


/* =========================================================
   BOTTOM CTA
========================================================= */

.bottom-section {

    padding:
        0
        0
        120px;
}

.bottom-box {

    text-align: center;

    padding:
        80px
        30px;

    border:
        1px solid
        rgba(255,255,255,0.07);

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,0.025),
            transparent
        );
}

.bottom-box small {

    color: #b99755;

    letter-spacing: 4px;

    font-size: 9px;

    text-transform: uppercase;
}

.bottom-box h2 {

    font-family: Georgia, serif;

    font-size:
        clamp(
            32px,
            5vw,
            55px
        );

    font-weight: 400;

    margin:
        15px
        0;
}

.bottom-box p {

    color: #777;

    font-size: 13px;
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

.reveal {

    opacity: 0;

    transform:
        translateY(30px);

    transition:
        opacity 0.8s ease,
        transform 0.8s ease;
}

.reveal.show {

    opacity: 1;

    transform:
        translateY(0);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1000px) {

    .navbar {

        padding:
            0
            20px;
    }

    .nav-links {

        gap: 17px;
    }

    .contact-grid {

        grid-template-columns: 1fr;

        gap: 45px;
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

        padding-top:
            180px;
    }

    .contact-form {

        padding:
            30px
            20px;
    }

    .form-row {

        grid-template-columns: 1fr;
    }

    .bottom-box {

        padding:
            60px
            20px;
    }

}


@media (max-width: 480px) {

    .hero h1 {

        font-size: 50px;

        letter-spacing: -2px;
    }

    .info-title {

        font-size: 32px;
    }

    .contact-form {

        padding:
            28px
            18px;
    }

}

</style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar">


    <!-- BRAND -->

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


    <!-- NAVIGATION -->

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

        <!-- CONTACT -->

        <a
            href="contact.php"
            class="active"
        >
            Contact
        </a>


        <!-- MY ORDERS -->

        <a href="my-orders.php">
            My Orders
        </a>


        <!-- CART -->

        <a
            href="cart.php"
            class="cart-link"
        >

            Cart

            <span class="cart-count">
                <?= $cartCount ?>
            </span>

        </a>


        <!-- LOGOUT -->

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

    <div class="hero-content">

        <div class="hero-label">
            We'd love to hear from you
        </div>

        <h1>
            Let's <span>talk.</span>
        </h1>

        <p>
            Whether you have a question about our menu,
            want to plan a special evening, or simply want
            to say hello — we're here.
        </p>

    </div>

</section>



<!-- =========================================================
     CONTACT SECTION
========================================================= -->

<section class="contact-section">

    <div class="container">


        <!-- SUCCESS -->

        <?php if ($successMessage): ?>

            <div class="alert alert-success">

                <?= e($successMessage) ?>

            </div>

        <?php endif; ?>


        <!-- ERROR -->

        <?php if ($errorMessage): ?>

            <div class="alert alert-error">

                <?= e($errorMessage) ?>

            </div>

        <?php endif; ?>


        <div class="contact-grid">


            <!-- =================================================
                 CONTACT INFORMATION
            ================================================= -->

            <div class="contact-info reveal">

                <div class="info-title">
                    Come say hello.
                </div>

                <p class="info-description">

                    Great food is only part of the experience.
                    If there's anything you'd like to know,
                    our team would be happy to help.

                </p>


                <!-- LOCATION -->

                <div class="info-item">

                    <div class="info-icon">
                        ◇
                    </div>

                    <div>

                        <h4>
                            Visit us
                        </h4>

                        <p>
                            Ember &amp; Plate<br>
                            Fine Dining Restaurant<br>
                            Kolkata, West Bengal
                        </p>

                    </div>

                </div>


                <!-- PHONE -->

                <div class="info-item">

                    <div class="info-icon">
                        ☎
                    </div>

                    <div>

                        <h4>
                            Call us
                        </h4>

                        <p>
                            +91 XXXXX XXXXX
                        </p>

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="info-item">

                    <div class="info-icon">
                        @
                    </div>

                    <div>

                        <h4>
                            Email
                        </h4>

                        <p>
                            hello@emberandplate.com
                        </p>

                    </div>

                </div>


                <!-- HOURS -->

                <div class="info-item">

                    <div class="info-icon">
                        ◷
                    </div>

                    <div>

                        <h4>
                            Opening hours
                        </h4>

                        <p>
                            Monday – Sunday<br>
                            11:00 AM – 11:00 PM
                        </p>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 CONTACT FORM
            ================================================= -->

            <div class="contact-form reveal">

                <div class="form-heading">

                    <small>
                        Send us a message
                    </small>

                    <h2>
                        How can we help?
                    </h2>

                </div>


                <form
                    method="POST"
                    action="contact.php"
                >


                    <!-- NAME + EMAIL -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Your Name *
                            </label>

                            <input
                                type="text"
                                name="name"
                                placeholder="Enter your name"
                                value="<?= e($userName) ?>"
                                maxlength="100"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Email *
                            </label>

                            <input
                                type="email"
                                name="email"
                                placeholder="you@example.com"
                                value="<?= e($userEmail) ?>"
                                maxlength="150"
                                required
                            >

                        </div>

                    </div>



                    <!-- PHONE + SUBJECT -->

                    <div class="form-row">

                        <div class="form-group">

                            <label>
                                Phone
                            </label>

                            <input
                                type="tel"
                                name="phone"
                                placeholder="+91 XXXXX XXXXX"
                                value="<?= e($userPhone) ?>"
                                maxlength="20"
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Subject
                            </label>

                            <input
                                type="text"
                                name="subject"
                                placeholder="How can we help?"
                                maxlength="200"
                            >

                        </div>

                    </div>



                    <!-- MESSAGE -->

                    <div class="form-group">

                        <label>
                            Message *
                        </label>

                        <textarea
                            name="message"
                            placeholder="Write your message here..."
                            maxlength="5000"
                            required
                        ></textarea>

                    </div>



                    <!-- SEND -->

                    <button
                        type="submit"
                        class="send-btn"
                    >
                        Send Message
                    </button>

                </form>

            </div>

        </div>

    </div>

</section>



<!-- =========================================================
     BOTTOM CTA
========================================================= -->

<section class="bottom-section">

    <div class="container">

        <div class="bottom-box reveal">

            <small>
                Ember &amp; Plate
            </small>

            <h2>
                Good food deserves<br>
                good company.
            </h2>

            <p>
                We look forward to welcoming you.
            </p>

        </div>

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



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =====================================================
           SCROLL REVEAL
        ===================================================== */

        const elements =
            document.querySelectorAll(
                ".reveal"
            );


        const observer =
            new IntersectionObserver(
                function (entries) {

                    entries.forEach(
                        function (entry) {

                            if (
                                entry.isIntersecting
                            ) {

                                entry.target.classList.add(
                                    "show"
                                );

                                observer.unobserve(
                                    entry.target
                                );

                            }

                        }
                    );

                },
                {
                    threshold: 0.12
                }
            );


        elements.forEach(
            function (element) {

                observer.observe(element);

            }
        );



        /* =====================================================
           NAVBAR SCROLL EFFECT
        ===================================================== */

        const navbar =
            document.querySelector(
                ".navbar"
            );


        window.addEventListener(
            "scroll",
            function () {

                if (
                    window.scrollY > 30
                ) {

                    navbar.style.background =
                        "rgba(4,4,4,0.97)";

                } else {

                    navbar.style.background =
                        "rgba(5,5,5,0.90)";

                }

            }
        );


    }
);

</script>


</body>

</html>