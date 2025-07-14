<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LogData;
use Carbon\Carbon;

class LogDataSeeder extends Seeder
{
    public function run()
    {
        $components = [14, 15, 16, 17, 18, 19, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59];
        $operators = [1, 16, 17, 19]; // Existing OperatorIDs
        $startDate = Carbon::create(2025, 7, 11, 0, 0, 0, 'Asia/Jakarta'); // 12:00 AM WIB, July 11, 2025
        $endDate = Carbon::create(2025, 7, 11, 23, 59, 0, 'Asia/Jakarta'); // 11:59 PM WIB, July 11, 2025

        // Clear existing data for AreaID = 16 components to avoid duplicates
        LogData::whereIn('ComponentID', $components)->delete();

        foreach ($components as $componentId) {
            for ($hour = 0; $hour < 24; $hour++) {
                $logTimestamp = $startDate->copy()->addHours($hour)->addMinutes(rand(0, 59)); // Random minute within the hour
                $logValue = $this->generateLogValue($componentId);

                LogData::create([
                    'ComponentID' => $componentId,
                    'OperatorID' => $operators[array_rand($operators)],
                    'LogValue' => $logValue,
                    'LogTimestamp' => $logTimestamp,
                    'Notes' => $logValue, // Mirror LogValue for simplicity
                    'created_at' => $logTimestamp,
                    'updated_at' => $logTimestamp,
                ]);
            }
        }
    }

    private function generateLogValue($componentId)
    {
        $valueRanges = [
            14 => [0, 1000], 15 => [0, 20], 16 => [0, 100], 17 => [0, 1000], 18 => [0, 1000],
            19 => [0, 100], 25 => [0, 500], 26 => [0, 10000], 27 => [0, 500], 28 => [0, 500],
            29 => [0, 20], 30 => [0, 500], 31 => [0, 500], 32 => [0, 500], 33 => [0, 20],
            34 => [0, 500], 35 => [0, 10000], 36 => [0, 1000], 37 => [0, 1000], 38 => [0, 1000],
            39 => [0, 1000], 40 => [0, 1000], 41 => [0, 1000], 42 => [0, 1000], 43 => [0, 1000],
            44 => [0, 50], 45 => [0, 50], 46 => [0, 50], 47 => [0, 50], 48 => [0, 50],
            49 => [0, 50], 50 => [0, 100], 51 => [0, 100], 52 => [0, 50], 53 => [0, 50],
            54 => [0, 50], 55 => [0, 50], 56 => [0, 50], 57 => [0, 50], 58 => [0, 50],
            59 => [0, 50],
        ];

        $range = $valueRanges[$componentId] ?? [0, 100];
        return rand($range[0], $range[1]);
    }
}