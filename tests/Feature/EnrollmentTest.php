<?php

namespace Tests\Feature;

use App\Events\StudentEnrolled;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Institution;
use App\Models\InvitationCode;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private Institution $institution;
    private User $teacher;
    private Classroom $classroom;
    private InvitationCode $invitationCode;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Student', 'guard_name' => 'web']);

        $this->institution = Institution::create([
            'name'      => 'Test Institution',
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name'            => 'Pro',
            'price'           => 499.00,
            'max_students'    => 50,
            'max_classrooms'  => 10,
            'duration_months' => 1,
            'is_active'       => true,
        ]);

        Subscription::create([
            'institution_id' => $this->institution->id,
            'plan_id'        => $plan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);

        $this->teacher = User::create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Doc',
            'last_name'      => 'Ente',
            'email'          => 'teacher@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $this->teacher->assignRole('Teacher');

        $this->classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id'     => $this->institution->id,
            'teacher_id'         => $this->teacher->id,
            'subject_name'       => 'Matemáticas',
            'period'             => '2026-A',
            'grupo'              => '189900',
            'max_capacity'       => 30,
            'min_attendance_pct' => 80,
            'is_active'          => true,
        ]);

        $this->invitationCode = InvitationCode::create([
            'classroom_id' => $this->classroom->id,
            'code'         => 'AULA2026',
            'expires_at'   => now()->addDay(),
            'is_used'      => false,
        ]);
    }

    #[Test]
    public function student_register_with_classroom_code_creates_enrollment(): void
    {
        $response = $this->post('/register', [
            'first_name'      => 'Ana',
            'last_name'       => 'Alumna',
            'email'           => 'ana@test.com',
            'role'            => 'Student',
            'password'        => 'password123',
            'password_confirmation' => 'password123',
            'invitation_code' => 'AULA2026',
            'privacy_accepted' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));

        $student = User::where('email', 'ana@test.com')->first();
        $this->assertNotNull($student);

        $this->assertDatabaseHas('enrollments', [
            'classroom_id' => $this->classroom->id,
            'student_id'   => $student->id,
            'is_active'    => true,
        ]);

        $this->assertFalse($this->invitationCode->fresh()->is_used);
    }

    #[Test]
    public function logged_in_student_can_join_classroom_with_code(): void
    {
        $student = User::create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Luis',
            'last_name'      => 'Pérez',
            'email'          => 'luis@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $student->assignRole('Student');

        $code2 = InvitationCode::create([
            'classroom_id' => $this->classroom->id,
            'code'         => 'JOINME01',
            'expires_at'   => now()->addDay(),
            'is_used'      => false,
        ]);

        $response = $this->actingAs($student)->post(route('enrollments.store'), [
            'invitation_code' => 'JOINME01',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('enrollments', [
            'classroom_id' => $this->classroom->id,
            'student_id'   => $student->id,
            'is_active'    => true,
        ]);

        $this->assertFalse($code2->fresh()->is_used);
    }

    #[Test]
    public function enrollment_dispatches_student_enrolled_event(): void
    {
        Event::fake([StudentEnrolled::class]);

        $student = User::create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Ana',
            'last_name'      => 'Realtime',
            'email'          => 'ana-rt@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $student->assignRole('Student');

        $this->actingAs($student)->post(route('enrollments.store'), [
            'invitation_code' => $this->invitationCode->code,
        ])->assertRedirect();

        Event::assertDispatched(StudentEnrolled::class, function (StudentEnrolled $event) {
            return $event->classroomId === $this->classroom->id
                && $event->enrollmentsCount === 1
                && str_contains($event->studentName, 'Ana');
        });
    }

    #[Test]
    public function cannot_enroll_twice_in_same_classroom(): void
    {
        $student = User::create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Duo',
            'last_name'      => 'Inscrito',
            'email'          => 'duo@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $student->assignRole('Student');

        Enrollment::create([
            'classroom_id' => $this->classroom->id,
            'student_id'   => $student->id,
            'enrolled_at'  => now(),
            'is_active'    => true,
        ]);

        $response = $this->actingAs($student)->post(route('enrollments.store'), [
            'invitation_code' => 'AULA2026',
        ]);

        $response->assertSessionHasErrors('invitation_code');
    }

    #[Test]
    public function same_code_can_enroll_multiple_students_until_expiration(): void
    {
        $studentA = User::create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Ana',
            'last_name'      => 'Multi',
            'email'          => 'ana.multi@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $studentA->assignRole('Student');

        $studentB = User::create([
            'institution_id' => $this->institution->id,
            'first_name'     => 'Beto',
            'last_name'      => 'Multi',
            'email'          => 'beto.multi@test.com',
            'password_hash'  => bcrypt('password'),
            'is_active'      => true,
        ]);
        $studentB->assignRole('Student');

        $this->actingAs($studentA)->post(route('enrollments.store'), [
            'invitation_code' => 'AULA2026',
        ])->assertRedirect(route('dashboard'));

        $this->actingAs($studentB)->post(route('enrollments.store'), [
            'invitation_code' => 'AULA2026',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('enrollments', [
            'classroom_id' => $this->classroom->id,
            'student_id'   => $studentA->id,
            'is_active'    => true,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'classroom_id' => $this->classroom->id,
            'student_id'   => $studentB->id,
            'is_active'    => true,
        ]);
    }
}
