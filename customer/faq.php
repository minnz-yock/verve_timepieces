<?php if (!isset($_SESSION)) session_start(); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>FAQ — Verve Timepieces</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Site stack -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">

    <style>
        /* Colors to match your other pages */
        body {
            background: #fff;
            color: #352826;
        }

        .section-title {
            color: #2e2a26;
            font-weight: 700;
            letter-spacing: .2px;
        }

        .soft-card {
            background: #DED2C8;
            border: 1px solid #d7ccbe;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
        }

        .pill {
            display: inline-block;
            border: 1px solid #d7ccbe;
            border-radius: 999px;
            padding: .45rem .75rem;
            margin: .25rem .35rem .25rem 0;
            background: #fff;
            color: #2e2a26;
            font-size: .9rem;
            cursor: pointer;
        }

        .pill.active {
            background: #4b3b32;
            color: #fff;
            border-color: #4b3b32;
        }

        .search-wrap input {
            border: 1px solid #d7ccbe;
            border-radius: 12px;
            padding: .85rem 1rem;
        }

        .accordion-item {
            border: 1px solid #d7ccbe !important;
            border-radius: 12px !important;
            overflow: hidden;
        }

        .accordion-button {
            background: #DED2C8;
            color: #2e2a26;
        }

        .accordion-button:focus {
            box-shadow: none;
        }

        .accordion-button:not(.collapsed) {
            background: #ebe6de;
            color: #2e2a26;
        }

        .accordion-body {
            background: #fff;
        }

        .mini-hint {
            font-size: .9rem;
            color: #8a817b;
        }

        .faq-cta {
            background: #fff;
            border: 1px solid #d7ccbe;
            border-radius: 12px;
            text-align: center;
            padding: 24px;
        }

        .btn-verve {
            background: #4b3b32;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .8rem 1rem;
        }

        .btn-verve:hover {
            filter: brightness(1.05);
        }
    </style>
</head>

<body>

    <?php include 'navbarnew.php'; ?>

    <main class="container my-4">

        <!-- Hero -->
        <section class="soft-card p-4 mb-4">
            <div class="d-md-flex align-items-center justify-content-between">
                <div class="mb-3 mb-md-0">
                    <h1 class="h3 section-title mb-1">Frequently Asked Questions</h1>
                    <div class="mini-hint">Find quick answers about orders, shipping, returns, payments, products, and more.</div>
                </div>
                <div class="search-wrap" style="min-width:320px; max-width:420px; width:100%;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                        <input id="faqSearch" type="text" class="form-control" placeholder="Search FAQs...">
                    </div>
                </div>
            </div>
        </section>

        <!-- Category pills -->
        <section class="soft-card p-3 mb-4">
            <div class="d-flex flex-wrap">
                <button class="pill active" data-filter="all">All</button>
                <button class="pill" data-filter="orders">Orders</button>
                <button class="pill" data-filter="shipping">Shipping & Delivery</button>
                <button class="pill" data-filter="returns">Returns & Refunds</button>
                <button class="pill" data-filter="payments">Payments</button>
                <button class="pill" data-filter="products">Products & Sizing</button>
                <button class="pill" data-filter="account">Account & Security</button>
                <button class="pill" data-filter="discounts">Promotions & Discounts</button>
                <button class="pill" data-filter="reviews">Reviews</button>
            </div>
        </section>

        <!-- FAQ Accordions -->
        <section class="mb-4">
            <div class="accordion" id="faqAccordion">

                <!-- ORDERS -->
                <div class="accordion-item mb-3" data-category="orders">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q1">
                            How do I track my order?
                        </button>
                    </h2>
                    <div id="q1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            After checkout, you'll receive an email with your order number and a tracking link once shipped.
                            You can also check tracking anytime under <em>My Account → Orders</em>.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-3" data-category="orders">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q2">
                            Can I change or cancel my order?
                        </button>
                    </h2>
                    <div id="q2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            We can modify or cancel if the order is still in <strong>pending</strong> or <strong>processing</strong> status.
                            If it’s already shipped, we can help with a return once delivered.
                        </div>
                    </div>
                </div>

                <!-- SHIPPING -->
                <div class="accordion-item mb-3" data-category="shipping">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q3">
                            What shipping options do you offer?
                        </button>
                    </h2>
                    <div id="q3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Standard (3–7 days) and Express (1–2 days). International express (DHL/UPS) is available for select countries.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-3" data-category="shipping">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q4">
                            Do you ship internationally?
                        </button>
                    </h2>
                    <div id="q4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes—duties and taxes may apply depending on your country. We’ll show available options at checkout.
                        </div>
                    </div>
                </div>

                <!-- RETURNS -->
                <div class="accordion-item mb-3" data-category="returns">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q5">
                            What is your return policy?
                        </button>
                    </h2>
                    <div id="q5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Returns are accepted within 14 days of delivery in original condition with all tags/packaging.
                            Refunds are issued to the original payment method after inspection.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-3" data-category="returns">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q6">
                            How do I start a return?
                        </button>
                    </h2>
                    <div id="q6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Contact us with your order number from the email you used at checkout. We’ll send a prepaid label if eligible.
                        </div>
                    </div>
                </div>

                <!-- PAYMENTS -->
                <div class="accordion-item mb-3" data-category="payments">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q7">
                            What payment methods do you accept?
                        </button>
                    </h2>
                    <div id="q7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Major cards are supported. Your card is charged when you place the order; you’ll receive a receipt by email.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-3" data-category="payments">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q8">
                            Is checkout secure?
                        </button>
                    </h2>
                    <div id="q8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes. We use HTTPS, and passwords are stored hashed. We never store full card details on our servers.
                        </div>
                    </div>
                </div>

                <!-- PRODUCTS -->
                <div class="accordion-item mb-3" data-category="products">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q9">
                            How do I choose the right size?
                        </button>
                    </h2>
                    <div id="q9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Check the case size (in mm) on each product page. As a quick guide, 36–40&nbsp;mm fits most wrists for daily wear; larger sizes offer a bolder look.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-3" data-category="products">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q10">
                            Are your watches water-resistant?
                        </button>
                    </h2>
                    <div id="q10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Water-resistance varies by model. Always check the spec (e.g., 50&nbsp;m for daily use, 200&nbsp;m for diving) and avoid hot water or saunas unless specified.
                        </div>
                    </div>
                </div>

                <!-- ACCOUNT -->
                <div class="accordion-item mb-3" data-category="account">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q11">
                            Do I need an account to order?
                        </button>
                    </h2>
                    <div id="q11" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            You can browse without signing in. To place reviews and manage orders, please register or log in.
                        </div>
                    </div>
                </div>

                <div class="accordion-item mb-3" data-category="account">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q12">
                            How do I reset my password?
                        </button>
                    </h2>
                    <div id="q12" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Use the “Forgot password” link on the login page. We’ll email a secure reset link to your registered address.
                        </div>
                    </div>
                </div>

                <!-- DISCOUNTS -->
                <div class="accordion-item mb-3" data-category="discounts">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q13">
                            How do discounts work?
                        </button>
                    </h2>
                    <div id="q13" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Some products show an automatic discount during active promos. Final price appears on the product page and at checkout when applicable.
                        </div>
                    </div>
                </div>

                <!-- REVIEWS -->
                <div class="accordion-item mb-3" data-category="reviews">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q14">
                            Who can write reviews?
                        </button>
                    </h2>
                    <div id="q14" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Registered customers can post reviews. Guest users may read reviews; attempting to post will open the login form.
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Still need help -->
        <section class="faq-cta">
            <h2 class="h5 section-title mb-2">Still need help?</h2>
            <p class="mb-3">If you can’t find what you’re looking for, our team will be happy to assist.</p>
            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <a href="contact.php" class="btn btn-verve"><i class="bi bi-envelope me-2"></i>Contact Us</a>
                <a href="tel:+15551234567" class="btn btn-verve"><i class="bi bi-telephone-outbound me-2"></i>Call (555) 123-4567</a>
            </div>
        </section>

    </main>

    <footer class="container pb-4 text-center text-muted mt-4">
        <small>© <?= date('Y') ?> Verve Timepieces. All rights reserved.</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple client-side filter + search
        (function() {
            const pills = document.querySelectorAll('.pill');
            const items = document.querySelectorAll('.accordion-item');
            const search = document.getElementById('faqSearch');

            let active = 'all';

            function update() {
                const q = (search.value || '').toLowerCase().trim();
                items.forEach(it => {
                    const cat = it.getAttribute('data-category');
                    const txt = it.textContent.toLowerCase();
                    const matchCat = (active === 'all') || (cat === active);
                    const matchText = !q || txt.includes(q);
                    it.style.display = (matchCat && matchText) ? '' : 'none';
                });
            }

            pills.forEach(p => p.addEventListener('click', () => {
                pills.forEach(x => x.classList.remove('active'));
                p.classList.add('active');
                active = p.getAttribute('data-filter');
                update();
            }));

            search.addEventListener('input', update);
        })();
    </script>
</body>

</html>