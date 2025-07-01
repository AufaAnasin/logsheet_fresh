<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckNonOperatorRole
{
    public function handle(Request $request, Closure $next)
    {
        ob_start();
        if (Auth::check()) {
            Log::debug('Non-Operator Check', ['role' => Auth::user()->role ?? 'null', 'url' => $request->url()]);
            if (Auth::user()->role !== 'Operator' || ($request->is('logdata') || $request->is('logdata/*'))) {
                return $next($request);
            }
            return redirect('/logdata')->with('error', 'Operators can only access the Logsheet page.');
        }
        return redirect('/login')->with('error', 'Please log in to access this page.');
    }
}