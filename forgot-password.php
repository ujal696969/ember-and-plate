<?php

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mail.php';

$error = '';

$selectedMethod = $_POST['method'] ?? 'email';
$oldValue = trim($_POST['value'] ?? '');


// ======================================================
// FORM SUBMIT
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $method = $_POST['method'] ?? '';
    $value  = trim($_POST['value'] ?? '');

    $selectedMethod = $method;
    $oldValue = $value;


    // ==================================================
    // VALIDATE METHOD
    // ==================================================

    if (!in_array($method, ['email', 'phone'], true)) {

        $error = "Please select a verification method.";

    }


    // ==================================================
    // EMPTY VALUE
    // ==================================================

    elseif ($value === '') {

        $error = ($method === 'email')
            ? "Please enter your email address."
            : "Please enter your phone number.";

    }


    // ==================================================
    // EMAIL
    // ==================================================

    elseif ($method === 'email') {

        $email = strtolower(trim($value));


        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please enter a valid email address.";

        } else {

            $stmt = $conn->prepare(
                "SELECT id, name, email, phone
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );


            if (!$stmt) {

                $error = "Database error. Please try again.";

            } else {

                $stmt->bind_param("s", $email);

                $stmt->execute();

                $result = $stmt->get_result();


                if ($result->num_rows === 1) {

                    $user = $result->fetch_assoc();


                    // ==================================
                    // GENERATE OTP
                    // ==================================

                    $otp = random_int(100000, 999999);

                    $expiry = time() + 600;


                    // ==================================
                    // CLEAR OLD RESET DATA
                    // ==================================

                    unset(
                        $_SESSION['reset_user_id'],
                        $_SESSION['reset_method'],
                        $_SESSION['reset_destination'],
                        $_SESSION['reset_otp'],
                        $_SESSION['reset_otp_expiry'],
                        $_SESSION['reset_verified']
                    );


                    // ==================================
                    // SAVE RESET DATA
                    // ==================================

                    $_SESSION['reset_user_id'] =
                        $user['id'];

                    $_SESSION['reset_method'] =
                        'email';

                    $_SESSION['reset_destination'] =
                        $user['email'];

                    $_SESSION['reset_otp'] =
                        (string)$otp;

                    $_SESSION['reset_otp_expiry'] =
                        $expiry;


                    // ==================================
                    // SEND EMAIL
                    // ==================================

                    $emailResult = sendOtpEmail(
                        $user['email'],
                        (string)$otp
                    );


                    if (
                        isset($emailResult['success']) &&
                        $emailResult['success'] === true
                    ) {

                        $stmt->close();

                        header(
                            "Location: verify-otp.php"
                        );

                        exit;
                    }


                    // ==================================
                    // EMAIL FAILED
                    // ==================================

                    unset(
                        $_SESSION['reset_user_id'],
                        $_SESSION['reset_method'],
                        $_SESSION['reset_destination'],
                        $_SESSION['reset_otp'],
                        $_SESSION['reset_otp_expiry']
                    );


                    $error =
                        "We couldn't send the OTP. Please try again.";
                }


                else {

                    $error =
                        "No account was found with that email address.";
                }


                $stmt->close();
            }
        }
    }


    // ==================================================
    // PHONE
    // ==================================================

    elseif ($method === 'phone') {

        $phone = preg_replace(
            '/\D+/',
            '',
            $value
        );


        if ($phone === '') {

            $error =
                "Please enter a valid phone number.";

        } else {

            $stmt = $conn->prepare(
                "SELECT id, name, email, phone
                 FROM users
                 WHERE phone = ?
                 LIMIT 1"
            );


            if (!$stmt) {

                $error =
                    "Database error. Please try again.";

            } else {

                $stmt->bind_param(
                    "s",
                    $phone
                );

                $stmt->execute();

                $result =
                    $stmt->get_result();


                if ($result->num_rows === 1) {

                    $error =
                        "Phone OTP is not connected yet. Please use Email OTP.";

                } else {

                    $error =
                        "No account was found with that phone number.";
                }


                $stmt->close();
            }
        }
    }
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
    Forgot Password | Ember & Plate
</title>


<style>

@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap');


* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}


body {

    min-height: 100vh;

    background:
        radial-gradient(
            circle at 15% 20%,
            rgba(214, 163, 91, .10),
            transparent 30%
        ),
        radial-gradient(
            circle at 85% 80%,
            rgba(214, 163, 91, .07),
            transparent 30%
        ),
        #0b0b0b;

    color: #fff;

    font-family:
        "DM Sans",
        Arial,
        sans-serif;

    display: flex;

    justify-content: center;

    align-items: center;

    padding: 25px;

    overflow-x: hidden;
}


/* =========================================
   BACKGROUND GLOW
========================================= */

body::before {

    content: "";

    position: fixed;

    width: 450px;

    height: 450px;

    border-radius: 50%;

    background: rgba(210, 158, 83, .05);

    filter: blur(90px);

    top: -150px;

    right: -150px;

    pointer-events: none;
}


/* =========================================
   MAIN CARD
========================================= */

.auth-card {

    width: 100%;

    max-width: 460px;

    background:
        linear-gradient(
            145deg,
            rgba(31,31,31,.97),
            rgba(16,16,16,.98)
        );

    border: 1px solid rgba(255,255,255,.08);

    border-radius: 26px;

    padding: 42px;

    position: relative;

    box-shadow:
        0 30px 80px rgba(0,0,0,.55),
        inset 0 1px 0 rgba(255,255,255,.03);

    animation: cardIn .55s ease;
}


@keyframes cardIn {

    from {

        opacity: 0;

        transform:
            translateY(18px)
            scale(.98);
    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            scale(1);
    }
}


/* =========================================
   BRAND
========================================= */

.brand {

    text-align: center;

    margin-bottom: 34px;
}


.brand-mark {

    width: 52px;

    height: 52px;

    margin: 0 auto 16px;

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #d9aa68,
            #9d6d35
        );

    color: #111;

    font-size: 25px;

    font-weight: 700;

    box-shadow:
        0 10px 30px rgba(196,140,67,.22);
}


.brand-name {

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size: 28px;

    letter-spacing: .2px;
}


.brand-subtitle {

    color: #777;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 3px;

    margin-top: 5px;
}


/* =========================================
   HEADING
========================================= */

.heading {

    text-align: center;

    font-size: 25px;

    font-weight: 600;

    margin-bottom: 8px;
}


.description {

    color: #858585;

    font-size: 14px;

    line-height: 1.6;

    text-align: center;

    margin-bottom: 28px;
}


/* =========================================
   ERROR
========================================= */

.error {

    background: rgba(145, 37, 37, .14);

    border: 1px solid rgba(255, 93, 93, .18);

    color: #ff9999;

    padding: 13px 15px;

    border-radius: 12px;

    font-size: 13px;

    line-height: 1.5;

    margin-bottom: 20px;
}


/* =========================================
   METHOD LABEL
========================================= */

.method-title {

    font-size: 12px;

    color: #777;

    text-transform: uppercase;

    letter-spacing: 1.5px;

    margin-bottom: 10px;
}


/* =========================================
   METHOD OPTIONS
========================================= */

.methods {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 12px;

    margin-bottom: 24px;
}


.method {

    position: relative;

    border: 1px solid #292929;

    background: #141414;

    border-radius: 15px;

    padding: 17px;

    cursor: pointer;

    transition:
        border .2s ease,
        background .2s ease,
        transform .2s ease;
}


.method:hover {

    border-color: #555;

    transform: translateY(-1px);
}


.method.active {

    border-color: #c99a59;

    background:
        linear-gradient(
            145deg,
            rgba(201,154,89,.12),
            rgba(201,154,89,.04)
        );
}


.method input {

    position: absolute;

    opacity: 0;

    pointer-events: none;
}


.method-icon {

    font-size: 21px;

    margin-bottom: 8px;
}


.method-name {

    font-size: 14px;

    font-weight: 600;
}


.method-info {

    font-size: 11px;

    color: #707070;

    margin-top: 3px;
}


.method.active .method-info {

    color: #aa8a60;
}


/* =========================================
   INPUT
========================================= */

.input-label {

    display: block;

    font-size: 13px;

    color: #aaa;

    margin-bottom: 9px;
}


.input-wrap {

    position: relative;
}


.input {

    width: 100%;

    height: 54px;

    padding: 0 16px;

    border-radius: 13px;

    border: 1px solid #292929;

    background: #101010;

    color: #fff;

    outline: none;

    font-size: 14px;

    transition:
        border .2s ease,
        box-shadow .2s ease;
}


.input::placeholder {

    color: #555;
}


.input:focus {

    border-color: #bd8d50;

    box-shadow:
        0 0 0 3px rgba(189,141,80,.09);
}


/* =========================================
   BUTTON
========================================= */

.submit-btn {

    width: 100%;

    height: 54px;

    margin-top: 18px;

    border: none;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #e0b36f,
            #b47b3e
        );

    color: #111;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}


.submit-btn:hover {

    transform: translateY(-2px);

    box-shadow:
        0 12px 28px rgba(190,137,68,.20);
}


.submit-btn:active {

    transform: translateY(0);
}


/* =========================================
   BACK LOGIN
========================================= */

.back-login {

    text-align: center;

    margin-top: 22px;
}


.back-login a {

    color: #888;

    font-size: 13px;

    text-decoration: none;

    transition: color .2s;
}


.back-login a:hover {

    color: #d2a263;
}


/* =========================================
   SECURITY NOTE
========================================= */

.security-note {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 7px;

    color: #555;

    font-size: 11px;

    margin-top: 25px;
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 520px) {

    body {

        padding: 15px;
    }


    .auth-card {

        padding: 30px 22px;

        border-radius: 21px;
    }


    .brand-name {

        font-size: 25px;
    }


    .heading {

        font-size: 22px;
    }

}


</style>

</head>


<body>


<div class="auth-card">


    <!-- BRAND -->

    <div class="brand">

        <div class="brand-mark">
            EP
        </div>


        <div class="brand-name">
            Ember & Plate
        </div>


        <div class="brand-subtitle">
            Restaurant & Dining
        </div>

    </div>


    <!-- HEADING -->

    <h1 class="heading">

        Forgot Password?

    </h1>


    <p class="description">

        Choose how you'd like to receive your
        one-time verification code.

    </p>


    <!-- ERROR -->

    <?php if ($error): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <form method="POST">


        <!-- METHOD -->

        <div class="method-title">

            Verification Method

        </div>


        <div class="methods">


            <!-- EMAIL -->

            <label
                class="method
                <?php
                echo $selectedMethod === 'email'
                    ? ' active'
                    : '';
                ?>"
            >

                <input
                    type="radio"
                    name="method"
                    value="email"

                    <?php
                    echo $selectedMethod === 'email'
                        ? 'checked'
                        : '';
                    ?>

                    onclick="selectMethod('email')"

                    required
                >


                <div class="method-icon">
                    📧
                </div>


                <div class="method-name">
                    Email
                </div>


                <div class="method-info">
                    Receive OTP by email
                </div>

            </label>



            <!-- PHONE -->

            <label
                class="method
                <?php
                echo $selectedMethod === 'phone'
                    ? ' active'
                    : '';
                ?>"
            >

                <input
                    type="radio"
                    name="method"
                    value="phone"

                    <?php
                    echo $selectedMethod === 'phone'
                        ? 'checked'
                        : '';
                    ?>

                    onclick="selectMethod('phone')"
                >


                <div class="method-icon">
                    📱
                </div>


                <div class="method-name">
                    Phone
                </div>


                <div class="method-info">
                    Receive OTP by SMS
                </div>

            </label>


        </div>



        <!-- INPUT -->

        <label
            class="input-label"
            id="field-label"
        >

            <?php

            echo $selectedMethod === 'phone'
                ? 'Phone Number'
                : 'Email Address';

            ?>

        </label>


        <div class="input-wrap">

            <input

                <?php

                echo $selectedMethod === 'phone'
                    ? 'type="tel"'
                    : 'type="email"';

                ?>

                name="value"

                id="destination"

                class="input"

                value="<?php
                    echo htmlspecialchars($oldValue);
                ?>"

                placeholder="<?php

                echo $selectedMethod === 'phone'

                    ? 'Enter your registered phone number'

                    : 'Enter your registered email address';

                ?>"

                autocomplete="email"

                required
            >

        </div>



        <!-- BUTTON -->

        <button
            type="submit"
            class="submit-btn"
        >

            Send Verification Code

        </button>


    </form>



    <!-- BACK -->

    <div class="back-login">

        <a href="login.php">

            ← Back to Login

        </a>

    </div>


    <!-- SECURITY -->

    <div class="security-note">

        🔒 Your account information is protected

    </div>


</div>



<script>

function selectMethod(method) {

    const emailOption =
        document.querySelectorAll('.method')[0];

    const phoneOption =
        document.querySelectorAll('.method')[1];

    const label =
        document.getElementById('field-label');

    const input =
        document.getElementById('destination');


    if (method === 'email') {

        emailOption.classList.add('active');

        phoneOption.classList.remove('active');


        label.textContent =
            'Email Address';


        input.type =
            'email';


        input.placeholder =
            'Enter your registered email address';


        input.autocomplete =
            'email';


    } else {

        phoneOption.classList.add('active');

        emailOption.classList.remove('active');


        label.textContent =
            'Phone Number';


        input.type =
            'tel';


        input.placeholder =
            'Enter your registered phone number';


        input.autocomplete =
            'tel';

    }

}

</script>


</body>

</html>