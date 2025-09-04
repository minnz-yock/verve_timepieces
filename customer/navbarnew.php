<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <!-- Logo -->
        <a class="navbar-brand" href="index.php">
            <img src="../images/logo_image/logo1.png" alt="Verve Timepieces Logo">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Navigation Links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link active" href="homepage.php">Home</a></li>
                <!-- Buy Watch Dropdown ... -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown">Buy Watch</a>
                    <ul class="dropdown-menu multi-column-dropdown" aria-labelledby="navbarDropdown">
                        <div class="row">
                            <!-- Column 1: Brands -->
                            <div class="col-md-6">
                                <h6>Brands</h6>
                                <ul class="list-unstyled">
                                    <li><a class="dropdown-item" href="brand-a.php">Brand Name A</a></li>
                                    <li><a class="dropdown-item" href="brand-b.php">Brand Name B</a></li>
                                    <li><a class="dropdown-item" href="brand-c.php">Brand Name C</a></li>
                                    <li><a class="dropdown-item" href="all-brands.php">View All Brands</a></li>
                                </ul>
                            </div>
                            <!-- Column 2: Collections -->
                            <div class="col-md-6">
                                <h6>Collections</h6>
                                <ul class="list-unstyled">
                                    <li><a class="dropdown-item" href="dress-watches.php">Dress Watches</a></li>
                                    <li><a class="dropdown-item" href="sport-watches.php">Sport Watches</a></li>
                                    <li><a class="dropdown-item" href="all-collections.php">View All Collections</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
            </ul>

            <!-- Search Form -->
            <!-- <form class="d-flex me-3" role="search" style="width: 250px;">
                <input class="form-control me-2" type="search" placeholder="Search Watches..." aria-label="Search" />
                <button class="btn btn-outline-dark" type="submit">Search</button>
            </form> -->

            <!-- Search Form -->
            <form class="d-flex me-auto ms-3 search-form" role="search" method="GET" action="search.php">
                <div class="input-group">
                    <input class="form-control search-input" type="search" name="q" placeholder="Search Watches..." aria-label="Search">
                    <!-- clickable submit icon -->
                    <button class="btn btn-search" type="submit" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>


            <!-- User Account Dropdown & Shopping Cart -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Show logged in user info -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <span class="dropdown-item-text">
                                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                                </span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="accdetails.php">Account Details</a></li>
                            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="signinform.php">Sign In</a></li>
                            <li><a class="dropdown-item" href="signupform.php">Create Account</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">
                        <i class="bi bi-cart3" style="font-size: 1.5rem;"></i>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="wishlist.php">
                        <i class="bi bi-suit-heart" style="font-size: 1.5rem;"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<style>
    .navbar {
        background-color: #FFF;
        border-bottom: 1px solid #DED2C8;
        padding: 1rem 0;
        height: 100px;
    }

    .navbar-brand img {
        height: 110px;
        margin-top: -50px;
        margin-bottom: -50px;
    }

    .nav-link {
        color: #785A49 !important;
        font-weight: 500;
        padding: 0.5rem 1rem;
        transition: color 0.2s ease-in-out;
    }

    .nav-link:hover,
    .nav-link.active {
        color: #352826 !important;
    }

    .dropdown-item {
        color: #A57A5B;
        font-size: 0.95rem;
        padding: -1rem -1rem;
        transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
    }

    .dropdown-item:hover {
        background-color: #DED2C8;
        color: #352826;
    }

    .dropdown-menu.multi-column-dropdown {
        width: 450px;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.10);
        border: 1px solid #DED2C8;
        background-color: #FFF;
    }

    .dropdown-menu.multi-column-dropdown .row {
        margin-left: 0;
        margin-right: 0;
    }

    .dropdown-menu.multi-column-dropdown .col-md-6 {
        padding: 0 10px;
    }

    .dropdown-menu.multi-column-dropdown h6 {
        font-size: 0.85rem;
        color: #785A49;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 15px;
        border-bottom: 1px solid #DED2C8;
        padding-bottom: 5px;
    }

    .dropdown-menu.multi-column-dropdown .list-unstyled {
        padding-left: 0;
        list-style: none;
    }

    .dropdown-menu.multi-column-dropdown .list-unstyled li {
        margin-bottom: 10px;
    }

    /* --- Search form look & layout --- */
    .search-form {
        max-width: 320px;
        /* keep it compact; adjust as you like */
    }

    .search-input {
        border: 1.5px solid #A57A5B;
        background-color: #FFF;
        color: #352826;
        border-radius: 6px 0 0 6px;
        /* rounded left side */
        font-size: 0.95rem;
        padding: 0.6rem 0.9rem;
    }

    .search-input::placeholder {
        color: #A57A5B;
        font-weight: 500;
    }

    /* icon button that replaces the Search text button */
    .btn-search {
        background-color: #352826;
        border: 1.5px solid #352826;
        color: #DED2C8;
        border-radius: 0 6px 6px 0;
        /* rounded right side */
        padding: 0.6rem 0.8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-search:hover {
        background-color: #785A49;
        border-color: #785A49;
        color: #FFFFFF;
    }


    .navbar-nav .nav-link .bi {
        color: #352826;
    }

    .navbar-nav .nav-link:hover .bi {
        color: #A57A5B;
    }

    .navbar-nav .nav-item+.nav-item {
        margin-left: 15px;
    }

    @media (max-width: 991px) {
        .multi-column-dropdown {
            width: 100%;
            padding: 10px;
        }

        .dropdown-menu.multi-column-dropdown .col-md-6 {
            padding: 0 5px;
        }

        .dropdown-menu.multi-column-dropdown h6 {
            margin-bottom: 10px;
        }

        .navbar-nav .nav-item+.nav-item {
            margin-left: 0;
        }
    }
</style>