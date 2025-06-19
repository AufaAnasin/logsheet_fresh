<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Department;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Database\QueryException;
use Inertia\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ComponentController extends Controller
{
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Component store request:', $request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'AreaID' => 'required|integer|exists:areas,AreaID',
            'desc' => 'nullable|string',
        ]);

        try {
            $component = Component::create([
                'name' => $validated['name'],
                'AreaID' => $validated['AreaID'],
                'desc' => $validated['desc'] ?? null,
            ]);
            \Illuminate\Support\Facades\Log::info('Component created:', ['ComponentID' => $component->ComponentID]);
            return redirect()->route('dashboard')->with('success', 'Component created successfully!');
        } catch (QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create component:', ['error' => $e->getMessage(), 'data' => $validated]);
            return redirect()->route('dashboard')->withErrors(['create' => 'Failed to create component. Please try again.']);
        }
    }

    public function update(Request $request, Component $component)
    {
        \Illuminate\Support\Facades\Log::info('Component update request:', ['id' => $component->ComponentID, 'data' => $request->all()]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'AreaID' => 'required|integer|exists:areas,AreaID',
            'desc' => 'nullable|string',
        ]);

        try {
            $component->update([
                'name' => $validated['name'],
                'AreaID' => $validated['AreaID'],
                'desc' => $validated['desc'] ?? null,
            ]);
            \Illuminate\Support\Facades\Log::info('Component updated:', ['ComponentID' => $component->ComponentID]);
            return redirect()->route('dashboard')->with('success', 'Component updated successfully!');
        } catch (QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Failed to update component:', ['id' => $component->ComponentID, 'error' => $e->getMessage()]);
            return redirect()->route('dashboard')->withErrors(['update' => 'Failed to update component. Please try again.']);
        }
    }

    public function destroy(Component $component)
    {
        \Illuminate\Support\Facades\Log::info('Component delete request:', ['id' => $component->ComponentID]);
        try {
            $component->delete();
            \Illuminate\Support\Facades\Log::info('Component deleted:', ['ComponentID' => $component->ComponentID]);
            return redirect()->route('dashboard')->with('success', 'Component deleted successfully!');
        } catch (QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Failed to delete component:', ['id' => $component->ComponentID, 'error' => $e->getMessage()]);
            return redirect()->route('dashboard')->withErrors(['delete' => 'Cannot delete component because it has associated log data.']);
        }
    }

    public function logData(): Response
    {
        $areas = Area::with('department')->get()->map(function ($area) {
            return [
                'AreaID' => $area->AreaID,
                'name' => $area->name,
                'DepartmentID' => $area->DepartmentID,
                'department_name' => $area->department ? $area->department->name : 'Unknown',
                'desc' => $area->desc ?? null,
            ];
        });

        $components = Component::with('area')->get()->map(function ($component) {
            return [
                'ComponentID' => $component->ComponentID,
                'name' => $component->name,
                'AreaID' => $component->AreaID,
                'area_name' => $component->area ? $component->area->name : 'Unknown',
                'desc' => $component->desc ?? null,
            ];
        });

        Log::info('Areas and Components fetched for Logdata:', ['areas' => $areas, 'components' => $components]);

        return Inertia::render('Logdata', [
            'areas' => $areas,
            'components' => $components,
        ]);
    }

public function storeLog(Request $request)
{
    $validated = $request->validate([
        'area_id' => 'required|exists:areas,AreaID',
        'logs' => 'required|array',
        'logs.*.component_id' => 'required|exists:components,ComponentID',
        'logs.*.log_message' => 'required|string',
    ]);

    try {
        foreach ($validated['logs'] as $log) {
            \App\Models\LogData::create([
                'ComponentID' => $log['component_id'],
                'OperatorID' => Auth::user()->id,
                'LogValue' => $log['log_message'], // Match LogData model field
                'LogTimestamp' => now(), // Match LogData model field
                'Notes' => $log['log_message'], // Use log_message as Notes for now
            ]);
        }

        Log::info('Log data stored:', ['area_id' => $validated['area_id'], 'logs' => $validated['logs']]);
        return redirect()->route('logdata')->with('success', 'Log data submitted successfully!');
    } catch (\Exception $e) {
        Log::error('Failed to store log data:', ['error' => $e->getMessage(), 'data' => $validated]);
        return redirect()->back()->withErrors(['error' => 'Failed to store log data. Please try again.'])->withInput();
    }
}
}
