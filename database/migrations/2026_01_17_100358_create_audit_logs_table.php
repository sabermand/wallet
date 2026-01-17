<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->uuid('transaction_id')->nullable()->index();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();

            $table->string('action', 30)->index(); // approve/reject
            $table->string('reason')->nullable();

            $table->uuid('source_wallet_id')->nullable()->index();
            $table->uuid('destination_wallet_id')->nullable()->index();

            $table->decimal('amount', 18, 2)->nullable();
            $table->decimal('fee_amount', 18, 2)->nullable();

            $table->decimal('source_balance_before', 18, 2)->nullable();
            $table->decimal('source_balance_after', 18, 2)->nullable();
            $table->decimal('dest_balance_before', 18, 2)->nullable();
            $table->decimal('dest_balance_after', 18, 2)->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
