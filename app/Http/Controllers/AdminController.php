<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Role;
use App\Models\MasterUnit;
use App\Models\MasterInventoryItem;
use App\Models\MasterTruck;
use App\Models\MasterEmployee;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    // ... Method index tetap sama ...
public function index()
{
    $totalDocs = DailyReport::count();
    $todayDocs = DailyReport::whereDate('report_date', Carbon::today())->count();
    $monthlyDocs = DailyReport::whereMonth('report_date', Carbon::now()->month)
                                ->whereYear('report_date', Carbon::now()->year)
                                ->count();
    $totalPetugas = User::whereHas('role', function($q){
        $q->where('name', 'like', '%petugas%');
    })->count();
    $recentReports = DailyReport::with(['user', 'creator', 'receiver'])
                                ->where('status', 'acknowledged')
                                ->orderBy('received_at', 'asc')
                                ->get();
    return view('admin.dashboard', compact(
        'totalDocs', 'todayDocs', 'monthlyDocs', 'totalPetugas', 'recentReports'
    ));
}

public function approve($id)
{
    $report = DailyReport::with([
        'loadingActivities.timesheets', 'bulkLoadingActivities.logs', 'materialActivity.items',
        'containerActivity.items', 'turbaActivity.deliveries', 'unitCheckLogs', 'employeeLogs',
        'creator', 'receiver'
    ])->findOrFail($id);
    if ($report->status !== 'acknowledged') {
        return redirect()->back()->with('error', 'Status dokumen tidak valid.');
    }
    $report->update([
        'status'      => 'approved',
        'approved_by' => Auth::id(),
        'approved_at' => Carbon::now(),
    ]);
    $report->load('approver');
    try {
        // Path Penyimpanan (Pastikan konsisten)
        $storagePath = storage_path('app/public/reports');
        if (!file_exists($storagePath)) { mkdir($storagePath, 0755, true); }
        // Gunakan format nama file fisik yang konsisten: report-{id}.pdf
        $physicalName = 'report-' . $report->id . '.pdf';
        $pdf = Pdf::loadView('officer.pdf', compact('report'));
        $pdf->setPaper([0, 0, 612.00, 1008.00], 'portrait');
        // Simpan ke storage/app/public/reports/report-{id}.pdf
        $pdf->save($storagePath . '/' . $physicalName);
    } catch (\Exception $e) {
        Log::error("Gagal menyimpan cache PDF ID {$id}: " . $e->getMessage());
    }
    return redirect()->back()->with('success', 'Dokumen berhasil disetujui dan diarsipkan.');
}

    /**
     * MODIFIED: Method viewPdf diperbaiki logikanya untuk memastikan download
     */
public function viewPdf(Request $request, $id)
{
    $report = DailyReport::findOrFail($id);
    $formattedDate = Carbon::parse($report->report_date)->format('j F Y');
    $shiftName = $report->shift ?? 'Shift';
    $groupName = $report->work_group ?? $report->group_name ?? 'Group';
    $downloadFilename = sprintf(
        'Laporan Shift Harian - %s - %s - %s.pdf',
        $shiftName,
        $groupName,
        $formattedDate
    );
    $physicalPath = storage_path('app/public/reports/report-' . $report->id . '.pdf');
    $action = $request->query('action');
    $isDownload = ($action === 'download');
    if ($report->status == 'approved' && file_exists($physicalPath)) {
        if ($isDownload) {
            return response()->download($physicalPath, $downloadFilename);
        } else {
            return response()->file($physicalPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $downloadFilename . '"'
            ]);
        }
    }
    $report->load([
        'loadingActivities.timesheets', 'bulkLoadingActivities.logs', 'materialActivity.items',
        'containerActivity.items', 'turbaActivity.deliveries', 'unitCheckLogs',
        'employeeLogs', 'creator', 'receiver', 'approver'
    ]);
    $pdf = Pdf::loadView('officer.pdf', compact('report'));
    $pdf->setPaper([0, 0, 612.00, 1008.00], 'portrait');
    if ($isDownload) {
        return $pdf->download($downloadFilename);
    } else {
        return $pdf->stream($downloadFilename);
    }
}

    // ... Method lainnya tetap sama ...
public function showPdf($id) {
    $report = DailyReport::with([
        'loadingActivities.timesheets', 'bulkLoadingActivities.logs', 'materialActivity.items', 'containerActivity.items', 'turbaActivity.deliveries', 'unitCheckLogs', 'employeeLogs','creator', 'receiver', 'approver'
    ])->findOrFail($id);
    return view('officer.viewpdf', compact('report'));
}

public function dokumen(Request $request) {
    $query = DailyReport::with(['creator', 'approver'])->where('status', 'approved');
    if ($request->filled('cari')) {
        $search = $request->cari;
        $query->where(function($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")->orWhereHas('creator', function($u) use ($search) { $u->where('name', 'like', "%{$search}%"); });
        });
    }
    if ($request->filled('tanggal')) { $query->whereDate('report_date', $request->tanggal); }
    $reports = $query->latest('report_date')->paginate(10)->withQueryString();
    return view('admin.dokumen', compact('reports'));
}
public function dokumenDestroy($id) {
    $report = DailyReport::findOrFail($id);
    // Hapus file fisik juga agar bersih
    $path = storage_path('app/public/reports/report-' . $report->id . '.pdf');
    if (file_exists($path)) { unlink($path); }
    $report->delete();
    return redirect()->back()->with('success', 'Dokumen laporan berhasil dihapus.');
}

public function pengguna(Request $request) {
    $query = User::with('role');
    if ($request->filled('cari')) { $query->where('name', 'like', '%' . $request->cari . '%')->orWhere('username', 'like', '%' . $request->cari . '%'); }
    $users = $query->orderBy('role_id')->paginate(10)->withQueryString();
    $roles = Role::all();
    return view('admin.pengguna', compact('users', 'roles'));
}

public function penggunaStore(Request $request) {
    $request->validate(['name' => 'required|string|max:255', 'username' => 'required|string|unique:users,username|max:255', 'password' => 'required|string|min:6', 'role_id' => 'required|exists:roles,id']);
    User::create(['name' => $request->name, 'username' => $request->username, 'password' => Hash::make($request->password), 'role_id' => $request->role_id, 'status' => 'aktif']);
    return redirect()->back()->with('success', 'Pengguna berhasil ditambahkan.');
}
public function penggunaUpdate(Request $request, $id) {
    $user = User::findOrFail($id);
    $request->validate(['name' => 'required|string|max:255', 'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)], 'role_id' => 'required|exists:roles,id', 'status' => 'required|in:aktif,nonaktif']);
    $data = ['name' => $request->name, 'username' => $request->username, 'role_id' => $request->role_id, 'status' => $request->status];
    if ($request->filled('password')) { $data['password'] = Hash::make($request->password); }
    $user->update($data);
    return redirect()->back()->with('success', 'Data pengguna berhasil diperbarui.');
}
public function penggunaDestroy($id) {
    $user = User::findOrFail($id);
    if (auth()->id() == $user->id) { return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun sendiri.'); }
    $user->delete();
    return redirect()->back()->with('success', 'Pengguna berhasil dihapus.');
}

public function masterdata() {
    $units = MasterUnit::orderBy('id', 'asc')->paginate(10, ['*'], 'units_page');
    $trucks = MasterTruck::orderBy('id', 'asc')->paginate(10, ['*'], 'trucks_page');
    $inventories = MasterInventoryItem::orderBy('id', 'asc')->paginate(10, ['*'], 'inv_page');
    $employees = MasterEmployee::orderBy('id', 'asc')->paginate(10, ['*'], 'emps_page');
    return view('admin.masterdata', compact('units', 'trucks', 'inventories', 'employees'));
}

    public function unitsStore(Request $request) {
        $request->validate(['name' => 'required|string|max:255', 'type' => 'required|string|max:255']);
        MasterUnit::create(['name' => $request->name, 'type' => $request->type, 'status' => 'active']);
        return redirect()->back()->with('success', 'Unit berhasil ditambahkan.');
    }
public function unitsUpdate(Request $request, $id) {
    $request->validate(['name' => 'required|string|max:255', 'type' => 'required|string|max:255']);
    $unit = MasterUnit::findOrFail($id);
    $unit->update($request->all());
    return redirect()->back()->with('success', 'Unit berhasil diperbarui.');
}
public function unitsDestroy($id) {
    $unit = MasterUnit::findOrFail($id);
    $unit->delete();
    return redirect()->back()->with('success', 'Unit berhasil dihapus.');
}
public function trucksStore(Request $request) {
    $request->validate(['name' => 'required|string|max:255', 'plate_number' => 'nullable|string|max:20', 'description' => 'nullable|string|max:255']);
    MasterTruck::create(['name' => $request->name, 'plate_number' => $request->plate_number, 'description' => $request->description]);
    return redirect()->back()->with('success', 'Truck berhasil ditambahkan.');
}
public function trucksUpdate(Request $request, $id) {
    $request->validate(['name' => 'required|string|max:255', 'plate_number' => 'nullable|string|max:20', 'description' => 'nullable|string|max:255']);
    $truck = MasterTruck::findOrFail($id);
    $truck->update(['name' => $request->name, 'plate_number' => $request->plate_number, 'description' => $request->description]);
    return redirect()->back()->with('success', 'Truck berhasil diperbarui.');
}
public function trucksDestroy($id) {
    $truck = MasterTruck::findOrFail($id);
    $truck->delete();
    return redirect()->back()->with('success', 'Truck berhasil dihapus.');
}
public function inventoriesStore(Request $request) {
    $request->validate(['name' => 'required|string|max:255', 'category' => 'required|string|max:255']);
    MasterInventoryItem::create(['name' => $request->name, 'category' => $request->category, 'stock' => 0, 'status' => 'active']);
    return redirect()->back()->with('success', 'Item inventory berhasil ditambahkan.');
}
public function inventoriesUpdate(Request $request, $id) {
    $request->validate(['name' => 'required|string|max:255', 'category' => 'required|string|max:255']);
    $item = MasterInventoryItem::findOrFail($id);
    $item->update($request->all());
    return redirect()->back()->with('success', 'Item inventory berhasil diperbarui.');
}
public function inventoriesDestroy($id) {
    $item = MasterInventoryItem::findOrFail($id);
    $item->delete();
    return redirect()->back()->with('success', 'Item inventory berhasil dihapus.');
}
public function employeesStore(Request $request) {
    $request->validate(['npk' => 'required|unique:master_employees,npk', 'name' => 'required|string|max:255', 'group_name' => 'nullable|string|max:255', 'position' => 'nullable|string|max:255']);
    MasterEmployee::create(['npk' => $request->npk, 'name' => $request->name, 'group_name' => $request->group_name, 'position' => $request->position, 'status' => 'active']);
    return redirect()->back()->with('success', 'Karyawan berhasil ditambahkan.');
}
public function employeesUpdate(Request $request, $id) {
    $request->validate(['npk' => 'required|unique:master_employees,npk,' . $id, 'name' => 'required|string|max:255', 'position' => 'nullable|string|max:255']);
    $emp = MasterEmployee::findOrFail($id);
    $emp->update($request->all());
    return redirect()->back()->with('success', 'Data karyawan berhasil diperbarui.');
}
public function employeesDestroy($id) {
    $emp = MasterEmployee::findOrFail($id);
    $emp->delete();
    return redirect()->back()->with('success', 'Data karyawan berhasil dihapus.');
}
}
