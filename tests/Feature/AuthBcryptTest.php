<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthBcryptTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $institution = Institution::create([
            'name'      => 'Test Institution',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'institution_id'        => $institution->id,
            'first_name'            => 'Test',
            'last_name'             => 'User',
            'email'                 => 'test@gama.com',
            'password_hash'         => bcrypt('Password1!'),
            'is_active'             => true,
            'failed_login_attempts' => 0,
        ]);
    }

    #[Test]
    public function login_with_correct_credentials_returns_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'test@gama.com',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }

    #[Test]
    public function login_with_wrong_password_returns_401(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'test@gama.com',
            'password' => 'WrongPassword1!',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function account_locks_after_5_failed_attempts(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', [
                'email'    => 'test@gama.com',
                'password' => 'WrongPassword1!',
            ]);
        }

        $this->user->refresh();
        $this->assertNotNull($this->user->locked_until);
        $this->assertTrue($this->user->isLocked());
    }

    #[Test]
    public function locked_account_returns_423(): void
    {
        $this->user->update([
            'failed_login_attempts' => 5,
            'locked_until'          => now()->addMinutes(15),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'test@gama.com',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(423);
    }

    #[Test]
    public function successful_login_resets_failed_attempts(): void
    {
        $this->user->update(['failed_login_attempts' => 3]);

        $this->postJson('/api/login', [
            'email'    => 'test@gama.com',
            'password' => 'Password1!',
        ]);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->failed_login_attempts);
        $this->assertNull($this->user->locked_until);
    }

    #[Test]
    public function logout_revokes_token(): void
    {
        $token = $this->user->createToken('web')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/logout');

        $response->assertStatus(200);
        $this->assertEquals(0, $this->user->tokens()->count());
    }

    #[Test]
    public function double_session_is_prevented(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'test@gama.com',
            'password' => 'Password1!',
        ]);

        $this->postJson('/api/login', [
            'email'    => 'test@gama.com',
            'password' => 'Password1!',
        ]);

        $this->assertEquals(1, $this->user->tokens()->count());
    }
}