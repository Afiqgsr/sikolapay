<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bill_batches', function (Blueprint $table) {
            // target_type diubah dari enum ke varchar(50) NOT NULL sesuai skema asli (tanpa nullable)
            $table->string('target_type', 50)->change();

            // target_value diubah dari unsignedBigInteger nullable ke varchar(255) nullable
            // untuk mendukung string tingkat/grade ('X', 'XI', 'XII') sekaligus backward compatible dengan numeric IDs
            $table->string('target_value', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Guard: Periksa apakah masih terdapat record dengan target_type = 'grade' atau target_value non-numerik
        if (Schema::hasTable('bill_batches')) {
            $hasGradeBatches = DB::table('bill_batches')
                ->where('target_type', 'grade')
                ->exists();

            if ($hasGradeBatches) {
                throw new RuntimeException(
                    "Rollback dibatalkan: Masih terdapat record pada tabel 'bill_batches' dengan target_type = 'grade'. ".
                    "Enum lama tidak mendukung nilai 'grade'. Silakan periksa atau sesuaikan data tersebut terlebih dahulu sebelum melakukan rollback."
                );
            }

            $hasNonNumericValues = DB::table('bill_batches')
                ->whereNotNull('target_value')
                ->whereRaw("target_value NOT REGEXP '^[0-9]+$'")
                ->exists();

            if ($hasNonNumericValues) {
                throw new RuntimeException(
                    "Rollback dibatalkan: Masih terdapat record pada tabel 'bill_batches' dengan target_value non-numerik. ".
                    'Kolom lama bertipe unsignedBigInteger dan tidak dapat menampung nilai string non-angka. Silakan sesuaikan data terlebih dahulu.'
                );
            }
        }

        Schema::table('bill_batches', function (Blueprint $table) {
            $table->enum('target_type', [
                'student',
                'class',
                'cohort',
                'school',
            ])->change();

            $table->unsignedBigInteger('target_value')->nullable()->change();
        });
    }
};
