# Bestellando - Restaurant Ordering System Prototype

## Project Overview

Bestellando is a prototype for a comprehensive restaurant ordering system. It aims to provide a seamless experience for waiters, kitchen staff, and managers. This system features a robust backend built with PHP, a MySQL database, and a modern, intuitive frontend powered by Bootstrap 5.

The primary objective is to deliver a zero-bug-tolerance system that is both functionally perfect and visually impressive, serving as a highly polished proof-of-concept.

## Core Features (User Personas)

*   **Waiter Interface:** Select table, view menu, add/edit/remove items (with notes), submit order to kitchen, modify active order, initiate billing.
*   **Kitchen Display System (KDS):** Real-time list of new/active orders, mark orders as 'In Progress' or 'Ready for Pickup'.
*   **Admin Panel:** Securely log into an admin area, perform full CRUD operations on menu categories and items, view and print a final bill (view/status change implemented, print is conceptual), mark a bill as 'Paid' (status change implemented).

## Technology Stack

*   **Backend:** PHP 8.1+ (Vanilla), MySQL 8.0+
*   **Frontend:** HTML, Bootstrap 5, Custom CSS, JavaScript (with some inline JS for interactivity)
*   **Development Environment:** Docker (Apache/PHP, MySQL)
*   **Testing:** PHPUnit (Unit tests for all Models)
*   **Dependency Management:** Composer

## Setup Instructions

### Docker-Based Setup (Recommended)

1.  **Clone the repository:**
    ```bash
    git clone <repository-url> # Replace <repository-url> with the actual URL
    cd bestellando # Or your chosen directory name
    ```
2.  **Create environment file:**
    Copy `.env.example` to `.env` and update the necessary variables (database credentials, `APP_URL`, `APP_PORT`).
    ```bash
    cp .env.example .env
    # Open .env and edit variables like DB_PASSWORD, DB_ROOT_PASSWORD, APP_PORT if needed
    ```
3.  **Build and run containers:**
    ```bash
    docker-compose up -d --build
    ```
    This will start the `web` (Apache/PHP) and `db` (MySQL) services. The web service will be accessible on the `APP_PORT` specified in your `.env` file (default 8080).
4.  **Install PHP dependencies:**
    ```bash
    docker-compose exec web composer install --no-interaction --prefer-dist --optimize-autoloader
    ```
5.  **Initialize database:**
    The `schema.sql` file contains the database structure and sample data.
    You can import it into your MySQL database running in the `db` container:
    ```bash
    docker-compose exec -T db mysql -u${DB_USER} -p${DB_PASSWORD} ${DB_DATABASE} < schema.sql
    ```
    (Replace `${DB_USER}`, `${DB_PASSWORD}`, `${DB_DATABASE}` with values from your `.env` file if they are different from the command's context. Or ensure your shell substitutes them.)
    Alternatively, connect using a MySQL client (e.g., DBeaver, TablePlus, phpMyAdmin via another container) to host `127.0.0.1` and port specified by `DB_PORT` (default 3306 from host) and run the `schema.sql` script.

6.  **Access the application:**
    *   Main Entry / Waiter Interface (Table Select): `http://localhost:YOUR_APP_PORT/` (e.g., `http://localhost:8080/`)
    *   Kitchen Display System: `http://localhost:YOUR_APP_PORT/kds`
    *   Admin Panel Login: `http://localhost:YOUR_APP_PORT/admin/login`
    *(Note: The root path `/` currently serves as a welcome page with links. The actual waiter table selection is at `/waiter` as per current routes, but the welcome page links to it.)*

### Manual Setup (XAMPP/MAMP)

1.  **Ensure prerequisites:**
    *   PHP 8.1+ (with `pdo_mysql`, `intl`, `gd`, `zip` extensions)
    *   MySQL 8.0+ (or MariaDB equivalent)
    *   Apache (or Nginx with appropriate configuration for front controller)
    *   Composer

2.  **Clone the repository:**
    Place the project files in your web server's document root (e.g., `htdocs` for XAMPP).

3.  **Install PHP dependencies:**
    Navigate to the project root in your terminal and run:
    ```bash
    composer install --no-interaction --prefer-dist --optimize-autoloader
    ```

4.  **Create database:**
    Create a new MySQL database (e.g., `bestellando_db`) with `utf8mb4_unicode_ci` collation.

5.  **Import schema:**
    Import `schema.sql` into your newly created database using a tool like phpMyAdmin or the MySQL command line.

6.  **Configure environment:**
    Create a `.env` file in the project root by copying `.env.example`. Update the database credentials (`DB_HOST=localhost`, `DB_NAME`, `DB_USER`, `DB_PASS`) and `APP_URL` (e.g., `http://localhost/bestellando_project_folder`). The application relies on these environment variables; PHP's `getenv()` or `$_ENV` is used. For manual setups without Docker, ensure your web server/PHP can access these (e.g. through Apache's `SetEnv` or by using a library like `vlucas/phpdotenv` - currently not a composer dependency, but `public/index.php` has commented-out example code).

7.  **Web server configuration:**
    Ensure your web server's document root is pointing to the `public/` directory of the project. For Apache, you'll need `mod_rewrite` enabled and an `.htaccess` file in the `public/` directory to route all requests to `index.php`. A sample `.htaccess` for `public/.htaccess` would be:
    ```apacheconf
    RewriteEngine On

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)/$ /$1 [L,R=301]

    # Handle Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
    ```
    *(Ensure `AllowOverride All` is set for the project directory in your Apache virtual host configuration.)*

8.  **Access the application:**
    Open your browser and navigate to the local URL configured for the project (e.g., `http://localhost/bestellando_project_folder/`).

## Running the Test Suite

1.  **Ensure setup is complete** (Docker or Manual).
2.  **Configure Test Database:** The `phpunit.xml.dist` file is configured to use a separate test database (e.g., `bestellando_test_db`). Ensure this database exists and the credentials in `phpunit.xml.dist` (or a custom `phpunit.xml`) are correct and have permissions to create/drop tables (as `schema.sql` is run by `TestCase.php`).
3.  **Run tests:**
    *   **Docker:**
        ```bash
        docker-compose exec web ./vendor/bin/phpunit
        ```
    *   **Manual:** From the project root:
        ```bash
        ./vendor/bin/phpunit
        ```
4.  **Coverage Report:** HTML coverage reports are generated in `tests/coverage/html/index.html`. Text summary in `tests/coverage/coverage.txt`.

## Architecture and Design Choices

*   **Front Controller:** `public/index.php` is the single entry point. It initializes the autoloader, environment variables (basic support), session, and router.
*   **Routing:** `App\Core\Router` handles mapping URLs to controller actions. Routes are defined in `config/routes.php`. It supports basic path parameters (e.g., `/users/{id}`).
*   **MVC-like Structure:**
    *   **Models (`src/Models`):** Handle data logic and database interaction. Extend `App\Core\Model` which provides basic CRUD. Uses PDO with prepared statements.
    *   **Views (`src/Views`):** PHP files for presentation. Bootstrap 5 is used for UI, primarily via CDN. Views are organized by feature/persona (admin, waiter, kds, layouts). A simple `render` method in the base `Controller` is used. Layouts are included by specific view files.
    *   **Controllers (`src/Controllers`):** Handle user input, interact with models, and select views. Extend `App\Core\Controller`. Organized by persona (Admin, Waiter, Kds).
*   **Core Components (`src/Core`):**
    *   `Database.php`: Singleton PDO database connection manager.
    *   `Security.php`: Handles CSRF tokens, password hashing, basic session security.
    *   `Validator.php`: Basic input validation rules.
*   **Dependency Management:** Composer for PHPUnit. No other external libraries are used in the core application logic to keep it vanilla PHP.
*   **Database:** MySQL. Schema and sample data in `schema.sql`.
*   **Security:**
    *   PDO Prepared Statements against SQL injection.
    *   `htmlspecialchars` for output escaping (XSS prevention).
    *   CSRF token protection on forms (implemented in `Security.php` and used in views/controllers).
    *   Password hashing using `password_hash` (Argon2ID preferred).
    *   Basic secure session settings.
    *   Authorization is handled by checks within controllers (e.g., `AuthController::checkAdminAuth()`).
*   **Styling:** Bootstrap 5 via CDN. Custom styles in `assets/css/style.css`. Specific interface needs (e.g., KDS dark theme) are handled in respective layouts/views.
*   **JavaScript:** Vanilla JavaScript. Some interactivity logic (e.g., Waiter order screen, KDS updates) is embedded directly in the view files for simplicity and access to PHP-generated data, with placeholder global JS files in `assets/js/`.
*   **Error Handling:** Basic error display control in `public/index.php` based on `APP_DEBUG`. Critical errors like DB connection issues are caught. Router handles 404/500 errors at a basic level.
*   **Testing:** PHPUnit. Unit tests for all Model classes are provided, aiming for good coverage of data logic. A base `TestCase` handles test database setup (running `schema.sql`) and transaction management for test isolation.

## Screenshots

*(Screenshots will be added by the user once the UI is visually rendered and reviewed)*

*   Waiter Interface (Table Selection, Order Screen)
*   Kitchen Display System
*   Admin Panel (Login, Dashboard, CRUD forms)

## URLs

A `PlaceholderHomeController` is currently mapped to `/` to provide easy navigation to the main interfaces.

*   **Welcome/Navigation Page:** `/`
*   **Waiter Table Selection:** `/waiter`
*   **Waiter Order Screen Example:** `/waiter/table/{tableId}/order`
*   **Kitchen Display System:** `/kds`
*   **Admin Login:** `/admin/login`
*   **Admin Dashboard:** `/admin/dashboard`
*   **Admin Categories:** `/admin/categories`
*   **Admin Menu Items:** `/admin/menu-items`
*   **Admin Orders:** `/admin/orders`

---
*This README is a living document and will be updated throughout the project lifecycle.*
