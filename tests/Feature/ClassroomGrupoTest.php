<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClassroomGrupoTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher', 'guard_name' => 'web']);

        $this->institution = Institution::create(['name' => 'Test Inst', 'is_active' => true]);
        $plan = Plan::create([
            'name' => 'Pro', 'price' => 199, 'max_students' => 50,
            'max_classrooms' => 10, 'duration_months' => 1, 'is_active' => true,
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
            'email'          => 'doc@grupo.test',
            'password_hash'  => bcrypt('secret'),
            'is_active'      => true,
        ]);
        $this->teacher->assignRole('Teacher');
    }

    #[Test]
    public function teacher_can_create_two_classrooms_same_subject_with_different_grupo(): void
    {
        $payload = [
            'subject_name'       => 'Historia',
            'period'             => '2026-A',
            'grupo'              => '189900',
            'max_capacity'       => 30,
            'min_attendance_pct' => 80,
        ];

        $this->actingAs($this->teacher)
            ->post(route('aulas.store'), $payload)
            ->assertRedirect(route('aulas.index'));

        $payload['grupo'] = '190001';

        $this->actingAs($this->teacher)
            ->post(route('aulas.store'), $payload)
            ->assertRedirect(route('aulas.index'));

        $this->assertEquals(2, Classroom::withoutGlobalScopes()
            ->where('teacher_id', $this->teacher->id)
            ->where('subject_name', 'Historia')
            ->count());
    }

    #[Test]
    public function cannot_create_duplicate_subject_period_and_grupo(): void
    {
        Classroom::withoutGlobalScopes()->create([
            'institution_id'     => $this->institution->id,
            'teacher_id'         => $this->teacher->id,
            'subject_name'       => 'Historia',
            'period'             => '2026-A',
            'grupo'              => '189900',
            'max_capacity'       => 30,
            'min_attendance_pct' => 80,
            'is_active'          => true,
        ]);

        $this->actingAs($this->teacher)
            ->from(route('aulas.create'))
            ->post(route('aulas.store'), [
                'subject_name'       => 'Historia',
                'period'             => '2026-A',
                'grupo'              => '189900',
                'max_capacity'       => 25,
                'min_attendance_pct' => 80,
            ])
            ->assertRedirect(route('aulas.create'))
            ->assertSessionHasErrors('general');

        $this->assertEquals(1, Classroom::withoutGlobalScopes()
            ->where('teacher_id', $this->teacher->id)
            ->where('subject_name', 'Historia')
            ->count());
    }

    #[Test]
    public function grupo_must_be_exactly_six_digits(): void
    {
        $this->actingAs($this->teacher)
            ->from(route('aulas.create'))
            ->post(route('aulas.store'), [
                'subject_name'       => 'Historia',
                'period'             => '2026-A',
                'grupo'              => '1899',
                'max_capacity'       => 30,
                'min_attendance_pct' => 80,
            ])
            ->assertSessionHasErrors('grupo');
    }
}
