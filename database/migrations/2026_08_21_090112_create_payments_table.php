<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('payer_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('payment_method_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('payment_number')->unique();

            $table->decimal('amount', 15, 2);

            $table->string('gateway_transaction_id')
                ->nullable()
                ->unique();

            $table->string('gateway_reference')->nullable();

            $table->string('gateway_status')->nullable();

            $table->string('payment_url')->nullable();

            $table->string('proof_of_payment')->nullable();

            $table->enum('status', [
                'pending',
                'paid',
                'failed',
                'expired',
                'cancelled',
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
