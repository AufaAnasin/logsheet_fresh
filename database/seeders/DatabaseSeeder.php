<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
// use Database\Seeders\LogDataSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\AreaSeeder;
use Database\Seeders\ComponentSeeder;   

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // $this->call(UserSeeder::class);
        // $this->call(DepartmentSeeder::class);
        // $this->call(AreaSeeder::class);
        $this->call(ComponentSeeder::class);
    }
}
