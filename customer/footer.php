<!-- Footer (simple HTML + CSS) -->
<footer class="site-footer">
    <div class="ft-wrap">
        <!-- Brand -->
        <div class="ft-col">
            <a class="ft-brand" href="/">
                <img src="../images/logo_image/logo1.png" alt="Verve Timepieces logo" class="ft-logo">
            </a>
            <p class="ft-tag">Your trusted destination for watches.</p>

            <div class="ft-social">
                <a href="https://facebook.com" aria-label="Facebook" class="ft-social-link">
                    <!-- Facebook -->
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M22 12.07C22 6.48 17.52 2 11.93 2S2 6.48 2 12.07c0 5.02 3.66 9.19 8.44 9.96v-7.04H7.9v-2.92h2.54V9.41c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.23.2 2.23.2v2.45h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.92h-2.34v7.04C18.34 21.26 22 17.09 22 12.07z" />
                    </svg>
                </a>
                <a href="https://instagram.com" aria-label="Instagram" class="ft-social-link">
                    <!-- Instagram -->
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3.5a5.5 5.5 0 1 1 0 11.001 5.5 5.5 0 0 1 0-11Zm0 2a3.5 3.5 0 1 0 .001 7.001A3.5 3.5 0 0 0 12 9.5Zm5.25-.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                    </svg>
                </a>
                <a href="https://twitter.com" aria-label="Twitter/X" class="ft-social-link">
                    <!-- Twitter -->
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M22 5.92c-.73.32-1.5.53-2.31.63a4.02 4.02 0 0 0 1.77-2.23 8 8 0 0 1-2.54.97 4 4 0 0 0-6.9 3.65A11.38 11.38 0 0 1 3.1 4.86a4 4 0 0 0 1.24 5.34 3.97 3.97 0 0 1-1.81-.5v.05a4.01 4.01 0 0 0 3.2 3.93 4.03 4.03 0 0 1-1.8.07 4.01 4.01 0 0 0 3.74 2.78A8.04 8.04 0 0 1 2 18.58a11.36 11.36 0 0 0 6.15 1.8c7.38 0 11.42-6.12 11.42-11.42 0-.17 0-.34-.01-.5A8.15 8.15 0 0 0 22 5.92Z" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="ft-col">
            <h6 class="ft-title">Quick Links</h6>
            <ul class="ft-list">
                <li><a href="index.php">Home</a></li>
                <li><a href="view_products.php">Shop</a></li>
                <li><a href="about_us.php">About</a></li>
                <li><a href="contact_us.php">Contact</a></li>
                <li><a href="faq.php">FAQ</a></li>
            </ul>
        </div>

        <!-- Account -->
        <div class="ft-col">
            <h6 class="ft-title">Account</h6>
            <ul class="ft-list">
                <li><a href="account_details.php">Sign In</a></li>
                <li><a href="order_tracking.php">Orders</a></li>
                <li><a href="favorites.php">Favorites</a></li>
                <li><a href="returns.php">Returns</a></li>
                <li><a href="privacy.php">Privacy Policy</a></li>
                <li><a href="terms.php">Terms of Service</a></li>
            </ul>
        </div>

        <!-- Newsletter -->
        <div class="ft-col">
            <h6 class="ft-title">Newsletter</h6>
            <p class="ft-note">Get updates on new arrivals & offers.</p>
            <form class="ft-form" method="post" action="/newsletter_subscribe.php">
                <input class="ft-input" type="email" name="email" placeholder="Your email" required>
                <button class="ft-btn" type="submit">Subscribe</button>
            </form>
        </div>
    </div>

    <hr class="ft-sep">

    <div class="ft-bottom">
        <small class="ft-copy">© <span id="ft-year"></span> Verve Timepieces. All rights reserved.</small>
        <button id="ft-top" class="ft-top" aria-label="Back to top">↑</button>
    </div>
</footer>

<style>
    /* Minimal, clean, dark footer */
    .site-footer {
        background: #DED2C8;
        color: #e2e8f0;
        font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Arial, sans-serif;
    }

    .ft-wrap {
        max-width: 1100px;
        margin: 0 auto;
        padding: 48px 16px;
        display: grid;
        gap: 24px;
        grid-template-columns: repeat(12, 1fr);
    }

    .ft-col {
        grid-column: span 12;
    }

    .ft-brand {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        color: #fff;
    }

    .ft-logo {
        height: 80px;
        width: auto
    }

    .ft-name {
        font-weight: 600;
        font-size: 1.1rem
    }

    .ft-tag {
        color: #352826;
        margin: 12px 0 16px;
        max-width: 420px
    }

    .ft-social {
        display: flex;
        gap: 12px
    }

    .ft-social-link {
        color: #352826;
        text-decoration: none;
        border: 1px solid #1f2937;
        border-radius: 8px;
        padding: 8px;
        line-height: 0
    }

    .ft-social-link:hover {
        color: #fff;
        border-color: #334155;
        background: #111827
    }

    .ft-title {
        font-weight: 1000;
        font-size: .9rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        color: #352826;
        margin: 6px 0 12px
    }

    .ft-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 8px
    }

    .ft-list a {
        color: #352826;
        text-decoration: none
    }

    .ft-list a:hover {
        color: #fff;
        text-decoration: underline
    }

    .ft-note {
        color: #352826;
        margin: 0 0 10px
    }

    .ft-form {
        display: flex;
        gap: 8px
    }

    .ft-input {
        flex: 1;
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #334155;
        background: #0b1220;
        color: #e2e8f0
    }

    .ft-input:focus {
        outline: none;
        border-color: #3b82f6
    }

    .ft-btn {
        padding: 10px 14px;
        border-radius: 8px;
        border: 0;
        background: #352826;
        color: #fff;
        cursor: pointer
    }

    .ft-btn:hover {
        filter: brightness(1.07)
    }

    .ft-sep {
        border: 0;
        border-top: 1px solid #1f2937;
        margin: 0
    }

    .ft-bottom {
        max-width: 1100px;
        margin: 0 auto;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px
    }

    .ft-copy {
        color: #352826;
    }

    .ft-top {
        border: 0;
        background: #352826;
        color: #fff;
        border-radius: 8px;
        cursor: pointer;
        padding: 8px 10px;
        display: none
    }

    .ft-top.show {
        display: inline-block
    }

    /* Responsive columns */
    @media (min-width:700px) {
        .ft-col:nth-child(1) {
            grid-column: span 5
        }

        .ft-col:nth-child(2) {
            grid-column: span 2
        }

        .ft-col:nth-child(3) {
            grid-column: span 2
        }

        .ft-col:nth-child(4) {
            grid-column: span 3
        }
    }
</style>
