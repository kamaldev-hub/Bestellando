<?php

declare(strict_types=1);

namespace App\Core;

class Security
{
    private const CSRF_TOKEN_NAME = 'csrf_token'; // Can be made configurable via .env

    /**
     * Starts a secure session if not already started.
     */
    public static function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // More secure session settings
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1'); // Ensure this is true if using HTTPS
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1'); // Prevents session fixation via URL

            // Consider session name from .env
            $sessionName = $_ENV['SESSION_NAME'] ?? getenv('SESSION_NAME') ?: 'BESTELLANDO_SESS';
            session_name($sessionName);

            session_start();
        }
    }

    /**
     * Generates or retrieves a CSRF token.
     *
     * @return string The CSRF token.
     */
    public static function getCsrfToken(): string
    {
        self::startSecureSession();
        if (empty($_SESSION[self::CSRF_TOKEN_NAME])) {
            $_SESSION[self::CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CSRF_TOKEN_NAME];
    }

    /**
     * Validates a CSRF token from POST data.
     *
     * @param array $postData Typically $_POST.
     * @return bool True if valid, false otherwise.
     */
    public static function verifyCsrfToken(array $postData): bool
    {
        self::startSecureSession();
        $tokenName = self::CSRF_TOKEN_NAME;
        if (!isset($postData[$tokenName]) || !isset($_SESSION[$tokenName])) {
            return false;
        }
        return hash_equals($_SESSION[$tokenName], $postData[$tokenName]);
    }

    /**
     * Generates a hidden input field for CSRF token.
     *
     * @return string HTML input field.
     */
    public static function csrfInput(): string
    {
        return '<input type="hidden" name="' . self::CSRF_TOKEN_NAME . '" value="' . self::getCsrfToken() . '">';
    }

    /**
     * Escapes output to prevent XSS.
     * This is a basic wrapper. Consider context-aware escaping for complex scenarios.
     *
     * @param string|null $data The string to escape.
     * @return string The escaped string.
     */
    public static function escape(?string $data): string
    {
        return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Hashes a password using PHP's password_hash function.
     *
     * @param string $password The password to hash.
     * @return string The hashed password.
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID); // Or PASSWORD_DEFAULT
    }

    /**
     * Verifies a password against a hash.
     *
     * @param string $password The password to verify.
     * @param string $hash The hash to verify against.
     * @return bool True if the password matches the hash, false otherwise.
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Regenerates session ID to prevent session fixation.
     * Call this after login or privilege level change.
     */
    public static function regenerateSessionId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
