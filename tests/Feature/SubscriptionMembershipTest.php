<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionMembershipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Plan $basic;
    private Plan $pro;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);

        $institution = Institution::create(['name' => 'Admin Inst', 'is_active' => true]);
        $this->basic = Plan::create([
            'name' => 'Basic', 'price' => 0, 'max_students' => 15,
            'max_classrooms' => 3, 'duration_months' => 1, 'is_active' => true,
        ]);
        $this->pro = Plan::create([
            'name' => 'Pro', 'price' => 199, 'max_students' => 50,
            'max_classrooms' => 10, 'duration_months' => 1, 'is_active' => true,
        ]);

        $this->admin = User::create([
            'institution_id' => $institution->id,
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@membresias.test',
            'password_hash' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $this->admin->assignRole('Administrator');
    }

    #[Test]
    public function assign_modal_lists_only_institutions_without_active_subscription(): void
    {
        $withSub = Institution::create(['name' => 'Con plan', 'is_active' => true]);
        Subscription::create([
            'institution_id' => $withSub->id,
            'plan_id'        => $this->basic->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);
        $withoutSub = Institution::create(['name' => 'Sin plan', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->get(route('membresias.index'));

        $response->assertOk();
        $response->assertViewHas('institutionsForAssign', function ($collection) use ($withSub, $withoutSub) {
            $ids = $collection->pluck('id');

            return $ids->contains($withoutSub->id) && ! $ids->contains($withSub->id);
        });
    }

    #[Test]
    public function cannot_assign_second_active_subscription_to_same_institution(): void
    {
        $inst = Institution::create(['name' => 'Duplicada', 'is_active' => true]);
        Subscription::create([
            'institution_id' => $inst->id,
            'plan_id'        => $this->pro->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('membresias.upgrade'), [
            'institution_id' => $inst->id,
            'plan_id'        => $this->pro->id,
            'intent'         => 'assign',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('general');
        $this->assertEquals(1, Subscription::where('institution_id', $inst->id)->where('status', 'active')->count());
    }

    #[Test]
    public function can_assign_initial_free_plan_to_institution_without_subscription(): void
    {
        $inst = Institution::create(['name' => 'Nueva', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('membresias.upgrade'), [
            'institution_id' => $inst->id,
            'plan_id'        => $this->basic->id,
            'intent'         => 'assign',
        ]);

        $response->assertRedirect(route('membresias.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('subscriptions', [
            'institution_id' => $inst->id,
            'plan_id'        => $this->basic->id,
            'status'         => 'active',
        ]);
    }
}
