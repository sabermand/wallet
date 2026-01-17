<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'transaction_id',
        'admin_id',
        'action',
        'reason',
        'source_wallet_id',
        'destination_wallet_id',
        'amount',
        'fee_amount',
        'source_balance_before',
        'source_balance_after',
        'dest_balance_before',
        'dest_balance_after',
        'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'source_balance_before' => 'decimal:2',
        'source_balance_after' => 'decimal:2',
        'dest_balance_before' => 'decimal:2',
        'dest_balance_after' => 'decimal:2',
    ];
}
