<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
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
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'desc' => 'nullable|string',
        ]);

        Department::create($validated);

        return redirect()->route('dashboard')->with('success', 'Department created successfully!')
            ->with([
                'departments' => Department::all()->map(function ($department) {
                    return [
                        'DepartmentID' => $department->DepartmentID,
                        'name' => $department->name,
                        'desc' => $department->desc,
                    ];
                }),
            ]);
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('dashboard')->with('success', 'Department deleted successfully!')
            ->with([
                'departments' => Department::all()->map(function ($department) {
                    return [
                        'DepartmentID' => $department->DepartmentID,
                        'name' => $department->name,
                        'desc' => $department->desc,
                    ];
                }),
            ]);
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->DepartmentID, 'DepartmentID'),
            ],
            'desc' => 'nullable|string',
        ]);

        $department->update($validated);

        return redirect()->route('dashboard')->with('success', 'Department updated successfully!')
            ->with([
                'departments' => Department::all()->map(function ($department) {
                    return [
                        'DepartmentID' => $department->DepartmentID,
                        'name' => $department->name,
                        'desc' => $department->desc,
                    ];
                }),
            ]);
    }
}
