<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'andi@sikolapay.test')
            ->firstOrFail();

        $guardian = Guardian::whereHas('user', function ($query) {
            $query->where('email', 'budi@sikolapay.test');
        })->firstOrFail();

        $classRoom = ClassRoom::where('name', 'X RPL 1')
            ->firstOrFail();

        Student::create([
            'user_id' => $user->id,
            'guardian_id' => $guardian->id,
            'class_room_id' => $classRoom->id,
            'nisn' => '0012345678',
            'nis' => '20260001',
            'name' => 'Andi Santoso',
            'gender' => 'L',
            'birth_date' => '2010-05-15',
            'birth_place' => 'Madiun',
            'address' => 'Jl. Contoh No. 20',
        ]);
    }
}