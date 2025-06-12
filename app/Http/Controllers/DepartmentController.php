<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string',
        ]);

        // Create the department
        Department::create($validated);

        // Redirect back with a success message
        return redirect()->route('dashboard')->with('success', 'Department created successfully!');
    }
}