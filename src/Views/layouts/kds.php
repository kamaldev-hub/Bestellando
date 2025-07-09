<?php
// Basic KDS Layout
use App\Core\Security;

$baseUrl = rtrim(getenv('APP_URL') ?: '', '/');
// Basic check for KDS role (can be expanded)
$isKdsUserLoggedIn = (isset($_SESSION['user_role']) && (in_array($_SESSION['user_role'], [\App\Models\User::ROLE_KITCHEN_STAFF, \App\Models\User::ROLE_ADMIN])));
$kdsUserName = $isKdsUserLoggedIn ? ($_SESSION['username'] ?? 'Kitchen Staff') : '';

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark"> <!-- Dark theme for KDS -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? Security::escape($pageTitle) . ' - Bestellando KDS' : 'Bestellando KDS'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <!-- Specific styles for KDS can go into a kds.css or be added here -->
    <style>
        body {
            font-size: 1.1rem; /* Slightly larger base font for readability */
        }
        .navbar {
            margin-bottom: 1rem;
        }
        .kds-order-ticket {
            border-width: 2px;
            margin-bottom: 1.5rem;
        }
        .kds-order-ticket .card-header {
            font-size: 1.5rem; /* Larger table number */
            font-weight: bold;
        }
        .kds-order-ticket .card-body {
            padding: 1rem;
        }
        .kds-order-ticket ul {
            padding-left: 1.2rem; /* Indent list items */
        }
        .kds-order-ticket li {
            font-size: 1.2rem; /* Larger item names */
            margin-bottom: 0.5rem;
        }
        .kds-order-ticket .item-quantity {
            font-weight: bold;
        }
        .kds-order-ticket .item-notes {
            font-size: 0.9rem;
            color: #adb5bd; /* Lighter color for notes */
            padding-left: 1rem;
        }
        .kds-actions button {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        /* High contrast for dark theme */
        .bg-warning.kds-order-ticket { /* New orders */
            border-color: #ffc107 !important;
        }
         .bg-warning.kds-order-ticket .card-header, .bg-warning.kds-order-ticket .card-body {
            color: #000 !important; /* Ensure text is dark on yellow */
        }
        .bg-info.kds-order-ticket { /* In Progress orders */
            border-color: #0dcaf0 !important;
        }
        .bg-success.kds-order-ticket { /* Ready orders - though KDS might not show these long */
            border-color: #198754 !important;
        }
        .kds-column {
            /* max-height: calc(100vh - 150px); /* Adjust based on navbar/footer */
            /* overflow-y: auto; */
            /* padding-bottom: 20px; */
        }

    </style>
    <?php if (isset($headContent)): echo $headContent; endif; ?>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo $baseUrl; ?>/kds">Bestellando KDS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#kdsNavbar" aria-controls="kdsNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="kdsNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="<?php echo $baseUrl; ?>/kds">Active Orders</a>
                </li>
                <!-- Potentially add link to "Recently Completed" or settings -->
            </ul>
            <?php if ($isKdsUserLoggedIn): ?>
            <span class="navbar-text me-3">
                User: <?php echo Security::escape($kdsUserName); ?>
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

<div class="container-fluid kds-content-wrapper">
    <?php
        // Flash messages
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            echo '<div class="alert alert-' . Security::escape($flash['type']) . ' alert-dismissible fade show" role="alert">';
            echo Security::escape($flash['text']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['flash_message']);
        }
    ?>
    <!-- Content will be rendered by the specific view file (kds/index.php) -->
</div>

<footer class="container-fluid text-center mt-auto py-3 bg-dark border-top border-secondary">
    <p class="text-muted">&copy; <?php echo date('Y'); ?> Bestellando KDS. Auto-refresh <span id="refresh-status">enabled</span>. Last refresh: <span id="last-refresh-time">N/A</span></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<?php if (isset($footerScript)): echo $footerScript; endif; ?>
</body>
</html>
