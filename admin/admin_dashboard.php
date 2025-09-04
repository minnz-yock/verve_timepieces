<?php

require_once('../admin_login_check.php');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Verve Timepieces</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Verve Timepieces Admin Dashboard - Brown Color Theme */

/* Main Content Area Styles */
body {
    background-color: #DED2C8; /* Light sand background */
    color: #352826; /* Deep brown text */
}
.main-content {
    margin-left: 250px;
    padding: 30px;
    width: 100%;
}
.main-content h1 {
    color: #352826;
    font-weight: 800;
    margin-bottom: 30px;
    font-size: 2.2rem;
    letter-spacing: 0.5px;
}

/* Cards */
.card {
    background: #785A49; /* Medium brown */
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(53, 40, 38, 0.08);
    border: 1px solid #A57A5B; /* Accent brown */
    margin-bottom: 30px;
}
.card-header {
    background: #A57A5B; /* Accent brown */
    border-bottom: 1px solid #785A49;
    border-radius: 12px 12px 0 0;
    padding: 15px 20px;
    font-weight: 600;
    color: #DED2C8; /* Light sand text */
    font-size: 1.1rem;
}
.card-title {
    color: #352826 !important; /* Deep brown for stats numbers */
}

/* Table Styles */
.table {
    border-radius: 0 0 12px 12px;
    overflow: hidden;
}
.table thead th {
    background-color: #A57A5B; /* Header accent brown */
    color: #DED2C8; /* Light sand text */
    font-weight: 600;
    border-bottom: 2px solid #785A49;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}
.table tbody td {
    vertical-align: middle;
    font-weight: 550;
    color: #352826; /* Deep brown */
    font-size: 0.95rem;
    border-top: 1px solid #DED2C8;
}
.table-hover tbody tr:hover {
    background-color: #A57A5B; /* Accent brown on hover */
    color: #DED2C8;
}

/* Badges (Bootstrap default colors, but you can override if needed) */
.badge.bg-success {
    background-color: #785A49 !important;
    color: #DED2C8 !important;
}
.badge.bg-warning {
    background-color: #A57A5B !important;
    color: #352826 !important;
}
.badge.bg-secondary {
    background-color: #DED2C8 !important;
    color: #352826 !important;
}
.badge.bg-danger {
    background-color: #352826 !important;
    color: #DED2C8 !important;
}

/* Action Buttons */
.action-buttons button,
.action-buttons a {
    margin-right: 5px;
    font-size: 0.9rem;
    padding: 0.4rem 0.8rem;
    border-radius: 5px;
    border: none;
    color: #DED2C8;
    background-color: #352826; /* Deep brown for icons/buttons */
    transition: background 0.2s;
}
.action-buttons button:hover,
.action-buttons a:hover {
    background-color: #785A49; /* Medium brown hover */
    color: #DED2C8;
}

/* Forms */
.form-label {
    color: #785A49;
    font-weight: 600;
    font-size: 0.85rem;
}
.form-control {
    background: #DED2C8;
    border: 1px solid #A57A5B;
    color: #352826;
    border-radius: 6px;
    padding: 0.6rem 0.8rem;
    font-size: 0.95rem;
}
.form-control:focus {
    border-color: #785A49;
    box-shadow: 0 0 0 2px rgba(168, 122, 91, 0.15);
}
.form-control::placeholder {
    color: #A57A5B;
}

/* Admin Primary Button */
.btn-primary-admin {
    background: #352826;
    color: #DED2C8;
    font-weight: 700;
    letter-spacing: 0.8px;
    border-radius: 6px;
    padding: 0.6rem 1rem;
    transition: background 0.2s;
}
.btn-primary-admin:hover {
    background: #785A49;
}

/* Responsive adjustments for sidebar */
@media (max-width: 991.98px) {
    .main-content {
        margin-left: 0;
    }
}
    </style>
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <h1>Dashboard Overview</h1>

        <!-- Recent Orders Card -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-receipt-cutoff"></i> Recent Orders
            </div>
            <div class="card-body">
                <p class="card-text">Displaying the latest 5 orders:</p>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#VT1001</td>
                            <td>John Doe</td>
                            <td>2024-08-05</td>
                            <td>\$299.00</td>
                            <td><span class="badge bg-success">Shipped</span></td>
                        </tr>
                        <tr>
                            <td>#VT1002</td>
                            <td>Jane Smith</td>
                            <td>2024-08-05</td>
                            <td>\$349.00</td>
                            <td><span class="badge bg-warning text-dark">Processing</span></td>
                        </tr>
                        <tr>
                            <td>#VT1003</td>
                            <td>Peter Jones</td>
                            <td>2024-08-04</td>
                            <td>\$429.00</td>
                            <td><span class="badge bg-secondary">Delivered</span></td>
                        </tr>
                        <tr>
                            <td>#VT1004</td>
                            <td>Alice Williams</td>
                            <td>2024-08-04</td>
                            <td>\$199.00</td>
                            <td><span class="badge bg-success">Shipped</span></td>
                        </tr>
                        <tr>
                            <td>#VT1005</td>
                            <td>Bob Brown</td>
                            <td>2024-08-03</td>
                            <td>\$550.00</td>
                            <td><span class="badge bg-danger">Cancelled</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Stats Cards -->
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-header">
                        <i class="bi bi-box-seam"></i> Total Products
                    </div>
                    <div class="card-body">
                        <h3 class="card-title" style="color: #6792C5;">157</h3>
                        <p class="card-text">Total items available in stock</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-header">
                        <i class="bi bi-people"></i> Total Users
                    </div>
                    <div class="card-body">
                        <h3 class="card-title" style="color: #4D6CA8;">1245</h3>
                        <p class="card-text">Registered customers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-header">
                        <i class="bi bi-currency-dollar"></i> Revenue (This Month)
                    </div>
                    <div class="card-body">
                        <h3 class="card-title" style="color: #272F44;">\$25,678.50</h3>
                        <p class="card-text">Total sales this month</p>
                    </div>
                </div>
            </div>
        </div>


    </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script to highlight active menu item
        document.addEventListener('DOMContentLoaded', (event) => {
            const currentFile = window.location.pathname.split('/').pop(); // Get the current file name (e.g., 'admin_dashboard.php')

            // Highlight the active main navigation link
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                if (link.getAttribute('href') === currentFile) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>

</html>