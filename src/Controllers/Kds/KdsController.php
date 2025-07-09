<?php

declare(strict_types=1);

namespace App\Controllers\Kds;

use App\Core\Controller;
use App\Core\Security;
use App\Models\Order;
use App\Models\User; // For User roles

class KdsController extends Controller
{
    private Order $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->checkKdsAuth(); // Protect KDS actions
    }

    /**
     * Placeholder for KDS-specific authentication.
     * This should verify if the logged-in user has 'kitchen_staff' or 'admin' role.
     */
    private function checkKdsAuth(): void
    {
        Security::startSecureSession();
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            $_SESSION['error_message'] = 'Please log in to access the Kitchen Display System.';
            $this->redirect('/admin/login'); // Redirect to a generic login page
            exit;
        }

        // Allow admin to also access KDS for testing/oversight
        if (!in_array($_SESSION['user_role'], [User::ROLE_KITCHEN_STAFF, User::ROLE_ADMIN])) {
            $_SESSION['error_message'] = 'You do not have permission to access the KDS.';
            $this->redirect('/'); // Redirect to home page or a relevant error page
            exit;
        }
    }

    /**
     * Display the main KDS screen.
     * Shows new and in-progress orders.
     */
    public function index(): void
    {
        // Fetch orders that are 'new' or 'in_progress'
        // This could be combined into one query or fetched separately if display logic differs significantly
        $newOrders = $this->orderModel->getAllOrdersWithDetails(Order::STATUS_NEW, 'o.created_at ASC');
        $inProgressOrders = $this->orderModel->getAllOrdersWithDetails(Order::STATUS_IN_PROGRESS, 'o.updated_at ASC');

        $pageTitle = 'Kitchen Display System';
        // The actual view file (kds/index.php) will be created next.
        $this->render('kds/index', [
            'pageTitle' => $pageTitle,
            'newOrders' => $newOrders,
            'inProgressOrders' => $inProgressOrders,
            'csrf_token' => Security::getCsrfToken() // For status update actions
        ]);
    }

    /**
     * Update the status of an order from the KDS.
     * Expected to be an AJAX request.
     *
     * @param string $orderIdParam The ID of the order from the URL.
     */
    public function updateOrderStatus(string $orderIdParam): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method.'], 405);
            return;
        }

        $orderId = (int)$orderIdParam;
        $input = $this->getJsonInput(); // Expect 'status' in the JSON body

        if (!Security::verifyCsrfToken($input ?? [])) {
            $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!$csrfHeader || !hash_equals(Security::getCsrfToken(), $csrfHeader)) {
                $this->jsonResponse(['error' => 'CSRF token mismatch.'], 403);
                return;
            }
        }

        $newStatus = $input['status'] ?? null;

        if (!$newStatus || !$this->orderModel->isValidStatus($newStatus)) {
            $this->jsonResponse(['error' => 'Invalid or missing new status.'], 400);
            return;
        }

        // KDS can typically transition to 'in_progress' or 'ready_for_pickup'
        if (!in_array($newStatus, [Order::STATUS_IN_PROGRESS, Order::STATUS_READY_FOR_PICKUP])) {
            $this->jsonResponse(['error' => 'KDS cannot set this status: ' . $newStatus], 400);
            return;
        }

        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->jsonResponse(['error' => 'Order not found.'], 404);
            return;
        }

        // Basic state transition validation (e.g., can't go from 'ready' back to 'new' via KDS)
        if ($order['status'] === Order::STATUS_NEW && $newStatus === Order::STATUS_IN_PROGRESS) {
            // OK: New -> In Progress
        } elseif ($order['status'] === Order::STATUS_IN_PROGRESS && $newStatus === Order::STATUS_READY_FOR_PICKUP) {
            // OK: In Progress -> Ready for Pickup
        } else {
            // Invalid transition for KDS typical workflow
            $this->jsonResponse(['error' => "Invalid status transition from {$order['status']} to {$newStatus} via KDS."], 400);
            return;
        }

        if ($this->orderModel->updateStatus($orderId, $newStatus)) {
            // Optionally, fetch all relevant orders again to send back the updated KDS view state
            // For now, just success. Client-side will need to refresh or update UI.
            $this->jsonResponse([
                'success' => true,
                'message' => 'Order status updated to ' . $newStatus,
                'order_id' => $orderId,
                'new_status' => $newStatus
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update order status.'], 500);
        }
    }
}
