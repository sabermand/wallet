<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('type', 20)->index(); // deposit/withdrawal/transfer/refund
            $table->string('status', 30)->default('pending')->index(); // completed/pending_review/...
            $table->string('currency', 3)->index();

            $table->decimal('amount', 18, 2);
            $table->decimal('fee_amount', 18, 2)->default(0);

            $table->uuid('source_wallet_id')->nullable()->index();
            $table->uuid('destination_wallet_id')->nullable()->index();

            
            $table->string('idempotency_key', 80)->nullable()->unique();

            $table->uuid('refunded_transaction_id')->nullable()->index();

            $table->ipAddress('ip_address')->nullable()->index();

            $table->timestamp('completed_at')->nullable()->index();

            $table->timestamps();

            $table->index(['type', 'status', 'created_at']);
            $table->index(['currency', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
