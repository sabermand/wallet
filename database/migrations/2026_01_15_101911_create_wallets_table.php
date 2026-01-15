<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('currency', 3)->index(); // TRY/USD/EUR
            $table->decimal('balance', 18, 2)->default(0);

            $table->string('status', 20)->default('active')->index(); // active/blocked
            $table->string('block_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'currency']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
