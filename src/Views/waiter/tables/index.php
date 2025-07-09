<?php
// Waiter Table Selection View
// Expected variables: $pageTitle, $tables (array of table objects/arrays), $statuses (array of status descriptions)
use App\Core\Security;

$pageTitle = $pageTitle ?? 'Select a Table'; // Set by TableController
$baseUrl = rtrim(getenv('APP_URL') ?: '', '/');

// This view includes the main waiter layout
include ROOT_PATH . '/src/Views/layouts/waiter.php';
?>

<?php
// Content for the table selection page starts here, after the layout is included
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><?php echo Security::escape($pageTitle); ?></h2>
        <!-- Optional: Add filter or sort options here -->
    </div>

    <?php if (empty($tables)): ?>
        <div class="alert alert-info" role="alert">
            No tables found. Please ensure tables are added in the admin panel.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($tables as $table): ?>
                <?php
                    $cardClass = 'bg-light'; // Default
                    $statusText = $statuses[$table['status']] ?? ucfirst($table['status']);
                    $actionUrl = $baseUrl . '/waiter/table/' . Security::escape($table['id']) . '/order';
                    $isDisabled = false;

                    switch ($table['status']) {
                        case 'available':
                            $cardClass = 'border-success shadow-sm'; // Green border for available
                            $statusBadge = 'bg-success';
                            break;
                        case 'occupied':
                            $cardClass = 'border-warning shadow-sm'; // Orange border for occupied
                            $statusBadge = 'bg-warning text-dark';
                            break;
                        case 'reserved':
                            $cardClass = 'border-secondary shadow-sm'; // Grey for reserved
                            $statusBadge = 'bg-secondary';
                            // $isDisabled = true; // Or allow viewing reservation details
                            break;
                        default:
                            $statusBadge = 'bg-light text-dark';
                    }
                ?>
                <div class="col">
                    <div class="card h-100 <?php echo $cardClass; ?>">
                        <div class="card-body text-center d-flex flex-column">
                            <h5 class="card-title">Table <?php echo Security::escape($table['table_number']); ?></h5>
                            <p class="card-text">
                                Capacity: <?php echo Security::escape((string)$table['capacity']); ?>
                            </p>
                            <p class="card-text">
                                Status: <span class="badge <?php echo $statusBadge; ?>"><?php echo Security::escape($statusText); ?></span>
                            </p>
                            <div class="mt-auto">
                                <?php if ($table['status'] === 'available'): ?>
                                    <a href="<?php echo $actionUrl; ?>" class="btn btn-primary w-100">Start Order</a>
                                <?php elseif ($table['status'] === 'occupied'): ?>
                                    <a href="<?php echo $actionUrl; ?>" class="btn btn-warning w-100">View/Edit Order</a>
                                <?php elseif ($table['status'] === 'reserved'): ?>
                                     <a href="#" class="btn btn-secondary w-100 disabled" aria-disabled="true">Reserved</a>
                                     <!-- Or link to reservation details / check-in -->
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// End of specific content for table selection page.
// The main layout (waiter.php) continues after this.
?>
