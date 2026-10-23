<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PolicyRequest;
use App\Services\MockInsurerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolicyRequestViewController extends Controller
{
    public function index(Request $request): View
    {
        $query = PolicyRequest::query()->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('client_request_id', 'like', $search)
                  ->orWhere('agent_id', 'like', $search)
                  ->orWhere('customer_name', 'like', $search)
                  ->orWhere('policy_number', 'like', $search)
                  ->orWhere('provider_reference', 'like', $search);
            });
        }

        $requests = $query->paginate(12)->withQueryString();
        $statuses = PolicyRequest::ALL_STATUSES;

        return view('requests.index', compact('requests', 'statuses'));
    }

    public function create(): View
    {
        $plans = ['BASIC' => 'Basic (₹12/day)', 'PLUS' => 'Plus (₹20/day)', 'PREMIUM' => 'Premium (₹30/day)'];
        $coverages = [
            100000 => '₹1,00,000 (1.00x)',
            300000 => '₹3,00,000 (1.25x)',
            500000 => '₹5,00,000 (1.50x)',
        ];
        $scenarios = MockInsurerService::ALL_SCENARIOS;

        return view('requests.create', compact('plans', 'coverages', 'scenarios'));
    }

    public function show(string $id): View
    {
        $policy = PolicyRequest::where('id', $id)
            ->orWhere('client_request_id', $id)
            ->with(['attempts', 'stateHistory', 'providerEvents'])
            ->firstOrFail();

        return view('requests.show', compact('policy'));
    }

    public function mockLab(): View
    {
        $recentPolicies = PolicyRequest::orderBy('id', 'desc')->limit(10)->get();
        $scenarios = MockInsurerService::ALL_SCENARIOS;

        return view('mock-lab', compact('recentPolicies', 'scenarios'));
    }
}
