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
        // Validate input
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

        // Restrict DepartmentAdmin to only create Operator users in their own department
        if ($request->user()->role === 'DepartmentAdmin') {
            if ($validated['role'] !== 'Operator') {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['role' => 'Admins can only create users with the Operator role.']);
            }
            if ($validated['department_id'] !== $request->user()->DepartmentID) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['department_id' => 'Admins can only create users in their own department.']);
            }
        }

        // Double-check email uniqueness
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

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
            'role' => 'required|in:SuperUser,DepartmentAdmin,Operator',
            'department_id' => [
                'required',
                'integer',
                'exists:departments,DepartmentID',
            ],
        ]);

        try {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'DepartmentID' => $validated['department_id'],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update user. Please try again.']);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Failed to delete user. Please try again.']);
        }

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}