<?php
session_start();

require_once __DIR__ . '/../config/db.php';

$isLoggedIn = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
$userName = $_SESSION['user_name'] ?? '';

/* =====================================================
   CART COUNT
===================================================== */

$cartCount = 0;

if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int)$qty;
    }

}


/* =====================================================
   GET FEATURED PRODUCTS
===================================================== */

$products = [];

$sql = "SELECT id, name, description, price, image
        FROM products
        WHERE is_available = 1
        ORDER BY is_featured DESC, id DESC
        LIMIT 6";

$result = mysqli_query($conn, $sql);

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }

}

/* =====================================================
   IMAGE PATH HELPER
   ===================================================== */

function productImageUrl($image)
{
    $image = trim((string)$image);

    if ($image === '') {
        return '../assets/images/chicken-biryani.jpg';
    }

    // External image URL
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }

    // Already a path relative to user/
    if (strpos($image, '../') === 0 || strpos($image, '/') === 0) {
        return $image;
    }

    // Database may contain assets/images/filename
    if (strpos($image, 'assets/') === 0) {
        return '../' . $image;
    }

    // Database may contain only filename
    return '../assets/images/' . ltrim($image, '/');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Ember & Plate — Fine Dining</title>

<meta name="description"
content="Ember & Plate — crafted cuisine, warm atmosphere and unforgettable dining experiences.">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect"
href="https://fonts.gstatic.com"
crossorigin>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap"
rel="stylesheet">


<style>

/* =====================================================
   ROOT
===================================================== */

:root{

    --black:#080807;
    --black2:#0d0d0b;
    --cream:#f4efe5;
    --muted:#aaa69c;

    --gold:#c8a45d;
    --gold-light:#e2c681;

    --line:rgba(255,255,255,.10);

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
   PAGE LOADER
===================================================== */

.loader{

    position:fixed;

    inset:0;

    background:#080807;

    z-index:9999;

    display:flex;

    align-items:center;

    justify-content:center;

    animation:
        loaderOut 1s ease 1.4s forwards;

}


.loader-inner{
    text-align:center;
}


.loader-logo{

    font-family:"Playfair Display",serif;

    font-size:32px;

    letter-spacing:3px;

    color:var(--cream);

    margin-bottom:12px;

}


.loader-line{

    width:0;

    height:1px;

    background:var(--gold);

    margin:auto;

    animation:
        loaderLine .9s ease forwards;

}


@keyframes loaderLine{

    to{
        width:160px;
    }

}


@keyframes loaderOut{

    to{
        opacity:0;
        visibility:hidden;
    }

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

    transition:.4s ease;

}


.navbar.scrolled{

    background:rgba(8,8,7,.90);

    backdrop-filter:blur(18px);

    border-bottom:1px solid rgba(255,255,255,.07);

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


/* =====================================================
   BRAND
===================================================== */

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

    letter-spacing:.5px;

}


.brand-sub{

    display:block;

    color:var(--gold);

    font-size:8px;

    letter-spacing:3px;

    margin-top:2px;

}


/* =====================================================
   MAIN NAVIGATION
===================================================== */

.nav-links{

    display:flex;

    align-items:center;

    justify-content:center;

    gap:27px;

    flex:1;

}


.nav-links a{

    color:#d0ccc2;

    font-size:11px;

    letter-spacing:1.1px;

    text-transform:uppercase;

    position:relative;

    white-space:nowrap;

    transition:.3s;

}


.nav-links a:hover{

    color:var(--gold-light);

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


.nav-links a:hover::after{

    width:100%;

}


/* ACTIVE NAV */

.nav-links a.active{

    color:var(--gold-light);

}


.nav-links a.active::after{

    width:100%;

}


/* =====================================================
   CART
===================================================== */

.cart-link{

    display:flex !important;

    align-items:center;

    gap:7px;

}


.cart-count{

    min-width:18px;

    height:18px;

    padding:0 5px;

    display:inline-flex;

    align-items:center;

    justify-content:center;

    background:var(--gold);

    color:#090908;

    border-radius:50px;

    font-size:9px;

    font-weight:700;

    letter-spacing:0;

}


/* =====================================================
   NAV RIGHT
===================================================== */

.nav-right{

    display:flex;

    align-items:center;

    gap:15px;

    flex-shrink:0;

}


.login-link{

    font-size:11px;

    color:#d0ccc2;

    letter-spacing:.8px;

    transition:.3s;

}


.login-link:hover{

    color:var(--gold-light);

}


.reserve-btn{

    padding:11px 19px;

    border:1px solid var(--gold);

    color:var(--gold-light);

    font-size:10px;

    letter-spacing:1.2px;

    text-transform:uppercase;

    transition:.3s;

    white-space:nowrap;

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

    display:flex;

    align-items:center;

    overflow:hidden;

}


.hero-bg{

    position:absolute;

    inset:0;

    background:

        linear-gradient(
            90deg,
            rgba(8,8,7,.98) 0%,
            rgba(8,8,7,.88) 38%,
            rgba(8,8,7,.35) 70%,
            rgba(8,8,7,.70) 100%
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
            circle at 78% 50%,
            rgba(200,164,93,.16),
            transparent 27%
        );

}


.hero-inner{

    width:min(1200px,92%);

    margin:auto;

    position:relative;

    z-index:2;

    padding-top:80px;

}


.hero-content{

    max-width:650px;

    animation:heroContent 1.2s ease .3s both;

}


@keyframes heroContent{

    from{
        opacity:0;
        transform:translateY(35px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }

}


.hero-eyebrow{

    display:flex;

    align-items:center;

    gap:12px;

    color:var(--gold-light);

    font-size:10px;

    text-transform:uppercase;

    letter-spacing:4px;

    margin-bottom:25px;

}


.hero-eyebrow::before{

    content:"";

    width:45px;

    height:1px;

    background:var(--gold);

}


.hero h1{

    font-family:"Playfair Display",serif;

    font-size:clamp(58px,7vw,94px);

    line-height:.98;

    font-weight:500;

    letter-spacing:-3px;

    margin-bottom:28px;

}


.hero h1 span{

    color:var(--gold-light);

    font-style:italic;

}


.hero-description{

    max-width:510px;

    color:#bbb7ad;

    font-size:15px;

    line-height:1.9;

    margin-bottom:35px;

}


.hero-buttons{

    display:flex;

    align-items:center;

    gap:25px;

}


.btn-gold{

    display:inline-flex;

    align-items:center;

    gap:12px;

    background:var(--gold);

    color:#090908;

    padding:15px 25px;

    font-size:11px;

    font-weight:700;

    letter-spacing:1.5px;

    text-transform:uppercase;

    transition:.35s;

}


.btn-gold:hover{

    background:var(--gold-light);

    transform:translateY(-3px);

}


.btn-text{

    color:#e2ddd2;

    font-size:11px;

    letter-spacing:1.5px;

    text-transform:uppercase;

    border-bottom:1px solid rgba(255,255,255,.35);

    padding-bottom:6px;

    transition:.3s;

}


.btn-text:hover{

    color:var(--gold-light);

    border-color:var(--gold);

}


/* =====================================================
   HERO SIDE INFO
===================================================== */

.hero-info{

    position:absolute;

    right:4%;

    bottom:50px;

    z-index:3;

    display:flex;

    align-items:center;

    gap:35px;

    animation:fadeUp 1s ease 1s both;

}


.hero-info-item{

    text-align:right;

}


.hero-info-item strong{

    display:block;

    font-family:"Playfair Display",serif;

    font-size:24px;

    color:var(--gold-light);

}


.hero-info-item span{

    font-size:9px;

    text-transform:uppercase;

    letter-spacing:2px;

    color:#aaa69c;

}


/* =====================================================
   SCROLL
===================================================== */

.scroll-indicator{

    position:absolute;

    left:4%;

    bottom:45px;

    z-index:3;

    display:flex;

    align-items:center;

    gap:12px;

    color:#aaa69c;

    font-size:9px;

    text-transform:uppercase;

    letter-spacing:2px;

    writing-mode:vertical-rl;

}


.scroll-line{

    width:1px;

    height:55px;

    background:linear-gradient(
        var(--gold),
        transparent
    );

}


/* =====================================================
   INTRO
===================================================== */

.intro{

    padding:130px 0;

    background:var(--black);

}


.container{

    width:min(1200px,92%);

    margin:auto;

}


.intro-grid{

    display:grid;

    grid-template-columns:.8fr 1.2fr;

    gap:90px;

    align-items:start;

}


.section-label{

    color:var(--gold);

    font-size:9px;

    text-transform:uppercase;

    letter-spacing:4px;

    margin-bottom:20px;

}


.intro-title{

    font-family:"Playfair Display",serif;

    font-size:clamp(40px,4vw,60px);

    font-weight:500;

    line-height:1.1;

}


.intro-title em{

    color:var(--gold-light);

}


.intro-text{

    color:#a5a198;

    font-size:15px;

    line-height:2;

    max-width:650px;

}


.intro-signature{

    margin-top:30px;

    font-family:"Playfair Display",serif;

    font-style:italic;

    color:#ddd5c6;

    font-size:20px;

}


/* =====================================================
   FOOD SHOWCASE
===================================================== */

.showcase{

    padding:0 0 130px;

}


.showcase-heading{

    display:flex;

    justify-content:space-between;

    align-items:end;

    margin-bottom:40px;

}


.showcase-title{

    font-family:"Playfair Display",serif;

    font-size:48px;

    font-weight:500;

}


.showcase-title span{

    color:var(--gold-light);

}


.view-menu{

    color:var(--gold-light);

    font-size:11px;

    text-transform:uppercase;

    letter-spacing:2px;

    border-bottom:1px solid var(--gold);

    padding-bottom:7px;

}


.food-grid{

    display:grid;

    grid-template-columns:repeat(3,1fr);

    gap:16px;

}


.food-card{

    position:relative;

    height:430px;

    overflow:hidden;

    background:#151512;

}


.food-card:nth-child(2){

    margin-top:50px;

}


.food-card-image{

    position:absolute;

    inset:0;

    transition:transform .7s cubic-bezier(.2,.7,.2,1);

}


.food-card:hover .food-card-image{

    transform:scale(1.08);

}


.food-card-image img{

    width:100%;

    height:100%;

    object-fit:cover;

}


.food-card::after{

    content:"";

    position:absolute;

    inset:0;

    background:linear-gradient(
        transparent 30%,
        rgba(0,0,0,.85)
    );

}


.food-card-info{

    position:absolute;

    left:25px;

    right:25px;

    bottom:25px;

    z-index:2;

}


.food-category{

    color:var(--gold-light);

    font-size:9px;

    letter-spacing:3px;

    text-transform:uppercase;

    margin-bottom:8px;

}


.food-card-info h3{

    font-family:"Playfair Display",serif;

    font-size:27px;

    font-weight:500;

    margin-bottom:8px;

}


.food-card-info p{

    color:#c0bbb0;

    font-size:11px;

    line-height:1.6;

}


.food-price{

    margin-top:14px;

    color:var(--gold-light);

    font-size:14px;

}


/* =====================================================
   EXPERIENCE
===================================================== */

.experience{

    padding:130px 0;

    background:#0d0d0b;

}


.experience-grid{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:90px;

    align-items:center;

}


.experience-image{

    height:570px;

    position:relative;

    overflow:hidden;

    background:

        url("../assets/images/chicken-biryani.jpg")
        center/cover no-repeat;

}


.experience-image::after{

    content:"";

    position:absolute;

    inset:0;

    background:
        linear-gradient(
            180deg,
            transparent 50%,
            rgba(0,0,0,.65)
        );

}


.experience-frame{

    position:absolute;

    width:85%;

    height:85%;

    border:1px solid rgba(226,198,129,.35);

    top:7.5%;

    left:7.5%;

    z-index:2;

}


.experience-number{

    position:absolute;

    bottom:25px;

    left:25px;

    z-index:3;

    font-family:"Playfair Display",serif;

    font-size:65px;

    color:rgba(255,255,255,.18);

}


.experience-content h2{

    font-family:"Playfair Display",serif;

    font-size:clamp(42px,4vw,62px);

    line-height:1.05;

    font-weight:500;

    margin-bottom:25px;

}


.experience-content h2 span{

    color:var(--gold-light);

}


.experience-content p{

    color:#99958d;

    font-size:14px;

    line-height:2;

    margin-bottom:22px;

}


.features{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:25px;

    margin-top:35px;

}


.feature{

    border-top:1px solid var(--line);

    padding-top:17px;

}


.feature strong{

    display:block;

    font-family:"Playfair Display",serif;

    font-size:19px;

    color:#eee7d9;

    margin-bottom:5px;

}


.feature span{

    color:#77746e;

    font-size:11px;

}


/* =====================================================
   QUOTE
===================================================== */

.quote-section{

    padding:130px 0;

    text-align:center;

    position:relative;

    overflow:hidden;

}


.quote-section::before{

    content:"“";

    position:absolute;

    font-family:"Playfair Display",serif;

    font-size:420px;

    color:rgba(200,164,93,.035);

    top:-150px;

    left:50%;

    transform:translateX(-50%);

}


.quote{

    position:relative;

    z-index:2;

    max-width:900px;

    margin:auto;

}


.quote p{

    font-family:"Playfair Display",serif;

    font-size:clamp(32px,4vw,55px);

    line-height:1.25;

    font-weight:400;

}


.quote p em{

    color:var(--gold-light);

}


.quote-author{

    margin-top:30px;

    color:var(--gold);

    font-size:10px;

    letter-spacing:3px;

    text-transform:uppercase;

}


/* =====================================================
   REVIEWS
===================================================== */

.reviews{

    padding:0 0 130px;

}


.reviews-header{

    text-align:center;

    margin-bottom:55px;

}


.reviews-header h2{

    font-family:"Playfair Display",serif;

    font-size:50px;

    font-weight:500;

}


.review-grid{

    display:grid;

    grid-template-columns:repeat(3,1fr);

    gap:18px;

}


.review{

    padding:32px;

    background:#10100e;

    border:1px solid rgba(255,255,255,.07);

}


.review-stars{

    color:var(--gold-light);

    letter-spacing:3px;

    font-size:11px;

    margin-bottom:20px;

}


.review p{

    color:#aaa69c;

    font-family:"Playfair Display",serif;

    font-size:18px;

    line-height:1.65;

}


.review-author{

    margin-top:28px;

    color:#d8d2c5;

    font-size:11px;

    letter-spacing:1px;

}


/* =====================================================
   RESERVATION CTA
===================================================== */

.reservation{

    padding:0 0 130px;

}


.reservation-box{

    min-height:430px;

    position:relative;

    display:flex;

    align-items:center;

    justify-content:center;

    text-align:center;

    overflow:hidden;

    background:

        linear-gradient(
            rgba(8,8,7,.72),
            rgba(8,8,7,.88)
        ),

        url("../assets/images/chicken-biryani.jpg")
        center/cover;

}


.reservation-box::before{

    content:"";

    position:absolute;

    inset:20px;

    border:1px solid rgba(226,198,129,.28);

}


.reservation-content{

    position:relative;

    z-index:2;

}


.reservation-content .section-label{

    margin-bottom:15px;

}


.reservation-content h2{

    font-family:"Playfair Display",serif;

    font-size:clamp(42px,5vw,68px);

    font-weight:500;

    margin-bottom:15px;

}


.reservation-content p{

    color:#c1bcb1;

    font-size:14px;

    margin-bottom:28px;

}


/* =====================================================
   FOOTER
===================================================== */

footer{

    border-top:1px solid rgba(255,255,255,.08);

    padding:60px 0 25px;

    background:#070706;

}


.footer-top{

    width:min(1200px,92%);

    margin:auto;

    display:grid;

    grid-template-columns:1.5fr 1fr 1fr 1fr;

    gap:50px;

    padding-bottom:50px;

}


.footer-brand p{

    color:#706e68;

    max-width:310px;

    line-height:1.8;

    font-size:12px;

    margin-top:18px;

}


.footer-col h4{

    color:var(--gold);

    font-size:9px;

    letter-spacing:3px;

    text-transform:uppercase;

    margin-bottom:20px;

}


.footer-col a{

    display:block;

    color:#77746e;

    font-size:12px;

    margin-bottom:13px;

    transition:.3s;

}


.footer-col a:hover{

    color:var(--gold-light);

}


.footer-bottom{

    width:min(1200px,92%);

    margin:auto;

    border-top:1px solid rgba(255,255,255,.06);

    padding-top:22px;

    display:flex;

    justify-content:space-between;

    color:#4e4c48;

    font-size:10px;

}


/* =====================================================
   REVEAL ANIMATION
===================================================== */

.reveal{

    opacity:0;

    transform:translateY(35px);

    transition:
        opacity .9s ease,
        transform .9s ease;

}


.reveal.active{

    opacity:1;

    transform:translateY(0);

}


/* =====================================================
   TABLET
===================================================== */

@media(max-width:1100px){

    .nav-inner{
        width:94%;
        gap:15px;
    }

    .nav-links{
        gap:18px;
    }

    .nav-links a{
        font-size:10px;
    }

}


/* =====================================================
   MOBILE / TABLET NAV
===================================================== */

@media(max-width:900px){

    .navbar{

        height:115px;

        background:rgba(8,8,7,.92);

        backdrop-filter:blur(18px);

    }


    .nav-inner{

        width:94%;

        height:100%;

        display:grid;

        grid-template-columns:1fr auto;

        grid-template-rows:65px 45px;

        column-gap:15px;

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

        align-items:center;

        justify-content:flex-start;

        gap:24px;

        width:100%;

        overflow-x:auto;

        overflow-y:hidden;

        padding:0 2px;

        scrollbar-width:none;

    }


    .nav-links::-webkit-scrollbar{
        display:none;
    }


    .nav-links a{

        flex-shrink:0;

        font-size:10px;

        letter-spacing:1px;

    }


    .login-link{
        display:none;
    }


    .reserve-btn{

        padding:9px 15px;

        font-size:9px;

    }


    .hero{

        padding-top:115px;

    }


    .hero-bg{

        background:

            linear-gradient(
                90deg,
                rgba(8,8,7,.94),
                rgba(8,8,7,.62)
            ),

            url("../assets/images/chicken-biryani.jpg")
            center/cover;

    }


    .hero-info{
        display:none;
    }


    .intro-grid,
    .experience-grid{

        grid-template-columns:1fr;

        gap:45px;

    }


    .food-grid{

        grid-template-columns:1fr 1fr;

    }


    .food-card:nth-child(2){

        margin-top:0;

    }


    .review-grid{

        grid-template-columns:1fr;

    }


    .footer-top{

        grid-template-columns:1fr 1fr;

    }

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:600px){

    .navbar{
        height:115px;
    }


    .brand-name{
        font-size:16px;
    }


    .brand-mark{

        width:36px;

        height:36px;

    }


    .brand-sub{
        font-size:7px;
    }


    .reserve-btn{

        padding:9px 12px;

        font-size:8px;

    }


    .nav-links{

        gap:22px;

    }


    .nav-links a{

        font-size:9px;

    }


    .hero{

        min-height:850px;

        padding-top:115px;

    }


    .hero-inner{

        padding-top:80px;

    }


    .hero h1{

        font-size:54px;

        letter-spacing:-2px;

    }


    .hero-description{

        font-size:13px;

    }


    .hero-buttons{

        align-items:flex-start;

        flex-direction:column;

        gap:18px;

    }


    .scroll-indicator{

        display:none;

    }


    .intro{

        padding:90px 0;

    }


    .intro-grid{

        gap:30px;

    }


    .showcase{

        padding-bottom:90px;

    }


    .showcase-heading{

        align-items:flex-start;

        flex-direction:column;

        gap:20px;

    }


    .showcase-title{

        font-size:39px;

    }


    .food-grid{

        grid-template-columns:1fr;

    }


    .food-card{

        height:420px;

    }


    .experience{

        padding:90px 0;

    }


    .experience-image{

        height:420px;

    }


    .features{

        grid-template-columns:1fr;

    }


    .quote-section{

        padding:90px 0;

    }


    .reviews{

        padding-bottom:90px;

    }


    .reviews-header h2{

        font-size:40px;

    }


    .reservation{

        padding-bottom:90px;

    }


    .reservation-box{

        min-height:400px;

    }


    .footer-top{

        grid-template-columns:1fr 1fr;

        gap:35px 20px;

    }


    .footer-brand{

        grid-column:1/-1;

    }


    .footer-bottom{

        flex-direction:column;

        gap:10px;

        text-align:center;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     LOADER
===================================================== -->

<div class="loader">

    <div class="loader-inner">

        <div class="loader-logo">
            EMBER & PLATE
        </div>

        <div class="loader-line"></div>

    </div>

</div>



<!-- =====================================================
     NAVBAR
===================================================== -->

<header class="navbar" id="navbar">

    <div class="nav-inner">


        <!-- LOGO -->

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



        <!-- MAIN NAVIGATION -->

        <nav class="nav-links">

            <a href="index.php" class="active">
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

            <a href="contact.php">
                Contact
            </a>

            <a href="cart.php" class="cart-link">

                Cart

                <?php if($cartCount > 0): ?>

                    <span class="cart-count">
                        <?= $cartCount ?>
                    </span>

                <?php endif; ?>

            </a>

        </nav>



        <!-- RIGHT SIDE -->

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


            <div class="hero-eyebrow">
                A Modern Dining Experience
            </div>


            <h1>

                Where every dish<br>

                becomes a
                <span>memory.</span>

            </h1>


            <p class="hero-description">

                An intimate dining experience built around
                honest ingredients, bold flavors and the art
                of bringing people together.

            </p>


            <div class="hero-buttons">


                <a href="menu.php" class="btn-gold">

                    Explore Menu

                    <span>→</span>

                </a>


                <a href="contact.php" class="btn-text">

                    Reserve Your Table

                </a>


            </div>


        </div>

    </div>



    <!-- HERO INFO -->

    <div class="hero-info">


        <div class="hero-info-item">

            <strong>4.9</strong>

            <span>Guest Rating</span>

        </div>


        <div class="hero-info-item">

            <strong>25+</strong>

            <span>Signature Dishes</span>

        </div>


        <div class="hero-info-item">

            <strong>2026</strong>

            <span>Est.</span>

        </div>


    </div>



    <!-- SCROLL -->

    <div class="scroll-indicator">

        <div class="scroll-line"></div>

        Scroll to explore

    </div>

</section>



<!-- =====================================================
     INTRO
===================================================== -->

<section class="intro">

    <div class="container">

        <div class="intro-grid reveal">


            <div>

                <div class="section-label">
                    Welcome to Ember & Plate
                </div>


                <h2 class="intro-title">

                    Food is more than<br>

                    <em>what's on the plate.</em>

                </h2>

            </div>



            <div>

                <p class="intro-text">

                    We believe the best meals are not simply
                    eaten — they are experienced.

                    <br><br>

                    Every plate at Ember & Plate is created
                    with carefully selected ingredients,
                    thoughtful preparation and a deep respect
                    for flavor.

                    <br><br>

                    Come for the food. Stay for the feeling.

                </p>


                <div class="intro-signature">
                    — The Ember & Plate Kitchen
                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     SIGNATURE FOOD
===================================================== -->

<section class="showcase">

    <div class="container">


        <div class="showcase-heading reveal">


            <div>

                <div class="section-label">
                    From Our Kitchen
                </div>


                <h2 class="showcase-title">

                    Signature
                    <span>plates.</span>

                </h2>

            </div>


            <a href="menu.php" class="view-menu">
                View Full Menu →
            </a>


        </div>



        <div class="food-grid">


            <?php if(!empty($products)): ?>


                <?php foreach($products as $product): ?>


                    <a
                        href="product.php?id=<?= (int)$product['id'] ?>"
                        class="food-card reveal"
                    >


                        <div class="food-card-image">


                            <?php if(!empty($product['image'])): ?>


                                <img
                                    src="<?= htmlspecialchars(productImageUrl($product['image'] ?? '')) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    onerror="this.onerror=null; this.src='../assets/images/chicken-biryani.jpg'"
                                >


                            <?php else: ?>


                                <div style="
                                    width:100%;
                                    height:100%;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    background:
                                    radial-gradient(
                                        circle,
                                        rgba(200,164,93,.18),
                                        #151512
                                    );
                                    font-family:'Playfair Display',serif;
                                    font-size:28px;
                                    color:#c8a45d;
                                ">

                                    Ember & Plate

                                </div>


                            <?php endif; ?>


                        </div>



                        <div class="food-card-info">


                            <div class="food-category">
                                Chef's Selection
                            </div>


                            <h3>
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>


                            <p>

                                <?= htmlspecialchars(
                                    mb_strimwidth(
                                        $product['description'] ?? '',
                                        0,
                                        100,
                                        '...'
                                    )
                                ) ?>

                            </p>


                            <div class="food-price">

                                ₹<?= number_format(
                                    (float)$product['price'],
                                    0
                                ) ?>

                            </div>


                        </div>


                    </a>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- FALLBACK -->

                <div class="food-card reveal">


                    <div class="food-card-image">

                        <img
                            src="../assets/images/chicken-biryani.jpg"
                            alt="Signature Dish"
                        >

                    </div>


                    <div class="food-card-info">

                        <div class="food-category">
                            Chef's Selection
                        </div>

                        <h3>
                            Signature Biryani
                        </h3>

                        <p>
                            Fragrant rice, rich spices and
                            beautifully layered flavors.
                        </p>

                        <div class="food-price">
                            Explore Menu →
                        </div>

                    </div>


                </div>



                <div class="food-card reveal">


                    <div class="food-card-image">

                        <img
                            src="../assets/images/chicken-biryani.jpg"
                            alt="Chef Special"
                        >

                    </div>


                    <div class="food-card-info">

                        <div class="food-category">
                            Chef's Selection
                        </div>

                        <h3>
                            Chef's Special
                        </h3>

                        <p>
                            A carefully crafted plate from
                            our kitchen.
                        </p>

                        <div class="food-price">
                            Explore Menu →
                        </div>

                    </div>


                </div>



                <div class="food-card reveal">


                    <div class="food-card-image">

                        <img
                            src="../assets/images/chicken-biryani.jpg"
                            alt="House Special"
                        >

                    </div>


                    <div class="food-card-info">

                        <div class="food-category">
                            House Favorite
                        </div>

                        <h3>
                            Ember Signature
                        </h3>

                        <p>
                            Bold flavors made for unforgettable
                            evenings.
                        </p>

                        <div class="food-price">
                            Explore Menu →
                        </div>

                    </div>


                </div>


            <?php endif; ?>


        </div>

    </div>

</section>



<!-- =====================================================
     EXPERIENCE
===================================================== -->

<section class="experience">

    <div class="container">

        <div class="experience-grid reveal">


            <div class="experience-image">

                <div class="experience-frame"></div>

                <div class="experience-number">
                    01
                </div>

            </div>



            <div class="experience-content">


                <div class="section-label">
                    The Experience
                </div>


                <h2>

                    Crafted with<br>

                    <span>intention.</span>

                </h2>


                <p>

                    From the first aroma that reaches your table
                    to the final bite, every detail matters.

                </p>


                <p>

                    Our kitchen combines timeless techniques
                    with modern presentation to create food
                    that feels familiar yet completely new.

                </p>



                <div class="features">


                    <div class="feature">

                        <strong>
                            Fresh Ingredients
                        </strong>

                        <span>
                            Selected with care
                        </span>

                    </div>


                    <div class="feature">

                        <strong>
                            Chef Crafted
                        </strong>

                        <span>
                            Made fresh to order
                        </span>

                    </div>


                    <div class="feature">

                        <strong>
                            Warm Service
                        </strong>

                        <span>
                            Hospitality at heart
                        </span>

                    </div>


                    <div class="feature">

                        <strong>
                            Beautiful Plates
                        </strong>

                        <span>
                            Made to be remembered
                        </span>

                    </div>


                </div>


            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     QUOTE
===================================================== -->

<section class="quote-section">

    <div class="container">

        <div class="quote reveal">

            <p>

                “Good food fills the stomach.

                <em>Great food stays with you.</em>”

            </p>


            <div class="quote-author">

                Ember & Plate

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     REVIEWS PREVIEW
===================================================== -->

<section class="reviews">

    <div class="container">


        <div class="reviews-header reveal">


            <div class="section-label">
                Guest Stories
            </div>


            <h2>
                Loved at the table.
            </h2>


        </div>



        <div class="review-grid">


            <div class="review reveal">

                <div class="review-stars">
                    ★★★★★
                </div>

                <p>

                    “Beautiful food, amazing atmosphere
                    and a really memorable experience.
                    Everything felt thoughtfully done.”

                </p>

                <div class="review-author">
                    — Arjun
                </div>

            </div>



            <div class="review reveal">

                <div class="review-stars">
                    ★★★★★
                </div>

                <p>

                    “The presentation was beautiful and
                    every dish had so much flavor.
                    Definitely coming back.”

                </p>

                <div class="review-author">
                    — Riya
                </div>

            </div>



            <div class="review reveal">

                <div class="review-stars">
                    ★★★★★
                </div>

                <p>

                    “A perfect place for a special evening.
                    Great food, beautiful setting and
                    excellent service.”

                </p>

                <div class="review-author">
                    — Sayan
                </div>

            </div>


        </div>


    </div>

</section>



<!-- =====================================================
     RESERVATION
===================================================== -->

<section class="reservation">

    <div class="container">


        <div class="reservation-box reveal">


            <div class="reservation-content">


                <div class="section-label">
                    Your Table Awaits
                </div>


                <h2>

                    Make it an evening<br>

                    to remember.

                </h2>


                <p>
                    Good food. Good company. Good memories.
                </p>


                <a href="contact.php" class="btn-gold">

                    Reserve a Table

                    <span>→</span>

                </a>


            </div>


        </div>


    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer>


    <div class="footer-top">


        <!-- FOOTER BRAND -->

        <div class="footer-brand">


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


            <p>

                A modern dining experience built around
                honest ingredients, bold flavors and
                unforgettable moments.

            </p>


        </div>



        <!-- EXPLORE -->

        <div class="footer-col">

            <h4>
                Explore
            </h4>


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


            <a href="contact.php">
                Contact
            </a>


            <a href="cart.php">
                Cart
            </a>


        </div>



        <!-- ACCOUNT -->

        <div class="footer-col">

            <h4>
                Account
            </h4>


            <?php if($isLoggedIn): ?>


                <a href="menu.php">
                    Order Online
                </a>


                <a href="cart.php">
                    Your Cart
                </a>


                <a href="logout.php">
                    Logout
                </a>


            <?php else: ?>


                <a href="login.php">
                    Login
                </a>


                <a href="signup.php">
                    Create Account
                </a>


                <a href="forgot-password.php">
                    Forgot Password
                </a>


            <?php endif; ?>


        </div>



        <!-- VISIT -->

        <div class="footer-col">

            <h4>
                Visit
            </h4>


            <a href="contact.php">
                Contact Us
            </a>


            <a href="contact.php">
                Reserve Table
            </a>


            <a href="mailto:hello@emberandplate.com">
                Email Us
            </a>


        </div>


    </div>



    <div class="footer-bottom">


        <span>
            © <?= date('Y') ?> Ember & Plate
        </span>


        <span>
            Crafted with passion.
        </span>


    </div>


</footer>



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>


/* =====================================================
   NAVBAR SCROLL
===================================================== */

const navbar =
    document.getElementById("navbar");


window.addEventListener("scroll", function(){

    if(window.scrollY > 40){

        navbar.classList.add("scrolled");

    }else{

        navbar.classList.remove("scrolled");

    }

});



/* =====================================================
   SCROLL REVEAL
===================================================== */

const revealElements =
    document.querySelectorAll(".reveal");


const revealObserver =
    new IntersectionObserver(

        function(entries){

            entries.forEach(function(entry){

                if(entry.isIntersecting){

                    entry.target.classList.add("active");

                    revealObserver.unobserve(
                        entry.target
                    );

                }

            });

        },

        {
            threshold:.12
        }

    );


revealElements.forEach(function(element){

    revealObserver.observe(element);

});



/* =====================================================
   HERO PARALLAX
===================================================== */

window.addEventListener("scroll",function(){

    const heroBg =
        document.querySelector(".hero-bg");

    if(!heroBg) return;


    const scroll =
        window.scrollY;


    if(scroll < window.innerHeight){

        heroBg.style.transform =
            "scale(1) translateY(" +
            (scroll * .12) +
            "px)";

    }

});


</script>


</body>

</html>