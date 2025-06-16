<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Department;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Database\QueryException;

class ComponentController extends Controller
{
    public function store(Request $request)
    {
        \Log::info('Component store request:', $request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'AreaID' => 'required|integer|exists:areas,AreaID',
            'desc' => 'nullable|string',
        ]);

        try {
            $component = Component::create([
                'name' => $validated['name'],
                'AreaID' => $validated['AreaID'],
                'desc' => $validated['desc'],
            ]);
            \Log::info('Component created:', ['ComponentID' => $component->ComponentID]);
        } catch (QueryException $e) {
            \Log::error('Failed to create component:', ['error' => $e->getMessage(), 'data' => $validated]);
            return Inertia::render('Dashboard', $this->renderDashboard()->getData())
                ->withErrors(['create' => 'Failed to create component. Please try again.']);
        }

        return $this->renderDashboard();
    }

    public function update(Request $request, Component $component)
    {
        \Log::info('Component update request:', ['id' => $component->ComponentID, 'data' => $request->all()]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'AreaID' => 'required|integer|exists:areas,AreaID',
            'desc' => 'nullable|string',
        ]);

        try {
            $component->update([
                'name' => $validated['name'],
                'AreaID' => $validated['AreaID'],
                'desc' => $validated['desc'],
            ]);
            \Log::info('Component updated:', ['ComponentID' => $component->ComponentID]);
        } catch (QueryException $e) {
            \Log::error('Failed to update component:', ['id' => $component->ComponentID, 'error' => $e->getMessage()]);
            return Inertia::render('Dashboard', $this->renderDashboard()->getData())
                ->withErrors(['update' => 'Failed to update component. Please try again.']);
        }

        return $this->renderDashboard();
    }

    public function destroy(Component $component)
    {
        \Log::info('Component delete request:', ['id' => $component->ComponentID]);
        try {
            $component->delete();
            \Log::info('Component deleted:', ['ComponentID' => $component->ComponentID]);
        } catch (QueryException $e) {
            \Log::error('Failed to delete component:', ['id' => $component->ComponentID, 'error' => $e->getMessage()]);
            return Inertia::render('Dashboard', $this->renderDashboard()->getData())
                ->withErrors(['delete' => 'Cannot delete component because it has associated log data.']);
        }

        return $this->renderDashboard();
    }

    protected function renderDashboard()
    {
        return Inertia::render('Dashboard', [
            'departments' => Department::all()->map(function ($department) {
                return [
                    'DepartmentID' => $department->DepartmentID,
                    'name' => $department->name,
                    'desc' => $department->desc,
                ];
            }),
            'areas' => Area::with('department')->get()->map(function ($area) {
                return [
                    'AreaID' => $area->AreaID,
                    'name' => $area->name,
                    'DepartmentID' => $area->DepartmentID,
                    'department_name' => $area->department ? $area->department->name : 'No Department',
                    'desc' => $area->desc,
                ];
            }),
            'components' => Component::with('area')->get()->map(function ($component) {
                return [
                    'ComponentID' => $component->ComponentID,
                    'name' => $component->name,
                    'AreaID' => $component->AreaID,
                    'area_name' => $component->area ? $component->area->name : null,
                    'desc' => $component->desc,
                ];
            }),
        ]);
    }
}