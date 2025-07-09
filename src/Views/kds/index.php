<?php
// KDS Main View
// Expected variables: $pageTitle, $newOrders, $inProgressOrders, $csrf_token
use App\Core\Security;
use App\Models\Order; // For status constants

$pageTitle = $pageTitle ?? 'Kitchen Display';
$baseUrl = rtrim(getenv('APP_URL') ?: '', '/');

// This view includes the KDS layout
include ROOT_PATH . '/src/Views/layouts/kds.php'; // This includes the layout

// --- Helper function to render an order ticket ---
function renderOrderTicket(array $order, string $csrfToken, string $baseUrl): void {
    $statusClass = '';
    $statusText = '';
    $nextAction = null;
    $nextActionText = '';

    switch ($order['status']) {
        case Order::STATUS_NEW:
            $statusClass = 'bg-warning text-dark'; // Bootstrap yellow, ensure dark text for readability
            $statusText = 'New';
            $nextAction = Order::STATUS_IN_PROGRESS;
            $nextActionText = 'Start Cooking';
            break;
        case Order::STATUS_IN_PROGRESS:
            $statusClass = 'bg-info text-dark'; // Bootstrap blue, ensure dark text for readability
            $statusText = 'In Progress';
            $nextAction = Order::STATUS_READY_FOR_PICKUP;
            $nextActionText = 'Mark as Ready';
            break;
        // KDS might not show 'ready_for_pickup' for long, or it might be a separate column/view
        case Order::STATUS_READY_FOR_PICKUP:
            $statusClass = 'bg-success text-white'; // Bootstrap green
            $statusText = 'Ready for Pickup';
            // No further action from KDS typically
            break;
        default:
            $statusClass = 'bg-secondary text-white';
            $statusText = ucfirst(str_replace('_', ' ', $order['status']));
    }
?>
    <div class="col-md-6 col-lg-4 col-xl-3 kds-column">
        <div class="card kds-order-ticket <?php echo $statusClass; ?> h-100" id="order-ticket-<?php echo Security::escape($order['id']); ?>" data-order-id="<?php echo Security::escape($order['id']); ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Table <?php echo Security::escape($order['table_number']); ?></span>
                <small>#<?php echo Security::escape($order['id']); ?></small>
            </div>
            <div class="card-body">
                <p class="card-text small text-muted">Received: <?php echo Security::escape(date('h:i:s A', strtotime($order['created_at']))); ?></p>
                <?php if ($order['status'] === Order::STATUS_IN_PROGRESS): ?>
                     <p class="card-text small text-muted">Started: <?php echo Security::escape(date('h:i:s A', strtotime($order['updated_at']))); ?> (<?php echo Security::escape(round((time() - strtotime($order['updated_at'])) / 60 )) ?> mins ago)</p>
                <?php endif; ?>

                <?php if (!empty($order['items'])): ?>
                    <ul>
                        <?php foreach ($order['items'] as $item): ?>
                            <li>
                                <span class="item-quantity"><?php echo Security::escape((string)$item['quantity']); ?>x</span>
                                <?php echo Security::escape($item['menu_item_name']); ?>
                                <?php if (!empty($item['notes'])): ?>
                                    <div class="item-notes"><em>Notes: <?php echo Security::escape($item['notes']); ?></em></div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No items in this order.</p>
                <?php endif; ?>

                <?php if (!empty($order['notes'])): ?>
                    <hr>
                    <p class="order-overall-notes"><strong>Order Notes:</strong> <?php echo Security::escape($order['notes']); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($nextAction): ?>
            <div class="card-footer text-center kds-actions">
                 <button class="btn btn-primary update-order-status-btn w-100"
                        data-order-id="<?php echo Security::escape($order['id']); ?>"
                        data-new-status="<?php echo Security::escape($nextAction); ?>"
                        data-csrf-token="<?php echo Security::escape($csrfToken); ?>">
                    <?php echo Security::escape($nextActionText); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php
} // End of renderOrderTicket function
?>


<?php
// Content for the KDS page starts here
?>
<div class="container-fluid mt-3 kds-main-content" id="kds-main-content">
    <div class="row mb-4">
        <div class="col">
            <h2 class="display-6">New Orders <span class="badge bg-warning text-dark" id="new-orders-count"><?php echo count($newOrders); ?></span></h2>
            <hr>
            <div class="row" id="new-orders-column">
                <?php if (empty($newOrders)): ?>
                    <p class="text-muted ms-3">No new orders at the moment.</p>
                <?php else: ?>
                    <?php foreach ($newOrders as $order): ?>
                        <?php renderOrderTicket($order, $csrf_token, $baseUrl); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <h2 class="display-6">In Progress <span class="badge bg-info text-dark" id="in-progress-orders-count"><?php echo count($inProgressOrders); ?></span></h2>
            <hr>
            <div class="row" id="in-progress-orders-column">
                 <?php if (empty($inProgressOrders)): ?>
                    <p class="text-muted ms-3">No orders currently in progress.</p>
                <?php else: ?>
                    <?php foreach ($inProgressOrders as $order): ?>
                        <?php renderOrderTicket($order, $csrf_token, $baseUrl); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<?php
// JavaScript for KDS page (auto-refresh, status updates)
ob_start(); // Buffer for $footerScript in layout
?>
<script src="<?php echo $baseUrl; ?>/assets/js/kds.js"></script>
<script>
// This script block can remain here if kds.js is kept minimal,
// or all this logic can be moved to kds.js.
// For now, keeping it here for easier access to PHP variables like csrf_token and baseUrl.

document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?php echo Security::escape($csrf_token); ?>';
    const baseUrl = '<?php echo Security::escape($baseUrl); ?>';
    let autoRefreshInterval = 30000; // 30 seconds
    let refreshTimerId;

    const newOrdersColumn = document.getElementById('new-orders-column');
    const inProgressOrdersColumn = document.getElementById('in-progress-orders-column');
    const newOrdersCountEl = document.getElementById('new-orders-count');
    const inProgressOrdersCountEl = document.getElementById('in-progress-orders-count');
    const lastRefreshTimeEl = document.getElementById('last-refresh-time');
    const refreshStatusEl = document.getElementById('refresh-status');

    function updateLastRefreshTime() {
        if (lastRefreshTimeEl) {
            lastRefreshTimeEl.textContent = new Date().toLocaleTimeString();
        }
    }

    async function fetchKdsData() {
        try {
            // In a real app, this would be an API endpoint returning JSON
            // For now, we'll simulate by reloading the page content area or fetching HTML partial
            // This is a simplified approach; a proper API is better.
            // To avoid full page reload, we'd fetch JSON and re-render with JS.
            // For this prototype, we'll just refresh the relevant parts if possible, or full page.

            // Let's try fetching the full page and replacing content (simple but not ideal)
            const response = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' } // Optional: identify AJAX
            });
            if (!response.ok) {
                console.error('KDS refresh failed:', response.status);
                if (refreshStatusEl) refreshStatusEl.textContent = 'Error';
                return;
            }
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newMainContent = doc.getElementById('kds-main-content');
            const currentMainContent = document.getElementById('kds-main-content');

            if (newMainContent && currentMainContent) {
                currentMainContent.innerHTML = newMainContent.innerHTML;
                // Re-attach event listeners after updating DOM content
                attachEventListenersToTickets();
                updateCounts(); // Update counts based on new DOM
            } else {
                // Fallback to full page reload if parsing fails
                // window.location.reload();
                 console.warn("Could not find kds-main-content for partial update. Consider full reload or API.");
            }
            updateLastRefreshTime();
            if (refreshStatusEl) refreshStatusEl.textContent = 'enabled';

        } catch (error) {
            console.error('Error fetching KDS data:', error);
            if (refreshStatusEl) refreshStatusEl.textContent = 'Error';
        }
    }

    function updateCounts() {
        if (newOrdersCountEl) newOrdersCountEl.textContent = newOrdersColumn.querySelectorAll('.kds-order-ticket').length;
        if (inProgressOrdersCountEl) inProgressOrdersCountEl.textContent = inProgressOrdersColumn.querySelectorAll('.kds-order-ticket').length;
    }

    async function updateOrderStatus(orderId, newStatus) {
        const url = `${baseUrl}/kds/order/${orderId}/update-status`;
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status: newStatus, csrf_token: csrfToken })
            });
            const result = await response.json();

            if (result.success) {
                console.log(`Order ${orderId} status updated to ${newStatus}`);
                // Move ticket in UI or refresh data
                // For simplicity, trigger a data refresh after successful update
                await fetchKdsData();
            } else {
                alert('Error updating status: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Failed to update order status:', error);
            alert('Failed to send status update to server.');
        }
    }

    function attachEventListenersToTickets() {
        document.querySelectorAll('.update-order-status-btn').forEach(button => {
            // Remove old listener before adding new one to prevent duplicates if this function is called multiple times
            const newButton = button.cloneNode(true); // Clone to remove existing listeners
            button.parentNode.replaceChild(newButton, button);

            newButton.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const newStatus = this.dataset.newStatus;
                if (confirm(`Mark order #${orderId} as "${newStatus.replace('_', ' ')}"?`)) {
                    updateOrderStatus(orderId, newStatus);
                }
            });
        });
    }

    function startAutoRefresh() {
        if (refreshTimerId) clearInterval(refreshTimerId); // Clear existing timer
        refreshTimerId = setInterval(fetchKdsData, autoRefreshInterval);
        if (refreshStatusEl) refreshStatusEl.textContent = 'enabled';
        console.log('KDS auto-refresh started.');
    }

    function stopAutoRefresh() {
        clearInterval(refreshTimerId);
        if (refreshStatusEl) refreshStatusEl.textContent = 'disabled';
        console.log('KDS auto-refresh stopped.');
    }

    // Initial setup
    updateLastRefreshTime();
    attachEventListenersToTickets();
    startAutoRefresh();

    // Optional: Toggle refresh on/off (e.g. by clicking footer status)
    if (refreshStatusEl && refreshStatusEl.parentElement) {
        refreshStatusEl.parentElement.addEventListener('click', () => {
            if (refreshTimerId && refreshStatusEl.textContent === 'enabled') {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
                fetchKdsData(); // Refresh immediately when re-enabled
            }
        });
    }
});
</script>
<?php
$footerScript = ob_get_clean(); // Assign buffered JS to $footerScript in layout
?>

<?php
// End of KDS page content
?>
