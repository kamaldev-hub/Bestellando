<?php

declare(strict_types=1);

namespace App\Controllers\Waiter;

use App\Core\Controller;
use App\Core\Security;
use App\Core\Validator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\User; // For User roles

class OrderController extends Controller
{
    private Order $orderModel;
    private OrderItem $orderItemModel;
    private Table $tableModel;
    private MenuItem $menuItemModel;
    private Category $categoryModel;
    private ?int $currentWaiterId;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->tableModel = new Table();
        $this->menuItemModel = new MenuItem();
        $this->categoryModel = new Category();

        $this->checkWaiterAuth(); // Ensure waiter is authenticated
        $this->currentWaiterId = (int) ($_SESSION['user_id'] ?? null);
    }

    private function checkWaiterAuth(): void
    {
        Security::startSecureSession();
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            $_SESSION['error_message'] = 'Please log in to access the waiter interface.';
            $this->redirect('/admin/login'); // Or specific waiter login
            exit;
        }
        // Allow admin to also access for testing/oversight
        if ($_SESSION['user_role'] !== User::ROLE_WAITER && $_SESSION['user_role'] !== User::ROLE_ADMIN) {
            $_SESSION['error_message'] = 'You do not have permission to access this page.';
            $this->redirect('/');
            exit;
        }
    }

    /**
     * Display the main order screen for a specific table.
     * This includes the menu and the current order summary.
     *
     * @param string $tableIdParam The ID of the table from the URL.
     */
    public function showOrderScreen(string $tableIdParam): void
    {
        $tableId = (int)$tableIdParam;
        $table = $this->tableModel->find($tableId);

        if (!$table) {
            $_SESSION['error_message'] = "Table not found.";
            $this->redirect('/waiter'); // Redirect to table selection
            return;
        }

        // Try to find an active order for this table
        $activeOrder = $this->orderModel->getActiveOrderByTable($tableId);
        $orderId = null;
        $currentOrderItems = [];

        if ($activeOrder) {
            $orderId = (int)$activeOrder['id'];
            $currentOrderItems = $activeOrder['items'] ?? [];
        }

        $categories = $this->categoryModel->findAllWithMenuItems(); // Assumes method in CategoryModel

        $pageTitle = "Order for Table " . $this->sanitize($table['table_number']);
        $this->render('waiter/order/index', [
            'pageTitle' => $pageTitle,
            'table' => $table,
            'categories' => $categories, // Menu items grouped by category
            'activeOrder' => $activeOrder, // Contains order details and items
            'currentOrderItems' => $currentOrderItems, // Extracted for easier access in view
            'orderId' => $orderId,
            'csrf_token' => Security::getCsrfToken()
        ]);
    }

    /**
     * Handles adding an item to the current order (or creating a new order if none exists).
     * This is expected to be an AJAX request from the order screen.
     *
     * @param string $tableIdParam
     */
    public function addItemToOrder(string $tableIdParam): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method.'], 405);
            return;
        }

        $tableId = (int)$tableIdParam;
        $input = $this->getJsonInput();

        if (!Security::verifyCsrfToken($input ?? [])) {
             // Allow CSRF from header for AJAX if input is empty (token in header)
            $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!$csrfHeader || !hash_equals(Security::getCsrfToken(), $csrfHeader)) {
                $this->jsonResponse(['error' => 'CSRF token mismatch.'], 403);
                return;
            }
        }


        $validator = new Validator();
        if (!$validator->validate($input, [
            'menu_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'] // Assuming min rule for integer quantity
        ])) {
            $this->jsonResponse(['error' => 'Validation failed.', 'errors' => $validator->getErrors()], 400);
            return;
        }

        $menuItemId = (int)$input['menu_item_id'];
        $quantity = (int)$input['quantity'];

        $menuItem = $this->menuItemModel->find($menuItemId);
        if (!$menuItem || !$menuItem['is_available']) {
            $this->jsonResponse(['error' => 'Menu item not found or unavailable.'], 404);
            return;
        }

        // Find or create order
        $order = $this->orderModel->getActiveOrderByTable($tableId);
        $orderId = $order ? (int)$order['id'] : null;

        if (!$orderId) {
            // Create new order if none active for the table
            $newOrderId = $this->orderModel->createOrder($tableId, $this->currentWaiterId, Order::STATUS_NEW);
            if (!$newOrderId) {
                $this->jsonResponse(['error' => 'Failed to create new order.'], 500);
                return;
            }
            $orderId = (int)$newOrderId;
            // Change table status to occupied if it was available
            $table = $this->tableModel->find($tableId);
            if ($table && $table['status'] === Table::STATUS_AVAILABLE) {
                $this->tableModel->updateStatus($tableId, Table::STATUS_OCCUPIED);
            }
        }

        // Check if item already exists in order to update quantity, or add as new
        $existingItem = $this->orderItemModel->findExistingItemInOrder($orderId, $menuItemId);
        $notes = $input['notes'] ?? null;

        if ($existingItem) {
            $newQuantity = $existingItem['quantity'] + $quantity;
            // Combine notes if necessary or overwrite. For now, let's assume new notes overwrite if provided.
            $newNotes = $notes ?? $existingItem['notes'];
            if (!$this->orderItemModel->updateItem((int)$existingItem['id'], $newQuantity, $newNotes)) {
                 $this->jsonResponse(['error' => 'Failed to update item quantity.'], 500);
                return;
            }
        } else {
            if (!$this->orderItemModel->addItem($orderId, $menuItemId, $quantity, (float)$menuItem['price'], $notes)) {
                $this->jsonResponse(['error' => 'Failed to add item to order.'], 500);
                return;
            }
        }

        $this->orderModel->updateTotalAmount($orderId);
        $updatedOrder = $this->orderModel->findWithDetails($orderId);

        $this->jsonResponse([
            'success' => true,
            'message' => 'Item added/updated successfully.',
            'order' => $updatedOrder
        ]);
    }

    /**
     * Update quantity or notes of an existing item in an order.
     * Expected to be an AJAX request.
     * @param string $orderItemIdParam
     */
    public function updateOrderItem(string $orderItemIdParam): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method.'], 405);
            return;
        }

        $orderItemId = (int)$orderItemIdParam;
        $input = $this->getJsonInput(); // Expect 'quantity' and optionally 'notes'

        if (!Security::verifyCsrfToken($input ?? [])) {
            $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!$csrfHeader || !hash_equals(Security::getCsrfToken(), $csrfHeader)) {
                $this->jsonResponse(['error' => 'CSRF token mismatch.'], 403);
                return;
            }
        }

        $validator = new Validator();
        // Quantity must be at least 1. To remove, use removeOrderItem.
        if (!$validator->validate($input, [
            'quantity' => ['required', 'integer', 'min:1'],
            // 'notes' is optional
        ])) {
            $this->jsonResponse(['error' => 'Validation failed.', 'errors' => $validator->getErrors()], 400);
            return;
        }

        $quantity = (int)$input['quantity'];
        $notes = $input['notes'] ?? null; // If notes are not sent, don't update them (or pass existing notes)

        $orderItem = $this->orderItemModel->find($orderItemId);
        if (!$orderItem) {
            $this->jsonResponse(['error' => 'Order item not found.'], 404);
            return;
        }

        // If notes are not part of the input, keep existing notes.
        // If input['notes'] is explicitly null or an empty string, it will update/clear notes.
        $finalNotes = array_key_exists('notes', $input) ? $input['notes'] : $orderItem['notes'];

        if (!$this->orderItemModel->updateItem($orderItemId, $quantity, $finalNotes)) {
            $this->jsonResponse(['error' => 'Failed to update order item.'], 500);
            return;
        }

        $orderId = (int)$orderItem['order_id'];
        $this->orderModel->updateTotalAmount($orderId);
        $updatedOrder = $this->orderModel->findWithDetails($orderId);

        $this->jsonResponse([
            'success' => true,
            'message' => 'Order item updated.',
            'order' => $updatedOrder
        ]);
    }

    /**
     * Remove an item from an order.
     * Expected to be an AJAX request.
     * @param string $orderItemIdParam
     */
    public function removeOrderItem(string $orderItemIdParam): void
    {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Should ideally be DELETE, but forms often use POST
            $this->jsonResponse(['error' => 'Invalid request method.'], 405);
            return;
        }
        $orderItemId = (int)$orderItemIdParam;

        // CSRF check for POST (could come from form data or JSON body)
        $input = $_POST ?: $this->getJsonInput(); // Prioritize POST form data for simplicity
        if (!Security::verifyCsrfToken($input ?? [])) {
            $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!$csrfHeader || !hash_equals(Security::getCsrfToken(), $csrfHeader)) {
                $this->jsonResponse(['error' => 'CSRF token mismatch.'], 403);
                return;
            }
        }

        $orderItem = $this->orderItemModel->find($orderItemId);
        if (!$orderItem) {
            $this->jsonResponse(['error' => 'Order item not found.'], 404);
            return;
        }

        if (!$this->orderItemModel->deleteItem($orderItemId)) {
            $this->jsonResponse(['error' => 'Failed to remove order item.'], 500);
            return;
        }

        $orderId = (int)$orderItem['order_id'];
        $this->orderModel->updateTotalAmount($orderId);
        $updatedOrder = $this->orderModel->findWithDetails($orderId);

        // If order becomes empty, consider changing its status or deleting it?
        // For now, an empty order remains until explicitly submitted or cancelled.

        $this->jsonResponse([
            'success' => true,
            'message' => 'Order item removed.',
            'order' => $updatedOrder
        ]);
    }


    /**
     * Submits the current order to the kitchen.
     * Changes order status (e.g., to 'new' or confirms it if already 'new').
     * @param string $tableIdParam (or orderIdParam if order is already created)
     */
    public function submitOrder(string $tableIdParam): void // Or orderIdParam
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithMessage('/waiter/table/' . $tableIdParam . '/order', 'Invalid request method.', 'error');
            return;
        }

        $tableId = (int)$tableIdParam;
        // CSRF check for POST form submission
        if (!Security::verifyCsrfToken($_POST)) {
            $this->redirectWithMessage('/waiter/table/' . $tableIdParam . '/order', 'Invalid request. Please try again.', 'error');
            return;
        }

        $order = $this->orderModel->getActiveOrderByTable($tableId);
        if (!$order || empty($order['items'])) {
            $this->redirectWithMessage('/waiter/table/' . $tableIdParam . '/order', 'Cannot submit an empty order.', 'warning');
            return;
        }

        $orderId = (int)$order['id'];
        // Update order notes if provided
        if (isset($_POST['order_notes'])) {
            $this->orderModel->update($orderId, ['notes' => $_POST['order_notes']]);
        }

        // Change status to 'new' (or confirm if already new and just adding items)
        // KDS will pick up 'new' orders.
        if ($this->orderModel->updateStatus($orderId, Order::STATUS_NEW)) {
            // Also update table status to 'occupied' if it isn't already
            $table = $this->tableModel->find($tableId);
            if ($table && $table['status'] === Table::STATUS_AVAILABLE) {
                $this->tableModel->updateStatus($tableId, Table::STATUS_OCCUPIED);
            }
            $this->redirectWithMessage('/waiter', 'Order submitted successfully for Table ' . $this->sanitize($table['table_number'] ?? $tableId) . '!', 'success');
        } else {
            $this->redirectWithMessage('/waiter/table/' . $tableIdParam . '/order', 'Failed to submit order. Please try again.', 'error');
        }
    }

    /**
     * Marks an order for billing.
     * @param string $orderIdParam
     */
    public function initiateBilling(string $orderIdParam): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method.'], 405); // Or redirect
            return;
        }
        $orderId = (int)$orderIdParam;

        // CSRF check for POST form submission or AJAX
        $input = $_POST ?: $this->getJsonInput();
        if (!Security::verifyCsrfToken($input ?? [])) {
             $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!$csrfHeader || !hash_equals(Security::getCsrfToken(), $csrfHeader)) {
                $this->jsonResponse(['error' => 'CSRF token mismatch.'], 403); // Or redirect
                return;
            }
        }

        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->jsonResponse(['error' => 'Order not found.'], 404); // Or redirect
            return;
        }

        // Check if order is in a state that allows billing (e.g., not new, not cancelled)
        if (!in_array($order['status'], [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_IN_PROGRESS, Order::STATUS_NEW /* maybe not new */])) {
             $this->jsonResponse(['error' => 'Order cannot be billed at this stage. Status: ' . $order['status']], 400);
            return;
        }

        if ($this->orderModel->updateStatus($orderId, Order::STATUS_BILLING)) {
            // In a full UI, this might redirect to a bill preview page or just confirm.
            // For AJAX, return success.
            $this->jsonResponse(['success' => true, 'message' => 'Order marked for billing.']);
        } else {
            $this->jsonResponse(['error' => 'Failed to mark order for billing.'], 500);
        }
    }

    /**
     * Helper to redirect with a flash message.
     */
    private function redirectWithMessage(string $url, string $message, string $type = 'info'): void
    {
        $_SESSION['flash_message'] = [
            'type' => $type, // 'success', 'error', 'warning', 'info'
            'text' => $message
        ];
        $this->redirect($url);
    }
}
