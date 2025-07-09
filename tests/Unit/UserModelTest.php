<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase; // Our custom base TestCase
use App\Models\User;
use App\Core\Security;

class UserModelTest extends TestCase
{
    private User $userModel;

    protected function setUp(): void
    {
        parent::setUp(); // Handles DB transaction
        $this->userModel = new User();
    }

    public function testCanCreateUser(): void
    {
        $username = 'testuser_create';
        $password = 'password123';
        $role = User::ROLE_WAITER;

        $userId = $this->userModel->createUser($username, $password, $role);

        $this->assertIsString($userId, "createUser should return a string ID.");
        $this->assertNotEmpty($userId, "User ID should not be empty.");

        $createdUser = $this->userModel->find((int)$userId);

        $this->assertIsArray($createdUser);
        $this->assertEquals($username, $createdUser['username']);
        $this->assertEquals($role, $createdUser['role']);
        $this->assertTrue(Security::verifyPassword($password, $createdUser['password_hash']));
    }

    public function testCreateUserFailsWithInvalidRole(): void
    {
        $userId = $this->userModel->createUser('test_invalid_role', 'password', 'invalid_role_type');
        $this->assertFalse($userId, "createUser should return false for an invalid role.");
    }

    public function testFindByUsernameReturnsUser(): void
    {
        $username = 'findmeuser';
        $this->userModel->createUser($username, 'password123', User::ROLE_ADMIN);

        $foundUser = $this->userModel->findByUsername($username);

        $this->assertIsArray($foundUser);
        $this->assertEquals($username, $foundUser['username']);
    }

    public function testFindByUsernameReturnsFalseForNonExistentUser(): void
    {
        $foundUser = $this->userModel->findByUsername('nonexistentuser12345');
        $this->assertFalse($foundUser);
    }

    public function testVerifyUserPasswordSuccess(): void
    {
        $username = 'verifyuser';
        $password = 'securePassword!@#';
        $this->userModel->createUser($username, $password, User::ROLE_KITCHEN_STAFF);

        $verifiedUser = $this->userModel->verifyUserPassword($username, $password);

        $this->assertIsArray($verifiedUser);
        $this->assertEquals($username, $verifiedUser['username']);
    }

    public function testVerifyUserPasswordFailsForWrongPassword(): void
    {
        $username = 'verifyuser_wrongpass';
        $password = 'correctPassword';
        $this->userModel->createUser($username, $password, User::ROLE_WAITER);

        $verifiedUser = $this->userModel->verifyUserPassword($username, 'wrongPasswordAttempt');
        $this->assertFalse($verifiedUser);
    }

    public function testVerifyUserPasswordFailsForNonExistentUser(): void
    {
        $verifiedUser = $this->userModel->verifyUserPassword('ghostuser', 'anypassword');
        $this->assertFalse($verifiedUser);
    }

    public function testUpdatePassword(): void
    {
        $username = 'updatepassuser';
        $initialPassword = 'oldPassword123';
        $newPassword = 'newStrongPassword456';

        $userId = $this->userModel->createUser($username, $initialPassword, User::ROLE_ADMIN);
        $this->assertIsString($userId);

        $updateResult = $this->userModel->updatePassword((int)$userId, $newPassword);
        $this->assertTrue($updateResult, "updatePassword should return true on success.");

        // Verify with the new password
        $verifiedUser = $this->userModel->verifyUserPassword($username, $newPassword);
        $this->assertIsArray($verifiedUser, "Should be able to verify with new password.");

        // Verify that the old password no longer works
        $verifiedUserOldPass = $this->userModel->verifyUserPassword($username, $initialPassword);
        $this->assertFalse($verifiedUserOldPass, "Old password should no longer work.");
    }

    public function testIsValidRole(): void
    {
        $this->assertTrue($this->userModel->isValidRole(User::ROLE_ADMIN));
        $this->assertTrue($this->userModel->isValidRole(User::ROLE_WAITER));
        $this->assertTrue($this->userModel->isValidRole(User::ROLE_KITCHEN_STAFF));
        $this->assertFalse($this->userModel->isValidRole('guest'));
        $this->assertFalse($this->userModel->isValidRole(''));
    }

    public function testGetAvailableRoles(): void
    {
        $roles = User::getAvailableRoles();
        $this->assertIsArray($roles);
        $this->assertArrayHasKey(User::ROLE_ADMIN, $roles);
        $this->assertArrayHasKey(User::ROLE_WAITER, $roles);
        $this->assertArrayHasKey(User::ROLE_KITCHEN_STAFF, $roles);
        $this->assertEquals('Administrator', $roles[User::ROLE_ADMIN]);
    }

    // Add more tests:
    // - Uniqueness of username (should fail if trying to create user with existing username)
    //   This might require catching PDOException or checking return values carefully.
    //   The base Model::create does not explicitly handle unique constraint violations yet.
}
