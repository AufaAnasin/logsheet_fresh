<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Component;

class ComponentSeeder extends Seeder
{
    public function run()
    {
        $components = [
            ['ComponentID' => 34, 'name' => 'Drum Water Level', 'AreaID' => 16, 'desc' => 'Water level in the drum.', 'value_type' => 'numeric'],
            ['ComponentID' => 35, 'name' => 'Feed Water Pressure', 'AreaID' => 16, 'desc' => 'Pressure of feed water supply.', 'value_type' => 'numeric'],
            ['ComponentID' => 36, 'name' => 'Feed Water Temperature', 'AreaID' => 16, 'desc' => 'Temperature of feed water.', 'value_type' => 'numeric'],
            ['ComponentID' => 37, 'name' => 'Instantaneous Flow', 'AreaID' => 16, 'desc' => 'Instantaneous flow rate measurement.', 'value_type' => 'numeric'],
            ['ComponentID' => 38, 'name' => 'Accumulative Flow', 'AreaID' => 16, 'desc' => 'Cumulative flow rate over time.', 'value_type' => 'numeric'],
            ['ComponentID' => 39, 'name' => 'Flow Rate 1', 'AreaID' => 16, 'desc' => 'First flow rate measurement.', 'value_type' => 'numeric'], // Deduplicated "Flow"
            ['ComponentID' => 40, 'name' => 'Flow Rate 2', 'AreaID' => 16, 'desc' => 'Second flow rate measurement.', 'value_type' => 'numeric'], // Deduplicated "Flow"
            ['ComponentID' => 41, 'name' => 'Pressure', 'AreaID' => 16, 'desc' => 'General pressure measurement.', 'value_type' => 'numeric'],
            ['ComponentID' => 42, 'name' => 'Temperature', 'AreaID' => 16, 'desc' => 'General temperature measurement.', 'value_type' => 'numeric'],
            ['ComponentID' => 43, 'name' => 'Inlet Steam Temperature 1', 'AreaID' => 16, 'desc' => 'First inlet steam temperature.', 'value_type' => 'numeric'], // Deduplicated "Inlet steam temp."
            ['ComponentID' => 44, 'name' => 'Inlet Steam Temperature 2', 'AreaID' => 16, 'desc' => 'Second inlet steam temperature.', 'value_type' => 'numeric'], // Deduplicated "Inlet steam temp."
            ['ComponentID' => 45, 'name' => 'Temperature 2', 'AreaID' => 16, 'desc' => 'Additional temperature measurement.', 'value_type' => 'numeric'], // Deduplicated "Temp."
            ['ComponentID' => 46, 'name' => 'Pressure 2', 'AreaID' => 16, 'desc' => 'Additional pressure measurement.', 'value_type' => 'numeric'], // Deduplicated "Pres."
            ['ComponentID' => 47, 'name' => 'Flow Rate 3', 'AreaID' => 16, 'desc' => 'Third flow rate measurement.', 'value_type' => 'numeric'], // Deduplicated "Flow"
            ['ComponentID' => 48, 'name' => 'Accumulative Flow 2', 'AreaID' => 16, 'desc' => 'Second cumulative flow rate.', 'value_type' => 'numeric'], // Deduplicated "Accumulative"
            ['ComponentID' => 49, 'name' => 'Furnace Lower Middle Part Temp (Right)', 'AreaID' => 16, 'desc' => 'Temperature at lower middle right side of furnace.', 'value_type' => 'numeric'],
            ['ComponentID' => 50, 'name' => 'Furnace Lower Middle Part Temp (Left)', 'AreaID' => 16, 'desc' => 'Temperature at lower middle left side of furnace.', 'value_type' => 'numeric'],
            ['ComponentID' => 51, 'name' => 'Furnace Upper Middle Part Temp (Right)', 'AreaID' => 16, 'desc' => 'Temperature at upper middle right side of furnace.', 'value_type' => 'numeric'],
            ['ComponentID' => 52, 'name' => 'Furnace Upper Middle Part Temp (Left)', 'AreaID' => 16, 'desc' => 'Temperature at upper middle left side of furnace.', 'value_type' => 'numeric'],
            ['ComponentID' => 53, 'name' => 'Furnace Outlet Gas Temp (Right)', 'AreaID' => 16, 'desc' => 'Temperature of outlet gas on right side.', 'value_type' => 'numeric'], // Deduplicated "Furnace outlet gas temp(right side)"
            ['ComponentID' => 54, 'name' => 'Furnace Outlet Gas Temp (Left)', 'AreaID' => 16, 'desc' => 'Temperature of outlet gas on left side.', 'value_type' => 'numeric'], // Deduplicated "Furnace outlet gas temp(left side)"
            ['ComponentID' => 55, 'name' => 'U Valve A Ash Temperature', 'AreaID' => 16, 'desc' => 'Temperature at U valve A ash.', 'value_type' => 'numeric'],
            ['ComponentID' => 56, 'name' => 'U Valve B Ash Temperature', 'AreaID' => 16, 'desc' => 'Temperature at U valve B ash.', 'value_type' => 'numeric'],
            ['ComponentID' => 57, 'name' => 'Furnace Air Plenum Pressure (Right)', 'AreaID' => 16, 'desc' => 'Pressure in air plenum on right side.', 'value_type' => 'numeric'],
            ['ComponentID' => 58, 'name' => 'Furnace Air Plenum Pressure (Left)', 'AreaID' => 16, 'desc' => 'Pressure in air plenum on left side.', 'value_type' => 'numeric'],
            ['ComponentID' => 59, 'name' => 'Furnace Bed Pressure (Right)', 'AreaID' => 16, 'desc' => 'Pressure at furnace bed on right side.', 'value_type' => 'numeric'],
            ['ComponentID' => 60, 'name' => 'Furnace Bed Pressure (Left)', 'AreaID' => 16, 'desc' => 'Pressure at furnace bed on left side.', 'value_type' => 'numeric'],
            ['ComponentID' => 61, 'name' => 'Furnace Bed Density (Right)', 'AreaID' => 16, 'desc' => 'Density at furnace bed on right side.', 'value_type' => 'numeric'],
            ['ComponentID' => 62, 'name' => 'Furnace Bed Density (Left)', 'AreaID' => 16, 'desc' => 'Density at furnace bed on left side.', 'value_type' => 'numeric'],
            ['ComponentID' => 63, 'name' => 'Furnace Lower Pressure (Right)', 'AreaID' => 16, 'desc' => 'Pressure at lower furnace on right side.', 'value_type' => 'numeric'],
            ['ComponentID' => 64, 'name' => 'Furnace Lower Pressure (Left)', 'AreaID' => 16, 'desc' => 'Pressure at lower furnace on left side', 'value_type' => 'numeric'],
            ['ComponentID' => 65, 'name' => 'Furnace Middle Pressure (Right)', 'AreaID' => 16, 'desc' => 'Pressure at middle furnace on right side.', 'value_type' => 'numeric'],
            ['ComponentID' => 66, 'name' => 'Furnace Middle Pressure (Left)', 'AreaID' => 16, 'desc' => 'Pressure at middle furnace on left side.', 'value_type' => 'numeric'],
            ['ComponentID' => 67, 'name' => 'Furnace Upper Pressure (Right)', 'AreaID' => 16, 'desc' => 'Pressure at upper furnace on right side.', 'value_type' => 'numeric'],
            ['ComponentID' => 68, 'name' => 'Furnace Upper Pressure (Left)', 'AreaID' => 16, 'desc' => 'Pressure at upper furnace on left side.', 'value_type' => 'numeric'],
            ['ComponentID' => 69, 'name' => 'Furnace Right Side Differential Pressure', 'AreaID' => 16, 'desc' => 'Differential pressure on right side.', 'value_type' => 'numeric'],
            ['ComponentID' => 70, 'name' => 'Furnace Left Side Differential Pressure', 'AreaID' => 16, 'desc' => 'Differential pressure on left side.', 'value_type' => 'numeric'],
            ['ComponentID' => 71, 'name' => 'Steam', 'AreaID' => 16, 'desc' => 'General steam measurement.', 'value_type' => 'numeric'],
            ['ComponentID' => 72, 'name' => 'Desuperheater', 'AreaID' => 16, 'desc' => 'Desuperheater system status.', 'value_type' => 'numeric'],
            ['ComponentID' => 73, 'name' => 'Primary Flow', 'AreaID' => 16, 'desc' => 'Primary flow rate.', 'value_type' => 'numeric'],
            ['ComponentID' => 74, 'name' => 'Secondary Flow', 'AreaID' => 16, 'desc' => 'Secondary flow rate.', 'value_type' => 'numeric'],
            ['ComponentID' => 75, 'name' => 'Main Steam', 'AreaID' => 16, 'desc' => 'Main steam output.', 'value_type' => 'numeric'],
            ['ComponentID' => 76, 'name' => 'Combustion Chamber Lemo Temperature', 'AreaID' => 16, 'desc' => 'Temperature at combustion chamber lemo.', 'value_type' => 'numeric'],
            ['ComponentID' => 77, 'name' => 'Combustion Chamber Lemo Pressure', 'AreaID' => 16, 'desc' => 'Pressure at combustion chamber lemo.', 'value_type' => 'numeric'],
            ['ComponentID' => 78, 'name' => 'Combustion Chamber Lemo Flow', 'AreaID' => 16, 'desc' => 'Flow at combustion chamber lemo.', 'value_type' => 'numeric'],
            ['ComponentID' => 79, 'name' => 'Primary Secondary Flow', 'AreaID' => 16, 'desc' => 'Combined primary and secondary flow.', 'value_type' => 'numeric'],
            ['ComponentID' => 80, 'name' => 'Drum', 'AreaID' => 16, 'desc' => 'General drum measurement.', 'value_type' => 'numeric'],
            ['ComponentID' => 81, 'name' => 'Feed Water', 'AreaID' => 16, 'desc' => 'General feed water measurement.', 'value_type' => 'numeric'],
            ['ComponentID' => 82, 'name' => 'Desuperheating Water', 'AreaID' => 16, 'desc' => 'Desuperheating water flow.', 'value_type' => 'numeric'],
        ];

        foreach ($components as $component) {
            Component::create($component);
        }
    }
}