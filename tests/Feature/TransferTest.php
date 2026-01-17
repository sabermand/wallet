<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_successful(): void
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $w1 = $u1->wallets()->create(['currency' => 'TRY', 'balance' => 2000]);
        $w2 = $u2->wallets()->create(['currency' => 'TRY', 'balance' => 0]);

        $token = $u1->createToken('t')->plainTextToken;

        $res = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Idempotency-Key' => 'k-1',
        ])->postJson('/api/v1/transactions/transfer', [
            'source_wallet_id' => $w1->id,
            'destination_wallet_id' => $w2->id,
            'amount' => 100,
        ]);

        $res->assertStatus(201);
    }
}
