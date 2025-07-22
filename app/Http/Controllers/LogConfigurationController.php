<?php

namespace App\Http\Controllers;

use App\Models\LogConfiguration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogConfigurationController extends Controller
{
    public function index()
    {
        $logConfigurations = LogConfiguration::with(['department', 'area'])->get();
        return Inertia::render('Dashboard', [
            'logConfigurations' => $logConfigurations,
            // ... other props
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule' => 'required|json',
            'frequency_type' => 'required|in:hourly,twice_daily,daily',
            'DepartmentID' => 'required|exists:departments,DepartmentID',
            'AreaID' => 'required|exists:areas,AreaID',
        ]);

        $logConfiguration = LogConfiguration::create($validated);

        return redirect()->back()->with('flash', [
            'success' => 'Log configuration created successfully.'
        ]);
    }

    public function update(Request $request, LogConfiguration $logConfiguration)
    {
        $validated = $request->validate([
            'schedule' => 'required|json',
            'frequency_type' => 'required|in:hourly,twice_daily,daily',
            'DepartmentID' => 'required|exists:departments,DepartmentID',
            'AreaID' => 'required|exists:areas,AreaID',
        ]);

        $logConfiguration->update($validated);

        return redirect()->back()->with('flash', [
            'success' => 'Log configuration updated successfully.'
        ]);
    }
}
