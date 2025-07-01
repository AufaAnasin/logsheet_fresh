<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Area;
use App\Models\Component;
use App\Models\LogData;
use Illuminate\Support\Facades\Log;

class LogdataController extends Controller
{
    public function logData(): Response
    {
        return Inertia::render('Logdata');
    }

    public function analytics(Request $request): Response
    {
        $user = $request->user();
        $query = Area::with('department');

        if ($user->role !== 'SuperUser') {
            $query->where('DepartmentID', $user->DepartmentID);
        }

        $areas = $query->get()->map(function ($area) {
            return [
                'AreaID' => $area->AreaID,
                'name' => $area->name,
                'DepartmentID' => $area->DepartmentID,
                'department_name' => $area->department ? $area->department->name : 'No Department',
                'desc' => $area->desc,
            ];
        });

        return Inertia::render('Analytics', [
            'areas' => $areas,
            'user' => [
                'DepartmentID' => $user->DepartmentID,
                'role' => $user->role,
            ],
        ]);
    }

    public function componentsInsights(Request $request, $areaId): Response
    {
        $user = $request->user();
        $area = Area::find($areaId);

        if (!$area) {
            return Inertia::render('ComponentsInsight', [
                'components' => [],
                'area' => null,
                'user' => [
                    'DepartmentID' => $user->DepartmentID,
                    'role' => $user->role,
                ],
                'flash' => ['error' => 'Area not found.'],
            ]);
        }

        if ($user->role !== 'SuperUser' && $area->DepartmentID !== $user->DepartmentID) {
            return Inertia::render('ComponentsInsight', [
                'components' => [],
                'area' => null,
                'user' => [
                    'DepartmentID' => $user->DepartmentID,
                    'role' => $user->role,
                ],
                'flash' => ['error' => 'Unauthorized access to this area.'],
            ]);
        }

        $components = Component::where('AreaID', $area->AreaID)
            ->get()
            ->map(function ($component) use ($area) {
                return [
                    'ComponentID' => $component->ComponentID,
                    'name' => $component->name,
                    'AreaID' => $component->AreaID,
                    'area_name' => $area->name,
                    'desc' => $component->desc,
                ];
            });

        return Inertia::render('ComponentsInsight', [
            'components' => $components,
            'area' => [
                'AreaID' => $area->AreaID,
                'name' => $area->name,
                'DepartmentID' => $area->DepartmentID,
            ],
            'user' => [
                'DepartmentID' => $user->DepartmentID,
                'role' => $user->role,
            ],
        ]);
    }

    public function visualizeandtable(Request $request, $componentId): Response
    {
        $user = $request->user();
        $component = Component::with(['area', 'logs.operator'])->find($componentId);

        if (!$component) {
            Log::warning("Component not found for ID: {$componentId}");
            return Inertia::render('TableAndVisualize', [
                'title' => 'Visualize and Table',
                'description' => 'This page allows you to visualize and table data.',
                'component' => null,
                'logs' => [],
                'chart_data' => [
                    'xAxis' => ['type' => 'category', 'data' => []],
                    'series' => [],
                ],
                'user' => [
                    'DepartmentID' => $user->DepartmentID,
                    'role' => $user->role,
                ],
                'flash' => ['error' => 'Component not found.'],
            ]);
        }

        if ($user->role !== 'SuperUser' && $component->area->DepartmentID !== $user->DepartmentID) {
            Log::warning("Unauthorized access to component ID: {$componentId} by user ID: {$user->id}");
            return Inertia::render('TableAndVisualize', [
                'title' => 'Visualize and Table',
                'description' => 'This page allows you to visualize and table data.',
                'component' => null,
                'logs' => [],
                'chart_data' => [
                    'xAxis' => ['type' => 'category', 'data' => []],
                    'series' => [],
                ],
                'user' => [
                    'DepartmentID' => $user->DepartmentID,
                    'role' => $user->role,
                ],
                'flash' => ['error' => 'Unauthorized access to this component.'],
            ]);
        }

        $logs = $component->logs->map(function ($log) {
            return [
                'LogID' => $log->LogID,
                'ComponentID' => $log->ComponentID,
                'LogValue' => $log->LogValue,
                'LogTimestamp' => $log->LogTimestamp ? $log->LogTimestamp->format('Y-m-d H:i:s') : 'N/A',
                'Notes' => $log->Notes,
                'OperatorName' => $log->operator ? $log->operator->name : 'Unknown',
            ];
        })->sortByDesc('LogTimestamp')->values();

        $chartData = $component->logs->filter(function ($log) {
            return $log->LogValue !== null && $log->LogTimestamp !== null;
        })->groupBy(function ($log) {
            return $log->LogTimestamp->format('Y-m-d');
        })->map(function ($logs, $date) {
            return [
                'date' => $date,
                'value' => $logs->sum('LogValue'), // Sum LogValue per day
            ];
        })->sortBy('date')->values();

        Log::info("Chart data for component ID: {$componentId}", [
            'chart_data' => $chartData->toArray(),
            'logs_count' => $logs->count(),
        ]);

        return Inertia::render('TableAndVisualize', [
            'title' => 'Visualize and Table',
            'description' => 'This page allows you to visualize and table data.',
            'component' => [
                'ComponentID' => $component->ComponentID,
                'name' => $component->name,
                'AreaID' => $component->AreaID,
                'area_name' => $component->area->name,
                'desc' => $component->desc,
            ],
            'logs' => $logs,
            'chart_data' => [
                'xAxis' => [
                    'type' => 'category',
                    'data' => $chartData->pluck('date')->toArray(),
                ],
                'series' => [
                    [
                        'name' => $component->name,
                        'type' => 'line',
                        'data' => $chartData->pluck('value')->toArray(),
                        'lineStyle' => [
                            'color' => '#3b82f6',
                        ],
                        'smooth' => true,
                        'symbol' => 'circle',
                        'symbolSize' => 8,
                    ],
                ],
            ],
            'user' => [
                'DepartmentID' => $user->DepartmentID,
                'role' => $user->role,
            ],
        ]);
    }
}