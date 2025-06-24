<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return inertia('UserList', [
            'users' => User::with('department')->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department_name' => $user->department?->name ?? 'N/A',
                ];
            }),
            'departments' => Department::all()->map(function ($department) {
                return [
                    'DepartmentID' => $department->DepartmentID,
                    'name' => $department->name,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        // Validate input with stricter rules
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'role' => 'required|in:SuperUser,DepartmentAdmin,Operator',
            'department_id' => [
                'required',
                'integer',
                'exists:departments,DepartmentID',
            ],
        ]);

        // Double-check email uniqueness for robustness
        if (User::where('email', $validated['email'])->exists()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => 'The email address is already registered.']);
        }

        try {
            User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make('Logsheet123#'),
                'role' => $validated['role'],
                'DepartmentID' => $validated['department_id'],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create user. Please try again.']);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }
}