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
        Schema::table('bill_batches', function (Blueprint $table) {
            $table->date('billing_period')
                ->nullable()
                ->after('amount')
                ->index();
        });

        Schema::table('bills', function (Blueprint $table) {
            $table->date('billing_period')
                ->nullable()
                ->after('amount');

            $table->index([
                'name',
                'billing_period',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropIndex([
                'name',
                'billing_period',
            ]);
            $table->dropColumn('billing_period');
        });

        Schema::table('bill_batches', function (Blueprint $table) {
            $table->dropIndex(['billing_period']);
            $table->dropColumn('billing_period');
        });
    }
};
