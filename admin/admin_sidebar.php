<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="sidebar">
    <div class="brand-logo">
        <img src="/images/logo_image/logo1.png" alt="Verve Timepieces Logo">
    </div>
    <!-- Admin Info Dropdown -->
    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === "admin"): ?>
        <div class="admin-info-wrapper text-center mb-3">
            <div class="dropdown">
                <a href="#" class="admin-dropdown-toggle" id="adminInfoDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="display:inline-block;">
                    <!-- <i class="bi bi-person-circle" style="font-size: 1.7rem; color: #352826; vertical-align:middle;"></i> -->
                    <span style="font-weight:600; color:#785A49; margin-left:8px;"><b>Welcome: </b><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </a>
                <ul class="dropdown-menu" aria-labelledby="adminInfoDropdown" style="min-width:220px; border-radius:10px;">
                    <li class="dropdown-item-text" style="padding: 12px 18px;">
                        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong><br>
                        <small><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></small>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
    <h3 class="text-center mb-4">Admin Menu</h3>
    <ul class="nav flex-column">
        <li class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : ''; ?>">
            <a class="nav-link py-3" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
        </li>
        <!-- Products Category -->
        <div class="category-title">Products</div>
        <li>
            <a href="product_management.php" class="nav-link py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'product_management.php') ? 'active' : ''; ?>">
                <i class="bi bi-tags me-2"></i> Products Management
            </a>
        </li>
     
      
        <!-- Users Category -->
        <div class="category-title">Users</div>
        <li>
            <a href="admin_users.php" class="nav-link py-2 <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_users.php') ? 'active' : ''; ?>">
                <i class="bi bi-people me-2"></i> See All Users
            </a>
        </li>
    
        <!-- Add more main categories as needed -->
    </ul>
</div>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
body {
    background-color: #DED2C8;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    min-height: 100vh;
    margin: 0;
    color: #352826;
}
.sidebar {
    width: 235px;
    background: #DED2C8;
    padding: 20px 0;
    border-right: 2px solid #785A49;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 2px 0 16px rgba(53, 40, 38, 0.08);
}
.sidebar .brand-logo {
    background-color: #fff;
    text-align: center;
    margin-top: -20px;
    margin-bottom: 20px;
    padding: 0 15px;
    border-radius: 0 0 14px 14px;
    box-shadow: 0 2px 8px rgba(53, 40, 38, 0.06);
}
.sidebar .brand-logo img {
    max-width: 100%;
    height: 105px;
    display: block;
    margin: 0 auto;
}
.admin-info-wrapper {
    margin-bottom: 12px;
    margin-top: -10px;
}
.admin-dropdown-toggle {
    text-decoration: none !important;
    font-size: 18px;
    font-style: italic;
   
}
.sidebar h3 {
    color: #785A49;
    font-weight: 700;
    padding: 0 15px 10px 15px;
    margin-bottom: 18px;
    border-bottom: 1px solid #A57A5B;
    font-size: 1.08rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar ul li {
    margin-bottom: 0;
}
.sidebar ul li a {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    color: #352826;
    text-decoration: none;
    font-size: 0.95rem;
    font-weight: 500;
    background: none;
    transition: background 0.18s, color 0.18s, border-left 0.2s;
    position: relative;
    overflow: hidden;
    border-left: 4px solid transparent;
    line-height: 1.4;
    border-radius: 0 20px 20px 0;
}
.sidebar ul li a .bi {
    margin-right: 10px;
    font-size: 1.13rem;
    color: #352826;
    transition: color 0.18s;
}
.sidebar ul li a:hover,
.sidebar ul li a.active {
    background-color: #785A49;
    color: #DED2C8;
    border-left: 4px solid #A57A5B;
}
.sidebar ul li a:hover .bi,
.sidebar ul li a.active .bi {
    color: #352826;
}
.sidebar .category-title {
    font-weight: 600;
    color: #352826;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.82rem;
    margin: 17px 0 7px 22px;
}
@media (max-width: 991.98px) {
    .sidebar {
        display: none;
    }
}
</style>