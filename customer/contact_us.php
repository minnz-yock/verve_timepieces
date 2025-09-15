<?php
if (!isset($_SESSION)) session_start();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Contact Us — Verve Timepieces</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Site stack -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            color: #352826;
            background: #fdfdfd;
        }

        /* HERO section: full-width, no border-radius, themed background */
        .hero {
            width: 100vw;
            max-width: 100vw;
            margin-left: 50%;
            transform: translateX(-50%);
            background-color: #3f352e;
            background-image:
                linear-gradient(160deg, #352826, #785A49, #785A49, #352826);
            color: #ffffff;
            border-radius: 0;
            padding: 90px 0 100px 0;
            box-shadow: none;
            text-align: center;
        }

        .hero h1,
        .hero .lead {
            font-weight: 800;
            letter-spacing: .5px;
            text-shadow: none;
        }

        .hero .lead {
            font-size: 1.25rem;
            font-weight: 500;
            margin-top: 12px;
            opacity: .95;
            color: #ffffff;
        }

        /* Info tiles */
        .info-card {
            background: #fff;
            border: 1px solid #d7ccbe;
            border-radius: 12px;
            padding: 28px 22px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            height: 100%;
            text-align: center;
            color: #352826;
            margin-top: 30px;
        }

        .info-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #352826;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px auto;
            font-size: 1.25rem;
        }

        .info-card h3 {
            color: #352826;
            font-weight: 700;
            font-size: 1.13rem;
        }

        .info-card .text-muted {
            color: #896f56 !important;
        }

        /* Form card */
        .form-card {
            background: #fff;
            border: 1px solid #d7ccbe;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            color: #352826;
            margin-top: 70px;
        }

        .form-label {
            color: #352826;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 1px solid #d7ccbe;
            background: #fff;
            color: #352826;
        }

        .form-control::placeholder {
            color: #896f56;
            opacity: 0.9;
        }

        .btn-verve {
            background: #352826;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .8rem 1rem;
            font-weight: 600;
            letter-spacing: .5px;
            font-size: 1rem;
            margin-top: 12px;
        }

        .btn-verve:hover {
            filter: brightness(1.08);
            background: #896f56;
            color: #fff;
        }

        /* Immediate Assistance */
        .help-card {
            background: #fff;
            border: 1px solid #d7ccbe;
            border-radius: 12px;
            padding: 28px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
            text-align: center;
            color: #352826;
        }

        .btn-soft {
            background: #ebe6de;
            color: #352826;
            border: 1px solid #d7ccbe;
            border-radius: 12px;
            padding: .75rem 1rem;
            font-weight: 500;
        }

        .btn-soft:hover {
            filter: brightness(0.98);
            background: #d7ccbe;
            color: #352826;
        }

        .lead {
            color: #352826;
        }

        .page-section {
            margin-bottom: 28px;
        }

        .section-title {
            color: #352826;
            font-weight: 700;
            letter-spacing: .5px;
        }

        /* Footer */
        footer {
            color: #352826;
            background: transparent;
            font-weight: 400;
            font-size: 1rem;
        }

        @media (max-width: 991px) {
            .hero {
                padding: 60px 0 35px 0;
            }

            .section-title {
                font-size: 1.15rem;
            }
        }
    </style>
</head>

<body>

    <div class="row">
        <?php include 'navbarnew.php'; ?>
    </div>


    <main class="container-fluid px-0 my-0">

        <!-- HERO (First image background) -->
        <section class="hero mb-4">
            <h1 class="display-6 mb-2">Get In Touch</h1>
            <p class="lead mb-0">Our dedicated team is here to help you find the perfect timepiece or answer any questions you may have.</p>
        </section>

        <!-- Contact tiles -->
        <section class="page-section container">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-telephone"></i></div>
                        <h3 class="mb-1">Call Us</h3>
                        <div class="small">
                            <div class="mb-1">+959 790-677-220</div>
                            <div class="text-muted">Mon - Sat: 9AM - 7PM PST</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-envelope"></i></div>
                        <h3 class="mb-1">Email Us</h3>
                        <div class="small">
                            <div class="mb-1">support_vervetimepieces@gmail.com</div>
                            <div class="text-muted">We reply within 24 hours</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon"><i class="bi bi-clock-history"></i></div>
                        <h3 class="mb-1">Business Hours</h3>
                        <div class="small">
                            <div>Mon - Sat: 9AM - 7PM</div>
                            <div class="text-muted">Sunday: 11AM - 5PM</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact form -->
        <section class="page-section container">
            <div class="form-card">
                <h2 class="h4 section-title mb-1">Send Us a Message</h2>
                <p class="mb-3">Fill out the form below and we’ll get back to you within 24 hours.</p>
                <form method="post" action="contact_submit.php" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" placeholder="John" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" placeholder="Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="john@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone (Optional)</label>
                            <input type="text" class="form-control" name="phone" placeholder="+1 (555) 123-4567">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" name="subject" placeholder="Watch inquiry or service request" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="5" placeholder="Tell us about your watch needs or questions..." required></textarea>
                        </div>
                        <!-- Honeypot anti-spam (hidden to real users) -->
                        <div style="position:absolute;left:-9999px;">
                            <label>Do not fill this field</label>
                            <input type="text" name="website">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-verve w-100" type="submit"><i class="bi bi-send me-2"></i>Send Message</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Immediate Assistance -->
        <section class="page-section container">
            <div class="help-card">
                <h2 class="h5 section-title mb-2">Need Immediate Assistance?</h2>
                <p class="mb-3">For urgent matters or time-sensitive inquiries, our concierge team is available to assist you immediately.</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <a href="tel:+15551234567" class="btn btn-verve">
                        <i class="bi bi-telephone-outbound me-2"></i>Call Now: 9 790-677-220
                    </a>
                    <a href="#" class="btn btn-soft">
                        <i class="bi bi-whatsapp me-2"></i>Chatbot Support
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="container pb-4 text-center text-muted">
        <small>© <?= date('Y') ?> Verve Timepieces. All rights reserved.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>