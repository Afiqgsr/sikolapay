<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $guardian = Auth::user()->guardian;

        $students = $guardian->students()->with([
            'classRoom',
            'bills',
        ])->get();

        return view('guardian.dashboard', compact(
            'guardian',
            'students'
        ));
    }
}