<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudFlag extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'rule_type',
        'flagged_amount',
        'details',
        'triggered_at',
    ];

    protected $casts = [
        'flagged_amount' => 'decimal:2',
        'details' => 'array',
        'triggered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
