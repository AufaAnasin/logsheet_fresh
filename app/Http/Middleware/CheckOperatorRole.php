<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckOperatorRole
{
    public function handle(Request $request, Closure $next)
    {
        ob_start(); // Prevent headers already sent error
        if (Auth::check()) {
            Log::debug('Role Check', ['role' => Auth::user()->role ?? 'null', 'url' => $request->url()]);
            $allowedRoles = ['Operator', 'DepartmentAdmin', 'SuperUser'];
            if (in_array(Auth::user()->role, $allowedRoles)) {
                return $next($request);
            }
            return redirect('/dashboard')->with('error', 'You do not have permission to access the Logsheet page.');
        }
        return redirect('/login')->with('error', 'Please log in to access the Logsheet page.');
    }
}