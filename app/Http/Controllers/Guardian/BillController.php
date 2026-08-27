<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Support\Facades\Auth;

class BillController extends Controller
{
    
    public function index()
    {
        $guardian = Auth::user()->guardian;

        $students = $guardian->students()
            ->with([
                'bills.latestPayment.latestVerification',
            ])
            ->get();

        return view('guardian.bills.index', compact('students'));
    }

    public function show($id)
    {
        $guardian = Auth::user()->guardian;

        $bill = Bill::where('id', $id)
            ->whereHas('student', function ($query) use ($guardian) {
                $query->where('guardian_id', $guardian->id);
            })
            ->with('student')
            ->firstOrFail();

        return view('guardian.bills.show', compact('bill'));
    }
}