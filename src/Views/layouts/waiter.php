<?php
// Basic Waiter Interface Layout
use App\Core\Security;

$baseUrl = rtrim(getenv('APP_URL') ?: '', '/');
// Basic check for waiter role (can be expanded)
$isWaiterLoggedIn = (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === \App\Models\User::ROLE_WAITER || $_SESSION['user_role'] === \App\Models\User::ROLE_ADMIN));
$waiterName = $isWaiterLoggedIn ? ($_SESSION['username'] ?? 'Waiter') : '';

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? Security::escape($pageTitle) . ' - Bestellando Waiter' : 'Bestellando Waiter'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <!-- Specific styles for waiter interface can go into a waiter.css or be added here -->
    <style>
        body {
            background-color: #f8f9fa; /* Light background for the interface */
        }
        .navbar {
            margin-bottom: 1rem;
        }
        /* Additional custom styles for waiter interface can be added in assets/css/waiter.css or style.css */
    </style>
    <?php if (isset($headContent)): echo $headContent; endif; // For page-specific CSS/JS in head ?>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $baseUrl; ?>/waiter">Bestellando Waiter</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#waiterNavbar" aria-controls="waiterNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="waiterNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/waiter') !== false && strpos($_SERVER['REQUEST_URI'], '/order') === false) ? 'active' : ''; ?>" href="<?php echo $baseUrl; ?>/waiter">Tables</a>
                </li>
                <!-- Potentially add other links like "Active Orders", "My Profile" -->
            </ul>
            <?php if ($isWaiterLoggedIn): ?>
            <span class="navbar-text me-3">
                Welcome, <?php echo Security::escape($waiterName); ?>
            </span>
            <form action="<?php echo $baseUrl; ?>/admin/logout" method="POST" class="d-flex">
                 <?php echo Security::csrfInput(); ?>
                <button class="btn btn-outline-light" type="submit">Logout</button>
            </form>
            <?php else: ?>
             <a href="<?php echo $baseUrl; ?>/admin/login" class="btn btn-outline-light">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container-fluid waiter-content-wrapper">
    <?php
        // Display flash messages if any
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            echo '<div class="alert alert-' . Security::escape($flash['type']) . ' alert-dismissible fade show" role="alert">';
            echo Security::escape($flash['text']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['flash_message']);
        }
    ?>

    <?php
        // This is where the specific view content will be injected.
        // The controller's render method will include the specific view file (e.g., tables/index.php or order/index.php)
        // That specific view file will then generate its content.
        // For this simple layout, we assume the specific view file is included by the Controller's render method.
        // And the specific view file itself should output its content directly.
        // (No $viewContentPath mechanism like in admin layout for now, for simplicity)
        // The controller's render method:
        //   ob_start();
        //   include $viewFile; // $viewFile is (e.g. src/Views/waiter/tables/index.php)
        //   $content = ob_get_clean();
        //   echo $content;
        // This means the $viewFile must produce the content that fits *within* this layout.
        // So, the $viewFile itself should NOT include <html>, <head>, <body> tags.
        // This part is tricky. The `Controller::render` method needs to be consistent.
        // If `Controller::render` is just `include $viewFile; echo $content`, then this layout
        // needs to be included by each specific view.

        // Let's refine this: The specific view (e.g., waiter/tables/index.php) will be responsible
        // for including this layout file at its beginning, then outputting its own content.
        // Example in waiter/tables/index.php:
        // <?php
        // $pageTitle = "Select Table";
        // include ROOT_PATH . '/src/Views/layouts/waiter.php'; // This includes the layout
        // // Now output specific content for table selection below this comment
        // ?>
        // <div class="container"> ... table selection content ... </div>
        //
        // This approach is common for simple PHP templating.
        // The content for the main area is generated by the specific view *after* it includes this layout.
        // So, this layout file should end here, and the specific view will append its content.
    ?>
<!-- Content will be rendered by the specific view file which includes this layout -->
</div> <!-- .container-fluid.waiter-content-wrapper -->

<footer class="container-fluid text-center mt-auto py-3 bg-light">
    <p>&copy; <?php echo date('Y'); ?> Bestellando. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<?php if (isset($footerScript)): echo $footerScript; endif; // For page-specific JS at the end of body ?>
</body>
</html>
