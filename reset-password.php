<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// ======================================================
// CHECK RESET SESSION
// ======================================================

if (
    !isset($_SESSION['reset_user_id']) ||
    !isset($_SESSION['reset_verified']) ||
    $_SESSION['reset_verified'] !== true
) {
    header("Location: forgot-password.php");
    exit;
}


$error = '';


// ======================================================
// FORM SUBMIT
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';


    // ==============================================
    // EMPTY PASSWORD
    // ==============================================

    if ($password === '' || $confirmPassword === '') {

        $error =
            "Please fill in both password fields.";

    }


    // ==============================================
    // PASSWORD LENGTH
    // ==============================================

    elseif (strlen($password) < 8) {

        $error =
            "Password must be at least 8 characters long.";

    }


    // ==============================================
    // PASSWORD MATCH
    // ==============================================

    elseif ($password !== $confirmPassword) {

        $error =
            "Passwords do not match.";

    }


    // ==============================================
    // UPDATE PASSWORD
    // ==============================================

    else {

        $hashedPassword =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        $stmt = $conn->prepare(
            "UPDATE users
             SET password = ?
             WHERE id = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $error =
                "Database error. Please try again.";

        } else {

            $stmt->bind_param(
                "si",
                $hashedPassword,
                $_SESSION['reset_user_id']
            );


            if ($stmt->execute()) {

                // ==================================
                // CLEAR RESET SESSION
                // ==================================

                unset(
                    $_SESSION['reset_user_id'],
                    $_SESSION['reset_method'],
                    $_SESSION['reset_destination'],
                    $_SESSION['reset_verified']
                );


                $stmt->close();

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
                        Password Updated | Ember & Plate
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


                    .success-card {

                        width: 100%;

                        max-width: 460px;

                        padding: 45px 40px;

                        background:
                            linear-gradient(
                                145deg,
                                rgba(31,31,31,.97),
                                rgba(16,16,16,.98)
                            );

                        border: 1px solid rgba(255,255,255,.08);

                        border-radius: 26px;

                        text-align: center;

                        box-shadow:
                            0 30px 80px rgba(0,0,0,.55);

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


                    .brand-mark {

                        width: 55px;

                        height: 55px;

                        margin: 0 auto 18px;

                        border-radius: 16px;

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
                    }


                    .brand-name {

                        font-family:
                            "Playfair Display",
                            Georgia,
                            serif;

                        font-size: 27px;

                        margin-bottom: 30px;
                    }


                    .success-icon {

                        width: 82px;

                        height: 82px;

                        margin: 0 auto 24px;

                        border-radius: 50%;

                        display: flex;

                        align-items: center;

                        justify-content: center;

                        background:
                            rgba(96, 180, 108, .10);

                        border:
                            1px solid rgba(96, 180, 108, .25);

                        color: #82d38b;

                        font-size: 36px;

                        box-shadow:
                            0 10px 40px rgba(96,180,108,.08);
                    }


                    h1 {

                        font-size: 25px;

                        margin-bottom: 10px;
                    }


                    p {

                        color: #858585;

                        font-size: 14px;

                        line-height: 1.7;

                        margin-bottom: 27px;
                    }


                    .login-btn {

                        display: flex;

                        align-items: center;

                        justify-content: center;

                        width: 100%;

                        height: 54px;

                        border-radius: 13px;

                        background:
                            linear-gradient(
                                135deg,
                                #e0b36f,
                                #b47b3e
                            );

                        color: #111;

                        text-decoration: none;

                        font-size: 14px;

                        font-weight: 700;

                        transition:
                            transform .2s ease,
                            box-shadow .2s ease;
                    }


                    .login-btn:hover {

                        transform: translateY(-2px);

                        box-shadow:
                            0 12px 28px rgba(190,137,68,.20);
                    }


                    .security-note {

                        color: #555;

                        font-size: 11px;

                        margin-top: 24px;
                    }


                    @media (max-width: 520px) {

                        body {
                            padding: 15px;
                        }

                        .success-card {
                            padding: 35px 22px;
                            border-radius: 21px;
                        }

                        h1 {
                            font-size: 22px;
                        }

                    }

                    </style>

                </head>


                <body>


                <div class="success-card">


                    <div class="brand-mark">
                        EP
                    </div>


                    <div class="brand-name">
                        Ember & Plate
                    </div>


                    <div class="success-icon">
                        ✓
                    </div>


                    <h1>
                        Password Updated
                    </h1>


                    <p>

                        Your password has been successfully
                        changed. You can now use your new
                        password to sign in to your account.

                    </p>


                    <a
                        href="login.php"
                        class="login-btn"
                    >

                        Continue to Login

                    </a>


                    <div class="security-note">

                        🔒 Your account is protected

                    </div>


                </div>


                </body>

                </html>

                <?php

                exit;

            } else {

                $error =
                    "Unable to update your password. Please try again.";

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
    Reset Password | Ember & Plate
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

.password-icon {

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

    border:
        1px solid rgba(211,165,96,.20);

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

    margin-bottom: 28px;
}


/* =========================================
   ERROR
========================================= */

.error {

    background:
        rgba(145,37,37,.14);

    border:
        1px solid rgba(255,93,93,.18);

    color: #ff9999;

    padding: 13px 15px;

    border-radius: 12px;

    font-size: 13px;

    line-height: 1.5;

    margin-bottom: 20px;
}


/* =========================================
   FIELD
========================================= */

.field {

    margin-bottom: 18px;
}


.field-label {

    display: block;

    color: #aaa;

    font-size: 13px;

    margin-bottom: 9px;
}


.password-wrap {

    position: relative;
}


.password-input {

    width: 100%;

    height: 54px;

    padding:
        0 50px
        0 16px;

    border-radius: 13px;

    border:
        1px solid #292929;

    background: #101010;

    color: #fff;

    outline: none;

    font-size: 14px;

    transition:
        border .2s ease,
        box-shadow .2s ease;
}


.password-input::placeholder {

    color: #555;
}


.password-input:focus {

    border-color: #bd8d50;

    box-shadow:
        0 0 0 3px rgba(189,141,80,.09);
}


/* =========================================
   SHOW PASSWORD
========================================= */

.toggle-password {

    position: absolute;

    right: 15px;

    top: 50%;

    transform: translateY(-50%);

    border: none;

    background: none;

    color: #666;

    cursor: pointer;

    font-size: 18px;

    padding: 4px;
}


.toggle-password:hover {

    color: #d2a263;
}


/* =========================================
   STRENGTH
========================================= */

.strength {

    margin-top: 10px;
}


.strength-bar {

    display: flex;

    gap: 5px;

    height: 4px;

    margin-bottom: 7px;
}


.strength-segment {

    flex: 1;

    border-radius: 10px;

    background: #292929;

    transition: background .2s ease;
}


.strength-text {

    color: #666;

    font-size: 11px;
}


/* =========================================
   REQUIREMENTS
========================================= */

.requirements {

    background: #111;

    border:
        1px solid #242424;

    border-radius: 13px;

    padding: 13px 15px;

    margin-bottom: 20px;
}


.requirement {

    color: #666;

    font-size: 11px;

    margin: 5px 0;
}


.requirement.valid {

    color: #81c98a;
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

    margin-top: 24px;
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 520px) {

    body {
        padding: 15px;
    }


    .auth-card {

        padding: 30px 21px;

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


    <!-- ICON -->

    <div class="password-icon">
        🔐
    </div>


    <!-- HEADING -->

    <h1 class="heading">

        Create New Password

    </h1>


    <p class="description">

        Choose a strong password to keep
        your Ember & Plate account secure.

    </p>


    <!-- ERROR -->

    <?php if ($error): ?>

        <div class="error">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <form
        method="POST"
        id="passwordForm"
    >


        <!-- NEW PASSWORD -->

        <div class="field">

            <label class="field-label">

                New Password

            </label>


            <div class="password-wrap">

                <input
                    type="password"
                    name="password"
                    id="password"
                    class="password-input"
                    placeholder="Enter new password"
                    minlength="8"
                    required
                >


                <button
                    type="button"
                    class="toggle-password"
                    onclick="togglePassword(
                        'password',
                        this
                    )"
                    aria-label="Show password"
                >

                    👁

                </button>

            </div>


            <!-- STRENGTH -->

            <div class="strength">

                <div class="strength-bar">

                    <div
                        class="strength-segment"
                        id="strength1"
                    ></div>

                    <div
                        class="strength-segment"
                        id="strength2"
                    ></div>

                    <div
                        class="strength-segment"
                        id="strength3"
                    ></div>

                    <div
                        class="strength-segment"
                        id="strength4"
                    ></div>

                </div>


                <div
                    class="strength-text"
                    id="strengthText"
                >

                    Password strength

                </div>

            </div>

        </div>


        <!-- CONFIRM PASSWORD -->

        <div class="field">

            <label class="field-label">

                Confirm Password

            </label>


            <div class="password-wrap">

                <input
                    type="password"
                    name="confirm_password"
                    id="confirmPassword"
                    class="password-input"
                    placeholder="Re-enter your password"
                    minlength="8"
                    required
                >


                <button
                    type="button"
                    class="toggle-password"
                    onclick="togglePassword(
                        'confirmPassword',
                        this
                    )"
                    aria-label="Show password"
                >

                    👁

                </button>

            </div>

        </div>


        <!-- REQUIREMENTS -->

        <div class="requirements">

            <div
                class="requirement"
                id="reqLength"
            >
                • At least 8 characters
            </div>


            <div
                class="requirement"
                id="reqUpper"
            >
                • At least one uppercase letter
            </div>


            <div
                class="requirement"
                id="reqNumber"
            >
                • At least one number
            </div>


            <div
                class="requirement"
                id="reqSpecial"
            >
                • At least one special character
            </div>

        </div>


        <!-- BUTTON -->

        <button
            type="submit"
            class="submit-btn"
        >

            Update Password

        </button>


    </form>


    <!-- SECURITY -->

    <div class="security-note">

        🔒 Your new password is securely encrypted

    </div>


</div>



<script>

// ============================================
// SHOW / HIDE PASSWORD
// ============================================

function togglePassword(
    inputId,
    button
) {

    const input =
        document.getElementById(inputId);


    if (input.type === 'password') {

        input.type = 'text';

        button.textContent = '🙈';

    } else {

        input.type = 'password';

        button.textContent = '👁';

    }

}


// ============================================
// PASSWORD STRENGTH
// ============================================

const password =
    document.getElementById('password');

const strengthText =
    document.getElementById('strengthText');

const segments = [

    document.getElementById('strength1'),

    document.getElementById('strength2'),

    document.getElementById('strength3'),

    document.getElementById('strength4')

];


password.addEventListener(
    'input',
    function() {

        const value = this.value;

        let score = 0;


        // Length

        if (value.length >= 8) {

            score++;

        }


        // Uppercase

        if (/[A-Z]/.test(value)) {

            score++;

        }


        // Number

        if (/[0-9]/.test(value)) {

            score++;

        }


        // Special character

        if (/[^A-Za-z0-9]/.test(value)) {

            score++;

        }


        // Reset

        segments.forEach(
            segment => {

                segment.style.background =
                    '#292929';

            }
        );


        if (score === 0) {

            strengthText.textContent =
                'Password strength';

        }


        if (score === 1) {

            segments[0].style.background =
                '#b74a4a';

            strengthText.textContent =
                'Weak password';

        }


        if (score === 2) {

            segments[0].style.background =
                '#c57c4a';

            segments[1].style.background =
                '#c57c4a';

            strengthText.textContent =
                'Fair password';

        }


        if (score === 3) {

            segments[0].style.background =
                '#c6a04e';

            segments[1].style.background =
                '#c6a04e';

            segments[2].style.background =
                '#c6a04e';

            strengthText.textContent =
                'Good password';

        }


        if (score === 4) {

            segments.forEach(
                segment => {

                    segment.style.background =
                        '#72b97b';

                }
            );

            strengthText.textContent =
                'Strong password';

        }


        // Requirements

        checkRequirement(
            'reqLength',
            value.length >= 8
        );

        checkRequirement(
            'reqUpper',
            /[A-Z]/.test(value)
        );

        checkRequirement(
            'reqNumber',
            /[0-9]/.test(value)
        );

        checkRequirement(
            'reqSpecial',
            /[^A-Za-z0-9]/.test(value)
        );

    }
);


// ============================================
// REQUIREMENT CHECK
// ============================================

function checkRequirement(
    id,
    valid
) {

    const element =
        document.getElementById(id);


    if (valid) {

        element.classList.add('valid');

        element.textContent =
            '✓ ' +
            element.textContent
                .replace('• ', '')
                .replace('✓ ', '');

    } else {

        element.classList.remove('valid');

        element.textContent =
            '• ' +
            element.textContent
                .replace('• ', '')
                .replace('✓ ', '');

    }

}

</script>


</body>

</html>