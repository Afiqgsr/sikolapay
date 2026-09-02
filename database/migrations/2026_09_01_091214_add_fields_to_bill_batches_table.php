<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_batches', function (Blueprint $table) {

            if (!Schema::hasColumn('bill_batches', 'description')) {
                $table->text('description')->nullable();
            }

            if (!Schema::hasColumn('bill_batches', 'semester')) {
                $table->string('semester')->nullable();
            }

            if (!Schema::hasColumn('bill_batches', 'amount')) {
                $table->decimal('amount', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('bill_batches', 'due_date')) {
                $table->date('due_date')->nullable();
            }

            if (!Schema::hasColumn('bill_batches', 'target_type')) {
                $table->enum('target_type', [
                    'student',
                    'class',
                    'cohort',
                    'school',
                ])->nullable();
            }

            if (!Schema::hasColumn('bill_batches', 'target_value')) {
                $table->unsignedBigInteger('target_value')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bill_batches', function (Blueprint $table) {

            $columns = [];

            if (Schema::hasColumn('bill_batches', 'description')) {
                $columns[] = 'description';
            }

            if (Schema::hasColumn('bill_batches', 'semester')) {
                $columns[] = 'semester';
            }

            if (Schema::hasColumn('bill_batches', 'amount')) {
                $columns[] = 'amount';
            }

            if (Schema::hasColumn('bill_batches', 'due_date')) {
                $columns[] = 'due_date';
            }

            if (Schema::hasColumn('bill_batches', 'target_type')) {
                $columns[] = 'target_type';
            }

            if (Schema::hasColumn('bill_batches', 'target_value')) {
                $columns[] = 'target_value';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};