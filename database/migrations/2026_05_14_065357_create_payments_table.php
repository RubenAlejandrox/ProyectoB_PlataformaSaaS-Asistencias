<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 8, 2)->notNull();
            $table->string('currency', 3)->default('MXN');
            $table->enum('status', ['pending','completed','failed','refunded'])
                ->default('pending');
            $table->string('paypal_order_id')->nullable();   // orden PayPal
            $table->string('paypal_capture_id')->nullable(); // captura (para reembolsos)
            $table->string('payment_method')->default('paypal');
            $table->timestamp('paid_at')->nullable();        // solo en completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
