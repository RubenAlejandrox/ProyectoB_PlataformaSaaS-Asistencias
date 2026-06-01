<?php

namespace Tests\Unit;

use App\Models\Institution;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SubscriptionService::class);
    }

    #[Test]
    public function change_plan_from_basic_to_pro_keeps_single_active_subscription(): void
    {
        $inst = Institution::create(['name' => 'Escuela', 'is_active' => true]);
        $basic = Plan::create([
            'name' => 'Basic', 'price' => 0, 'max_students' => 15,
            'max_classrooms' => 3, 'duration_months' => 1, 'is_active' => true,
        ]);
        $pro = Plan::create([
            'name' => 'Pro', 'price' => 199, 'max_students' => 50,
            'max_classrooms' => 10, 'duration_months' => 1, 'is_active' => true,
        ]);
        $old = Subscription::create([
            'institution_id' => $inst->id,
            'plan_id'        => $basic->id,
            'start_date'     => now()->subWeek(),
            'end_date'       => now()->addWeek(),
            'status'         => 'active',
        ]);

        $new = $this->service->changeOrRenew($inst, $pro);

        $old->refresh();
        $this->assertSame('expired', $old->status);
        $this->assertSame($pro->id, $new->plan_id);
        $this->assertSame('active', $new->status);
        $this->assertEquals(1, Subscription::where('institution_id', $inst->id)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString())
            ->count());
    }

    #[Test]
    public function assign_initial_rejects_institution_with_active_subscription(): void
    {
        $inst = Institution::create(['name' => 'X', 'is_active' => true]);
        $plan = Plan::create([
            'name' => 'Pro', 'price' => 199, 'max_students' => 50,
            'max_classrooms' => 10, 'duration_months' => 1, 'is_active' => true,
        ]);
        Subscription::create([
            'institution_id' => $inst->id,
            'plan_id'        => $plan->id,
            'start_date'     => now(),
            'end_date'       => now()->addMonth(),
            'status'         => 'active',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->service->assignInitial($inst, $plan);
    }
}
