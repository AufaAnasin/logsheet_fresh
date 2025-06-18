<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogdataController extends Controller
{
    //
    public function logData(): Response
    {
        return Inertia::render('Logdata');
    }
}
