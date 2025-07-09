<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Security;
use PDO;

class User extends Model
{
    protected string $table = 'users';

    // User roles - could be stored in a config or a dedicated class
    public const ROLE_ADMIN = 'admin';
    public const ROLE_WAITER = 'waiter';
    public const ROLE_KITCHEN_STAFF = 'kitchen_staff';

    /**
     * Create a new user.
     *
     * @param string $username
     * @param string $password Plain text password
     * @param string $role User role (e.g., 'admin', 'waiter')
     * @return string|false The ID of the newly created user, or false on failure.
     */
    public function createUser(string $username, string $password, string $role): string|false
    {
        if (!$this->isValidRole($role)) {
            // Or throw an InvalidArgumentException
            error_log("Invalid user role provided: {$role}");
            return false;
        }

        $passwordHash = Security::hashPassword($password);

        $data = [
            'username' => $username,
            'password_hash' => $passwordHash,
            'role' => $role,
        ];

        return $this->create($data);
    }

    /**
     * Find a user by their username.
     *
     * @param string $username
     * @return array|false User data as an associative array, or false if not found.
     */
    public function findByUsername(string $username): array|false
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verify a user's password.
     *
     * @param string $username
     * @param string $password Plain text password to verify.
     * @return array|false User data if verification successful, false otherwise.
     */
    public function verifyUserPassword(string $username, string $password): array|false
    {
        $user = $this->findByUsername($username);
        if ($user && Security::verifyPassword($password, $user['password_hash'])) {
            return $user; // Password matches
        }
        return false; // User not found or password mismatch
    }

    /**
     * Update user's password.
     *
     * @param int $userId
     * @param string $newPassword Plain text new password.
     * @return bool True on success, false on failure.
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $newPasswordHash = Security::hashPassword($newPassword);
        return $this->update($userId, ['password_hash' => $newPasswordHash]);
    }

    /**
     * Check if a given role is valid.
     * @param string $role
     * @return bool
     */
    public function isValidRole(string $role): bool
    {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_WAITER, self::ROLE_KITCHEN_STAFF], true);
    }

    /**
     * Get all available roles.
     * @return array
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_WAITER => 'Waiter',
            self::ROLE_KITCHEN_STAFF => 'Kitchen Staff'
        ];
    }
}
