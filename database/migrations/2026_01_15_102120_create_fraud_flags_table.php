<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_flags', function (Blueprint $table) {
            $table->id();

            $table->uuid('transaction_id')->nullable()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('rule_type', 60)->index();
            $table->decimal('flagged_amount', 18, 2)->nullable();
            $table->json('details')->nullable();

            $table->timestamp('triggered_at')->index();

            $table->timestamps();

            $table->index(['user_id', 'rule_type', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_flags');
    }
};
