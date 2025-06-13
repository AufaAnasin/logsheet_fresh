<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AreaController extends Controller
{
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
            'name' => 'required|string|max:255|unique:areas,name,' . $area->AreaID . ',AreaID',
            'DepartmentID' => 'required|exists:departments,DepartmentID',
            'desc' => 'nullable|string',
        ]);

        $area->update($validated);

        return redirect()->route('dashboard')->with('success', 'Area updated successfully!');
    }

    public function destroy(Area $area)
    {
        $area->delete();

        return redirect()->route('dashboard')->with('success', 'Area deleted successfully!');
    }
}