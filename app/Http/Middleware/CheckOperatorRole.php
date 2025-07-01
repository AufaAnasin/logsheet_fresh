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

            // Specific check for /logdata route
            if ($request->is('logdata') || $request->is('logdata/*')) {
                if (Auth::user()->role === 'Operator') {
                    return $next($request);
                }
                return $next($request); // Allow Admin/SuperAdmin to access /logdata
            }

            // No action for other routes, handled by other middleware
            return $next($request);
        }
        return redirect('/login')->with('error', 'Please log in to access this page.');
    }
}