<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $student;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear institución base
        $this->institution = Institution::create([
            'name'      => 'Test Institution',
            'is_active' => true,
        ]);

        // Crear plan y suscripción activa
        $plan = Plan::create([
            'name'             => 'Pro',
            'price'            => 499.00,
            'max_students'     => 50,
            'max_classrooms'   => 10,
            'duration_months'  => 1,
            'is_active'        => true,
        ]);

        Subscription::create([
            'institution_id' => $this->institution->id,
            'plan_id'        => $plan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);

        // Crear roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher',       'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student',       'guard_name' => 'web']);

        // Crear usuarios
        $this->admin = $this->createUser('admin@test.com', 'Administrator');
        $this->teacher = $this->createUser('teacher@test.com', 'Teacher');
        $this->student = $this->createUser('student@test.com', 'Student');
    }

    private function createUser(string $email, string $role): User
    {
        $user = User::create([
            'institution_id'        => $this->institution->id,
            'first_name'            => $role,
            'last_name'             => 'Test',
            'email'                 => $email,
            'password_hash'         => bcrypt('Password1!'),
            'is_active'             => true,
            'failed_login_attempts' => 0,
        ]);
        $user->assignRole($role);
        return $user;
    }

    // ── CheckRole ─────────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/institutions');
        $response->assertStatus(401);
    }

    #[Test]
    public function administrator_can_access_institution_routes(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/institutions');

        $response->assertStatus(200);
    }

    #[Test]
    public function teacher_cannot_access_institution_routes(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/institutions');

        $response->assertStatus(403);
    }

    #[Test]
    public function student_cannot_access_institution_routes(): void
    {
        $response = $this->actingAs($this->student)
            ->getJson('/api/institutions');

        $response->assertStatus(403);
    }

    #[Test]
    public function teacher_can_access_classroom_routes(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/classrooms');

        $response->assertStatus(200);
    }

    #[Test]
    public function student_cannot_access_classroom_routes(): void
    {
        $response = $this->actingAs($this->student)
            ->getJson('/api/classrooms');

        $response->assertStatus(403);
    }

    // ── CheckPlanAccess ───────────────────────────────────────────────────────

    #[Test]
    public function expired_plan_blocks_post_requests(): void
    {
        // Vencer la suscripción
        $this->institution->subscriptions()->update([
            'status'   => 'expired',
            'end_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/institutions', [
                'name' => 'New Institution',
            ]);

        $response->assertStatus(403)
                 ->assertJsonFragment(['plan_status' => 'expired']);
    }

    #[Test]
    public function expired_plan_allows_get_requests(): void
    {
        $this->institution->subscriptions()->update([
            'status'   => 'expired',
            'end_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/institutions');

        $response->assertStatus(200);
    }

    #[Test]
    public function active_plan_allows_all_requests(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/institutions');

        $response->assertStatus(200);
    }

    // ── LogAuditoria ──────────────────────────────────────────────────────────

    #[Test]
    public function post_request_creates_audit_log_entry(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/institutions', [
                'name'      => 'Audited Institution',
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('audit_log', [
            'user_id' => $this->admin->id,
            'action'  => 'create',
            'entity'  => 'institutions',
        ]);
    }

    #[Test]
    public function get_request_does_not_create_audit_log(): void
    {
        $initialCount = \App\Models\AuditLog::count();

        $this->actingAs($this->admin)
            ->getJson('/api/institutions');

        $this->assertEquals($initialCount, \App\Models\AuditLog::count());
    }

    #[Test]
    public function login_route_is_not_audited(): void
    {
        $initialCount = \App\Models\AuditLog::count();

        $this->postJson('/api/login', [
            'email'    => 'admin@test.com',
            'password' => 'Password1!',
        ]);

        $this->assertEquals($initialCount, \App\Models\AuditLog::count());
    }
}