<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Area;
use App\Models\Component;
use App\Models\LogData;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
                'created_at' => $area->created_at ? $area->created_at->format('Y-m-d H:i:s') : null,
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
            return Inertia::render('ComponentsInsight', [
                'title' => 'Visualize and Table',
                'description' => 'This page allows you to visualize and table all log data.',
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
            return Inertia::render('ComponentsInsight', [
                'title' => 'Visualize and Table',
                'description' => 'This page allows you to visualize and table all log data.',
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

        // Build query for logs
        $logsQuery = $component->logs()->with('operator');

        // Apply date filters
        $filter = $request->query('filter');
        if ($filter === 'yearly') {
            $year = $request->query('year', Carbon::now()->year);
            $logsQuery->whereYear('LogTimestamp', $year);
        } elseif ($filter === 'monthly') {
            $year = $request->query('year', Carbon::now()->year);
            $month = $request->query('month', Carbon::now()->month);
            $logsQuery->whereYear('LogTimestamp', $year)->whereMonth('LogTimestamp', $month);
        } elseif ($filter === 'daily') {
            $day = $request->query('day', Carbon::now()->toDateString());
            $logsQuery->whereDate('LogTimestamp', $day);
        }

        // Fetch logs
        $logs = $logsQuery->get()->map(function ($log) {
            return [
                'LogID' => $log->LogID,
                'ComponentID' => $log->ComponentID,
                'LogValue' => $log->LogValue,
                'LogTimestamp' => $log->LogTimestamp ? $log->LogTimestamp->format('Y-m-d H:i:s') : null,
                'OperatorName' => $log->operator ? $log->operator->name : 'Unknown',
            ];
        })->sortByDesc('LogTimestamp')->values();

        // Fetch chart data
        $chartQuery = $component->logs()->whereNotNull('LogValue')->whereNotNull('LogTimestamp');
        if ($filter === 'yearly') {
            $year = $request->query('year', Carbon::now()->year);
            $chartQuery->whereYear('LogTimestamp', $year);
        } elseif ($filter === 'monthly') {
            $year = $request->query('year', Carbon::now()->year);
            $month = $request->query('month', Carbon::now()->month);
            $chartQuery->whereYear('LogTimestamp', $year)->whereMonth('LogTimestamp', $month);
        } elseif ($filter === 'daily') {
            $day = $request->query('day', Carbon::now()->toDateString());
            $chartQuery->whereDate('LogTimestamp', $day);
        }

        $chartData = $chartQuery->get()->map(function ($log) {
            return [
                'date' => $log->LogTimestamp ? $log->LogTimestamp->format('Y-m-d H:i:s') : 'N/A',
                'value' => $log->LogValue,
            ];
        })->sortBy('date')->values();

        $chartDataArray = $chartData->isEmpty() ? [['date' => 'No Data', 'value' => 0]] : $chartData->all();

        Log::info("Chart data for component ID: {$componentId}", [
            'chart_data' => $chartDataArray,
            'logs_count' => $logs->count(),
            'filter' => $filter,
            'query_params' => $request->query(),
        ]);

        return Inertia::render('ComponentsInsight', [
            'title' => 'Visualize and Table',
            'description' => 'This page allows you to visualize and table all log data.',
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
                    'data' => array_column($chartDataArray, 'date'),
                ],
                'series' => [
                    [
                        'name' => $component->name,
                        'type' => 'line', // Default to line chart
                        'data' => array_column($chartDataArray, 'value'),
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