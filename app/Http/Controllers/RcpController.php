<?php

namespace App\Http\Controllers;

use App\Models\WorkShift;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class RcpController extends Controller
{
    // Widok panelu ewidencji RCP
    public function index(Request $request)
    {
        $activeShifts = WorkShift::with('user')
            ->whereIn('status', ['working', 'on_break'])
            ->get();

        $query = WorkShift::with('user')->whereNotNull('clock_out');

        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('clock_in', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('clock_in', '<=', $request->date_to);
        }

        $historyShifts = $query->latest('clock_in')->paginate(15)->withQueryString();

        $users = User::select('id', 'name', 'role')->get();

        // Statystyki dzisiejsze
        $todayShifts = WorkShift::whereDate('clock_in', Carbon::today())->get();
        $totalMinutesToday = $todayShifts->sum(function ($shift) {
            $end = $shift->clock_out ?? Carbon::now();
            return max(0, $shift->clock_in->diffInMinutes($end) - $shift->break_minutes);
        });

        return Inertia::render('Admin/WorkTime/Index', [
            'activeShifts' => $activeShifts,
            'historyShifts' => $historyShifts,
            'users' => $users,
            'filters' => $request->only(['user_id', 'date_from', 'date_to']),
            'stats' => [
                'total_hours_today' => round($totalMinutesToday / 60, 1),
                'labor_cost_today' => 0 // Można podpiąć mnożnik stawki
            ]
        ]);
    }

    // Start Pracy
    public function clockIn(Request $request)
    {
        $user = $request->user();

        $existing = WorkShift::where('user_id', $user->id)
            ->whereIn('status', ['working', 'on_break'])
            ->first();

        if ($existing) {
            return back()->with('error', 'Masz już rozpoczętą zmianę!');
        }

        WorkShift::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now(),
            'status' => 'working',
            'hourly_rate' => $user->hourly_rate ?? 0
        ]);

        return back()->with('success', 'Rozpoczęto zmianę roboczą.');
    }

    // Pauza / Wznowienie
    public function togglePause(Request $request)
    {
        $shift = WorkShift::where('user_id', $request->user()->id)
            ->whereIn('status', ['working', 'on_break'])
            ->firstOrFail();

        if ($shift->status === 'working') {
            $shift->update([
                'status' => 'on_break',
                'break_start_at' => Carbon::now()
            ]);
        } else {
            $breakAdd = $shift->break_start_at ? $shift->break_start_at->diffInMinutes(Carbon::now()) : 0;
            $shift->update([
                'status' => 'working',
                'break_minutes' => $shift->break_minutes + $breakAdd,
                'break_start_at' => null
            ]);
        }

        return back();
    }

    // Stop Pracy
    public function clockOut(Request $request)
    {
        $shift = WorkShift::where('user_id', $request->user()->id)
            ->whereIn('status', ['working', 'on_break'])
            ->firstOrFail();

        if ($shift->status === 'on_break' && $shift->break_start_at) {
            $shift->break_minutes += $shift->break_start_at->diffInMinutes(Carbon::now());
        }

        $shift->update([
            'clock_out' => Carbon::now(),
            'status' => 'completed',
            'break_start_at' => null
        ]);

        return back()->with('success', 'Zakończono zmianę roboczą.');
    }

    // Ręczny wpis / edycja przez Admina
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'clock_in' => 'required|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'break_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string'
        ]);

        $validated['status'] = $validated['clock_out'] ? 'completed' : 'working';

        WorkShift::create($validated);

        return back()->with('success', 'Wpis czasu pracy został utworzony.');
    }

    public function update(Request $request, WorkShift $shift)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'clock_in' => 'required|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'break_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string'
        ]);

        $validated['status'] = $validated['clock_out'] ? 'corrected' : 'working';

        $shift->update($validated);

        return back()->with('success', 'Zaktualizowano wpis ewidencji.');
    }
}