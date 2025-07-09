<?php
// Basic Admin Layout
// Variables expected: $pageTitle, $content (to be injected by specific views)
// For simplicity, $content is not formally used here; specific views include this layout.
// A more advanced templating engine would handle blocks/inheritance better.

use App\Core\Security; // For CSRF token in logout form

$baseUrl = rtrim(getenv('APP_URL') ?: '', '/');
$isLoggedIn = App\Controllers\Admin\AuthController::isAdminLoggedIn();
$username = $isLoggedIn ? ($_SESSION['username'] ?? 'Admin') : '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? Security::escape($pageTitle) . ' - Bestellando Admin' : 'Bestellando Admin'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .admin-wrapper {
            display: flex;
            flex-grow: 1;
        }
        .admin-sidebar {
            width: 280px;
            flex-shrink: 0;
            background-color: #f8f9fa; /* Light grey */
            border-right: 1px solid #dee2e6;
        }
        .admin-content {
            flex-grow: 1;
            padding: 20px;
            background-color: #ffffff; /* White content area */
        }
        .sidebar .nav-link {
            color: #333;
        }
        .sidebar .nav-link.active {
            background-color: #0d6efd; /* Bootstrap primary */
            color: white;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef; /* Lighter grey for hover */
        }
        .logout-form {
            display: inline;
        }
    </style>
</head>
<body>

<?php if ($isLoggedIn): ?>
<header class="navbar navbar-dark bg-dark sticky-top flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6" href="<?php echo $baseUrl; ?>/admin/dashboard">Bestellando Admin</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <!-- Optional: Search input or other header items -->
    <div class="navbar-nav">
        <div class="nav-item text-nowrap">
            <span class="nav-link px-3 text-white">Welcome, <?php echo Security::escape($username); ?></span>
        </div>
    </div>
    <div class="navbar-nav">
        <div class="nav-item text-nowrap">
            <form action="<?php echo $baseUrl; ?>/admin/logout" method="POST" class="logout-form d-inline">
                <?php echo Security::csrfInput(); // Important for POST logout ?>
                <button type="submit" class="nav-link px-3 bg-dark border-0">Logout</button>
            </form>
        </div>
    </div>
</header>

<div class="container-fluid admin-wrapper p-0">
    <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse admin-sidebar">
        <div class="position-sticky pt-3">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false) ? 'active' : ''; ?>" aria-current="page" href="<?php echo $baseUrl; ?>/admin/dashboard">
                        <span data-feather="home"></span> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/categories') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/admin/categories">
                        <span data-feather="folder"></span> Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/menu-items') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/admin/menu-items">
                        <span data-feather="coffee"></span> Menu Items
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/admin/orders') !== false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/admin/orders">
                        <span data-feather="file-text"></span> Orders
                    </a>
                </li>
                <!-- Add more admin links here: Users, Settings, etc. -->
            </ul>
        </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
        <?php
            // This is where the specific view content will be injected/included.
            // For this simple layout, the view files themselves will include this layout
            // and then output their specific content.
            // If $viewContentPath is set by the controller's render method, include it:
            if (isset($viewContentPath) && file_exists($viewContentPath)) {
                include $viewContentPath;
            } elseif (!isset($viewContentPath) && strpos($_SERVER['REQUEST_URI'], '/admin/login') === false) {
                // This is a fallback, ideally the controller always sets $viewContentPath for admin pages
                // Or each view includes the layout explicitly.
                // echo "<p><em>Content for this page should be loaded here.</em></p>";
            }
        ?>
<?php else: // Not logged in, typically for login page ?>
    <div class="container-fluid admin-content">
        <?php
            // For login page, it won't have sidebar/header, so content is directly shown.
            // The login view itself will handle its structure.
            // This part is tricky with a single layout file.
            // A better approach is separate layouts or conditional rendering inside render()
            if (isset($viewContentPath) && file_exists($viewContentPath)) {
                include $viewContentPath;
            }
        ?>
<?php endif; ?>
    </main>
</div>
<?php // endif; // End of $isLoggedIn check for main structure ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<!-- Feather Icons (optional, for sidebar icons) -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
  feather.replace() // Initialize Feather Icons
</script>
</body>
</html>
