<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DueController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReminderLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('clients.contacts', ContactController::class)->only(['store', 'update', 'destroy'])->scoped();
    Route::resource('clients.plans', PlanController::class)->except(['index', 'show'])->scoped();

    Route::get('cobros', [DueController::class, 'index'])->name('dues.index');
    Route::get('cobros/{due}/vista-previa', [DueController::class, 'preview'])->name('dues.preview');
    Route::post('cobros/{due}/pagado', [DueController::class, 'markPaid'])->name('dues.paid');
    Route::post('cobros/{due}/cancelar', [DueController::class, 'cancel'])->name('dues.cancel');
    Route::post('cobros/{due}/reabrir', [DueController::class, 'reopen'])->name('dues.reopen');
    Route::post('cobros/{due}/enviar', [DueController::class, 'send'])->name('dues.send');

    Route::get('historial', [ReminderLogController::class, 'index'])->name('logs.index');

    Route::get('dias-inhabiles', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('dias-inhabiles', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('dias-inhabiles/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
});
