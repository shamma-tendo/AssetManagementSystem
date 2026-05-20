<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user registration.
     */
    public function test_user_can_register(): void
    {
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'username' => 'johndoe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'technician',
            'phone' => '1234567890',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'User registered successfully',
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'username' => 'johndoe',
            'role' => 'technician',
        ]);
    }

    /**
     * Test user registration validation.
     */
    public function test_user_registration_validation(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'first_name',
                     'last_name',
                     'email',
                     'username',
                     'password',
                     'role',
                 ]);
    }

    /**
     * Test unique email validation.
     */
    public function test_unique_email_validation(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'technician',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user login.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Login successful',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'user',
                         'token',
                         'expires_at',
                     ],
                 ]);

        $this->assertAuthenticated();
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Invalid credentials',
                 ]);
    }

    /**
     * Test login with inactive user.
     */
    public function test_login_with_inactive_user(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Account is deactivated',
                 ]);
    }

    /**
     * Test user logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Logout successful',
                 ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test user profile retrieval.
     */
    public function test_can_get_user_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/profile');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id',
                         'first_name',
                         'last_name',
                         'email',
                         'username',
                         'role',
                         'department',
                         'location',
                     ],
                 ]);
    }

    /**
     * Test profile update.
     */
    public function test_can_update_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $updateData = [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '9876543210',
        ];

        $response = $this->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Profile updated successfully',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone' => '9876543210',
        ]);
    }

    /**
     * Test password change.
     */
    public function test_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Password changed successfully',
                 ]);

        // Verify token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test password change with wrong current password.
     */
    public function test_password_change_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/change-password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Current password is incorrect',
                 ]);
    }

    /**
     * Test token management.
     */
    public function test_can_get_user_tokens(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create some tokens
        $user->createToken('Token 1');
        $user->createToken('Token 2');

        $response = $this->getJson('/api/auth/tokens');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonCount(2, 'data');
    }

    /**
     * Test token revocation.
     */
    public function test_can_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Token');
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/auth/tokens/{$token->accessToken->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Token revoked successfully',
                 ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    /**
     * Test token refresh.
     */
    public function test_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('Old Token')->plainTextToken;
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/refresh-token');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Token refreshed successfully',
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'token',
                         'expires_at',
                     ],
                 ]);

        // Verify old token is revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', $oldToken),
        ]);
    }

    /**
     * Test role-based access control.
     */
    public function test_role_based_access(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $technician = User::factory()->create(['role' => UserRole::TECHNICIAN]);

        // Admin should be able to access admin routes
        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/users');
        $response->assertStatus(200);

        // Technician should not be able to access admin routes
        Sanctum::actingAs($technician);
        $response = $this->getJson('/api/users');
        $response->assertStatus(403);
    }

    /**
     * Test protected routes without authentication.
     */
    public function test_protected_routes_without_authentication(): void
    {
        $response = $this->getJson('/api/auth/profile');
        $response->assertStatus(401);

        $response = $this->getJson('/api/assets');
        $response->assertStatus(401);

        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }

    /**
     * Test admin user creation restrictions.
     */
    public function test_only_admins_can_create_admin_users(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'username' => 'adminuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(403)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Only administrators can create admin users',
                 ]);
    }
}
