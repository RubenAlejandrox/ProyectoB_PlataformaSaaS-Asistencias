<?php

namespace Tests\Feature;

use App\Mail\AttendanceReportMail;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportMailTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function report_mail_renders_institutional_template(): void
    {
        $mailable = new AttendanceReportMail(
            subjectLine: 'Reporte de prueba',
            messageBody: 'Cuerpo del mensaje de prueba.',
            attachmentName: 'reporte.xlsx',
            attachmentData: 'fake-bytes',
            classroomName: 'Matemáticas — 2026-A',
            reportTypeLabel: 'Matriz de asistencias',
            periodLabel: null,
            senderName: 'Docente Test',
            senderEmail: 'docente@test.com',
            reportTitle: 'Matriz de asistencias',
        );

        $html = $mailable->render();

        $this->assertStringContainsString('GAMA SOLUTIONS', $html);
        $this->assertStringContainsString('Matemáticas', $html);
        $this->assertStringContainsString('reporte.xlsx', $html);
    }

    #[Test]
    public function send_fails_when_mailer_is_log(): void
    {
        config(['mail.default' => 'log']);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        $institution = Institution::create(['name' => 'Inst', 'is_active' => true]);
        $plan = Plan::create([
            'name' => 'Pro', 'price' => 499, 'max_students' => 50,
            'max_classrooms' => 10, 'duration_months' => 1, 'is_active' => true,
        ]);
        Subscription::create([
            'institution_id' => $institution->id,
            'plan_id' => $plan->id,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        $teacher = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Doc',
            'last_name' => 'Test',
            'email' => 'teacher@test.com',
            'password_hash' => bcrypt('password'),
            'is_active' => true,
        ]);
        $teacher->assignRole('Teacher');

        $classroom = Classroom::withoutGlobalScopes()->create([
            'institution_id' => $institution->id,
            'teacher_id' => $teacher->id,
            'subject_name' => 'Mate',
            'period' => '2026-A',
            'max_capacity' => 30,
            'min_attendance_pct' => 80,
            'is_active' => true,
        ]);

        $this->actingAs($teacher)
            ->from(route('reportes.index'))
            ->post(route('reportes.send', $classroom), [
                'email' => 'destino@test.com',
                'type' => 'matrix',
                'month' => now()->format('Y-m'),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }
}
