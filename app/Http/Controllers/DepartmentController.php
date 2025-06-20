<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Area;
use App\Models\Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index()
    {
        $data = [
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
            'userRole' => Auth::user() ? Auth::user()->role : null,
        ];
        return Inertia::render('Dashboard', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
        ]);

        Department::create($validated);

        return redirect()->route('dashboard');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
        ]);

        $department->update($validated);

        return redirect()->route('dashboard');
    }

    public function destroy(Department $department)
    {
        try {
            $department->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('dashboard')->withErrors(['delete' => 'Cannot delete department because it has associated areas.']);
        }

        return redirect()->route('dashboard');
    }
}