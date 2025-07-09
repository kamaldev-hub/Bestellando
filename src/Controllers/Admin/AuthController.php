<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Security;
use App\Core\Validator;
use App\Models\User;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * Show the admin login form.
     */
    public function showLoginForm(): void
    {
        // If already logged in as admin, redirect to dashboard
        if (self::isAdminLoggedIn()) {
            $this->redirect('/admin/dashboard');
            return;
        }
        // The actual view file will be created later in the plan.
        $this->render('admin/auth/login', ['csrf_token' => Security::getCsrfToken()]);
    }

    /**
     * Handle the admin login attempt.
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/login');
            return;
        }

        if (!Security::verifyCsrfToken($_POST)) {
            // CSRF token mismatch
            $_SESSION['error_message'] = 'Invalid request. Please try again.';
            $this->redirect('/admin/login');
            return;
        }

        $validator = new Validator();
        $validationResult = $validator->validate($_POST, [
            'username' => ['required', 'minLength:3'],
            'password' => ['required', 'minLength:6']
        ]);

        if (!$validationResult) {
            $_SESSION['form_errors'] = $validator->getErrors();
            $_SESSION['form_data'] = $_POST; // Preserve username
            $this->redirect('/admin/login');
            return;
        }

        $username = $_POST['username'];
        $password = $_POST['password'];

        $user = $this->userModel->verifyUserPassword($username, $password);

        if ($user && $user['role'] === User::ROLE_ADMIN) {
            // Login successful for admin
            Security::regenerateSessionId(); // Important for security
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];

            // Clear any previous error messages or form data
            unset($_SESSION['error_message'], $_SESSION['form_errors'], $_SESSION['form_data']);

            $this->redirect('/admin/dashboard');
        } else {
            // Login failed
            $_SESSION['error_message'] = 'Invalid username or password, or not an admin account.';
            $_SESSION['form_data'] = $_POST;
            $this->redirect('/admin/login');
        }
    }

    /**
     * Handle admin logout.
     */
    public function logout(): void
    {
        // Ensure this is a POST request to prevent CSRF via GET logout
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            // Or handle more gracefully, perhaps redirect to login with a message
            http_response_code(405); // Method Not Allowed
            echo "Logout must be performed via POST request.";
            exit;
        }

        // Could add CSRF check here too for added security if desired,
        // though impact of CSRF logout is less severe than other actions.
        // For simplicity, we'll omit CSRF on logout for now unless specifically requested.

        Security::startSecureSession();
        session_unset(); // Unset all session variables
        session_destroy(); // Destroy the session

        $this->redirect('/admin/login');
    }

    /**
     * Check if an admin user is currently logged in.
     * This is a helper that might be moved to a base admin controller or a middleware layer later.
     * @return bool
     */
    public static function isAdminLoggedIn(): bool
    {
        Security::startSecureSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === User::ROLE_ADMIN;
    }

    /**
     * A simple auth check method to be called at the beginning of protected controller actions.
     * Redirects to login if not authenticated as admin.
     * In a more complex app, this would be handled by middleware.
     */
    public static function checkAdminAuth(): void
    {
        if (!self::isAdminLoggedIn()) {
            $_SESSION['error_message'] = 'Please log in to access this page.';
            // Store the intended URL to redirect back after login
            // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: /admin/login'); // Use header directly to avoid issues with Controller context
            exit;
        }
    }
}
