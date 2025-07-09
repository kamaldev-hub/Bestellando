<?php
// Waiter Order Screen View
// Expected variables:
// $pageTitle (from OrderController)
// $table (array: current table details)
// $categories (array: menu categories with their menu_items)
// $activeOrder (array|false: current active order for the table, includes items)
// $currentOrderItems (array: items in the current order, if any) - convenience variable
// $orderId (int|null: ID of the active order)
// $csrf_token (string: for forms)

use App\Core\Security;

$pageTitle = $pageTitle ?? 'Order Screen'; // Set by OrderController
$baseUrl = rtrim(getenv('APP_URL') ?: '', '/');

// This view includes the main waiter layout
include ROOT_PATH . '/src/Views/layouts/waiter.php';

// --- Additional CSS for this specific page (can be moved to a CSS file) ---
ob_start(); // Start buffering for $headContent in layout
?>
<style>
    .order-screen-wrapper {
        display: flex;
        flex-direction: row;
        gap: 1.5rem;
        /* max-height: calc(100vh - 120px); /* Adjust based on navbar/footer height */
        /* overflow-y: hidden; */
    }
    .menu-column {
        flex: 3; /* Takes more space */
        /* overflow-y: auto; */ /* Scrollable menu items */
        /* max-height: calc(100vh - 150px); */
        padding-bottom: 20px; /* Space for sticky submit button if not in sidebar */
    }
    .order-summary-column {
        flex: 2; /* Takes less space */
        background-color: #fff;
        padding: 1.5rem;
        border-radius: 0.375rem; /* Bootstrap's default border radius */
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
        position: sticky;
        top: 80px; /* Adjust based on navbar height */
        align-self: flex-start; /* Important for sticky positioning */
        max-height: calc(100vh - 100px); /* Max height before scrolling */
        overflow-y: auto;
    }
    .menu-category .card-header {
        cursor: pointer;
    }
    .menu-item-card {
        transition: transform 0.1s ease-in-out;
    }
    .menu-item-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
    }
    .order-item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #eee;
    }
    .order-item-row:last-child {
        border-bottom: none;
    }
    .order-item-details { flex-grow: 1; }
    .order-item-actions { white-space: nowrap; }
    .quantity-input { width: 60px; text-align: center; }

    /* Responsive adjustments */
    @media (max-width: 992px) { /* Tablets and below */
        .order-screen-wrapper {
            flex-direction: column;
        }
        .order-summary-column {
            position: relative; /* Becomes normal flow */
            top: auto;
            width: 100%;
            margin-top: 1.5rem;
            max-height: none; /* Allow full height scroll */
        }
    }
</style>
<?php
$headContent = ob_get_clean(); // Assign buffered styles to $headContent for layout
?>


<?php
// Content for the order screen page starts here
?>
<div class="container-fluid mt-4 order-screen-wrapper">

    <!-- Menu Column -->
    <div class="menu-column">
        <h3>Menu - Table <?php echo Security::escape($table['table_number']); ?></h3>
        <hr>
        <?php if (empty($categories)): ?>
            <div class="alert alert-warning">No menu categories or items found. Please set up the menu in the admin panel.</div>
        <?php else: ?>
            <div class="accordion" id="menuAccordion">
                <?php foreach ($categories as $index => $category): ?>
                    <div class="accordion-item menu-category mb-3">
                        <h2 class="accordion-header" id="heading-<?php echo Security::escape($category['id']); ?>">
                            <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo Security::escape($category['id']); ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo Security::escape($category['id']); ?>">
                                <?php echo Security::escape($category['name']); ?> (<?php echo count($category['menu_items'] ?? []); ?> items)
                            </button>
                        </h2>
                        <div id="collapse-<?php echo Security::escape($category['id']); ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo Security::escape($category['id']); ?>" data-bs-parent="#menuAccordion">
                            <div class="accordion-body">
                                <?php if (empty($category['menu_items'])): ?>
                                    <p>No items in this category.</p>
                                <?php else: ?>
                                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3">
                                        <?php foreach ($category['menu_items'] as $item): ?>
                                            <div class="col">
                                                <div class="card h-100 menu-item-card shadow-sm">
                                                    <?php if (!empty($item['image_url'])): ?>
                                                        <!-- <img src="<?php echo Security::escape($item['image_url']); ?>" class="card-img-top" alt="<?php echo Security::escape($item['name']); ?>" style="height: 150px; object-fit: cover;"> -->
                                                    <?php endif; ?>
                                                    <div class="card-body d-flex flex-column">
                                                        <h5 class="card-title"><?php echo Security::escape($item['name']); ?></h5>
                                                        <p class="card-text small text-muted flex-grow-1"><?php echo Security::escape($item['description'] ?? 'No description.'); ?></p>
                                                        <p class="card-text fw-bold fs-5 text-end mb-2">$<?php echo Security::escape(number_format((float)$item['price'], 2)); ?></p>
                                                        <button class="btn btn-sm btn-primary add-to-order-btn mt-auto"
                                                                data-item-id="<?php echo Security::escape($item['id']); ?>"
                                                                data-item-name="<?php echo Security::escape($item['name']); ?>"
                                                                data-item-price="<?php echo Security::escape($item['price']); ?>">
                                                            Add to Order
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Order Summary Column (Sticky Sidebar) -->
    <div class="order-summary-column">
        <h4>Current Order for Table <?php echo Security::escape($table['table_number']); ?></h4>
        <hr>
        <div id="current-order-items-list">
            <?php if (empty($currentOrderItems)): ?>
                <p id="empty-order-message" class="text-muted">No items added to the order yet.</p>
            <?php else: ?>
                <?php // This part will be dynamically updated by JavaScript primarily ?>
            <?php endif; ?>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Total:</h5>
            <h5 class="mb-0" id="order-total-amount">$0.00</h5>
        </div>

        <form id="submit-order-form" action="<?php echo $baseUrl; ?>/waiter/table/<?php echo Security::escape($table['id']); ?>/order" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo Security::escape($csrf_token); ?>">
            <div class="mb-3">
                <label for="order_notes" class="form-label">Order Notes (optional):</label>
                <textarea class="form-control" id="order_notes" name="order_notes" rows="2"><?php echo Security::escape($activeOrder['notes'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-success w-100 mb-2" id="submit-order-btn" <?php echo empty($currentOrderItems) ? 'disabled' : ''; ?>>
                Submit Order to Kitchen
            </button>
        </form>

        <?php if ($orderId && ($activeOrder['status'] ?? '') !== \App\Models\Order::STATUS_BILLING && ($activeOrder['status'] ?? '') !== \App\Models\Order::STATUS_COMPLETED ): ?>
        <button type="button" class="btn btn-info w-100" id="initiate-billing-btn" data-order-id="<?php echo Security::escape($orderId); ?>">
            Initiate Billing
        </button>
        <?php endif; ?>

        <!-- Hidden template for order item -->
        <div id="order-item-template" style="display: none;">
            <div class="order-item-row" data-order-item-id="{orderItemId}">
                <div class="order-item-details">
                    <strong class="item-name">{itemName}</strong>
                    <div class="item-notes-display text-muted small" style="display: none;">Notes: <span></span></div>
                </div>
                <div class="order-item-actions d-flex align-items-center">
                    <button class="btn btn-sm btn-outline-secondary decrease-qty-btn" type="button">&minus;</button>
                    <input type="number" class="form-control form-control-sm quantity-input item-quantity" value="{quantity}" min="1" data-item-id="{itemId}">
                    <button class="btn btn-sm btn-outline-secondary increase-qty-btn" type="button">&plus;</button>
                    <button class="btn btn-sm btn-outline-info ms-1 edit-notes-btn" type="button" title="Edit Notes">&#9998;</button> <!-- Pencil icon -->
                    <button class="btn btn-sm btn-danger ms-1 remove-item-btn" type="button" title="Remove Item">&times;</button>
                </div>
                <div class="col-12 item-price-summary text-end small">
                    <span>{quantity}</span> x $<span>{itemPrice}</span> = $<span class="item-total-price">{itemTotalPrice}</span>
                </div>
            </div>
        </div>
    </div> <!-- End Order Summary Column -->
</div> <!-- .order-screen-wrapper -->


<!-- Modal for Adding/Editing Item Notes -->
<div class="modal fade" id="itemNotesModal" tabindex="-1" aria-labelledby="itemNotesModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemNotesModalLabel">Add/Edit Notes for <span id="modalItemName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="modalOrderItemId" value="">
        <input type="hidden" id="modalMenuIdForNewItem" value=""> <!-- Used when adding a new item with notes -->
        <div class="mb-3">
          <label for="itemSpecificNotes" class="form-label">Notes:</label>
          <textarea class="form-control" id="itemSpecificNotes" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveItemNotesBtn">Save Notes</button>
      </div>
    </div>
  </div>
</div>


<?php
// --- JavaScript for this specific page ---
ob_start(); // Start buffering for $footerScript in layout
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currentOrderItemsList = document.getElementById('current-order-items-list');
    const orderTotalAmountEl = document.getElementById('order-total-amount');
    const emptyOrderMessage = document.getElementById('empty-order-message');
    const submitOrderBtn = document.getElementById('submit-order-btn');
    const itemNotesModal = new bootstrap.Modal(document.getElementById('itemNotesModal'));
    const modalItemNameEl = document.getElementById('modalItemName');
    const modalOrderItemIdInput = document.getElementById('modalOrderItemId');
    const modalMenuIdForNewItemInput = document.getElementById('modalMenuIdForNewItem');
    const itemSpecificNotesTextarea = document.getElementById('itemSpecificNotes');
    const initiateBillingBtn = document.getElementById('initiate-billing-btn');


    let currentOrder = <?php echo json_encode($activeOrder ?: ['items' => [], 'total_amount' => 0.00, 'notes' => '', 'id' => $orderId]); ?>;
    const csrfToken = '<?php echo Security::escape($csrf_token); ?>';
    const tableId = '<?php echo Security::escape($table['id']); ?>';
    const baseUrl = '<?php echo $baseUrl; ?>';

    function renderOrderSummary() {
        currentOrderItemsList.innerHTML = ''; // Clear current list
        let total = 0;

        if (currentOrder && currentOrder.items && currentOrder.items.length > 0) {
            if(emptyOrderMessage) emptyOrderMessage.style.display = 'none';
            currentOrder.items.forEach(item => {
                const template = document.getElementById('order-item-template').innerHTML;
                const itemPrice = parseFloat(item.price_at_order || item.itemPrice); // itemPrice for newly added before full sync
                const itemTotalPrice = itemPrice * item.quantity;
                total += itemTotalPrice;

                const html = template
                    .replace(/{orderItemId}/g, item.id)
                    .replace(/{itemId}/g, item.menu_item_id)
                    .replace(/{itemName}/g, Security.escapeHTML(item.menu_item_name || item.itemName)) // itemName for newly added
                    .replace(/{quantity}/g, item.quantity)
                    .replace(/{itemPrice}/g, itemPrice.toFixed(2))
                    .replace(/{itemTotalPrice}/g, itemTotalPrice.toFixed(2));

                const itemRow = document.createElement('div');
                itemRow.innerHTML = html;
                currentOrderItemsList.appendChild(itemRow.firstElementChild);

                // Display notes if they exist
                const notesDisplay = currentOrderItemsList.querySelector(`.order-item-row[data-order-item-id="${item.id}"] .item-notes-display`);
                const notesTextSpan = notesDisplay.querySelector('span');
                if (item.notes && item.notes.trim() !== '') {
                    notesTextSpan.textContent = Security.escapeHTML(item.notes);
                    notesDisplay.style.display = 'block';
                } else {
                    notesDisplay.style.display = 'none';
                }
            });
            if(submitOrderBtn) submitOrderBtn.disabled = false;
        } else {
            if(emptyOrderMessage) emptyOrderMessage.style.display = 'block';
            if(submitOrderBtn) submitOrderBtn.disabled = true;
        }
        if(orderTotalAmountEl) orderTotalAmountEl.textContent = '$' + (currentOrder.total_amount ? parseFloat(currentOrder.total_amount).toFixed(2) : total.toFixed(2));

        // Update overall order notes if present in currentOrder
        const orderNotesTextarea = document.getElementById('order_notes');
        if (orderNotesTextarea && currentOrder.notes) {
            orderNotesTextarea.value = currentOrder.notes;
        }

        // Update initiate billing button status
        if (initiateBillingBtn && currentOrder.id) {
            initiateBillingBtn.dataset.orderId = currentOrder.id;
            initiateBillingBtn.style.display = (currentOrder.items && currentOrder.items.length > 0 && currentOrder.status !== 'billing' && currentOrder.status !== 'completed') ? 'block' : 'none';
        } else if (initiateBillingBtn) {
            initiateBillingBtn.style.display = 'none';
        }
    }

    // Initial render
    renderOrderSummary();

    // --- Event Listeners ---

    // Add to Order buttons
    document.querySelectorAll('.add-to-order-btn').forEach(button => {
        button.addEventListener('click', function() {
            const menuItemId = this.dataset.itemId;
            // For adding new item, notes are handled via modal if needed, or added directly.
            // Let's simplify: add item, then edit notes. Or a modal to confirm quantity & add notes.
            // For now, direct add with quantity 1.
            modalOrderItemIdInput.value = ''; // Clear for new item
            modalMenuIdForNewItemInput.value = menuItemId;
            modalItemNameEl.textContent = this.dataset.itemName;
            itemSpecificNotesTextarea.value = ''; // Clear notes for new item
            // itemNotesModal.show(); // Option: show modal to add notes immediately
            // Or, directly add item and then allow editing notes:
            handleAddItem(menuItemId, 1, ''); // Add with quantity 1, no notes initially
        });
    });

    // Save Item Notes (from Modal)
    document.getElementById('saveItemNotesBtn').addEventListener('click', function() {
        const orderItemId = modalOrderItemIdInput.value;
        const notes = itemSpecificNotesTextarea.value;

        if (orderItemId) { // Editing notes for an existing order item
            const itemToUpdate = currentOrder.items.find(i => i.id == orderItemId);
            if (itemToUpdate) {
                 handleUpdateItem(orderItemId, itemToUpdate.quantity, notes);
            }
        } else { // Adding notes for a new item (not yet sent to server) - this flow needs refinement
            // This case is tricky if item not yet added.
            // Simplest: add item first, then edit notes.
            // Or, if 'add-to-order-btn' opens this modal first:
            const menuItemId = modalMenuIdForNewItemInput.value;
            if(menuItemId) {
                handleAddItem(menuItemId, 1, notes); // Assuming quantity 1 for now
            }
        }
        itemNotesModal.hide();
    });


    // Event delegation for dynamically added items
    currentOrderItemsList.addEventListener('click', function(event) {
        const target = event.target;
        const orderItemRow = target.closest('.order-item-row');
        if (!orderItemRow) return;

        const orderItemId = orderItemRow.dataset.orderItemId;
        const currentItem = currentOrder.items.find(i => i.id == orderItemId);
        if (!currentItem) return;

        if (target.classList.contains('increase-qty-btn')) {
            handleUpdateItem(orderItemId, currentItem.quantity + 1, currentItem.notes);
        } else if (target.classList.contains('decrease-qty-btn')) {
            if (currentItem.quantity > 1) {
                handleUpdateItem(orderItemId, currentItem.quantity - 1, currentItem.notes);
            } else {
                // Optionally confirm before removing if quantity becomes 0
                handleRemoveItem(orderItemId);
            }
        } else if (target.classList.contains('remove-item-btn')) {
            if (confirm('Are you sure you want to remove this item?')) {
                handleRemoveItem(orderItemId);
            }
        } else if (target.classList.contains('edit-notes-btn')) {
            modalOrderItemIdInput.value = orderItemId;
            modalMenuIdForNewItemInput.value = ''; // Not a new menu item
            modalItemNameEl.textContent = currentItem.menu_item_name || currentItem.itemName;
            itemSpecificNotesTextarea.value = currentItem.notes || '';
            itemNotesModal.show();
        }
    });

    currentOrderItemsList.addEventListener('change', function(event) {
        const target = event.target;
        if (target.classList.contains('item-quantity')) {
            const orderItemRow = target.closest('.order-item-row');
            if (!orderItemRow) return;

            const orderItemId = orderItemRow.dataset.orderItemId;
            const currentItem = currentOrder.items.find(i => i.id == orderItemId);
            if (!currentItem) return;

            let newQuantity = parseInt(target.value, 10);
            if (isNaN(newQuantity) || newQuantity < 1) {
                newQuantity = 1; // Reset to 1 if invalid
                target.value = newQuantity;
            }
            handleUpdateItem(orderItemId, newQuantity, currentItem.notes);
        }
    });


    // --- AJAX Helper Functions ---
    async function sendRequest(url, method = 'POST', body = null) {
        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        };
        try {
            const response = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : null });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Server error with no JSON response' }));
                console.error('API Error:', response.status, errorData);
                alert(`Error: ${errorData.error || response.statusText}`);
                return null;
            }
            return await response.json();
        } catch (error) {
            console.error('Fetch Error:', error);
            alert('Network error or server is unreachable.');
            return null;
        }
    }

    async function handleAddItem(menuItemId, quantity, notes) {
        const url = `${baseUrl}/waiter/order/${tableId}/add-item`; // Note: URL has tableId, not orderId for adding
        const data = await sendRequest(url, 'POST', { menu_item_id: menuItemId, quantity, notes, csrf_token: csrfToken });
        if (data && data.success) {
            currentOrder = data.order;
            renderOrderSummary();
        }
    }

    async function handleUpdateItem(orderItemId, quantity, notes) {
        const url = `${baseUrl}/waiter/order-item/${orderItemId}/update`;
        const data = await sendRequest(url, 'POST', { quantity, notes, csrf_token: csrfToken });
        if (data && data.success) {
            currentOrder = data.order;
            renderOrderSummary();
        } else if (data && data.error) { // Re-render to reflect original state if update failed
            renderOrderSummary();
        }
    }

    async function handleRemoveItem(orderItemId) {
        const url = `${baseUrl}/waiter/order-item/${orderItemId}/remove`;
        // For POST via form-like data, body might be different or handled by sendRequest if it adapts
        // Here we stick to JSON body for consistency with other AJAX calls
        const data = await sendRequest(url, 'POST', { csrf_token: csrfToken });
        if (data && data.success) {
            currentOrder = data.order;
            renderOrderSummary();
        }
    }

    if (initiateBillingBtn) {
        initiateBillingBtn.addEventListener('click', async function() {
            const orderIdToBill = this.dataset.orderId;
            if (!orderIdToBill) {
                alert('No active order to bill.');
                return;
            }
            if (!confirm('Are you sure you want to mark this order for billing?')) return;

            const url = `${baseUrl}/waiter/order/${orderIdToBill}/initiate-billing`;
            const data = await sendRequest(url, 'POST', { csrf_token: csrfToken });
            if (data && data.success) {
                alert(data.message || 'Order marked for billing.');
                // Update UI - e.g., change order status display, disable further edits
                // Fetch updated order state or reflect change locally
                currentOrder.status = 'billing'; // Example local update
                renderOrderSummary(); // Re-render to reflect status change
            }
        });
    }

    // Utility for escaping HTML in JS (very basic)
    const Security = {
        escapeHTML: function(str) {
            if (str === null || typeof str === 'undefined') return '';
            return String(str).replace(/[&<>"']/g, function (match) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[match];
            });
        }
    };

});
</script>
<?php
$footerScript = ob_get_clean(); // Assign buffered JS to $footerScript for layout
?>

<?php
// End of specific content for order screen page.
?>
