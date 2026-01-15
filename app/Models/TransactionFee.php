<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionFee extends Model
{
    protected $fillable = [
        'transaction_id',
        'fee_type',
        'fee_amount',
        'meta',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
