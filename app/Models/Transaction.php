<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuid;

class Transaction extends Model
{
    use HasUuid;

    protected $fillable = [
        'type',
        'status',
        'currency',
        'amount',
        'fee_amount',
        'source_wallet_id',
        'destination_wallet_id',
        'idempotency_key',
        'refunded_transaction_id',
        'ip_address',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function sourceWallet()
    {
        return $this->belongsTo(Wallet::class, 'source_wallet_id');
    }

    public function destinationWallet()
    {
        return $this->belongsTo(Wallet::class, 'destination_wallet_id');
    }

    public function fee()
    {
        return $this->hasOne(TransactionFee::class);
    }

    public function refundOf()
    {
        return $this->belongsTo(Transaction::class, 'refunded_transaction_id');
    }
}
