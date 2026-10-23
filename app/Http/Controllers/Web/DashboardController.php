<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PolicyRequest;
use App\Models\PolicyStateHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalRequests = PolicyRequest::count();
        $issuedCount = PolicyRequest::where('status', PolicyRequest::STATUS_ISSUED)->count();
        $pendingCount = PolicyRequest::whereIn('status', [
            PolicyRequest::STATUS_SUBMITTED,
            PolicyRequest::STATUS_PENDING_CONFIRMATION,
        ])->count();
        $failedCount = PolicyRequest::where('status', PolicyRequest::STATUS_FAILED)->count();
        $reconciliationCount = PolicyRequest::where('status', PolicyRequest::STATUS_NEEDS_RECONCILIATION)->count();
        $totalPremium = PolicyRequest::where('status', PolicyRequest::STATUS_ISSUED)->sum('calculated_premium');

        // Status counts breakdown for SVG Donut chart
        $statusCounts = [
            PolicyRequest::STATUS_ISSUED => $issuedCount,
            PolicyRequest::STATUS_PENDING_CONFIRMATION => PolicyRequest::where('status', PolicyRequest::STATUS_PENDING_CONFIRMATION)->count(),
            PolicyRequest::STATUS_SUBMITTED => PolicyRequest::where('status', PolicyRequest::STATUS_SUBMITTED)->count(),
            PolicyRequest::STATUS_NEEDS_RECONCILIATION => $reconciliationCount,
            PolicyRequest::STATUS_FAILED => $failedCount,
            PolicyRequest::STATUS_VALIDATED => PolicyRequest::where('status', PolicyRequest::STATUS_VALIDATED)->count(),
            PolicyRequest::STATUS_RECEIVED => PolicyRequest::where('status', PolicyRequest::STATUS_RECEIVED)->count(),
        ];

        // Recent audit activity feed
        $recentActivity = PolicyStateHistory::with('policyRequest')
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Recent policy requests
        $recentRequests = PolicyRequest::orderBy('id', 'desc')
            ->limit(6)
            ->get();

        return view('dashboard', compact(
            'totalRequests',
            'issuedCount',
            'pendingCount',
            'failedCount',
            'reconciliationCount',
            'totalPremium',
            'statusCounts',
            'recentActivity',
            'recentRequests'
        ));
    }
}
