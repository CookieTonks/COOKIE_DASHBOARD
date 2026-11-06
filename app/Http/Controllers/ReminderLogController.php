<?php

namespace App\Http\Controllers;

use App\Models\ReminderLog;
use Illuminate\View\View;

class ReminderLogController extends Controller
{
    public function index(): View
    {
        return view('logs.index', [
            'logs' => ReminderLog::query()->latest('id')->paginate(50),
        ]);
    }
}
