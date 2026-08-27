<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\ClassRoom;

class ClassRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::where('name', '2026/2027')->first();

        ClassRoom::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'X RPL 1',
            'grade' => 'X',
            'major' => 'RPL',
        ]);

        ClassRoom::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'X RPL 2',
            'grade' => 'X',
            'major' => 'RPL',
        ]);

        ClassRoom::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'XI RPL 1',
            'grade' => 'XI',
            'major' => 'RPL',
        ]);

        ClassRoom::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'XII RPL 1',
            'grade' => 'XII',
            'major' => 'RPL',
        ]);
    }
}