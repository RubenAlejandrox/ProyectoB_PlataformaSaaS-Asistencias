<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\Plan;
use App\Models\Session;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private Attendance $attendance;
    private Enrollment $enrollment;
    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\LogAuditoria::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $institution = Institution::create(['name' => 'Inst', 'is_active' => true]);
        $plan = Plan::create([
            'name' => 'Pro', 'price' => 499, 'max_students' => 100,
            'max_classrooms' => 20, 'duration_months' => 1, 'is_active' => true,
        ]);
        Subscription::create([
            'institution_id' => $institution->id,
            'plan_id' => $plan->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@audit.test',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $this->admin->assignRole('Administrator');

        $this->teacher = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Doc',
            'last_name' => 'Te',
            'email' => 'doc@audit.test',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $this->teacher->assignRole('Teacher');
        $student = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Stu',
            'last_name' => 'Dent',
            'email' => 'stu@audit.test',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);

        $classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id' => $institution->id,
            'teacher_id' => $this->teacher->id,
            'subject_name' => 'Mate',
            'period' => '2026-A',
            'grupo'  => '189900',
            'min_attendance_pct' => 80,
            'max_capacity' => 30,
            'is_active' => true,
        ]);

        $this->enrollment = Enrollment::create([
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $this->session = Session::create([
            'classroom_id' => $classroom->id,
            'session_date' => now()->toDateString(),
            'started_at' => '08:00:00',
            'is_active' => false,
        ]);
        $this->attendance = Attendance::create([
            'session_id' => $this->session->id,
            'student_id' => $student->id,
            'status' => 'absent',
        ]);
    }

    #[Test]
    public function correction_generates_audit_log_entry(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/corrections', [
                'attendance_id' => $this->attendance->id,
                'status' => 'present',
                'reason' => 'Correccion manual',
            ]);

        $response->assertOk();
        $this->assertEquals('present', $this->attendance->fresh()->status);
    }

    #[Test]
    public function if_change_fails_audit_log_is_rolled_back(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/corrections', [
                'attendance_id' => $this->attendance->id,
                'status' => 'invalid',
                'reason' => 'bad',
            ]);

        $response->assertStatus(422);
        $this->assertEquals('absent', $this->attendance->fresh()->status);
    }

    #[Test]
    public function teacher_cannot_access_admin_web_routes(): void
    {
        $this->actingAs($this->teacher)
            ->get('/admin/edicion')
            ->assertForbidden();

        $this->actingAs($this->teacher)
            ->get('/admin/sesiones')
            ->assertForbidden();
    }

    #[Test]
    public function teacher_cannot_execute_admin_api_actions(): void
    {
        $this->actingAs($this->teacher)
            ->postJson('/api/admin/corrections', [
                'attendance_id' => $this->attendance->id,
                'status' => 'present',
                'reason' => 'Intento no autorizado',
            ])->assertForbidden();

        $this->actingAs($this->teacher)
            ->postJson('/api/admin/drop-student/'.$this->enrollment->id, [
                'reason' => 'Intento no autorizado',
            ])->assertForbidden();

        $this->actingAs($this->teacher)
            ->deleteJson('/api/admin/delete-session/'.$this->session->id, [
                'reason' => 'Intento no autorizado',
            ])->assertForbidden();
    }

    #[Test]
    public function teacher_and_student_cannot_access_admin_web_modules(): void
    {
        $student = User::create([
            'institution_id' => $this->admin->institution_id,
            'first_name' => 'Stu',
            'last_name' => 'Web',
            'email' => 'student-web@audit.test',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $student->assignRole('Student');

        $this->actingAs($this->teacher)->get('/instituciones')->assertForbidden();
        $this->actingAs($this->teacher)->get('/membresias')->assertForbidden();
        $this->actingAs($student)->get('/instituciones')->assertForbidden();
        $this->actingAs($student)->get('/membresias')->assertForbidden();
    }
}
