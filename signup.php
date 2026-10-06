<?php

session_start();

require_once __DIR__ . '/../config/db.php';

$message = '';
$message_type = '';


// ======================================================
// SIGNUP FORM SUBMISSION
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $password = $_POST['password'] ?? '';


    // ==================================================
    // VALIDATION
    // ==================================================

    if ($name === '') {

        $message = "Full Name is required.";
        $message_type = "error";

    } elseif ($email === '') {

        $message = "Email is required.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $message_type = "error";

    } else {


        // ==============================================
        // CHECK IF EMAIL ALREADY EXISTS
        // ==============================================

        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );


        if (!$check) {

            $message =
                "Database error: " .
                mysqli_error($conn);

            $message_type = "error";

        } else {

            mysqli_stmt_bind_param(
                $check,
                "s",
                $email
            );

            mysqli_stmt_execute($check);

            $result = mysqli_stmt_get_result($check);

            mysqli_stmt_close($check);


            if (
                $result &&
                mysqli_num_rows($result) > 0
            ) {

                $message =
                    "This email is already registered.";

                $message_type = "error";

            } else {


                // ======================================
                // HASH PASSWORD
                // ======================================

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                // ======================================
                // INSERT USER
                // ======================================

                $sql = "
                    INSERT INTO users
                    (
                        name,
                        email,
                        phone,
                        password
                    )
                    VALUES
                    (?, ?, ?, ?)
                ";


                $stmt = mysqli_prepare(
                    $conn,
                    $sql
                );


                if (!$stmt) {

                    $message =
                        "Registration error: " .
                        mysqli_error($conn);

                    $message_type = "error";

                } else {


                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssss",
                        $name,
                        $email,
                        $phone,
                        $hashed_password
                    );


                    // ==================================
                    // CREATE ACCOUNT
                    // ==================================

                    if (mysqli_stmt_execute($stmt)) {


                        // Get newly created user ID

                        $user_id =
                            mysqli_insert_id($conn);


                        // ==================================
                        // CREATE LOGIN SESSION
                        // ==================================

                        $_SESSION['user_id'] =
                            (int)$user_id;

                        $_SESSION['user_name'] =
                            $name;

                        $_SESSION['user_email'] =
                            $email;


                        mysqli_stmt_close($stmt);


                        // ==================================
                        // REDIRECT TO USER HOME
                        // ==================================

                        header("Location: index.php");

                        exit;


                    } else {


                        $message =
                            "Registration failed: " .
                            mysqli_stmt_error($stmt);

                        $message_type = "error";


                        mysqli_stmt_close($stmt);

                    }

                }

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
    Sign Up | Ember & Plate
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

    width: 450px;

    height: 450px;

    border-radius: 50%;

    background:
        rgba(210,158,83,.05);

    filter: blur(90px);

    top: -160px;

    right: -140px;

    pointer-events: none;
}


/* =========================================
   SIGNUP CARD
========================================= */

.signup-box {

    width: 100%;

    max-width: 470px;

    background:
        linear-gradient(
            145deg,
            rgba(31,31,31,.97),
            rgba(16,16,16,.98)
        );

    border:
        1px solid rgba(255,255,255,.08);

    border-radius: 26px;

    padding: 40px;

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

    margin-bottom: 28px;
}


.brand-mark {

    width: 54px;

    height: 54px;

    margin: 0 auto 15px;

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

    font-size: 24px;

    font-weight: 600;

    margin-bottom: 7px;
}


.subtitle {

    text-align: center;

    color: #858585;

    font-size: 14px;

    line-height: 1.6;

    margin-bottom: 25px;
}


/* =========================================
   MESSAGE
========================================= */

.message {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 13px 15px;

    border-radius: 13px;

    margin-bottom: 19px;

    font-size: 13px;

    line-height: 1.5;
}


.message.error {

    background:
        rgba(145,37,37,.14);

    color: #ff9999;

    border:
        1px solid rgba(255,93,93,.18);
}


.message-icon {

    width: 25px;

    height: 25px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        rgba(255,93,93,.12);

    font-weight: 700;
}


/* =========================================
   FORM GROUP
========================================= */

.form-group {

    margin-bottom: 17px;
}


.form-group label {

    display: block;

    color: #aaa;

    font-size: 13px;

    margin-bottom: 8px;
}


/* =========================================
   INPUT
========================================= */

.form-group input {

    width: 100%;

    height: 52px;

    padding:
        0 15px;

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

    padding-right: 55px;
}


.show-password {

    position: absolute;

    right: 13px;

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
   PASSWORD STRENGTH
========================================= */

.strength {

    margin-top: 9px;
}


.strength-bars {

    display: flex;

    gap: 5px;

    height: 4px;

    margin-bottom: 6px;
}


.strength-bar {

    flex: 1;

    height: 4px;

    border-radius: 10px;

    background: #292929;

    transition:
        background .2s ease;
}


.strength-text {

    color: #555;

    font-size: 10px;
}


/* =========================================
   PASSWORD REQUIREMENT
========================================= */

.password-info {

    display: flex;

    gap: 8px;

    align-items: center;

    margin-top: 8px;

    color: #555;

    font-size: 10px;
}


/* =========================================
   SIGNUP BUTTON
========================================= */

.signup-btn {

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

    margin-top: 4px;

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}


.signup-btn:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 12px 28px
        rgba(190,137,68,.20);
}


.signup-btn:active {

    transform:
        translateY(0);
}


/* =========================================
   LOGIN LINK
========================================= */

.login-link {

    text-align: center;

    margin-top: 22px;

    color: #666;

    font-size: 13px;
}


.login-link a {

    color: #d2a263;

    text-decoration: none;

    font-weight: 600;

    margin-left: 4px;
}


.login-link a:hover {

    color: #e0b36f;
}


/* =========================================
   DIVIDER
========================================= */

.divider {

    display: flex;

    align-items: center;

    gap: 12px;

    margin: 20px 0;
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

    margin-top: 20px;
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 520px) {

    body {

        padding: 15px;
    }


    .signup-box {

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


<div class="signup-box">


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

        Create Your Account

    </h1>


    <p class="subtitle">

        Join Ember & Plate and start your dining journey.

    </p>



    <!-- =====================================
         MESSAGE
    ====================================== -->

    <?php if ($message !== ''): ?>

        <div
            class="message
            <?php
            echo htmlspecialchars($message_type);
            ?>"
        >

            <div class="message-icon">

                !

            </div>


            <div>

                <?php

                echo htmlspecialchars($message);

                ?>

            </div>

        </div>

    <?php endif; ?>



    <!-- =====================================
         SIGNUP FORM
    ====================================== -->

    <form
        method="POST"
        action=""
        autocomplete="on"
    >


        <!-- NAME -->

        <div class="form-group">

            <label for="name">

                Full Name

            </label>


            <input
                type="text"
                id="name"
                name="name"
                placeholder="Enter your full name"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['name'] ?? ''
                    );
                ?>"
                autocomplete="name"
                required
            >

        </div>



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
                    echo htmlspecialchars(
                        $_POST['email'] ?? ''
                    );
                ?>"
                autocomplete="email"
                required
            >

        </div>



        <!-- PHONE -->

        <div class="form-group">

            <label for="phone">

                Phone Number
                <span style="color:#555;">
                    (Optional)
                </span>

            </label>


            <input
                type="tel"
                id="phone"
                name="phone"
                placeholder="Enter your phone number"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['phone'] ?? ''
                    );
                ?>"
                autocomplete="tel"
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
                    placeholder="Minimum 6 characters"
                    minlength="6"
                    autocomplete="new-password"
                    required
                >


                <button
                    type="button"
                    class="show-password"
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

                <div class="strength-bars">

                    <div
                        class="strength-bar"
                        id="bar1"
                    ></div>

                    <div
                        class="strength-bar"
                        id="bar2"
                    ></div>

                    <div
                        class="strength-bar"
                        id="bar3"
                    ></div>

                    <div
                        class="strength-bar"
                        id="bar4"
                    ></div>

                </div>


                <div
                    class="strength-text"
                    id="strengthText"
                >

                    Password strength

                </div>

            </div>


            <div class="password-info">

                🔒 Use a combination of letters,
                numbers and symbols.

            </div>

        </div>



        <!-- BUTTON -->

        <button
            type="submit"
            class="signup-btn"
        >

            Create Account

        </button>


    </form>



    <!-- LOGIN -->

    <div class="login-link">

        Already have an account?

        <a href="login.php">

            Sign In

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

        🔒 Your account details are securely protected

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


    if (input.type === "password") {

        input.type = "text";

        button.textContent = "🙈";

        button.setAttribute(
            "aria-label",
            "Hide password"
        );

    } else {

        input.type = "password";

        button.textContent = "👁";

        button.setAttribute(
            "aria-label",
            "Show password"
        );

    }

}


// ============================================
// PASSWORD STRENGTH
// ============================================

const passwordInput =
    document.getElementById("password");

const strengthText =
    document.getElementById("strengthText");

const bars = [

    document.getElementById("bar1"),

    document.getElementById("bar2"),

    document.getElementById("bar3"),

    document.getElementById("bar4")

];


passwordInput.addEventListener(
    "input",
    function() {

        const value =
            this.value;

        let score = 0;


        // Length

        if (value.length >= 6) {

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


        // Special

        if (/[^A-Za-z0-9]/.test(value)) {

            score++;

        }


        // Reset bars

        bars.forEach(
            bar => {

                bar.style.background =
                    "#292929";

            }
        );


        if (score === 0) {

            strengthText.textContent =
                "Password strength";

        }


        if (score === 1) {

            bars[0].style.background =
                "#b74a4a";

            strengthText.textContent =
                "Weak password";

        }


        if (score === 2) {

            bars[0].style.background =
                "#c57c4a";

            bars[1].style.background =
                "#c57c4a";

            strengthText.textContent =
                "Fair password";

        }


        if (score === 3) {

            bars[0].style.background =
                "#c6a04e";

            bars[1].style.background =
                "#c6a04e";

            bars[2].style.background =
                "#c6a04e";

            strengthText.textContent =
                "Good password";

        }


        if (score === 4) {

            bars.forEach(
                bar => {

                    bar.style.background =
                        "#72b97b";

                }
            );

            strengthText.textContent =
                "Strong password";

        }

    }
);

</script>


</body>

</html>