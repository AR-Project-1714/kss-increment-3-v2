<?php

use App\Http\Controllers\LoginV2Controller;
use App\Http\Controllers\AdminV2Controller;
use App\Http\Controllers\ManajerController;
use App\Http\Controllers\ReportOpsController;
use App\Http\Middleware\PreventManagerDivisionAccess;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginV2Controller::class, 'index'])->name('login');
    Route::get('/login', [LoginV2Controller::class, 'index'])->name('login.index');
    Route::post('/login', [LoginV2Controller::class, 'authenticate'])->name('login.authenticate');
});

Route::post('/logout', [LoginV2Controller::class, 'logout'])->middleware('auth')->name('logout');

// Preview tampilan admin tanpa login.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminV2Controller::class, 'index'])->name('index');
    Route::get('/archive', [AdminV2Controller::class, 'archive'])->name('archive');
    Route::get('/log', [AdminV2Controller::class, 'log'])->name('log');
    Route::get('/user-manage', [AdminV2Controller::class, 'userManage'])->name('user-manage');
    Route::get('/datamaster', [AdminV2Controller::class, 'dataMaster'])->name('datamaster');
    Route::get('/backup', [AdminV2Controller::class, 'backup'])->name('backup');
    Route::get('/help', [AdminV2Controller::class, 'help'])->name('help');
});

Route::middleware('auth')->group(function () {
    Route::middleware(PreventManagerDivisionAccess::class)->group(function () {
        Route::get('/report-ops', [ReportOpsController::class, 'index'])->name('report-ops.index');
        Route::get('/report-ops/history/suggestions', [ReportOpsController::class, 'historySuggestions'])->name('report-ops.history.suggestions');
        Route::get('/report-ops/ship-operations/suggestions', [ReportOpsController::class, 'shipOperationSuggestions'])->name('report-ops.ship-operations.suggestions');
        Route::get('/report-ops/create', [ReportOpsController::class, 'create'])->name('report-ops.create');
        Route::post('/report-ops', [ReportOpsController::class, 'store'])->name('report-ops.store');
        Route::get('/report-ops/{report}', [ReportOpsController::class, 'show'])->name('report-ops.show');
        Route::get('/report-ops/{report}/edit', [ReportOpsController::class, 'edit'])->name('report-ops.edit');
        Route::put('/report-ops/{report}', [ReportOpsController::class, 'update'])->name('report-ops.update');
        Route::delete('/report-ops/{report}', [ReportOpsController::class, 'destroy'])->name('report-ops.destroy');
        Route::post('/report-ops/{report}/sign', [ReportOpsController::class, 'sign'])->name('report-ops.sign');
        Route::get('/report-ops/{report}/pdf', [ReportOpsController::class, 'exportPdf'])->name('report-ops.pdf');
        Route::get('/report-ops/{report}/excel', [ReportOpsController::class, 'exportExcel'])->name('report-ops.excel');
    });

    Route::get('/manajer', [ManajerController::class, 'index'])->name('manajer.index');
    Route::get('/manajer/archive', [ManajerController::class, 'archive'])->name('manajer.archive');
    Route::get('/manajer/archive/suggestions', [ManajerController::class, 'archiveSuggestions'])->name('manajer.archive.suggestions');
    Route::get('/manajer/bantuan', [ManajerController::class, 'bantuan'])->name('manajer.bantuan');
    Route::get('/manajer/reports/{report}', [ManajerController::class, 'show'])->name('manajer.reports.show');
    Route::post('/manajer/reports/{report}/approve', [ManajerController::class, 'approve'])->name('manajer.reports.approve');
    Route::get('/manajer/reports/{report}/download', [ManajerController::class, 'download'])->name('manajer.reports.download');
    Route::delete('/manajer/reports/{report}', [ManajerController::class, 'destroy'])->name('manajer.reports.destroy');
});
