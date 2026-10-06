<?php
session_start();

require_once __DIR__ . '/../config/db.php';

$isLoggedIn = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;


/* =====================================================
   REAL REVIEW / RATING DATA
===================================================== */

$totalReviews = 0;
$averageRating = 0;

$statsSql = "
    SELECT
        COUNT(*) AS total_reviews,
        COALESCE(AVG(rating), 0) AS average_rating
    FROM reviews
    WHERE status = 'approved'
";

$statsResult = mysqli_query($conn, $statsSql);

if ($statsResult) {

    $stats = mysqli_fetch_assoc($statsResult);

    $totalReviews = (int)($stats['total_reviews'] ?? 0);
    $averageRating = (float)($stats['average_rating'] ?? 0);

}


/* =====================================================
   REAL RATING BREAKDOWN
===================================================== */

$ratingCounts = [
    5 => 0,
    4 => 0,
    3 => 0,
    2 => 0,
    1 => 0
];

$ratingSql = "
    SELECT rating, COUNT(*) AS total
    FROM reviews
    WHERE status = 'approved'
    GROUP BY rating
";

$ratingResult = mysqli_query($conn, $ratingSql);

if ($ratingResult) {

    while ($row = mysqli_fetch_assoc($ratingResult)) {

        $rating = (int)$row['rating'];

        if (isset($ratingCounts[$rating])) {

            $ratingCounts[$rating] = (int)$row['total'];

        }

    }

}


/* =====================================================
   REAL CUSTOMER REVIEWS
===================================================== */

$reviews = [];

$reviewSql = "
    SELECT
        r.rating,
        r.review_text,
        r.created_at,
        u.name
    FROM reviews r
    INNER JOIN users u
        ON r.user_id = u.id
    WHERE r.status = 'approved'
    ORDER BY r.created_at DESC
    LIMIT 6
";

$reviewResult = mysqli_query($conn, $reviewSql);

if ($reviewResult) {

    while ($row = mysqli_fetch_assoc($reviewResult)) {

        $reviews[] = $row;

    }

}

?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>About | Ember & Plate</title>

<meta name="description"
content="Discover the story, philosophy and experience behind Ember & Plate.">


<link rel="preconnect"
href="https://fonts.googleapis.com">

<link rel="preconnect"
href="https://fonts.gstatic.com"
crossorigin>


<link
href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
rel="stylesheet">


<style>

/* =====================================================
   ROOT
===================================================== */

:root{

    --black:#080807;
    --black2:#0d0d0b;

    --cream:#f4efe5;

    --muted:#9d9990;

    --gold:#c8a45d;
    --gold-light:#e2c681;

    --line:rgba(255,255,255,.09);

}


*{

    margin:0;

    padding:0;

    box-sizing:border-box;

}


html{

    scroll-behavior:smooth;

}


body{

    background:var(--black);

    color:var(--cream);

    font-family:"DM Sans",sans-serif;

    overflow-x:hidden;

}


a{

    text-decoration:none;

    color:inherit;

}


img{

    max-width:100%;

    display:block;

}


/* =====================================================
   CURSOR GLOW
===================================================== */

.cursor-glow{

    position:fixed;

    width:300px;

    height:300px;

    border-radius:50%;

    background:rgba(200,164,93,.055);

    filter:blur(70px);

    pointer-events:none;

    transform:translate(-50%,-50%);

    z-index:0;

}


/* =====================================================
   NAVBAR
===================================================== */

.navbar{

    position:fixed;

    top:0;

    left:0;

    width:100%;

    height:82px;

    z-index:1000;

    background:rgba(8,8,7,.82);

    backdrop-filter:blur(18px);

    border-bottom:1px solid rgba(255,255,255,.06);

}


.nav-inner{

    width:min(1250px,94%);

    height:100%;

    margin:auto;

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:25px;

}


.brand{

    display:flex;

    align-items:center;

    gap:12px;

    flex-shrink:0;

}


.brand-mark{

    width:40px;

    height:40px;

    border:1px solid rgba(200,164,93,.65);

    display:flex;

    align-items:center;

    justify-content:center;

    font-family:"Playfair Display",serif;

    color:var(--gold-light);

    font-size:13px;

}


.brand-name{

    font-family:"Playfair Display",serif;

    font-size:19px;

}


.brand-sub{

    display:block;

    color:var(--gold);

    font-size:8px;

    letter-spacing:3px;

    margin-top:2px;

}


.nav-links{

    display:flex;

    align-items:center;

    gap:27px;

}


.nav-links a{

    color:#d0ccc2;

    font-size:11px;

    letter-spacing:1.1px;

    text-transform:uppercase;

    position:relative;

    white-space:nowrap;

}


.nav-links a::after{

    content:"";

    position:absolute;

    left:0;

    bottom:-8px;

    width:0;

    height:1px;

    background:var(--gold);

    transition:.3s;

}


.nav-links a:hover,

.nav-links a.active{

    color:var(--gold-light);

}


.nav-links a:hover::after,

.nav-links a.active::after{

    width:100%;

}


.nav-right{

    display:flex;

    align-items:center;

    gap:15px;

}


.login-link{

    font-size:11px;

    color:#d0ccc2;

}


.reserve-btn{

    padding:11px 19px;

    border:1px solid var(--gold);

    color:var(--gold-light);

    font-size:10px;

    letter-spacing:1.2px;

    text-transform:uppercase;

    transition:.3s;

}


.reserve-btn:hover{

    background:var(--gold);

    color:#090909;

}


/* =====================================================
   HERO
===================================================== */

.hero{

    min-height:100vh;

    position:relative;

    overflow:hidden;

    display:flex;

    align-items:center;

}


.hero-bg{

    position:absolute;

    inset:0;

    background:

        linear-gradient(
            90deg,
            rgba(8,8,7,.97) 0%,
            rgba(8,8,7,.84) 40%,
            rgba(8,8,7,.38) 75%,
            rgba(8,8,7,.82) 100%
        ),

        url("../assets/images/chicken-biryani.jpg")
        center/cover no-repeat;

    transform:scale(1.08);

    animation:heroZoom 12s ease forwards;

}


@keyframes heroZoom{

    to{

        transform:scale(1);

    }

}


.hero-overlay{

    position:absolute;

    inset:0;

    background:

        radial-gradient(
            circle at 75% 45%,
            rgba(200,164,93,.17),
            transparent 28%
        );

}


.hero-inner{

    width:min(1200px,92%);

    margin:auto;

    position:relative;

    z-index:2;

    padding-top:90px;

}


.hero-content{

    max-width:760px;

    animation:heroContent 1.2s ease .2s both;

}


@keyframes heroContent{

    from{

        opacity:0;

        transform:translateY(45px);

    }

    to{

        opacity:1;

        transform:translateY(0);

    }

}


.eyebrow{

    display:flex;

    align-items:center;

    gap:13px;

    color:var(--gold-light);

    font-size:10px;

    letter-spacing:5px;

    text-transform:uppercase;

    margin-bottom:25px;

}


.eyebrow::before{

    content:"";

    width:50px;

    height:1px;

    background:var(--gold);

}


.hero h1{

    font-family:"Playfair Display",serif;

    font-size:clamp(60px,8vw,105px);

    line-height:.92;

    font-weight:500;

    letter-spacing:-4px;

}


.hero h1 span{

    display:block;

    color:var(--gold-light);

    font-style:italic;

}


.hero-description{

    max-width:600px;

    color:#aaa69c;

    font-size:15px;

    line-height:2;

    margin-top:32px;

}


.hero-scroll{

    position:absolute;

    left:4%;

    bottom:40px;

    z-index:3;

    writing-mode:vertical-rl;

    color:#8d8981;

    font-size:9px;

    letter-spacing:3px;

    text-transform:uppercase;

}


.hero-scroll::before{

    content:"";

    display:block;

    width:1px;

    height:60px;

    margin:auto auto 12px;

    background:linear-gradient(
        var(--gold),
        transparent
    );

}


/* =====================================================
   STORY
===================================================== */

.section{

    padding:130px 0;

}


.container{

    width:min(1200px,92%);

    margin:auto;

}


.story{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:100px;

    align-items:center;

}


.story-visual{

    height:600px;

    position:relative;

    overflow:hidden;

}


.story-image{

    position:absolute;

    inset:25px;

    background:

        linear-gradient(
            rgba(0,0,0,.1),
            rgba(0,0,0,.5)
        ),

        url("../assets/images/chicken-biryani.jpg")
        center/cover no-repeat;

    transition:transform 1s ease;

}


.story-visual:hover .story-image{

    transform:scale(1.05);

}


.story-border{

    position:absolute;

    inset:0;

    border:1px solid rgba(226,198,129,.35);

    z-index:2;

}


.story-number{

    position:absolute;

    right:25px;

    bottom:15px;

    z-index:3;

    font-family:"Playfair Display",serif;

    font-size:110px;

    color:rgba(255,255,255,.12);

}


.section-label{

    color:var(--gold);

    font-size:9px;

    letter-spacing:4px;

    text-transform:uppercase;

    margin-bottom:20px;

}


.story-content h2{

    font-family:"Playfair Display",serif;

    font-size:clamp(45px,5vw,70px);

    font-weight:500;

    line-height:1;

    margin-bottom:30px;

}


.story-content h2 span{

    color:var(--gold-light);

}


.story-content p{

    color:#99958d;

    font-size:14px;

    line-height:2;

    margin-bottom:20px;

}


.signature{

    font-family:"Playfair Display",serif;

    font-size:21px;

    font-style:italic;

    color:#ded6c7;

    margin-top:30px;

}


/* =====================================================
   PHILOSOPHY
===================================================== */

.philosophy{

    background:#0d0d0b;

    position:relative;

}


.philosophy-header{

    max-width:700px;

    margin:0 auto 65px;

    text-align:center;

}


.philosophy-header h2{

    font-family:"Playfair Display",serif;

    font-size:clamp(45px,5vw,65px);

    font-weight:500;

}


.philosophy-header h2 span{

    color:var(--gold-light);

    font-style:italic;

}


.philosophy-header p{

    color:#88857e;

    font-size:14px;

    line-height:1.9;

    margin-top:20px;

}


.philosophy-grid{

    display:grid;

    grid-template-columns:repeat(3,1fr);

    gap:18px;

}


.philosophy-card{

    min-height:340px;

    padding:38px;

    background:#11110f;

    border:1px solid var(--line);

    position:relative;

    overflow:hidden;

    transition:.5s;

}


.philosophy-card::before{

    content:"";

    position:absolute;

    width:180px;

    height:180px;

    border-radius:50%;

    background:rgba(200,164,93,.08);

    filter:blur(60px);

    right:-90px;

    top:-90px;

    transition:.5s;

}


.philosophy-card:hover{

    transform:translateY(-10px);

    border-color:rgba(200,164,93,.45);

}


.philosophy-card:hover::before{

    transform:scale(1.6);

}


.card-number{

    color:rgba(226,198,129,.4);

    font-family:"Playfair Display",serif;

    font-size:18px;

    margin-bottom:60px;

}


.philosophy-icon{

    font-size:35px;

    margin-bottom:22px;

}


.philosophy-card h3{

    font-family:"Playfair Display",serif;

    font-size:28px;

    font-weight:500;

    margin-bottom:13px;

}


.philosophy-card p{

    color:#77746e;

    font-size:12px;

    line-height:1.8;

}


/* =====================================================
   REAL STATS
===================================================== */

.stats{

    padding:110px 0;

    border-top:1px solid var(--line);

    border-bottom:1px solid var(--line);

}


.stats-grid{

    display:grid;

    grid-template-columns:repeat(3,1fr);

    text-align:center;

}


.stat{

    padding:20px;

    border-right:1px solid var(--line);

}


.stat:last-child{

    border-right:none;

}


.stat-number{

    font-family:"Playfair Display",serif;

    font-size:65px;

    color:var(--gold-light);

}


.stat-label{

    color:#77746e;

    font-size:10px;

    letter-spacing:3px;

    text-transform:uppercase;

    margin-top:8px;

}


/* =====================================================
   REAL CUSTOMER LOVE
===================================================== */

.customer-love{

    background:#080807;

}


.love-header{

    display:flex;

    justify-content:space-between;

    align-items:end;

    margin-bottom:50px;

}


.love-header h2{

    font-family:"Playfair Display",serif;

    font-size:clamp(45px,5vw,65px);

    font-weight:500;

}


.love-header h2 span{

    color:var(--gold-light);

}


.view-reviews{

    color:var(--gold-light);

    font-size:10px;

    letter-spacing:2px;

    text-transform:uppercase;

    border-bottom:1px solid var(--gold);

    padding-bottom:7px;

}


/* =====================================================
   REAL RATING
===================================================== */

.rating-box{

    display:grid;

    grid-template-columns:280px 1fr;

    gap:60px;

    margin-bottom:55px;

}


.average{

    text-align:center;

    padding:35px;

    background:#10100e;

    border:1px solid var(--line);

}


.average-number{

    font-family:"Playfair Display",serif;

    font-size:72px;

    color:var(--gold-light);

}


.average-stars{

    color:var(--gold-light);

    letter-spacing:4px;

    margin:7px 0 12px;

}


.average-text{

    color:#66635d;

    font-size:10px;

    letter-spacing:1px;

}


.rating-bars{

    display:flex;

    flex-direction:column;

    justify-content:center;

    gap:14px;

}


.rating-row{

    display:grid;

    grid-template-columns:40px 1fr 40px;

    gap:12px;

    align-items:center;

    font-size:10px;

    color:#88857e;

}


.rating-track{

    height:5px;

    background:#25241f;

}


.rating-fill{

    width:0;

    height:100%;

    background:var(--gold);

    transition:width 1.5s cubic-bezier(.2,.8,.2,1);

}


/* =====================================================
   REVIEW CARDS
===================================================== */

.review-grid{

    display:grid;

    grid-template-columns:repeat(3,1fr);

    gap:18px;

}


.review-card{

    min-height:300px;

    padding:30px;

    background:#10100e;

    border:1px solid var(--line);

    position:relative;

    transition:.4s;

}


.review-card:hover{

    transform:translateY(-8px);

    border-color:rgba(200,164,93,.4);

}


.review-stars{

    color:var(--gold-light);

    letter-spacing:3px;

    font-size:11px;

    margin-bottom:22px;

}


.review-card p{

    color:#aaa69c;

    font-family:"Playfair Display",serif;

    font-size:17px;

    line-height:1.7;

}


.review-author{

    position:absolute;

    left:30px;

    right:30px;

    bottom:25px;

    padding-top:15px;

    border-top:1px solid var(--line);

    display:flex;

    justify-content:space-between;

}


.review-name{

    color:#ddd5c6;

    font-size:11px;

}


.review-date{

    color:#66635d;

    font-size:10px;

}


/* EMPTY REVIEWS */

.no-reviews{

    padding:70px 20px;

    text-align:center;

    border:1px solid var(--line);

    background:#10100e;

}


.no-reviews h3{

    font-family:"Playfair Display",serif;

    font-size:30px;

    margin-bottom:10px;

}


.no-reviews p{

    color:#77746e;

    font-size:13px;

}


/* =====================================================
   CTA
===================================================== */

.cta{

    padding:140px 0;

    text-align:center;

    position:relative;

    overflow:hidden;

    background:

        radial-gradient(
            circle at center,
            rgba(200,164,93,.13),
            transparent 40%
        );

}


.cta::before{

    content:"EMBER";

    position:absolute;

    left:50%;

    top:50%;

    transform:translate(-50%,-50%);

    font-family:"Playfair Display",serif;

    font-size:20vw;

    color:rgba(255,255,255,.018);

    white-space:nowrap;

}


.cta-content{

    position:relative;

    z-index:2;

}


.cta h2{

    font-family:"Playfair Display",serif;

    font-size:clamp(48px,6vw,80px);

    font-weight:500;

    line-height:1;

}


.cta h2 span{

    color:var(--gold-light);

    font-style:italic;

}


.cta p{

    color:#88857e;

    margin:25px auto 32px;

    font-size:14px;

}


.cta-buttons{

    display:flex;

    justify-content:center;

    gap:15px;

}


.btn{

    padding:14px 25px;

    font-size:10px;

    letter-spacing:1.5px;

    text-transform:uppercase;

    transition:.35s;

}


.btn-gold{

    background:var(--gold);

    color:#080807;

}


.btn-gold:hover{

    background:var(--gold-light);

    transform:translateY(-4px);

}


.btn-outline{

    border:1px solid #44413a;

    color:#ddd5c6;

}


.btn-outline:hover{

    border-color:var(--gold);

    color:var(--gold-light);

}


/* =====================================================
   FOOTER
===================================================== */

footer{

    border-top:1px solid var(--line);

    padding:30px 5%;

    display:flex;

    justify-content:space-between;

    color:#66635d;

    font-size:10px;

}


/* =====================================================
   SCROLL REVEAL
===================================================== */

.reveal{

    opacity:0;

    transform:translateY(45px);

    transition:

        opacity .9s ease,

        transform .9s cubic-bezier(.2,.7,.2,1);

}


.reveal.active{

    opacity:1;

    transform:translateY(0);

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:900px){

    .navbar{

        height:115px;

    }


    .nav-inner{

        width:94%;

        display:grid;

        grid-template-columns:1fr auto;

        grid-template-rows:65px 45px;

    }


    .brand{

        grid-column:1;

        grid-row:1;

    }


    .nav-right{

        grid-column:2;

        grid-row:1;

    }


    .nav-links{

        grid-column:1 / -1;

        grid-row:2;

        display:flex;

        overflow-x:auto;

        gap:22px;

        scrollbar-width:none;

    }


    .nav-links::-webkit-scrollbar{

        display:none;

    }


    .nav-links a{

        flex-shrink:0;

        font-size:9px;

    }


    .login-link{

        display:none;

    }


    .reserve-btn{

        padding:9px 12px;

        font-size:8px;

    }


    .hero{

        padding-top:115px;

        min-height:800px;

    }


    .hero h1{

        font-size:60px;

    }


    .story{

        grid-template-columns:1fr;

        gap:50px;

    }


    .story-visual{

        height:480px;

    }


    .philosophy-grid{

        grid-template-columns:1fr;

    }


    .stats-grid{

        grid-template-columns:1fr;

        gap:30px;

    }


    .stat{

        border-right:none;

        border-bottom:1px solid var(--line);

        padding-bottom:30px;

    }


    .stat:last-child{

        border-bottom:none;

    }


    .rating-box{

        grid-template-columns:1fr;

        gap:35px;

    }


    .review-grid{

        grid-template-columns:1fr 1fr;

    }


}


@media(max-width:600px){

    .brand-name{

        font-size:16px;

    }


    .brand-mark{

        width:36px;

        height:36px;

    }


    .hero{

        min-height:720px;

    }


    .hero h1{

        font-size:54px;

        letter-spacing:-2px;

    }


    .hero-description{

        font-size:13px;

    }


    .section{

        padding:90px 0;

    }


    .story-visual{

        height:400px;

    }


    .story-content h2{

        font-size:45px;

    }


    .philosophy-card{

        min-height:300px;

    }


    .stat-number{

        font-size:55px;

    }


    .love-header{

        flex-direction:column;

        align-items:flex-start;

        gap:20px;

    }


    .review-grid{

        grid-template-columns:1fr;

    }


    .review-card{

        min-height:280px;

    }


    .cta{

        padding:100px 0;

    }


    .cta-buttons{

        flex-direction:column;

        align-items:center;

    }


    footer{

        flex-direction:column;

        gap:10px;

        text-align:center;

    }

}

</style>

</head>


<body>


<!-- CURSOR GLOW -->

<div class="cursor-glow" id="cursorGlow"></div>



<!-- =====================================================
     NAVBAR
===================================================== -->

<header class="navbar">

    <div class="nav-inner">


        <a href="index.php" class="brand">

            <div class="brand-mark">
                EP
            </div>

            <div>

                <div class="brand-name">
                    Ember & Plate
                </div>

                <span class="brand-sub">
                    Fine Dining
                </span>

            </div>

        </a>



        <nav class="nav-links">

            <a href="index.php">
                Home
            </a>

            <a href="menu.php">
                Menu
            </a>

            <a href="about.php" class="active">
                About
            </a>

            <a href="reviews.php">
                Reviews
            </a>

            <a href="contact.php">
                Contact
            </a>

            <a href="cart.php">
                Cart
            </a>

        </nav>



        <div class="nav-right">

            <?php if($isLoggedIn): ?>

                <a href="logout.php" class="reserve-btn">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.php" class="login-link">
                    Login
                </a>

                <a href="signup.php" class="reserve-btn">
                    Sign Up
                </a>

            <?php endif; ?>

        </div>


    </div>

</header>



<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">

    <div class="hero-bg"></div>

    <div class="hero-overlay"></div>


    <div class="hero-inner">


        <div class="hero-content">


            <div class="eyebrow">
                The Story Behind The Plate
            </div>


            <h1>

                Food with
                <span>fire.</span>

                <br>

                Served with
                <span>soul.</span>

            </h1>


            <p class="hero-description">

                Ember & Plate was built around one simple belief:
                memorable food is not only about taste.
                It is about the people, the atmosphere and
                the moments created around the table.

            </p>


        </div>


    </div>



    <div class="hero-scroll">

        Scroll to discover

    </div>

</section>



<!-- =====================================================
     STORY
===================================================== -->

<section class="section">


    <div class="container">


        <div class="story reveal">


            <div class="story-visual">

                <div class="story-image"></div>

                <div class="story-border"></div>

                <div class="story-number">
                    01
                </div>

            </div>



            <div class="story-content">


                <div class="section-label">
                    Our Beginning
                </div>


                <h2>

                    It started with
                    <span>a plate.</span>

                </h2>


                <p>

                    Ember & Plate was imagined as a place where
                    food could feel both familiar and exciting.
                    A place where classic flavours meet modern
                    presentation.

                </p>


                <p>

                    We focus on honest ingredients, carefully
                    balanced spices and food prepared with
                    patience.

                </p>


                <p>

                    But the real heart of Ember & Plate isn't
                    inside the kitchen.

                </p>


                <p>

                    It is around the table — where friends meet,
                    families celebrate and new memories begin.

                </p>


                <div class="signature">

                    — The Ember & Plate Kitchen

                </div>


            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     PHILOSOPHY
===================================================== -->

<section class="section philosophy">


    <div class="container">


        <div class="philosophy-header reveal">


            <div class="section-label">
                What Drives Us
            </div>


            <h2>

                Our
                <span>philosophy.</span>

            </h2>


            <p>

                Three principles guide every dish,
                every service and every experience
                at Ember & Plate.

            </p>


        </div>



        <div class="philosophy-grid">


            <div class="philosophy-card reveal">

                <div class="card-number">
                    01
                </div>

                <div class="philosophy-icon">
                    ◈
                </div>

                <h3>
                    Quality
                </h3>

                <p>

                    We believe great food begins with
                    great ingredients. Every element
                    deserves attention.

                </p>

            </div>



            <div class="philosophy-card reveal">

                <div class="card-number">
                    02
                </div>

                <div class="philosophy-icon">
                    ◇
                </div>

                <h3>
                    Flavour
                </h3>

                <p>

                    Bold, balanced and memorable.
                    Our food is designed to make
                    every bite count.

                </p>

            </div>



            <div class="philosophy-card reveal">

                <div class="card-number">
                    03
                </div>

                <div class="philosophy-icon">
                    ✦
                </div>

                <h3>
                    Experience
                </h3>

                <p>

                    From the first welcome to the last
                    bite, every detail should feel
                    worth remembering.

                </p>

            </div>


        </div>


    </div>

</section>



<!-- =====================================================
     REAL STATS
===================================================== -->

<section class="stats">


    <div class="container">


        <div class="stats-grid">


            <div class="stat reveal">

                <div
                    class="stat-number"
                    data-target="<?= $totalReviews ?>"
                >
                    0
                </div>

                <div class="stat-label">
                    Customer Reviews
                </div>

            </div>



            <div class="stat reveal">

                <div
                    class="stat-number"
                    data-target="<?= number_format($averageRating, 1) ?>"
                    data-decimal="1"
                >
                    0
                </div>

                <div class="stat-label">
                    Average Rating
                </div>

            </div>



            <div class="stat reveal">

                <div
                    class="stat-number"
                    data-target="5"
                >
                    0
                </div>

                <div class="stat-label">
                    Star Experience
                </div>

            </div>


        </div>


    </div>

</section>



<!-- =====================================================
     CUSTOMER LOVE
===================================================== -->

<section class="section customer-love">


    <div class="container">


        <div class="love-header reveal">


            <div>

                <div class="section-label">
                    Real Guest Experiences
                </div>


                <h2>

                    Loved at the
                    <span>table.</span>

                </h2>

            </div>


            <a
                href="reviews.php"
                class="view-reviews"
            >
                View All Reviews →
            </a>


        </div>



        <!-- RATING -->

        <div class="rating-box reveal">


            <div class="average">


                <div class="average-number">

                    <?= $totalReviews > 0
                        ? number_format($averageRating,1)
                        : '—'
                    ?>

                </div>


                <div class="average-stars">

                    <?php

                    $rounded =
                        (int)round($averageRating);

                    for($i=1;$i<=5;$i++){

                        echo $i <= $rounded
                            ? '★'
                            : '☆';

                    }

                    ?>

                </div>


                <div class="average-text">

                    <?= $totalReviews ?>

                    <?= $totalReviews === 1
                        ? 'CUSTOMER REVIEW'
                        : 'CUSTOMER REVIEWS'
                    ?>

                </div>


            </div>



            <div class="rating-bars">


                <?php for(
                    $star=5;
                    $star>=1;
                    $star--
                ): ?>


                    <?php

                    $count =
                        $ratingCounts[$star];

                    $percentage =
                        $totalReviews > 0
                        ? ($count / $totalReviews) * 100
                        : 0;

                    ?>


                    <div class="rating-row">


                        <span>
                            <?= $star ?> ★
                        </span>


                        <div class="rating-track">

                            <div
                                class="rating-fill"
                                data-width="<?= $percentage ?>"
                            ></div>

                        </div>


                        <span>
                            <?= $count ?>
                        </span>


                    </div>


                <?php endfor; ?>


            </div>


        </div>



        <!-- REVIEWS -->

        <?php if(!empty($reviews)): ?>


            <div class="review-grid">


                <?php foreach(
                    $reviews as $index => $review
                ): ?>


                    <article
                        class="review-card reveal"
                        style="
                            transition-delay:
                            <?= $index * 0.12 ?>s;
                        "
                    >


                        <div class="review-stars">

                            <?php

                            for($i=1;$i<=5;$i++){

                                echo $i <=
                                    (int)$review['rating']
                                    ? '★'
                                    : '☆';

                            }

                            ?>

                        </div>


                        <p>

                            “<?= htmlspecialchars(
                                $review['review_text']
                            ) ?>”

                        </p>


                        <div class="review-author">


                            <div class="review-name">

                                <?= htmlspecialchars(
                                    $review['name']
                                ) ?>

                            </div>


                            <div class="review-date">

                                <?= date(
                                    'd M Y',
                                    strtotime(
                                        $review['created_at']
                                    )
                                ) ?>

                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div class="no-reviews reveal">


                <h3>
                    Your story could be first.
                </h3>


                <p>

                    We don't have customer reviews yet.
                    Visit us, share your experience
                    and become part of the story.

                </p>


            </div>


        <?php endif; ?>


    </div>

</section>



<!-- =====================================================
     CTA
===================================================== -->

<section class="cta">


    <div class="cta-content">


        <div class="section-label">
            Your Table Awaits
        </div>


        <h2>

            Come hungry.
            <span>Leave inspired.</span>

        </h2>


        <p>

            Discover the flavours,
            stories and experiences of Ember & Plate.

        </p>


        <div class="cta-buttons">


            <a
                href="menu.php"
                class="btn btn-gold"
            >
                Explore Menu
            </a>


            <a
                href="contact.php"
                class="btn btn-outline"
            >
                Contact Us
            </a>


        </div>


    </div>


</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>

    <span>
        © <?= date('Y') ?> Ember & Plate
    </span>

    <span>
        Crafted with passion.
    </span>

</footer>



<script>

/* =====================================================
   CURSOR GLOW
===================================================== */

const glow =
    document.getElementById("cursorGlow");


document.addEventListener(
    "mousemove",
    function(e){

        glow.style.left =
            e.clientX + "px";

        glow.style.top =
            e.clientY + "px";

    }
);


/* =====================================================
   SCROLL REVEAL
===================================================== */

const reveals =
    document.querySelectorAll(".reveal");


const observer =
    new IntersectionObserver(

        function(entries){

            entries.forEach(function(entry){

                if(entry.isIntersecting){

                    entry.target.classList.add(
                        "active"
                    );

                    observer.unobserve(
                        entry.target
                    );

                }

            });

        },

        {
            threshold:.12
        }

    );


reveals.forEach(function(element){

    observer.observe(element);

});


/* =====================================================
   RATING BAR ANIMATION
===================================================== */

const bars =
    document.querySelectorAll(".rating-fill");


const barObserver =
    new IntersectionObserver(

        function(entries){

            entries.forEach(function(entry){

                if(entry.isIntersecting){

                    const width =
                        entry.target.dataset.width;

                    entry.target.style.width =
                        width + "%";

                    barObserver.unobserve(
                        entry.target
                    );

                }

            });

        },

        {
            threshold:.3
        }

    );


bars.forEach(function(bar){

    barObserver.observe(bar);

});


/* =====================================================
   COUNTER ANIMATION
===================================================== */

const counters =
    document.querySelectorAll(".stat-number");


function animateCounter(element){

    const target =
        parseFloat(
            element.dataset.target
        ) || 0;


    const decimal =
        parseInt(
            element.dataset.decimal || 0
        );


    const duration = 1600;

    const startTime =
        performance.now();


    function update(currentTime){

        const elapsed =
            currentTime - startTime;


        const progress =
            Math.min(
                elapsed / duration,
                1
            );


        const ease =
            1 -
            Math.pow(
                1 - progress,
                4
            );


        const current =
            target * ease;


        element.textContent =
            current.toFixed(decimal);


        if(progress < 1){

            requestAnimationFrame(update);

        }else{

            element.textContent =
                target.toFixed(decimal);

        }

    }


    requestAnimationFrame(update);

}


const counterObserver =
    new IntersectionObserver(

        function(entries){

            entries.forEach(function(entry){

                if(entry.isIntersecting){

                    animateCounter(
                        entry.target
                    );

                    counterObserver.unobserve(
                        entry.target
                    );

                }

            });

        },

        {
            threshold:.5
        }

    );


counters.forEach(function(counter){

    counterObserver.observe(counter);

});


/* =====================================================
   HERO PARALLAX
===================================================== */

window.addEventListener(
    "scroll",
    function(){

        const bg =
            document.querySelector(".hero-bg");


        if(!bg) return;


        const scroll =
            window.scrollY;


        if(scroll <
            window.innerHeight
        ){

            bg.style.transform =
                "scale(1) translateY(" +
                (scroll * .10) +
                "px)";

        }

    }
);

</script>


</body>

</html>