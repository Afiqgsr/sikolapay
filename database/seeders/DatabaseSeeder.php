<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SchoolSeeder::class,
            AcademicYearSeeder::class,
            ClassRoomSeeder::class,
            GuardianSeeder::class,
            StudentSeeder::class,
            BillSeeder::class,
            PaymentMethodSeeder::class,
            PaymentSeeder::class,
            PaymentVerificationSeeder::class,
        ]);
    }
}