<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\HasUuid;

class Wallet extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency',
        'balance',
        'status',
        'block_reason',
        'blocked_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'blocked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outgoingTransactions()
    {
        return $this->hasMany(Transaction::class, 'source_wallet_id');
    }

    public function incomingTransactions()
    {
        return $this->hasMany(Transaction::class, 'destination_wallet_id');
    }
}
