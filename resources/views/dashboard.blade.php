<x-app-layout>
    <x-slot name="title">Operations Dashboard</x-slot>

    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Fault-Tolerant Engine Overview
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Real-time policy issuance, concurrency locks, and reconciliation observability.
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('mock-lab') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white/70 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-800 transition-all shadow-xs flex items-center space-x-2">
                <span>🧪 Launch Insurer Lab</span>
            </a>
            <a href="{{ route('requests.create') }}" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-md shadow-indigo-500/25 transition-all flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>New Policy Request</span>
            </a>
        </div>
    </div>

    <!-- Bento Grid 1: Key Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 mb-8">
        <x-stat label="Total Requests" :value="$totalRequests" icon="activity" color="indigo" subtext="All intake attempts" />
        <x-stat label="Issued Policies" :value="$issuedCount" icon="check-circle" color="emerald" subtext="Confirmed unique policies" />
        <x-stat label="In Flight / Pending" :value="$pendingCount" icon="clock" color="sky" subtext="Dispatched or async" />
        <x-stat label="Reconciliation" :value="$reconciliationCount" icon="refresh-cw" color="orange" subtext="Timeouts under resolution" />
        <x-stat label="Failed Requests" :value="$failedCount" icon="alert-triangle" color="rose" subtext="Terminal or retriable" />
        <x-stat label="Total Premium" :value="number_format($totalPremium, 2)" prefix="₹" icon="currency" color="emerald" subtext="Confirmed gross written" />
    </div>

    <!-- Bento Grid 2: Visual Distribution, Live Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
        <!-- Status Distribution Donut Card (5 cols) -->
        <div class="lg:col-span-5 glass-panel p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">
                        Status Distribution
                    </h2>
                    <span class="text-xs text-slate-400 font-mono">{{ $totalRequests }} Total</span>
                </div>

                <!-- SVG Donut Chart with Status Legend -->
                <div class="flex flex-col sm:flex-row items-center justify-around py-4 gap-6">
                    <div class="relative w-40 h-40 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                            <!-- Background ring -->
                            <circle cx="50" cy="50" r="38" stroke-width="12" class="stroke-slate-200/60 dark:stroke-slate-800/60 fill-none"/>
                            
                            @php
                                $circumference = 2 * 3.14159 * 38;
                                $offset = 0;
                                $donutColors = [
                                    'ISSUED' => '#10B981',
                                    'PENDING_CONFIRMATION' => '#F59E0B',
                                    'SUBMITTED' => '#6366F1',
                                    'NEEDS_RECONCILIATION' => '#F97316',
                                    'FAILED' => '#F43F5E',
                                    'VALIDATED' => '#0EA5E9',
                                    'RECEIVED' => '#94A3B8',
                                ];
                            @endphp

                            @foreach ($statusCounts as $statusName => $count)
                                @if ($totalRequests > 0 && $count > 0)
                                    @php
                                        $dash = ($count / $totalRequests) * $circumference;
                                    @endphp
                                    <circle cx="50" cy="50" r="38" stroke-width="12"
                                            stroke="{{ $donutColors[$statusName] ?? '#94A3B8' }}"
                                            stroke-dasharray="{{ $dash }} {{ $circumference - $dash }}"
                                            stroke-dashoffset="{{ -$offset }}"
                                            class="fill-none transition-all duration-500 hover:opacity-80"/>
                                    @php
                                        $offset += $dash;
                                    @endphp
                                @endif
                            @endforeach
                        </svg>

                        <!-- Center Count Label -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ $issuedCount }}</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-600 dark:text-emerald-400">Issued</span>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="space-y-2 text-xs">
                        @foreach ($statusCounts as $st => $cnt)
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-full" style="background-color: {{ $donutColors[$st] ?? '#94A3B8' }}"></span>
                                <span class="font-medium text-slate-600 dark:text-slate-300 w-32 truncate">{{ str_replace('_', ' ', $st) }}</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ $cnt }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200/50 dark:border-slate-800/50 flex items-center justify-between text-xs text-slate-500">
                <span>Reliability Guarantee:</span>
                <span class="font-semibold text-indigo-600 dark:text-indigo-400">Zero duplicate policy numbers</span>
            </div>
        </div>

        <!-- 7-Day Ingestion & Activity Sparkline Card (7 cols) -->
        <div class="lg:col-span-7 glass-panel p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                    <div>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            Engine Concurrency & Intake Flow
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Automated optimistic intake with DB-level unique deduplication
                        </p>
                    </div>
                    <span class="px-2.5 py-1 text-[11px] font-mono font-semibold rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                        Pessimistic Lock Active
                    </span>
                </div>

                <!-- Visual Engine Pipeline Diagram -->
                <div class="py-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="p-3.5 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">1. Intake</span>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1">Unique Constraint</div>
                            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-mono mt-0.5">agent_id + req_id</div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">2. Pricing</span>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1">BCMath Math</div>
                            <div class="text-[11px] text-indigo-600 dark:text-indigo-400 font-mono mt-0.5">Immutable Snapshot</div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">3. Issuance</span>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1">Row Locking</div>
                            <div class="text-[11px] text-amber-600 dark:text-amber-400 font-mono mt-0.5">lockForUpdate()</div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">4. Recovery</span>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-1">Reconciliation</div>
                            <div class="text-[11px] text-orange-600 dark:text-orange-400 font-mono mt-0.5">Timeout Resolver</div>
                        </div>
                    </div>

                    <!-- Faint Sparkline Graphic -->
                    <div class="mt-6 p-4 rounded-2xl bg-gradient-to-r from-indigo-500/10 via-sky-500/10 to-emerald-500/10 border border-indigo-200/30 dark:border-indigo-800/30 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-200">Terminal ISSUED Protection</div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Regressions such as ISSUED → SUBMITTED or late failure callbacks are strictly blocked.</p>
                        </div>
                        <span class="text-xl">🛡️</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-200/50 dark:border-slate-800/50">
                <span>Database Engine: <span class="font-mono text-slate-700 dark:text-slate-300">{{ config('database.default') }}</span></span>
                <a href="{{ route('requests.index') }}" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">View All Requests →</a>
            </div>
        </div>
    </div>

    <!-- Bento Grid 3: Live Activity Feed (6 cols) & Recent Requests (6 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Live Activity Feed Card -->
        <div class="lg:col-span-6 glass-panel p-6">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-ping"></span>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Live Audit Activity Feed</h2>
                </div>
                <span class="text-xs font-mono text-slate-400">policy_state_history</span>
            </div>

            <div class="space-y-3.5 max-h-[420px] overflow-y-auto pr-2">
                @forelse ($recentActivity as $act)
                    <div class="p-3.5 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60 flex items-start justify-between gap-3 hover:bg-white dark:hover:bg-slate-900 transition-colors">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-bold font-mono text-indigo-600 dark:text-indigo-400">
                                    {{ $act->trigger }}
                                </span>
                                <span class="text-slate-400 text-xs">→</span>
                                <x-status-badge :status="$act->to_status" size="sm" />
                            </div>
                            @if ($act->policyRequest)
                                <a href="{{ route('requests.show', $act->policyRequest->id) }}" class="text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-mono">
                                    {{ $act->policyRequest->client_request_id }} ({{ $act->policyRequest->customer_name }})
                                </a>
                            @endif
                            @if ($act->reason)
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                                    {{ $act->reason }}
                                </p>
                            @endif
                        </div>
                        <time class="text-[10px] font-mono text-slate-400 whitespace-nowrap">
                            {{ $act->created_at->diffForHumans() }}
                        </time>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-6 text-center">No transitions recorded yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Policy Requests Table Card -->
        <div class="lg:col-span-6 glass-panel p-6">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Recent Requests</h2>
                <a href="{{ route('requests.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                    Browse All
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200/60 dark:border-slate-800/60">
                            <th class="pb-3">Request ID</th>
                            <th class="pb-3">Customer</th>
                            <th class="pb-3">Premium</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/50 dark:divide-slate-800/50">
                        @forelse ($recentRequests as $req)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ $req->client_request_id }}
                                </td>
                                <td class="py-3 font-medium text-slate-700 dark:text-slate-300">
                                    {{ $req->customer_name }}
                                </td>
                                <td class="py-3 font-mono font-bold text-slate-900 dark:text-white">
                                    ₹{{ number_format($req->calculated_premium, 2) }}
                                </td>
                                <td class="py-3">
                                    <x-status-badge :status="$req->status" size="sm" />
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('requests.show', $req->id) }}" class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 transition-colors">
                                        View →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400">
                                    No requests created yet. Run the database seeder or click + New Request.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
