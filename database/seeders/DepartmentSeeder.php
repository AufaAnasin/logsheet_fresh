<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        Department::create([
            'DepartmentID' => 1,
            'name' => 'Thermal Power Plant',
            'desc' => 'Department responsible for power generation operations.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}