<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Component;
use App\Models\LogData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class LogdataController extends Controller
{
    public function logData(): Response
    {
        $user = Auth::user();
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

        $componentQuery = Component::with('area.department');
        if ($user->role !== 'SuperUser') {
            $componentQuery->whereHas('area', function ($q) use ($user) {
                $q->where('DepartmentID', $user->DepartmentID);
            });
        }

        $components = $componentQuery->get()->map(function ($component) {
            return [
                'ComponentID' => $component->ComponentID,
                'name' => $component->name,
                'AreaID' => $component->AreaID,
                'area_name' => $component->area ? $component->area->name : 'No Area',
                'department_name' => $component->area && $component->area->department ? $component->area->department->name : 'No Department',
                'desc' => $component->desc,
            ];
        });

        Log::info('logData response for visualization', [
            'user_id' => $user->id,
            'areas_count' => $areas->count(),
            'components_count' => $components->count(),
            'userRole' => $user->role,
        ]);

        return Inertia::render('DataVisualization', [
            'areas' => $areas,
            'components' => $components,
            'userRole' => $user->role,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate request
        $validated = $request->validate([
            'area_id' => 'required|exists:areas,AreaID',
            'logs' => 'required|array|min:1',
            'logs.*.component_id' => 'required|exists:components,ComponentID',
            'logs.*.log_message' => 'required|string|regex:/^-?\d*\.?\d*$/',
            'logs.*.notes' => 'nullable|string|max:255',
        ]);

        // Verify area access
        $area = Area::find($validated['area_id']);
        if ($user->role !== 'SuperUser' && $area->DepartmentID !== $user->DepartmentID) {
            Log::warning("Unauthorized attempt to log data for AreaID: {$validated['area_id']}", ['user_id' => $user->id]);
            return redirect()->route('logdata')->with('flash', ['error' => 'Unauthorized access to this area.']);
        }

        // Create log entries
        $logsCreated = 0;
        foreach ($validated['logs'] as $log) {
            $component = Component::find($log['component_id']);
            if ($component && $component->AreaID === $validated['area_id']) {
                LogData::create([
                    'ComponentID' => $log['component_id'],
                    'OperatorID' => $user->id,
                    'LogValue' => $log['log_message'],
                    'LogTimestamp' => now(),
                    'Notes' => $log['notes'],
                ]);
                $logsCreated++;
            }
        }

        Log::info("Log data stored for AreaID: {$validated['area_id']}", [
            'user_id' => $user->id,
            'logs_count' => $logsCreated,
            'logs' => $validated['logs'],
        ]);

        if ($logsCreated === 0) {
            return redirect()->route('logdata')->with('flash', ['error' => 'No valid logs were created.']);
        }

        return redirect()->route('logdata')->with('flash', ['success' => 'Log data submitted successfully.']);
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

        Log::info('Analytics response', [
            'user_id' => $user->id,
            'areas_count' => $areas->count(),
            'areas' => $areas->toArray(),
        ]);

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
            Log::warning("Area not found for ID: {$areaId}", ['user_id' => $user->id]);
            return Inertia::render('Components', [
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
            Log::warning("Unauthorized access to area ID: {$areaId} by user ID: {$user->id}");
            return Inertia::render('Components', [
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

        Log::info("Components insights for AreaID: {$areaId}", [
            'user_id' => $user->id,
            'components_count' => $components->count(),
            'components' => $components->toArray(),
            'area' => [
                'AreaID' => $area->AreaID,
                'name' => $area->name,
                'DepartmentID' => $area->DepartmentID,
            ],
        ]);

        return Inertia::render('Components', [
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
            Log::warning("Component not found for ID: {$componentId}", ['user_id' => $user->id]);
            return Inertia::render('TableAndVisualize', [
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
            return Inertia::render('TableAndVisualize', [
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
                'LogTimestamp' => $log->LogTimestamp ? $log->LogTimestamp->format('Y-m-d H:i:s') : 'N/A',
                'OperatorName' => $log->operator ? $log->operator->name : 'Unknown',
                'Notes' => $log->Notes,
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
            'user_id' => $user->id,
            'chart_data' => $chartDataArray,
            'logs_count' => $logs->count(),
            'filter' => $filter,
            'query_params' => $request->query(),
        ]);

        return Inertia::render('TableAndVisualize', [
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
                        'type' => 'line',
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

    public function generateAreaReport(Request $request, $areaId)
    {
        $user = $request->user();
        $area = Area::with('department')->find($areaId);

        if (!$area) {
            Log::warning("Area not found for ID: {$areaId}", ['user_id' => $user->id]);
            return redirect()->route('components', ['areaId' => $areaId])
                ->with('flash', ['error' => 'Area not found.']);
        }

        if ($user->role !== 'SuperUser' && $area->DepartmentID !== $user->DepartmentID) {
            Log::warning("Unauthorized access to area ID: {$areaId} by user ID: {$user->id}");
            return redirect()->route('components', ['areaId' => $areaId])
                ->with('flash', ['error' => 'Unauthorized access to this area.']);
        }

        $components = Component::with(['logs.operator'])
            ->where('AreaID', $area->AreaID)
            ->get();

        Log::info("Generating area PDF report for AreaID: {$areaId}", [
            'user_id' => $user->id,
            'components_count' => $components->count(),
            'area' => [
                'AreaID' => $area->AreaID,
                'name' => $area->name,
                'DepartmentID' => $area->DepartmentID,
            ],
        ]);

        $pdf = Pdf::loadView('reports.logdata_report', [
            'area' => $area,
            'components' => $components,
            'user' => $user,
            'notes' => $user->notes,
        ]);

        return $pdf->download('logdata_report_area_' . $area->AreaID . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function visualizationData(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'charts' => 'required|array',
            'charts.*.id' => 'required|integer',
            'charts.*.component_ids' => 'required|array|max:10',
            'charts.*.component_ids.*' => 'required|exists:components,ComponentID',
            'charts.*.area_ids' => 'required|array|max:5',
            'charts.*.area_ids.*' => 'required|exists:areas,AreaID',
            'charts.*.date_range' => 'required|array|size:2',
            'charts.*.date_range.*' => 'required|date',
        ]);
    
        // Verify component and area access
        $componentIds = collect($validated['charts'])->pluck('component_ids')->flatten()->unique();
        $areaIds = collect($validated['charts'])->pluck('area_ids')->flatten()->unique();
        $components = Component::whereIn('ComponentID', $componentIds)
            ->with('area.department')
            ->get();
        $areas = Area::whereIn('AreaID', $areaIds)->get();
    
        if ($components->count() !== $componentIds->count() || $areas->count() !== $areaIds->count()) {
            Log::warning("Invalid component or area IDs provided", [
                'user_id' => $user->id,
                'component_ids' => $componentIds->toArray(),
                'area_ids' => $areaIds->toArray(),
            ]);
            return response()->json(['error' => 'One or more component or area IDs are invalid.'], 422);
        }
    
        foreach ($components as $component) {
            if ($user->role !== 'SuperUser' && $component->area->DepartmentID !== $user->DepartmentID) {
                Log::warning("Unauthorized access to component ID: {$component->ComponentID} by user ID: {$user->id}");
                return response()->json(['error' => 'Unauthorized access to one or more components.'], 403);
            }
        }
    
        $results = [];
        foreach ($validated['charts'] as $chart) {
            $startDate = $chart['date_range'][0];
            $endDate = Carbon::parse($chart['date_range'][1])->endOfDay()->toDateTimeString();
    
            $query = LogData::whereIn('ComponentID', $chart['component_ids'])
                ->whereHas('component', function ($q) use ($chart, $user) {
                    $q->whereIn('AreaID', $chart['area_ids']);
                    if ($user->role !== 'SuperUser') {
                        $q->whereHas('area', fn($q) => $q->where('DepartmentID', $user->DepartmentID));
                    }
                })
                ->whereBetween('LogTimestamp', [$startDate, $endDate])
                ->join('components', 'log_data.ComponentID', '=', 'components.ComponentID')
                ->select(
                    'components.ComponentID as component_id',
                    'components.name as component_name',
                    'log_data.LogValue as value',
                    'log_data.LogTimestamp as timestamp',
                    'log_data.Notes as notes'
                )
                ->orderBy('log_data.LogTimestamp');
    
            $data = $query->get()->map(function ($item) {
                return [
                    'component_id' => $item->component_id,
                    'component_name' => $item->component_name,
                    'timestamp' => $item->timestamp,
                    'value' => (float) $item->value,
                    'notes' => $item->notes,
                ];
            })->toArray();
    
            if (empty($data)) {
                Log::info("No log data found for chart", [
                    'chart_id' => $chart['id'],
                    'component_ids' => $chart['component_ids'],
                    'area_ids' => $chart['area_ids'],
                    'date_range' => [$startDate, $endDate],
                ]);
            }
    
            $results[$chart['id']] = $data;
        }
    
        Log::info("Visualization data fetched", [
            'user_id' => $user->id,
            'charts_count' => count($validated['charts']),
            'component_ids' => $componentIds->toArray(),
            'area_ids' => $areaIds->toArray(),
        ]);
    
        return response()->json($results);
    }
}