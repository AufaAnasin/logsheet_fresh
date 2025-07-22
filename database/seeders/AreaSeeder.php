<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run()
    {
        Area::create([
            'AreaID' => 16,
            'name' => 'Boiler 1',
            'DepartmentID' => 1,
            'desc' => 'Boiler unit for steam generation in Thermal Power Plant.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}