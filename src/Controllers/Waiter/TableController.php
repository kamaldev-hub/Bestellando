<?php

declare(strict_types=1);

namespace App\Controllers\Waiter;

use App\Core\Controller;
use App\Models\Table;
use App\Models\User; // Assuming Waiter is a User role
use App\Controllers\Admin\AuthController; // For auth check (placeholder for waiter auth)

class TableController extends Controller
{
    private Table $tableModel;

    public function __construct()
    {
        parent::__construct();
        $this->tableModel = new Table();
        // TODO: Implement specific waiter authentication check
        // For now, reusing Admin Auth check as a placeholder, this needs refinement
        // A waiter should have 'waiter' role, not 'admin'
        // AuthController::checkAdminAuth(); // This is incorrect for waiter
        $this->checkWaiterAuth();
    }

    /**
     * Placeholder for waiter-specific authentication.
     * This should verify if the logged-in user has the 'waiter' role.
     */
    private function checkWaiterAuth(): void
    {
        // This is a simplified check. A proper RBAC or middleware is better.
        Security::startSecureSession(); // Ensure session is started
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            $_SESSION['error_message'] = 'Please log in to access the waiter interface.';
            $this->redirect('/admin/login'); // Redirect to a generic login or specific waiter login
            exit;
        }

        if ($_SESSION['user_role'] !== User::ROLE_WAITER && $_SESSION['user_role'] !== User::ROLE_ADMIN) {
             // Allow admin to also access waiter interface for testing/oversight
            $_SESSION['error_message'] = 'You do not have permission to access the waiter interface.';
            $this->redirect('/'); // Redirect to home page or a relevant error page
            exit;
        }
    }


    /**
     * Display the table selection screen for the waiter.
     */
    public function index(): void
    {
        $tables = $this->tableModel->getAllTables(); // Get all tables regardless of status for display
                                                     // UI can show status visually

        $pageTitle = 'Select a Table';
        // The actual view file will be created later in the plan.
        $this->render('waiter/tables/index', [
            'pageTitle' => $pageTitle,
            'tables' => $tables,
            'statuses' => Table::getAvailableStatuses() // Pass statuses for potential filtering/display
        ]);
    }
}
