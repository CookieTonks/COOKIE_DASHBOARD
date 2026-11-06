<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function index(): View
    {
        $holidays = Holiday::query()
            ->whereDate('date', '>=', CarbonImmutable::today()->startOfYear())
            ->orderBy('date')
            ->get()
            ->groupBy(fn (Holiday $holiday) => $holiday->date->year);

        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if (Holiday::whereDate('date', $data['date'])->exists()) {
            throw ValidationException::withMessages(['date' => 'Esa fecha ya está registrada.']);
        }

        Holiday::create($data);

        return back()->with('success', 'Día inhábil agregado.');
    }

    public function destroy(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('success', 'Día inhábil eliminado.');
    }
}
