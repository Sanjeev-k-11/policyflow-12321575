<x-app-layout>
    <x-slot name="title">Operations Dashboard</x-slot>

    <div x-data="{
        isReconciling: false,
        reconcileStats: null,
        searchQuery: '',
        statusFilter: 'ALL',

        // 1-Click live auto reconciliation of all stale policies
        autoReconcileAll() {
            this.isReconciling = true;
            fetch('/api/policy-requests/reconcile-all', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            })
            .then(r => r.json())
            .then(res => {
                this.isReconciling = false;
                $dispatch('toast', { 
                    message: res.message, 
                    type: res.reconciled > 0 ? 'success' : 'warning' 
                });
                setTimeout(() => { window.location.reload(); }, 1200);
            })
            .catch(() => {
                this.isReconciling = false;
                $dispatch('toast', { message: 'Reconciliation call failed', type: 'error' });
            });
        },

        // Fast sample intake creation from dashboard
        quickIntake() {
            const clientReqId = 'QUICK-' + Math.floor(1000 + Math.random() * 9000);
            fetch('/api/policy-requests', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    client_request_id: clientReqId,
                    agent_id: 'AGENT-DASHBOARD',
                    customer_name: 'Express Customer',
                    date_of_birth: '1996-05-12',
                    destination: 'INTERNATIONAL',
                    trip_start: '2026-11-20',
                    trip_end: '2026-11-25',
                    plan_code: 'PLUS',
                    sum_insured: 300000,
                    scenario: 'SUCCESS'
                })
            })
            .then(r => r.json())
            .then(res => {
                $dispatch('toast', { message: 'Created request ' + clientReqId + ' (₹270.00)', type: 'success' });
                setTimeout(() => { window.location.reload(); }, 900);
            });
        }
    }">

        <!-- Top Header & Fast Action Toolbar -->
        <div class="mb-8 flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        PolicyFlow Control Center
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80">
                        Live Engine
                    </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Fault-tolerant policy issuance, automated reconciliation & deterministic insurance pricing.
                </p>
            </div>

            <!-- Dashboard Action Toolbar -->
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" @click="quickIntake()"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white/70 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700 hover:bg-white dark:hover:bg-slate-800 transition-all shadow-2xs flex items-center space-x-1.5">
                    <span>⚡ Quick Intake Sample</span>
                </button>

                <button type="button" @click="autoReconcileAll()" :disabled="isReconciling"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-orange-600 via-amber-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 shadow-md shadow-orange-500/20 transition-all flex items-center space-x-2 disabled:opacity-50">
                    <svg x-show="isReconciling" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="isReconciling ? 'Reconciling...' : '🔄 Auto-Reconcile Stale ({{ $reconciliationCount }})'"></span>
                </button>

                <a href="{{ route('mock-lab') }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 transition-all shadow-2xs flex items-center space-x-1.5">
                    <span>🧪 Mock Insurer Lab</span>
                </a>

                <a href="{{ route('requests.create') }}" 
                   class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-md shadow-indigo-500/25 transition-all flex items-center space-x-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ New Policy Request</span>
                </a>
            </div>
        </div>

        <!-- Reliability & Engine System Health Bar -->
        <div class="glass-panel p-4 mb-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs divide-y sm:divide-y-0 sm:divide-x divide-slate-200/60 dark:divide-slate-800/60">
                <div class="flex items-center space-x-3 px-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                        🛡️
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold block">Duplicate Protection</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">100% DB-Level Unique</span>
                    </div>
                </div>

                <div class="flex items-center space-x-3 px-3 pt-2 sm:pt-0">
                    <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                        🔒
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold block">Concurrency Lock</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">Pessimistic Row Lock</span>
                    </div>
                </div>

                <div class="flex items-center space-x-3 px-3 pt-2 sm:pt-0">
                    <div class="w-8 h-8 rounded-xl bg-sky-100 dark:bg-sky-950/80 text-sky-600 dark:text-sky-400 flex items-center justify-center font-bold">
                        ⚡
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold block">Arbitrary Math</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200">BCMath Decimal-Safe</span>
                    </div>
                </div>

                <div class="flex items-center space-x-3 px-3 pt-2 sm:pt-0">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                        ⏱️
                    </div>
                    <div>
                        <span class="text-slate-400 uppercase text-[10px] font-bold block">Avg Issuance Duration</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 font-mono">~310ms (Local)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bento Grid Section 1: Hero Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 mb-8">
            <x-stat label="Total Intake" :value="$totalRequests" icon="activity" color="indigo" subtext="All recorded requests" />
            <x-stat label="Issued Policies" :value="$issuedCount" icon="check-circle" color="emerald" subtext="Confirmed unique policies" />
            <x-stat label="Pending / Dispatched" :value="$pendingCount" icon="clock" color="sky" subtext="Awaiting insurer / webhook" />
            <x-stat label="Reconciliation" :value="$reconciliationCount" icon="refresh-cw" color="orange" subtext="Timeouts under resolution" />
            <x-stat label="Failed Requests" :value="$failedCount" icon="alert-triangle" color="rose" subtext="Sanctions or gateways" />
            <x-stat label="Premium Written" :value="number_format($totalPremium, 2)" prefix="₹" icon="currency" color="emerald" subtext="Confirmed gross written" />
        </div>

        <!-- Bento Grid Section 2: Visual Distribution & 7-Day Dynamic Activity Trend -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
            <!-- 1. Status Ring Donut (5 cols) -->
            <div class="lg:col-span-5 glass-panel p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            Lifecycle State Distribution
                        </h2>
                        <span class="text-xs text-slate-400 font-mono">{{ $totalRequests }} Total</span>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-around py-4 gap-6">
                        <!-- SVG Ring -->
                        <div class="relative w-44 h-44 flex items-center justify-center">
                            <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
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
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ $issuedCount }}</span>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-600 dark:text-emerald-400">Issued</span>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="space-y-2 text-xs">
                            @foreach ($statusCounts as $st => $cnt)
                                <div class="flex items-center space-x-2">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $donutColors[$st] ?? '#94A3B8' }}"></span>
                                    <span class="font-medium text-slate-600 dark:text-slate-300 w-32 truncate">{{ str_replace('_', ' ', $st) }}</span>
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ $cnt }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-800/50 flex items-center justify-between text-xs text-slate-500">
                    <span>Conversion Rate:</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                        {{ $totalRequests > 0 ? round(($issuedCount / $totalRequests) * 100, 1) : 0 }}%
                    </span>
                </div>
            </div>

            <!-- 2. Dynamic 7-Day Ingestion & Issuance Trend Visual (7 cols) -->
            <div class="lg:col-span-7 glass-panel p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 dark:text-white">
                                7-Day Ingestion & Issuance Volume
                            </h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Comparison of intake requests vs successfully issued policies
                            </p>
                        </div>
                        <div class="flex items-center space-x-3 text-xs">
                            <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></span><span class="text-slate-600 dark:text-slate-400">Intake</span></span>
                            <span class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span><span class="text-slate-600 dark:text-slate-400">Issued</span></span>
                        </div>
                    </div>

                    <!-- Custom SVG Bar Chart -->
                    <div class="py-4">
                        <div class="grid grid-cols-7 gap-3 text-center items-end h-40 pt-4">
                            @foreach ($dailyTrend as $trend)
                                @php
                                    $intakeHeight = min(100, max(20, $trend['intake'] * 18));
                                    $issuedHeight = min(100, max(15, $trend['issued'] * 18));
                                @endphp
                                <div class="flex flex-col items-center h-full justify-end group">
                                    <div class="w-full flex items-end justify-center space-x-1.5 h-28">
                                        <!-- Intake Bar -->
                                        <div class="w-3 rounded-t-lg bg-indigo-500/80 group-hover:bg-indigo-500 transition-all duration-300 relative"
                                             style="height: {{ $intakeHeight }}%;">
                                            <span class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-5 left-1/2 transform -translate-x-1/2 text-[10px] font-mono font-bold text-indigo-600 bg-white dark:bg-slate-900 px-1 rounded shadow-xs">
                                                {{ $trend['intake'] }}
                                            </span>
                                        </div>
                                        <!-- Issued Bar -->
                                        <div class="w-3 rounded-t-lg bg-emerald-500/80 group-hover:bg-emerald-500 transition-all duration-300 relative"
                                             style="height: {{ $issuedHeight }}%;">
                                            <span class="opacity-0 group-hover:opacity-100 transition-opacity absolute -top-5 left-1/2 transform -translate-x-1/2 text-[10px] font-mono font-bold text-emerald-600 bg-white dark:bg-slate-900 px-1 rounded shadow-xs">
                                                {{ $trend['issued'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-mono font-bold text-slate-700 dark:text-slate-300 mt-2 block">{{ $trend['day_name'] }}</span>
                                    <span class="text-[9px] text-slate-400">{{ $trend['date'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-800/50 flex items-center justify-between text-xs text-slate-500">
                    <span>Engine Reliability: <span class="font-bold text-slate-700 dark:text-slate-300">Deterministic Idempotency</span></span>
                    <a href="{{ route('requests.index') }}" class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline">Inspect Ledger →</a>
                </div>
            </div>
        </div>

        <!-- Bento Grid Section 3: Plan Analytics & Geographic Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
            <!-- Plan Analytics Breakdown (6 cols) -->
            <div class="lg:col-span-6 glass-panel p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Plan Code Analytics</h2>
                    <span class="text-xs text-slate-400 font-mono">BASIC • PLUS • PREMIUM</span>
                </div>

                <div class="space-y-4">
                    <!-- BASIC -->
                    @php
                        $basicCount = $planBreakdown['BASIC']->count ?? 0;
                        $basicRev = $planBreakdown['BASIC']->total_premium ?? 0;
                        $basicPct = $totalRequests > 0 ? round(($basicCount / $totalRequests) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center text-xs mb-1.5">
                            <span class="font-bold text-slate-800 dark:text-slate-200">BASIC (₹12/day)</span>
                            <span class="font-mono text-slate-500 font-semibold">{{ $basicCount }} policies • ₹{{ number_format($basicRev, 2) }} ({{ $basicPct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-200/70 dark:bg-slate-800/70 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-sky-500 h-full rounded-full transition-all duration-700" style="width: {{ $basicPct }}%"></div>
                        </div>
                    </div>

                    <!-- PLUS -->
                    @php
                        $plusCount = $planBreakdown['PLUS']->count ?? 0;
                        $plusRev = $planBreakdown['PLUS']->total_premium ?? 0;
                        $plusPct = $totalRequests > 0 ? round(($plusCount / $totalRequests) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center text-xs mb-1.5">
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">PLUS (₹20/day)</span>
                            <span class="font-mono text-slate-500 font-semibold">{{ $plusCount }} policies • ₹{{ number_format($plusRev, 2) }} ({{ $plusPct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-200/70 dark:bg-slate-800/70 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-indigo-600 h-full rounded-full transition-all duration-700" style="width: {{ $plusPct }}%"></div>
                        </div>
                    </div>

                    <!-- PREMIUM -->
                    @php
                        $premCount = $planBreakdown['PREMIUM']->count ?? 0;
                        $premRev = $planBreakdown['PREMIUM']->total_premium ?? 0;
                        $premPct = $totalRequests > 0 ? round(($premCount / $totalRequests) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center text-xs mb-1.5">
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">PREMIUM (₹30/day)</span>
                            <span class="font-mono text-slate-500 font-semibold">{{ $premCount }} policies • ₹{{ number_format($premRev, 2) }} ({{ $premPct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-200/70 dark:bg-slate-800/70 h-2.5 rounded-full overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-700" style="width: {{ $premPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Geographic Destination Split (6 cols) -->
            <div class="lg:col-span-6 glass-panel p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Geographic Destination Split</h2>
                    <span class="text-xs text-slate-400 font-mono">DOMESTIC vs INTERNATIONAL</span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- DOMESTIC -->
                    @php
                        $dom = $destinationBreakdown['DOMESTIC'] ?? null;
                    @endphp
                    <div class="p-4 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xl">🇮🇳</span>
                                <span class="font-bold text-xs uppercase tracking-wider text-slate-600 dark:text-slate-300">Domestic</span>
                            </div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-2">
                                {{ $dom->count ?? 0 }}
                            </div>
                            <span class="text-[10px] text-slate-400 font-semibold block mt-0.5">Multiplier: 1.00x</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-200/40 dark:border-slate-800/40 text-[11px] text-slate-500">
                            Avg: <span class="font-mono font-bold text-slate-800 dark:text-slate-200">₹{{ number_format($dom->avg_premium ?? 0, 2) }}</span>
                        </div>
                    </div>

                    <!-- INTERNATIONAL -->
                    @php
                        $intl = $destinationBreakdown['INTERNATIONAL'] ?? null;
                    @endphp
                    <div class="p-4 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="text-xl">✈️</span>
                                <span class="font-bold text-xs uppercase tracking-wider text-indigo-600 dark:text-indigo-400">International</span>
                            </div>
                            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-2">
                                {{ $intl->count ?? 0 }}
                            </div>
                            <span class="text-[10px] text-indigo-500 font-semibold block mt-0.5">Multiplier: 1.80x</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-200/40 dark:border-slate-800/40 text-[11px] text-slate-500">
                            Avg: <span class="font-mono font-bold text-slate-800 dark:text-slate-200">₹{{ number_format($intl->avg_premium ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bento Grid Section 4: Top Performing Agents (5 cols) & Live Audit Feed (7 cols) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
            <!-- Top Performing Agents Card (5 cols) -->
            <div class="lg:col-span-5 glass-panel p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Agent Distribution</h2>
                    <span class="text-xs text-slate-400 font-mono">Top by Volume</span>
                </div>

                <div class="space-y-3">
                    @forelse ($topAgents as $agt)
                        <div class="p-3 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-500 to-sky-400 text-white font-bold flex items-center justify-center font-mono text-[11px]">
                                    {{ substr($agt->agent_id, -2) }}
                                </div>
                                <div>
                                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200 block">{{ $agt->agent_id }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $agt->total_requests }} requests • {{ $agt->issued_policies }} issued</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-extrabold text-slate-900 dark:text-white block">₹{{ number_format($agt->total_premium, 2) }}</span>
                                <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ $agt->total_requests > 0 ? round(($agt->issued_policies / $agt->total_requests) * 100) : 0 }}% conv
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-4 text-center">No agent data recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Live Audit Stream Card (7 cols) -->
            <div class="lg:col-span-7 glass-panel p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-ping"></span>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Live Policy Audit Trail</h2>
                    </div>
                    <span class="text-xs font-mono text-slate-400">policy_state_history</span>
                </div>

                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
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
        </div>

        <!-- Bento Grid Section 5: Recent Policy Requests with Instant Client-Side Filter -->
        <div class="glass-panel p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5 pb-4 border-b border-slate-200/50 dark:border-slate-800/50">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Active Policy Requests Ledger</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Quick search and inspect recent policy records</p>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="text" x-model="searchQuery" placeholder="Filter customer, request ID..."
                           class="glass-input rounded-xl px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 w-48 sm:w-64">
                    <a href="{{ route('requests.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        View All ({{ $totalRequests }}) →
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200/60 dark:border-slate-800/60">
                            <th class="pb-3">Request ID</th>
                            <th class="pb-3">Agent</th>
                            <th class="pb-3">Customer</th>
                            <th class="pb-3">Plan / Coverage</th>
                            <th class="pb-3">Calculated Premium</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Policy Number</th>
                            <th class="pb-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/50 dark:divide-slate-800/50">
                        @forelse ($recentRequests as $req)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors"
                                x-show="!searchQuery || '{{ strtolower($req->customer_name . ' ' . $req->client_request_id . ' ' . $req->agent_id) }}'.includes(searchQuery.toLowerCase())">
                                <td class="py-3 font-mono font-bold text-slate-800 dark:text-slate-200">
                                    {{ $req->client_request_id }}
                                </td>
                                <td class="py-3 font-mono text-slate-500">
                                    {{ $req->agent_id }}
                                </td>
                                <td class="py-3 font-medium text-slate-700 dark:text-slate-300">
                                    {{ $req->customer_name }}
                                </td>
                                <td class="py-3">
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $req->plan_code }}</span>
                                    <span class="text-slate-400 text-[10px] block">₹{{ number_format($req->sum_insured) }}</span>
                                </td>
                                <td class="py-3 font-mono font-bold text-slate-900 dark:text-white">
                                    ₹{{ number_format($req->calculated_premium, 2) }}
                                </td>
                                <td class="py-3">
                                    <x-status-badge :status="$req->status" size="sm" />
                                </td>
                                <td class="py-3 font-mono text-[11px]">
                                    @if ($req->policy_number)
                                        <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ $req->policy_number }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('requests.show', $req->id) }}" 
                                       class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 transition-colors">
                                        Inspect →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-slate-400">
                                    No requests found. Click ⚡ Quick Intake Sample above to generate test requests.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
