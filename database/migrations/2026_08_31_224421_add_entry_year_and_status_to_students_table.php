<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->year('entry_year')
                ->nullable()
                ->after('class_room_id');

            $table->enum('status', [
                'active',
                'inactive',
            ])
                ->default('active')
                ->after('entry_year');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'entry_year',
                'status',
            ]);
        });
    }
};