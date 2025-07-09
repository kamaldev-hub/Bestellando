<?php
// Admin Login View
// Expected variables: $csrf_token (passed from AuthController::showLoginForm)
// Session variables for errors/data: $_SESSION['error_message'], $_SESSION['form_errors'], $_SESSION['form_data']

use App\Core\Security;

$pageTitle = "Admin Login"; // Used by the layout if this view were to include a full layout.
                          // For this standalone login page, we might not use the full admin layout.

// Let's make this login page standalone, not using the full admin layout with sidebar etc.
$baseUrl = rtrim(getenv('APP_URL') ?: '', '/');

// Get errors and form data from session if they exist
$errorMessage = $_SESSION['error_message'] ?? null;
$formErrors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];

// Clear them from session after retrieving
unset($_SESSION['error_message'], $_SESSION['form_errors'], $_SESSION['form_data']);

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Security::escape($pageTitle); ?> - Bestellando</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5;
        }
        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
</head>
<body>

<main class="form-signin text-center">
    <form action="<?php echo $baseUrl; ?>/admin/login" method="POST">
        <?php echo Security::csrfInput(); // Use the static method from Security class ?>

        <!-- <img class="mb-4" src="/docs/5.3/assets/brand/bootstrap-logo.svg" alt="" width="72" height="57"> -->
        <h1 class="h3 mb-3 fw-normal">Admin Panel Login</h1>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo Security::escape($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="form-floating">
            <input type="text" class="form-control <?php echo isset($formErrors['username']) ? 'is-invalid' : ''; ?>"
                   id="username" name="username" placeholder="Username"
                   value="<?php echo Security::escape($formData['username'] ?? ''); ?>" required autofocus>
            <label for="username">Username</label>
            <?php if (isset($formErrors['username'])): ?>
                <div class="invalid-feedback">
                    <?php foreach ($formErrors['username'] as $error): echo Security::escape($error) . '<br>'; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-floating">
            <input type="password" class="form-control <?php echo isset($formErrors['password']) ? 'is-invalid' : ''; ?>"
                   id="password" name="password" placeholder="Password" required>
            <label for="password">Password</label>
            <?php if (isset($formErrors['password'])): ?>
                <div class="invalid-feedback">
                    <?php foreach ($formErrors['password'] as $error): echo Security::escape($error) . '<br>'; endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Optional: Remember me
        <div class="checkbox mb-3">
            <label>
                <input type="checkbox" value="remember-me"> Remember me
            </label>
        </div>
        -->
        <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
        <p class="mt-5 mb-3 text-muted">&copy; Bestellando <?php echo date('Y'); ?></p>
    </form>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
