<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;        
use App\Models\Plan;              

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::insert([
            [
                'id'               => Str::uuid(),
                'name'             => 'Basic',
                'price'            => 0.00,
                'max_students'     => 15,
                'max_classrooms'   => 3,
                'duration_months'  => 1,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'id'               => Str::uuid(),
                'name'             => 'Pro',
                'price'            => 199.00,
                'max_students'     => 50,
                'max_classrooms'   => 10,
                'duration_months'  => 1,
                'is_active'        => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);
    }
}
