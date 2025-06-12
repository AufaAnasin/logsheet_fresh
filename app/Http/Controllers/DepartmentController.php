<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'desc' => 'nullable|string',
        ]);

        // Create the department
        Department::create($validated);

        // Redirect back with a success message
        return redirect()->route('dashboard')->with('success', 'Department created successfully!');
    }
}