<?php

use App\Http\Controllers\LoginV2Controller;
use App\Http\Controllers\AdminV2Controller;
use App\Http\Controllers\ManajerController;
use App\Http\Controllers\ReportOpsController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginV2Controller::class, 'index'])->name('login');
    Route::get('/login', [LoginV2Controller::class, 'index'])->name('login.index');
    Route::post('/login', [LoginV2Controller::class, 'authenticate'])->name('login.authenticate');
});

Route::post('/logout', [LoginV2Controller::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:'.Role::ADMIN])->group(function () {
    Route::get('/', [AdminV2Controller::class, 'index'])->name('index');
    Route::get('/archive', [AdminV2Controller::class, 'archive'])->name('archive');
    Route::get('/log', [AdminV2Controller::class, 'log'])->name('log');
    Route::get('/user-manage', [AdminV2Controller::class, 'userManage'])->name('user-manage');
    Route::get('/datamaster', [AdminV2Controller::class, 'dataMaster'])->name('datamaster');
    Route::get('/backup', [AdminV2Controller::class, 'backup'])->name('backup');
    Route::get('/help', [AdminV2Controller::class, 'help'])->name('help');

    Route::get('/reports/{report}', [AdminV2Controller::class, 'showReport'])->name('reports.show');
    Route::get('/reports/{report}/download', [AdminV2Controller::class, 'downloadReport'])->name('reports.download');
    Route::delete('/reports/{report}', [AdminV2Controller::class, 'destroyReport'])->name('reports.destroy');

    Route::post('/users', [AdminV2Controller::class, 'storeUser'])->name('users.store');
    Route::put('/users/{user}', [AdminV2Controller::class, 'updateUser'])->name('users.update');
    Route::patch('/users/{user}/status', [AdminV2Controller::class, 'toggleUserStatus'])->name('users.status');
    Route::delete('/users/{user}', [AdminV2Controller::class, 'destroyUser'])->name('users.destroy');

    Route::post('/master/employees', [AdminV2Controller::class, 'storeEmployee'])->name('master.employees.store');
    Route::put('/master/employees/{employee}', [AdminV2Controller::class, 'updateEmployee'])->name('master.employees.update');
    Route::delete('/master/employees/{employee}', [AdminV2Controller::class, 'destroyEmployee'])->name('master.employees.destroy');
    Route::post('/master/units', [AdminV2Controller::class, 'storeUnit'])->name('master.units.store');
    Route::put('/master/units/{unit}', [AdminV2Controller::class, 'updateUnit'])->name('master.units.update');
    Route::delete('/master/units/{unit}', [AdminV2Controller::class, 'destroyUnit'])->name('master.units.destroy');
    Route::post('/master/trucks', [AdminV2Controller::class, 'storeTruck'])->name('master.trucks.store');
    Route::put('/master/trucks/{truck}', [AdminV2Controller::class, 'updateTruck'])->name('master.trucks.update');
    Route::delete('/master/trucks/{truck}', [AdminV2Controller::class, 'destroyTruck'])->name('master.trucks.destroy');
    Route::post('/master/inventories', [AdminV2Controller::class, 'storeInventory'])->name('master.inventories.store');
    Route::put('/master/inventories/{inventory}', [AdminV2Controller::class, 'updateInventory'])->name('master.inventories.update');
    Route::delete('/master/inventories/{inventory}', [AdminV2Controller::class, 'destroyInventory'])->name('master.inventories.destroy');

    Route::post('/backup/generate', [AdminV2Controller::class, 'generateBackup'])->name('backup.generate');
    Route::post('/backup/annual', [AdminV2Controller::class, 'annualBackup'])->name('backup.annual');
    Route::put('/backup/schedule', [AdminV2Controller::class, 'updateBackupSchedule'])->name('backup.schedule');
    Route::get('/backup/files/{file}', [AdminV2Controller::class, 'downloadBackup'])->name('backup.download');
    Route::delete('/backup/files/{file}', [AdminV2Controller::class, 'destroyBackup'])->name('backup.destroy');
    Route::post('/backup/files/{file}/restore', [AdminV2Controller::class, 'restoreBackup'])->name('backup.restore');

    Route::post('/help/ticket', [AdminV2Controller::class, 'storeHelpTicket'])->name('help.ticket');
});

Route::middleware('auth')->group(function () {
    Route::middleware('role:except,'.Role::ADMIN.','.Role::MANAGER)->group(function () {
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

    Route::middleware('role:'.Role::MANAGER)->group(function () {
        Route::get('/manajer', [ManajerController::class, 'index'])->name('manajer.index');
        Route::get('/manajer/archive', [ManajerController::class, 'archive'])->name('manajer.archive');
        Route::get('/manajer/archive/suggestions', [ManajerController::class, 'archiveSuggestions'])->name('manajer.archive.suggestions');
        Route::get('/manajer/bantuan', [ManajerController::class, 'bantuan'])->name('manajer.bantuan');
        Route::get('/manajer/reports/{report}', [ManajerController::class, 'show'])->name('manajer.reports.show');
        Route::post('/manajer/reports/{report}/approve', [ManajerController::class, 'approve'])->name('manajer.reports.approve');
        Route::get('/manajer/reports/{report}/download', [ManajerController::class, 'download'])->name('manajer.reports.download');
        Route::delete('/manajer/reports/{report}', [ManajerController::class, 'destroy'])->name('manajer.reports.destroy');
    });
});
