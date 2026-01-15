<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_fees', function (Blueprint $table) {
            $table->id();

            $table->uuid('transaction_id')->unique();
            $table->string('fee_type', 30); // fixed/percent/tiered
            $table->decimal('fee_amount', 18, 2);
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_fees');
    }
};
