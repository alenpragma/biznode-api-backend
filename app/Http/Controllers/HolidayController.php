<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('id')->get();
        return view('admin.pages.holidays.index', compact('holidays'));
    }

    public function toggleStatus(Request $request)
    {
        $holiday = Holiday::findOrFail($request->id);
        $holiday->status = $request->status;
        $holiday->save();

        return response()->json(['success' => true, 'message' => 'Holiday status updated.']);
    }
}
