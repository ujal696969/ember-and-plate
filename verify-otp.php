<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// ======================================================
// CHECK RESET SESSION
// ======================================================

if (
    !isset($_SESSION['reset_user_id']) ||
    !isset($_SESSION['reset_otp']) ||
    !isset($_SESSION['reset_method']) ||
    !isset($_SESSION['reset_otp_expiry'])
) {
    header("Location: forgot-password.php");
    exit;
}


$error = '';


// ======================================================
// REMAINING TIME
// ======================================================

$remainingSeconds =
    max(0, $_SESSION['reset_otp_expiry'] - time());


// ======================================================
// VERIFY OTP
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^\d{6}$/', $otp)) {

        $error = "Please enter the complete 6-digit OTP.";

    } elseif (time() > $_SESSION['reset_otp_expiry']) {

        $error =
            "This OTP has expired. Please request a new OTP.";

    } elseif (
        !hash_equals(
            (string)$_SESSION['reset_otp'],
            $otp
        )
    ) {

        $error =
            "Incorrect OTP. Please check the code and try again.";

    } else {

        // ==============================================
        // OTP VERIFIED
        // ==============================================

        $_SESSION['reset_verified'] = true;

        unset(
            $_SESSION['reset_otp'],
            $_SESSION['reset_otp_expiry']
        );

        header("Location: reset-password.php");
        exit;
    }
}


// ======================================================
// DESTINATION
// ======================================================

$destination =
    $_SESSION['reset_destination'] ?? '';

$method =
    $_SESSION['reset_method'] ?? 'email';


// Mask email / phone
if ($method === 'email') {

    $parts = explode('@', $destination);

    if (count($parts) === 2) {

        $username = $parts[0];
        $domain   = $parts[1];

        if (strlen($username) > 3) {

            $maskedUsername =
                substr($username, 0, 2) .
                str_repeat(
                    '•',
                    max(2, strlen($username) - 2)
                );

        } else {

            $maskedUsername =
                substr($username, 0, 1) .
                '••';
        }

        $maskedDestination =
            $maskedUsername . '@' . $domain;

    } else {

        $maskedDestination = $destination;
    }

} else {

    $digits = preg_replace(
        '/\D+/',
        '',
        $destination
    );

    if (strlen($digits) >= 4) {

        $maskedDestination =
            str_repeat(
                '•',
                strlen($digits) - 4
            ) .
            substr($digits, -4);

    } else {

        $maskedDestination = $destination;
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
    Verify OTP | Ember & Plate
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

    align-items: center;

    justify-content: center;

    padding: 25px;
}


/* =========================================
   CARD
========================================= */

.auth-card {

    width: 100%;

    max-width: 460px;

    padding: 42px;

    background:
        linear-gradient(
            145deg,
            rgba(31,31,31,.97),
            rgba(16,16,16,.98)
        );

    border: 1px solid rgba(255,255,255,.08);

    border-radius: 26px;

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

    margin-bottom: 30px;
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
            #e0b36f,
            #9d6d35
        );

    color: #111;

    font-size: 24px;

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
}


.brand-subtitle {

    color: #777;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 3px;

    margin-top: 5px;
}


/* =========================================
   ICON
========================================= */

.verify-icon {

    width: 70px;

    height: 70px;

    margin: 0 auto 20px;

    border-radius: 22px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        linear-gradient(
            145deg,
            rgba(211,165,96,.14),
            rgba(211,165,96,.04)
        );

    border: 1px solid rgba(211,165,96,.20);

    font-size: 30px;
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

    text-align: center;

    color: #858585;

    font-size: 14px;

    line-height: 1.6;

    margin-bottom: 7px;
}


.destination {

    text-align: center;

    color: #d4a667;

    font-size: 13px;

    font-weight: 600;

    word-break: break-all;

    margin-bottom: 25px;
}


/* =========================================
   ERROR
========================================= */

.error {

    background: rgba(145,37,37,.14);

    border: 1px solid rgba(255,93,93,.18);

    color: #ff9999;

    padding: 13px 15px;

    border-radius: 12px;

    font-size: 13px;

    line-height: 1.5;

    margin-bottom: 20px;

    text-align: center;
}


/* =========================================
   OTP BOXES
========================================= */

.otp-container {

    display: flex;

    justify-content: center;

    gap: 10px;

    margin: 25px 0 18px;
}


.otp-box {

    width: 52px;

    height: 60px;

    background: #101010;

    border: 1px solid #292929;

    border-radius: 13px;

    color: #fff;

    text-align: center;

    font-size: 23px;

    font-weight: 600;

    outline: none;

    transition:
        border .2s ease,
        box-shadow .2s ease,
        transform .2s ease;
}


.otp-box:focus {

    border-color: #c99a59;

    box-shadow:
        0 0 0 3px rgba(201,154,89,.09);

    transform: translateY(-2px);
}


/* =========================================
   TIMER
========================================= */

.timer {

    text-align: center;

    font-size: 12px;

    color: #777;

    margin-bottom: 22px;
}


.timer span {

    color: #d2a263;

    font-weight: 600;
}


.expired {

    color: #e47c7c !important;
}


/* =========================================
   BUTTON
========================================= */

.submit-btn {

    width: 100%;

    height: 54px;

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


.submit-btn:disabled {

    opacity: .45;

    cursor: not-allowed;

    transform: none;

    box-shadow: none;
}


/* =========================================
   ACTIONS
========================================= */

.actions {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 12px;

    margin-top: 20px;
}


.actions a {

    color: #888;

    text-decoration: none;

    font-size: 12px;

    transition: color .2s;
}


.actions a:hover {

    color: #d2a263;
}


.divider {

    width: 3px;

    height: 3px;

    border-radius: 50%;

    background: #444;
}


/* =========================================
   SECURITY
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

        padding: 30px 20px;

        border-radius: 21px;
    }


    .brand-name {

        font-size: 25px;
    }


    .heading {

        font-size: 22px;
    }


    .otp-container {

        gap: 7px;
    }


    .otp-box {

        width: 45px;

        height: 55px;

        font-size: 21px;
    }

}


@media (max-width: 370px) {

    .otp-box {

        width: 40px;

        height: 50px;

        font-size: 19px;
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


    <!-- ICON -->

    <div class="verify-icon">

        <?php
        echo $method === 'phone'
            ? '📱'
            : '📧';
        ?>

    </div>


    <!-- HEADING -->

    <h1 class="heading">

        Verify Your OTP

    </h1>


    <p class="description">

        We've sent a 6-digit verification code to

    </p>


    <div class="destination">

        <?php
        echo htmlspecialchars(
            $maskedDestination
        );
        ?>

    </div>


    <!-- ERROR -->

    <?php if ($error): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- FORM -->

    <form
        method="POST"
        id="otpForm"
    >


        <!-- Hidden real OTP -->

        <input
            type="hidden"
            name="otp"
            id="otp"
        >


        <!-- OTP BOXES -->

        <div class="otp-container">


            <input
                type="text"
                class="otp-box"
                maxlength="1"
                inputmode="numeric"
                autocomplete="one-time-code"
                autofocus
            >


            <input
                type="text"
                class="otp-box"
                maxlength="1"
                inputmode="numeric"
            >


            <input
                type="text"
                class="otp-box"
                maxlength="1"
                inputmode="numeric"
            >


            <input
                type="text"
                class="otp-box"
                maxlength="1"
                inputmode="numeric"
            >


            <input
                type="text"
                class="otp-box"
                maxlength="1"
                inputmode="numeric"
            >


            <input
                type="text"
                class="otp-box"
                maxlength="1"
                inputmode="numeric"
            >


        </div>


        <!-- TIMER -->

        <div class="timer">

            OTP expires in

            <span id="timer">

                10:00

            </span>

        </div>


        <!-- BUTTON -->

        <button
            type="submit"
            class="submit-btn"
            id="verifyButton"
        >

            Verify OTP

        </button>


    </form>


    <!-- ACTIONS -->

    <div class="actions">

        <a href="forgot-password.php">

            Request New OTP

        </a>


        <div class="divider"></div>


        <a href="login.php">

            Back to Login

        </a>

    </div>


    <!-- SECURITY -->

    <div class="security-note">

        🔒 Secure verification

    </div>


</div>



<script>

// ============================================
// OTP BOX LOGIC
// ============================================

const boxes =
    document.querySelectorAll('.otp-box');

const hiddenOtp =
    document.getElementById('otp');

const form =
    document.getElementById('otpForm');


// ============================================
// INPUT
// ============================================

boxes.forEach((box, index) => {

    box.addEventListener('input', function(e) {

        this.value =
            this.value.replace(/\D/g, '');

        if (this.value.length === 1) {

            if (index < boxes.length - 1) {

                boxes[index + 1].focus();

            }

        }

        updateOtp();

    });


    // ========================================
    // BACKSPACE
    // ========================================

    box.addEventListener('keydown', function(e) {

        if (
            e.key === 'Backspace' &&
            this.value === '' &&
            index > 0
        ) {

            boxes[index - 1].focus();

        }

    });

});


// ============================================
// PASTE OTP
// ============================================

boxes[0].addEventListener(
    'paste',
    function(e) {

        e.preventDefault();

        const pasted =
            (
                e.clipboardData ||
                window.clipboardData
            )
            .getData('text')
            .replace(/\D/g, '')
            .slice(0, 6);


        pasted
            .split('')
            .forEach((digit, index) => {

                if (boxes[index]) {

                    boxes[index].value = digit;

                }

            });


        updateOtp();


        if (pasted.length === 6) {

            boxes[5].focus();

        } else {

            boxes[pasted.length].focus();

        }

    }
);


// ============================================
// UPDATE HIDDEN OTP
// ============================================

function updateOtp() {

    let value = '';

    boxes.forEach(box => {

        value += box.value;

    });

    hiddenOtp.value = value;
}


// ============================================
// BEFORE SUBMIT
// ============================================

form.addEventListener(
    'submit',
    function(e) {

        updateOtp();

        if (hiddenOtp.value.length !== 6) {

            e.preventDefault();

            boxes[0].focus();

        }

    }
);


// ============================================
// COUNTDOWN
// ============================================

let remaining =
    <?php echo (int)$remainingSeconds; ?>;

const timer =
    document.getElementById('timer');

const button =
    document.getElementById('verifyButton');


function updateTimer() {

    if (remaining <= 0) {

        timer.textContent =
            'Expired';

        timer.classList.add('expired');

        button.disabled = true;

        return;
    }


    const minutes =
        Math.floor(remaining / 60);

    const seconds =
        remaining % 60;


    timer.textContent =
        String(minutes).padStart(2, '0') +
        ':' +
        String(seconds).padStart(2, '0');


    remaining--;

}


updateTimer();

setInterval(
    updateTimer,
    1000
);

</script>


</body>

</html>