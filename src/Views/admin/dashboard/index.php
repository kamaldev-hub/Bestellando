<?php
// Admin Dashboard View
// Expected variables: $pageTitle (from DashboardController), $username (from DashboardController)
// This view will be wrapped by the admin layout.

use App\Core\Security;

// Set page title for the layout
$pageTitle = $pageTitle ?? 'Admin Dashboard'; // Default if not set by controller

// The actual content for the dashboard
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo Security::escape($pageTitle); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- Optional: Buttons like "Export", "Settings" -->
        <!--
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary">Share</button>
            <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
            <span data-feather="calendar"></span>
            This week
        </button>
        -->
    </div>
</div>

<p>Welcome to the Bestellando Admin Dashboard, <?php echo Security::escape($username ?? 'Admin'); ?>!</p>
<p>From here you can manage menu categories, menu items, view orders, and oversee restaurant operations.</p>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Manage Categories</h5>
                <p class="card-text">Add, edit, or remove menu categories.</p>
                <a href="<?php echo rtrim(getenv('APP_URL') ?: '', '/'); ?>/admin/categories" class="btn btn-primary">Go to Categories</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Manage Menu Items</h5>
                <p class="card-text">Add, edit, or remove items from the menu.</p>
                <a href="<?php echo rtrim(getenv('APP_URL') ?: '', '/'); ?>/admin/menu-items" class="btn btn-primary">Go to Menu Items</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">View Orders</h5>
                <p class="card-text">Monitor incoming and processed orders.</p>
                <a href="<?php echo rtrim(getenv('APP_URL') ?: '', '/'); ?>/admin/orders" class="btn btn-info">Go to Orders</a>
            </div>
        </div>
    </div>
</div>

<h2 class="mt-5">System Overview (Sample Data)</h2>
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th scope="col">Metric</th>
                <th scope="col">Value</th>
                <th scope="col">Details</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Menu Items</td>
                <td><em>(Dynamic data pending)</em></td>
                <td>Count of all available dishes and beverages.</td>
            </tr>
            <tr>
                <td>Active Orders</td>
                <td><em>(Dynamic data pending)</em></td>
                <td>Orders currently new or in progress.</td>
            </tr>
            <tr>
                <td>Revenue Today</td>
                <td><em>(Dynamic data pending)</em></td>
                <td>Total sales for the current day.</td>
            </tr>
            <tr>
                <td>Users Online</td>
                <td><em>(Dynamic data pending)</em></td>
                <td>Currently active staff sessions.</td>
            </tr>
        </tbody>
    </table>
</div>

<?php
$viewContent = ob_get_clean();

// Now include the admin layout, which will use $viewContent and $pageTitle
// The $viewContentPath approach in the layout is an alternative, this is more direct for simple cases.
// To make this clean, the Controller's render method should handle layout wrapping.
// For now, this manual inclusion works.

// This is a bit of a hack due to the simple render method.
// Ideally, the render method in Controller.php would take $viewContent and inject it into the layout.
// Let's simulate that by setting a variable that the layout can conditionally include.
$viewContentPath = null; // This signals to the layout that content is already buffered or handled by the view itself.
                         // But for this to work with the current layout, the layout needs to be included *here*.

// The `render` method in `Controller.php` includes the view file, and that view file
// is what we are in right now. So, to use the layout, this file itself should output
// its content within the layout structure.
// The current `admin.php` layout tries to include `$viewContentPath`.
// Let's adjust the `Controller::render` method slightly or make this view include the layout.

// For now, the easiest is to assume the controller's render method will pass $pageTitle,
// and this file just outputs its content. The layout will then be "around" it if
// the render method is structured like: include layout_header; include view_file; include layout_footer;

// Given the current simple `Controller::render` method which just `include $viewFile; echo $content;`
// the $viewFile (this file) must produce the *entire* HTML or include the layout itself.

// Let's make this view assume it's being included by the layout.
// The layout will define the main structure, and this file provides $pageTitle (already set)
// and the content for the main area.

// The `admin.php` layout has a section:
// <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 admin-content">
//    <?php if (isset($viewContentPath) && file_exists($viewContentPath)) { include $viewContentPath; } ?>
// </main>
// So, the DashboardController's render('admin/dashboard/index', ...) will result in this file being included.
// This file needs to output the content that goes into that <main> block.
// The $pageTitle is already set.
echo $viewContent; // Output the buffered content.
?>
