<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_batches', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->text('description')->nullable();

            $table->string('semester')->nullable();

            $table->decimal('amount', 15, 2);

            $table->date('due_date')->nullable();

            $table->enum('target_type', [
                'student',
                'class',
                'cohort',
                'school',
            ]);

            $table->unsignedBigInteger('target_value')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_batches');
    }
};