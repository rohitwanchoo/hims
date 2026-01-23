<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\OpdVisit;
use App\Models\IpdAdmission;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\LabOrder;
use App\Models\PharmacySale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dashboard()
    {
        $today = today();
        $thisMonth = now()->startOfMonth();

        return response()->json([
            'patients' => [
                'total' => Patient::count(),
                'today' => Patient::whereDate('created_at', $today)->count(),
                'this_month' => Patient::where('created_at', '>=', $thisMonth)->count(),
            ],
            'opd' => [
                'today' => OpdVisit::whereDate('visit_date', $today)->count(),
                'this_month' => OpdVisit::where('visit_date', '>=', $thisMonth)->count(),
            ],
            'ipd' => [
                'current_admissions' => IpdAdmission::whereNull('discharge_date')->count(),
                'this_month' => IpdAdmission::where('admission_date', '>=', $thisMonth)->count(),
            ],
            'billing' => [
                'today_collection' => Payment::whereDate('payment_date', $today)->sum('amount'),
                'month_collection' => Payment::where('payment_date', '>=', $thisMonth)->sum('amount'),
                'pending_amount' => Bill::where('payment_status', '!=', 'paid')->sum('balance_amount'),
            ],
            'laboratory' => [
                'pending_tests' => LabOrder::where('status', 'pending')->count(),
                'today_orders' => LabOrder::whereDate('order_date', $today)->count(),
            ],
            'pharmacy' => [
                'today_sales' => PharmacySale::whereDate('sale_date', $today)->sum('total_amount'),
                'month_sales' => PharmacySale::where('sale_date', '>=', $thisMonth)->sum('total_amount'),
            ],
        ]);
    }

    public function patientSummary(Request $request)
    {
        $query = Patient::query();

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->gender) {
            $query->where('gender', $request->gender);
        }

        return response()->json([
            'total' => $query->count(),
            'by_gender' => Patient::selectRaw('gender, count(*) as count')
                ->groupBy('gender')
                ->get(),
            'by_blood_group' => Patient::selectRaw('blood_group, count(*) as count')
                ->whereNotNull('blood_group')
                ->groupBy('blood_group')
                ->get(),
            'patients' => $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 50),
        ]);
    }

    public function revenue(Request $request)
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->toDateString();
        $toDate = $request->to_date ?? now()->toDateString();

        return response()->json([
            'summary' => [
                'total_billed' => Bill::whereBetween('bill_date', [$fromDate, $toDate])->sum('total_amount'),
                'total_collected' => Payment::whereBetween('payment_date', [$fromDate, $toDate])->sum('amount'),
                'total_pending' => Bill::whereBetween('bill_date', [$fromDate, $toDate])
                    ->where('payment_status', '!=', 'paid')
                    ->sum('balance_amount'),
            ],
            'daily' => Payment::selectRaw('DATE(payment_date) as date, SUM(amount) as total')
                ->whereBetween('payment_date', [$fromDate, $toDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'by_payment_mode' => Payment::selectRaw('payment_mode, SUM(amount) as total')
                ->whereBetween('payment_date', [$fromDate, $toDate])
                ->groupBy('payment_mode')
                ->get(),
        ]);
    }

    public function departmentWise(Request $request)
    {
        return response()->json([
            'opd_by_department' => DB::table('opd_visits')
                ->join('doctors', 'opd_visits.doctor_id', '=', 'doctors.doctor_id')
                ->join('departments', 'doctors.department_id', '=', 'departments.department_id')
                ->selectRaw('departments.department_name, COUNT(*) as visit_count')
                ->when($request->from_date, fn($q) => $q->whereDate('visit_date', '>=', $request->from_date))
                ->when($request->to_date, fn($q) => $q->whereDate('visit_date', '<=', $request->to_date))
                ->groupBy('departments.department_id', 'departments.department_name')
                ->get(),
        ]);
    }
}
