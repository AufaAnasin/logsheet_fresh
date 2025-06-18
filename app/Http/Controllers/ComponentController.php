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
                'desc' => $validated['desc'],
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
                'desc' => $validated['desc'],
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
}