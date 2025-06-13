<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Department;
use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComponentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'AreaID' => 'required|integer|exists:areas,AreaID', // Ensure AreaID is not null
            'desc' => 'nullable|string', // Text type allows longer strings
        ]);

        Component::create([
            'name' => $request->input('name'),
            'AreaID' => $request->input('AreaID'),
            'desc' => $request->input('desc'),
        ]);

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
                    'area_name' => $component->area ? $component->area->name : null, // Match frontend interface
                    'desc' => $component->desc,
                ];
            }),
        ]);
    }
}