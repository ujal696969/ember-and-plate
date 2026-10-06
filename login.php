<?php

session_start();

require_once __DIR__ . '/../config/db.php';


// ======================================================
// ALREADY LOGGED IN
// ======================================================

if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {

    header("Location: menu.php");
    exit;
}


// ======================================================
// VARIABLES
// ======================================================

$error = '';

$email = '';


// ======================================================
// LOGIN FORM SUBMITTED
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    $password = $_POST['password'] ?? '';


    // ==================================================
    // VALIDATION
    // ==================================================

    if ($email === '') {

        $error = "Please enter your email.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($password === '') {

        $error = "Please enter your password.";

    } else {


        // ==================================================
        // FIND USER
        // ==================================================

        $sql = "
            SELECT
                id,
                name,
                email,
                phone,
                password
            FROM users
            WHERE email = ?
            LIMIT 1
        ";


        $stmt = mysqli_prepare($conn, $sql);


        if (!$stmt) {

            $error = "Database error. Please try again.";

        } else {


            // ==============================================
            // BIND EMAIL
            // ==============================================

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            // ==============================================
            // EXECUTE
            // ==============================================

            mysqli_stmt_execute($stmt);


            $result =
                mysqli_stmt_get_result($stmt);


            // ==============================================
            // CHECK USER
            // ==============================================

            if (
                $result &&
                mysqli_num_rows($result) === 1
            ) {

                $user =
                    mysqli_fetch_assoc($result);


                // ==========================================
                // CHECK PASSWORD
                // ==========================================

                if (
                    password_verify(
                        $password,
                        $user['password']
                    )
                ) {


                    // ======================================
                    // LOGIN SUCCESS
                    // ======================================

                    session_regenerate_id(true);


                    $_SESSION['user_id'] =
                        (int)$user['id'];

                    $_SESSION['user_name'] =
                        $user['name'];

                    $_SESSION['user_email'] =
                        $user['email'];

                    $_SESSION['user_phone'] =
                        $user['phone'];


                    // ======================================
                    // GO TO MENU
                    // ======================================

                    header("Location: menu.php");

                    exit;


                } else {

                    $error =
                        "Incorrect email or password.";

                }


            } else {

                $error =
                    "Incorrect email or password.";

            }


            mysqli_stmt_close($stmt);
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
    Login | Ember & Plate
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
            rgba(214,163,91,.10),
            transparent 30%
        ),

        radial-gradient(
            circle at 85% 80%,
            rgba(214,163,91,.07),
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

    overflow-x: hidden;
}


/* =========================================
   BACKGROUND GLOW
========================================= */

body::before {

    content: "";

    position: fixed;

    width: 420px;

    height: 420px;

    border-radius: 50%;

    background:
        rgba(210,158,83,.05);

    filter: blur(90px);

    top: -170px;

    right: -130px;

    pointer-events: none;
}


/* =========================================
   LOGIN CARD
========================================= */

.login-box {

    width: 100%;

    max-width: 460px;

    background:
        linear-gradient(
            145deg,
            rgba(31,31,31,.97),
            rgba(16,16,16,.98)
        );

    border:
        1px solid rgba(255,255,255,.08);

    border-radius: 26px;

    padding: 42px;

    box-shadow:
        0 30px 80px rgba(0,0,0,.55),
        inset 0 1px 0 rgba(255,255,255,.03);

    animation:
        cardIn .55s ease;
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

    margin-bottom: 32px;
}


.brand-mark {

    width: 54px;

    height: 54px;

    margin: 0 auto 16px;

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

    font-size: 23px;

    font-weight: 800;

    box-shadow:
        0 10px 30px
        rgba(196,140,67,.22);
}


.brand-name {

    font-family:
        "Playfair Display",
        Georgia,
        serif;

    font-size: 29px;

    letter-spacing: .2px;
}


.brand-subtitle {

    color: #777;

    font-size: 10px;

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


.subtitle {

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

    display: flex;

    align-items: center;

    gap: 10px;

    background:
        rgba(145,37,37,.14);

    border:
        1px solid rgba(255,93,93,.18);

    color: #ff9999;

    padding: 13px 15px;

    border-radius: 13px;

    margin-bottom: 20px;

    font-size: 13px;

    line-height: 1.5;
}


.error-icon {

    flex-shrink: 0;

    width: 25px;

    height: 25px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,93,93,.12);
}


/* =========================================
   FORM GROUP
========================================= */

.form-group {

    margin-bottom: 19px;
}


.form-group label {

    display: block;

    color: #aaa;

    font-size: 13px;

    margin-bottom: 9px;
}


/* =========================================
   INPUT
========================================= */

.input-wrapper {

    position: relative;
}


.form-group input {

    width: 100%;

    height: 54px;

    padding:
        0 16px;

    border:
        1px solid #292929;

    border-radius: 13px;

    background: #101010;

    color: #fff;

    outline: none;

    font-family:
        "DM Sans",
        Arial,
        sans-serif;

    font-size: 14px;

    transition:
        border .2s ease,
        box-shadow .2s ease;
}


.form-group input::placeholder {

    color: #555;
}


.form-group input:focus {

    border-color: #bd8d50;

    box-shadow:
        0 0 0 3px
        rgba(189,141,80,.09);
}


/* =========================================
   PASSWORD
========================================= */

.password-wrapper {

    position: relative;
}


.password-wrapper input {

    padding-right: 58px;
}


.show-password {

    position: absolute;

    right: 14px;

    top: 50%;

    transform:
        translateY(-50%);

    border: none;

    background: transparent;

    color: #777;

    cursor: pointer;

    font-size: 17px;

    padding: 4px;

    transition:
        color .2s ease;
}


.show-password:hover {

    color: #d2a263;
}


/* =========================================
   FORGOT
========================================= */

.forgot {

    text-align: right;

    margin-top: -4px;

    margin-bottom: 21px;
}


.forgot a {

    color: #c99a59;

    text-decoration: none;

    font-size: 12px;

    font-weight: 600;

    transition:
        color .2s ease;
}


.forgot a:hover {

    color: #e0b36f;
}


/* =========================================
   LOGIN BUTTON
========================================= */

.login-btn {

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


.login-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 28px
        rgba(190,137,68,.20);
}


.login-btn:active {

    transform:
        translateY(0);
}


/* =========================================
   SIGNUP
========================================= */

.signup-link {

    text-align: center;

    margin-top: 23px;

    color: #666;

    font-size: 13px;
}


.signup-link a {

    color: #d2a263;

    font-weight: 600;

    text-decoration: none;

    margin-left: 4px;
}


.signup-link a:hover {

    color: #e0b36f;
}


/* =========================================
   DIVIDER
========================================= */

.divider {

    display: flex;

    align-items: center;

    gap: 12px;

    margin: 22px 0;
}


.divider::before,
.divider::after {

    content: "";

    height: 1px;

    flex: 1;

    background: #252525;
}


.divider span {

    color: #555;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: 1.5px;
}


/* =========================================
   HOME
========================================= */

.home-link {

    text-align: center;
}


.home-link a {

    color: #666;

    text-decoration: none;

    font-size: 12px;

    transition:
        color .2s ease;
}


.home-link a:hover {

    color: #d2a263;
}


/* =========================================
   SECURITY
========================================= */

.security-note {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 7px;

    color: #4f4f4f;

    font-size: 10px;

    margin-top: 22px;
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 520px) {

    body {

        padding: 15px;
    }


    .login-box {

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


<div class="login-box">


    <!-- =====================================
         BRAND
    ====================================== -->

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



    <!-- =====================================
         HEADING
    ====================================== -->

    <h1 class="heading">

        Welcome Back

    </h1>


    <p class="subtitle">

        Sign in to continue to your account.

    </p>



    <!-- =====================================
         ERROR
    ====================================== -->

    <?php if ($error !== ''): ?>

        <div class="error">

            <div class="error-icon">

                !

            </div>

            <div>

                <?php

                echo htmlspecialchars($error);

                ?>

            </div>

        </div>

    <?php endif; ?>



    <!-- =====================================
         LOGIN FORM
    ====================================== -->

    <form
        method="POST"
        action=""
        autocomplete="on"
    >


        <!-- EMAIL -->

        <div class="form-group">

            <label for="email">

                Email Address

            </label>


            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                value="<?php
                    echo htmlspecialchars($email);
                ?>"
                autocomplete="email"
                required
            >

        </div>



        <!-- PASSWORD -->

        <div class="form-group">

            <label for="password">

                Password

            </label>


            <div class="password-wrapper">


                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >


                <button
                    type="button"
                    class="show-password"
                    onclick="togglePassword()"
                    aria-label="Show password"
                >

                    👁

                </button>


            </div>

        </div>



        <!-- FORGOT PASSWORD -->

        <div class="forgot">

            <a href="forgot-password.php">

                Forgot Password?

            </a>

        </div>



        <!-- LOGIN -->

        <button
            type="submit"
            class="login-btn"
        >

            Sign In

        </button>


    </form>



    <!-- SIGNUP -->

    <div class="signup-link">

        Don't have an account?

        <a href="signup.php">

            Create Account

        </a>

    </div>



    <!-- DIVIDER -->

    <div class="divider">

        <span>
            or
        </span>

    </div>



    <!-- HOME -->

    <div class="home-link">

        <a href="index.php">

            ← Back to Home

        </a>

    </div>



    <!-- SECURITY -->

    <div class="security-note">

        🔒 Secure login protected by Ember & Plate

    </div>


</div>



<script>

function togglePassword() {

    const password =
        document.getElementById("password");

    const button =
        document.querySelector(".show-password");


    if (password.type === "password") {

        password.type = "text";

        button.textContent = "🙈";

        button.setAttribute(
            "aria-label",
            "Hide password"
        );

    } else {

        password.type = "password";

        button.textContent = "👁";

        button.setAttribute(
            "aria-label",
            "Show password"
        );

    }

}

</script>


</body>

</html>