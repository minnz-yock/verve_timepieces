<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verve Timepieces - Home</title>
    <meta name="description" content="Verve Timepieces - Modern, unique watches with energy and style.">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        .watch-feature-section {
            display: flex;
            width: 100%;
            min-height: 420px;
            margin: 0 auto 3rem auto;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.10);
            position: relative;
            z-index: 2;
        }

        .watch-feature-side {
            flex: 1 1 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
            color: #fff;
            min-width: 0;
        }

        .watch-feature-left {
            background: #3a2517;
            /* dark brown */
            flex-direction: column;
            text-align: center;
        }

        .watch-feature-right {
            background: #e7d4c0;
            /* soft brown */
            color: #2f1b0b;
            flex-direction: column;
            text-align: center;
        }

        .watch-feature-watch-holder {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            z-index: 4;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .watch-feature-watch {
            position: relative;
            width: 230px;
            height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: filter 0.3s;
            cursor: pointer;
        }

        .watch-feature-watch img {
            width: 100%;
            height: auto;
            display: block;
            z-index: 5;
            pointer-events: none;
            user-select: none;
        }

        .watch-feature-watch-overlay {
            position: absolute;
            margin-top: 330px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            color: #fff;
            opacity: 0;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            transition: opacity 0.3s;
            z-index: 7;
        }

        .watch-feature-watch:hover .watch-feature-watch-overlay,
        .watch-feature-watch:focus .watch-feature-watch-overlay {
            opacity: 1;
            pointer-events: all;
        }

        .watch-feature-watch-overlay a {
            background: #785A49;
            color: #DED2C8;
            font-weight: 5px;
            padding: 3px 9px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;

        }


        @media (max-width: 991px) {
            .watch-feature-section {
                flex-direction: column;
                min-height: 600px;
                border-radius: 0 0 24px 24px;
            }

            .watch-feature-side {
                min-height: 200px;
            }

            .watch-feature-watch-holder {
                position: static;
                transform: none;
                margin: 1.5rem auto 0 auto;
            }
        }

        @media (max-width: 575px) {
            .watch-feature-section {
                min-height: 440px;
            }

            .watch-feature-watch {
                width: 160px;
                height: 220px;
            }

            .watch-feature-side {
                padding: 1rem 0.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- Include the navigation bar -->
    <?php include 'customer\navbarnew.php'; ?>

    <!-- Watch Feature Section (Below Navigation) -->
    <section class="watch-feature-section position-relative mb-5 mt-0">
        <div class="watch-feature-side watch-feature-left d-flex flex-column justify-content-center align-items-center">
            <div>
                <h3 class="fw-bold mb-4">EXPLORE THE WORLD'S<br>LARGEST WATCH FINDER</h3>
                <a href="shop.php" class="btn btn-light px-4 py-2 fw-semibold">EXPLORE</a>
            </div>
        </div>
        <div class="watch-feature-watch-holder">
            <div class="watch-feature-watch" tabindex="0">
                <img src="images\product_images\Tudor_Black_Bay_Fifty_Eight_Bronze.png" alt="Tudor Bronze Watch" style="height: 450px; width:450px;">
                <div class="watch-feature-watch-overlay">
                    <a href="product-verve-x.php">Tudor Black Bay Fifty Eight Bronze</a>
                </div>
            </div>
        </div>
        <div class="watch-feature-side watch-feature-right d-flex flex-column justify-content-center align-items-center">
            <div>
                <h3 class="fw-bold mb-4">SHOP OUR SELECTION<br>OF WATCHES &amp; WATCH ROLLS</h3>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="shop.php" class="btn btn-dark px-4 py-2 fw-semibold">WATCHES</a>
                    <a href="watch-rolls.php" class="btn btn-dark px-4 py-2 fw-semibold">WATCH ROLLS</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Area -->
    <main class="container mt-5">
        <!-- Hero Section (Example) -->
        <div id="carouselExampleIndicators" class="carousel slide mb-5" data-bs-ride="carousel">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active"
                    aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1"
                    aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2"
                    aria-label="Slide 3"></button>
            </div>
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="images/exquisite_timepieces_inc_cover.jpeg" class="d-block w-100"
                        alt="Verve Timepieces - Hero Banner 1">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Discover Your Verve</h5>
                        <p>Explore our latest collection of exquisite timepieces.</p>
                        <a href="shop.php" class="btn btn-primary">Shop Now</a>
                    </div>
                </div>
                <div class="carousel-item">
                    <img src="images/insured_watch.webp" class="d-block w-100" alt="Verve Timepieces - Hero Banner 2">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Crafted for Precision</h5>
                        <p>Experience unmatched quality and design.</p>
                        <a href="services.php" class="btn btn-outline-light">Our Services</a>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators"
                data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators"
                data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>

        <!-- Featured Products Section (Example) -->
        <section class="mb-5">
            <h2>Featured Timepieces</h2>
            <div class="row">
                <!-- Example Product Card 1 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <img src="images/watch-product-1.jpg" class="card-img-top" alt="Verve Watch Model X">
                        <div class="card-body">
                            <h5 class="card-title">Verve Model X</h5>
                            <p class="card-text">$299.00</p>
                            <a href="product-verve-x.php" class="btn btn-outline-dark">View Details</a>
                        </div>
                    </div>
                </div>
                <!-- Example Product Card 2 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <img src="images/watch-product-2.jpg" class="card-img-top" alt="Verve Watch Model Y">
                        <div class="card-body">
                            <h5 class="card-title">Verve Model Y</h5>
                            <p class="card-text">$349.00</p>
                            <a href="product-verve-y.php" class="btn btn-outline-dark">View Details</a>
                        </div>
                    </div>
                </div>
                <!-- Example Product Card 3 -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <img src="images/watch-product-3.jpg" class="card-img-top" alt="Verve Watch Model Z">
                        <div class="card-body">
                            <h5 class="card-title">Verve Model Z</h5>
                            <p class="card-text">$429.00</p>
                            <a href="product-verve-z.php" class="btn btn-outline-dark">View Details</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Highlight Section (Example) -->
        <section class="mb-5 text-center py-4" style="background-color: #E4ECF5; border-radius: 8px;">
            <h2>More Than Just Watches</h2>
            <p>Explore our expert services to enhance your timepiece experience.</p>
            <a href="services.php" class="btn btn-dark">Our Services</a>
        </section>

        <!-- About Us Snippet (Example) -->
        <section class="mb-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="images/about-snippet.jpg" class="img-fluid rounded" alt="Verve Timepieces Story">
                </div>
                <div class="col-lg-6">
                    <h3>Our Story</h3>
                    <p>At Verve Timepieces, we believe every moment counts. We curate exceptional watches that blend
                        precision, style, and energy...</p>
                    <a href="about.php" class="btn btn-outline-dark">Learn More About Us</a>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="text-center text-lg-start bg-body-tertiary text-muted py-4">
        <div class="text-center p-4" style="background-color: rgba(0, 0, 0, 0.05);">
            © 2025 Verve Timepieces. All rights reserved. | <a href="privacy.php">Privacy Policy</a> | <a
                href="terms.php">Terms & Conditions</a>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle - Needed for dropdowns and carousel -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>
    
    <!-- Home Page Reviews Section -->
    <?php include 'customer/review_handler.php'; ?>
    <?php include 'customer/home_reviews_section.php'; ?>
</body>

</html>