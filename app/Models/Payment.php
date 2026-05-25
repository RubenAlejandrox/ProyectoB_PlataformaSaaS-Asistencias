<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'institution_id',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'paypal_order_id',
        'paypal_capture_id',
        'payment_method',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'  => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }
}