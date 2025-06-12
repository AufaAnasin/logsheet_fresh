<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    public function index()
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
                    'department_name' => $area->department ? $area->department->name : null,
                    'desc' => $area->desc,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:areas,name',
            'DepartmentID' => 'required|exists:departments,DepartmentID',
            'desc' => 'nullable|string',
        ]);

        Area::create($validated);

        return redirect()->route('dashboard')->with('success', 'Area created successfully!');
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('areas', 'name')->ignore($area->AreaID, 'AreaID'),
            ],
            'DepartmentID' => 'required|exists:departments,DepartmentID',
            'desc' => 'nullable|string',
        ]);

        $area->update($validated);

        return redirect()->route('dashboard')->with('success', 'Area updated successfully!')
            ->with([
                'areas' => Area::with('department')->get()->map(function ($area) {
                    return [
                        'AreaID' => $area->AreaID,
                        'name' => $area->name,
                        'DepartmentID' => $area->DepartmentID,
                        'department_name' => $area->department ? $area->department->name : null,
                        'desc' => $area->desc,
                    ];
                }),
            ]);
    }

    public function destroy(Area $area)
    {
        if ($area->components()->exists()) {
            return redirect()->route('dashboard')->withErrors([
                'delete' => 'Cannot delete area because it has associated components.',
            ]);
        }

        $area->delete();

        return redirect()->route('dashboard')->with('success', 'Area deleted successfully!')
            ->with([
                'areas' => Area::with('department')->get()->map(function ($area) {
                    return [
                        'AreaID' => $area->AreaID,
                        'name' => $area->name,
                        'DepartmentID' => $area->DepartmentID,
                        'department_name' => $area->department ? $area->department->name : null,
                        'desc' => $area->desc,
                    ];
                }),
            ]);
    }
}
