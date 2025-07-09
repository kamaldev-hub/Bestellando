<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Controllers\Admin\AuthController; // For auth check

class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        AuthController::checkAdminAuth(); // Protect all actions in this controller
    }

    /**
     * Show the admin dashboard.
     */
    public function index(): void
    {
        $username = $_SESSION['username'] ?? 'Admin';
        // The actual view file will be created later in the plan.
        $this->render('admin/dashboard/index', [
            'pageTitle' => 'Admin Dashboard',
            'username' => $this->sanitize($username)
        ]);
    }
}
