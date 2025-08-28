<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
    <style>
        .sidebar a {
            padding: 10px 20px;
            text-decoration: none;
            font-size: 14px;
            color: white;
            display: block;
            transition: background 0.2s ease-in-out;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a class="navbar-brand mx-5 px-1 mt-5" href="<?php echo BASE_URL; ?>/">
            <div style="background: white; border-radius: 50%; padding: 5px; display: inline-block;">
                <img src="<?php echo BASE_URL; ?>/assets/img/ydf.png" alt="Your Daily Food Logo" style="height: 70px;">
            </div>
        </a>
        <a href="<?php echo BASE_URL; ?>/">Dashboard</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>

            <!-- Order Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Orders</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/orders/order_entry"><i class="fas fa-plus-circle me-2"></i>Create Order</a>
                    <a href="<?php echo BASE_URL; ?>/orders/order_list"><i class="fas fa-list me-2"></i>Order List</a>
                </div>
            </div>

            <!-- Purchase Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Purchase</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/supplier/view_orders"><i class="fas fa-eye me-2"></i>View Purchases</a>
                </div>
            </div>

            <!-- Receiving Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Receiving</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/recieving/recieving"><i class="fas fa-dolly me-2"></i>Collect</a>
                    <a href="<?php echo BASE_URL; ?>/recieving/recieved_list"><i class="fas fa-list me-2"></i>View Receival List</a>
                </div>
            </div>

            <!-- Quality Control Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Quality Control</a>
                <div class="dropdown-menu">
                    <div class="sidebar-dropdown">
                        <a href="<?php echo BASE_URL; ?>/dispatch/departure_information_list"><i class="fas fa-info-circle me-2"></i>Departure Info</a>
                    </div>
                    <div class="sidebar-dropdown">
                        <a href="<?php echo BASE_URL; ?>/quality/sword_quality_control/receiving_for_sword_view"><i class="fas fa-fish me-2"></i>Sword</a>
                    </div>
                    <div class="sidebar-dropdown">
                        <a href="<?php echo BASE_URL; ?>/quality/tuna_quality_control/receiving_for_tuna_view"><i class="fas fa-fish me-2"></i>Tuna</a>
                    </div>
                    <div class="sidebar-dropdown">
                        <a href="<?php echo BASE_URL; ?>/quality/other_fish_quality_control/received_fish_inspection_view"><i class="fas fa-fish me-2"></i>Other Seafood</a>
                    </div>
                    <div class="sidebar-dropdown">
                        <a href="<?php echo BASE_URL; ?>/stock/stock_inspection_list"><i class="fas fa-search me-2"></i>Stock Inspection</a>
                    </div>
                </div>
            </div>

            <!-- Stock Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Stock</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/stock/newstock_list"><i class="fas fa-eye me-2"></i>View stock</a>
                    <a href="<?php echo BASE_URL; ?>/other/sword_tuna_receiving_analysis_view"><i class="fas fa-chart-bar me-2"></i>Sword & Tuna Analysis</a>
                </div>
            </div>

            <!-- Recovery Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Recovery</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/other/recovery_master_sheet_reef_fish_view"><i class="fas fa-file-excel me-2"></i>Reef fish</a>
                    <a href="<?php echo BASE_URL; ?>/other/recovery_master_sheet_tuna_sword_view"><i class="fas fa-file-excel me-2"></i>Tuna/Sword</a>
                </div>
            </div>

            <!-- Other Documents Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Other Documents</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/other/net_to_gross_calculator"><i class="fas fa-file-excel me-2"></i>Net to Gross Calculator</a>
                    <a href="<?php echo BASE_URL; ?>/other/box_information_view"><i class="fas fa-file-excel me-2"></i>Box Information</a>
                    <a href="<?php echo BASE_URL; ?>/other/shortage_specieswise_view"><i class="fas fa-file-excel me-2"></i>Shortage</a>
                </div>
            </div>

            <!-- Logistic Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Logistic</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/logistic/packing_list_view"><i class="fas fa-info-circle me-2"></i>Packing List</a>
                    <a href="<?php echo BASE_URL; ?>/logistic/distribution_sheet"><i class="fas fa-info-circle me-2"></i>Distribution Sheet</a>
                    <a href="<?php echo BASE_URL; ?>/logistic/newdistribution_sheet"><i class="fas fa-info-circle me-2"></i>NEWDistribution Sheet</a>
                    <a href="<?php echo BASE_URL; ?>/logistic/net_to_gross_master"><i class="fas fa-info-circle me-2"></i>Net to Gross Master</a>
                    <a href="<?php echo BASE_URL; ?>/logistic/newnet_to_gross_master"><i class="fas fa-info-circle me-2"></i>NEWNet to Gross Master</a>
                    <a href="<?php echo BASE_URL; ?>/logistic/real_production"><i class="fas fa-chart-pie me-2"></i>Real Production</a>
                </div>
            </div>

            <!-- Manage Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Manage</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/products/manage"><i class="fas fa-box-open me-2"></i>Manage Products</a>
                    <a href="<?php echo BASE_URL; ?>/customers/manage"><i class="fas fa-users me-2"></i>Manage Customers</a>
                    <a href="<?php echo BASE_URL; ?>/suppliers/manage"><i class="fas fa-truck me-2"></i>Manage Suppliers</a>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Settings</a>
                <div class="dropdown-menu">
                    <div class="sidebar-dropdown">
                        <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">
                            <i class="fa-solid fa-user me-2"></i>Account
                        </a>
                        <div class="dropdown-menu">
                            <a href="<?php echo BASE_URL; ?>/reset-password"><i class="fa-solid fa-key me-2"></i>Reset Password</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access Dropdown -->
            <div class="sidebar-dropdown">
                <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)">Access</a>
                <div class="dropdown-menu">
                    <a href="<?php echo BASE_URL; ?>/access/users"><i class="fas fa-user-shield me-2"></i>Manage Access</a>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Top Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" id="navbar">
        <div class="container-fluid">
            <a class="toggle-btn" onclick="toggleSidebar(event)">
                <i class="fas fa-bars mx-2"></i>
            </a>
            <a class="navbar-brand mx-2" href="<?php echo BASE_URL; ?>/">
                <span class="d-none d-sm-inline ms-5">Your Daily Foods</span>
                <span class="d-inline d-sm-none ms-5">YDF</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout"><i class="fas fa-sign-out-alt me-2"></i></a>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])) { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-warning" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user me-1"></i>
                                <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Guest'); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php } else { ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/register"><i class="fas fa-user-plus me-1"></i><span class="d-none d-md-inline">Register</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/login"><i class="fas fa-sign-in-alt me-1"></i><span class="d-none d-md-inline">Login</span></a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        function toggleSidebar(event) {
            event.preventDefault();
            const sidebar = document.getElementById("sidebar");
            const navbar = document.getElementById("navbar");
            const content = document.getElementById("content");
            const body = document.body;

            sidebar.classList.toggle("active");
            navbar.classList.toggle("active");
            if (content) content.classList.toggle("active");
            body.classList.toggle("sidebar-open");

            localStorage.setItem("sidebarState", sidebar.classList.contains("active") ? "open" : "closed");
        }

        function restoreSidebarState() {
            const sidebar = document.getElementById("sidebar");
            const navbar = document.getElementById("navbar");
            const content = document.getElementById("content");

            const sidebarState = localStorage.getItem("sidebarState");

            if (sidebarState === "open") {
                sidebar.classList.add("active");
                navbar.classList.add("active");
                if (content) content.classList.add("active");
            } else {
                sidebar.classList.remove("active");
                navbar.classList.remove("active");
                if (content) content.classList.remove("active");
            }
        }

        function toggleDropdown(event) {
            event.preventDefault();
            event.stopPropagation();

            const dropdownToggle = event.currentTarget;
            const dropdownMenu = dropdownToggle.nextElementSibling;
            const isActive = dropdownMenu.classList.contains('active');

            // Close all dropdowns at the same level
            const parentItem = dropdownToggle.closest('.sidebar-dropdown');
            if (parentItem) {
                const siblings = parentItem.parentElement.querySelectorAll('.sidebar-dropdown > .dropdown-menu');
                siblings.forEach(menu => {
                    if (menu !== dropdownMenu) {
                        menu.classList.remove('active');
                        menu.previousElementSibling.classList.remove('active');
                    }
                });
            }

            // Toggle current dropdown
            dropdownToggle.classList.toggle('active');
            dropdownMenu.classList.toggle('active');

            // Close child dropdowns when parent is closed
            if (!dropdownMenu.classList.contains('active')) {
                dropdownMenu.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('active');
                    menu.previousElementSibling.classList.remove('active');
                });
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            restoreSidebarState();

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.sidebar-dropdown')) {
                    document.querySelectorAll('.sidebar-dropdown .dropdown-menu').forEach(menu => {
                        menu.classList.remove('active');
                    });
                    document.querySelectorAll('.sidebar-dropdown .dropdown-toggle').forEach(toggle => {
                        toggle.classList.remove('active');
                    });
                }
            });

            // Close sidebar when clicking on a link (for mobile)
            document.querySelectorAll('.sidebar a:not(.dropdown-toggle)').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        const sidebar = document.getElementById("sidebar");
                        const navbar = document.getElementById("navbar");
                        const content = document.getElementById("content");
                        const body = document.body;

                        sidebar.classList.remove("active");
                        navbar.classList.remove("active");
                        if (content) content.classList.remove("active");
                        body.classList.remove("sidebar-open");
                    }
                });
            });
        });

        // Close sidebar when window is resized above 992px
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                const sidebar = document.getElementById("sidebar");
                const navbar = document.getElementById("navbar");
                const content = document.getElementById("content");
                const body = document.body;

                sidebar.classList.remove("active");
                navbar.classList.remove("active");
                if (content) content.classList.remove("active");
                body.classList.remove("sidebar-open");
            }
        });
    </script>