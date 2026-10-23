<x-app-layout>
    <x-slot name="title">Policy Request {{ $policy->client_request_id }}</x-slot>

    <div class="w-full space-y-6"
         x-data="{
             policyId: '{{ $policy->id }}',
             status: '{{ $policy->status }}',
             policyNumber: '{{ $policy->policy_number }}',
             providerReference: '{{ $policy->provider_reference }}',
             loading: false,

             submitIssuance(scenario = null) {
                 this.loading = true;
                 fetch('/api/policy-requests/' + this.policyId + '/submit', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                     },
                     body: JSON.stringify({ scenario: scenario })
                 })
                 .then(r => r.json())
                 .then(res => {
                     this.loading = false;
                     $dispatch('toast', { 
                         message: res.message, 
                         type: res.success ? 'success' : (res.status === 'NEEDS_RECONCILIATION' ? 'warning' : 'error') 
                     });
                     setTimeout(() => { window.location.reload(); }, 900);
                 })
                 .catch(() => {
                     this.loading = false;
                     $dispatch('toast', { message: 'Submission failed', type: 'error' });
                 });
             },

             reconcileStatus() {
                 this.loading = true;
                 fetch('/api/policy-requests/' + this.policyId + '/reconcile', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                     }
                 })
                 .then(r => r.json())
                 .then(res => {
                     this.loading = false;
                     $dispatch('toast', { 
                         message: res.message, 
                         type: res.success ? 'success' : 'warning' 
                     });
                     setTimeout(() => { window.location.reload(); }, 900);
                 })
                 .catch(() => {
                     this.loading = false;
                     $dispatch('toast', { message: 'Reconciliation call failed', type: 'error' });
                 });
             }
         }">

        <!-- Top Navigation & Action Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <a href="{{ route('requests.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center space-x-1 mb-1">
                    <span>← Back to All Requests</span>
                </a>
                <div class="flex items-center space-x-3">
                    <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white font-mono">
                        {{ $policy->client_request_id }}
                    </h1>
                    <x-status-badge :status="$policy->status" size="lg" />
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Agent: <span class="font-mono font-bold">{{ $policy->agent_id }}</span> • 
                    Customer: <span class="font-bold">{{ $policy->customer_name }}</span> • 
                    Intake: {{ $policy->created_at->format('M d, Y H:i:s') }}
                </p>
            </div>

            <!-- Lifecycle Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                @if ($policy->canSubmit())
                    <button type="button" @click="submitIssuance()" :disabled="loading"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-md shadow-indigo-500/25 transition-all flex items-center space-x-2 disabled:opacity-50">
                        <span>🚀 Start Issuance</span>
                    </button>
                @endif

                @if ($policy->status === 'FAILED')
                    <button type="button" @click="submitIssuance('SUCCESS')" :disabled="loading"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-amber-600 to-orange-500 hover:from-amber-500 hover:to-orange-400 shadow-md shadow-amber-500/25 transition-all flex items-center space-x-2 disabled:opacity-50">
                        <span>🔄 Retry Issuance (Safe)</span>
                    </button>
                @endif

                @if ($policy->canReconcile())
                    <button type="button" @click="reconcileStatus()" :disabled="loading"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 shadow-md shadow-orange-500/25 transition-all flex items-center space-x-2 disabled:opacity-50 animate-pulse">
                        <span>🔍 Reconcile with Insurer</span>
                    </button>
                @endif

                @if ($policy->isIssued())
                    <div class="px-4 py-2 rounded-xl text-xs font-mono font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-100/80 dark:bg-emerald-950/80 border border-emerald-300/80 dark:border-emerald-800/80 flex items-center space-x-2 shadow-xs">
                        <span>✓ Policy Confirmed:</span>
                        <span class="underline">{{ $policy->policy_number }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Failure or Reconciliation Banner -->
        @if ($policy->status === 'NEEDS_RECONCILIATION')
            <div class="mb-6 p-4 rounded-2xl bg-orange-50 dark:bg-orange-950/50 border border-orange-300/80 dark:border-orange-800/80 flex items-start space-x-3 text-xs text-orange-800 dark:text-orange-200 shadow-xs">
                <span class="text-lg">⚠️</span>
                <div>
                    <h4 class="font-bold">Timeout Detected: Reconciliation Required</h4>
                    <p class="mt-0.5 leading-relaxed">
                        A timeout occurred after dispatch. The insurer may have already created this policy in its system.
                        To avoid double-issuing, this request is locked. Click <strong>"Reconcile with Insurer"</strong> above to safely query the status.
                    </p>
                </div>
            </div>
        @elseif ($policy->status === 'FAILED')
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-300/80 dark:border-rose-800/80 flex items-start space-x-3 text-xs text-rose-800 dark:text-rose-200 shadow-xs">
                <span class="text-lg">❌</span>
                <div>
                    <h4 class="font-bold">Issuance Failed</h4>
                    <p class="mt-0.5 leading-relaxed">{{ $policy->failure_reason ?? 'The insurer rejected or timed out on this request.' }}</p>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-8">
            <!-- Left Column: Core Snapshot & References (7 cols) -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Immutable Pricing Snapshot Card -->
                <div class="glass-panel p-6">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200/50 dark:border-slate-800/50">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            Immutable Pricing Snapshot
                        </h2>
                        <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">
                            {{ $policy->plan_code }} Plan
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center mb-6">
                        <div class="p-3 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Days</span>
                            <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ $policy->travel_days }}</div>
                            <span class="text-[10px] text-slate-500">inclusive</span>
                        </div>

                        <div class="p-3 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Age Factor</span>
                            <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ $policy->age_multiplier }}x</div>
                            <span class="text-[10px] text-slate-500">{{ $policy->age_at_trip_start }} yrs old</span>
                        </div>

                        <div class="p-3 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Destination</span>
                            <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ $policy->destination_multiplier }}x</div>
                            <span class="text-[10px] text-slate-500">{{ $policy->destination }}</span>
                        </div>

                        <div class="p-3 rounded-2xl bg-white/60 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Coverage</span>
                            <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ $policy->coverage_multiplier }}x</div>
                            <span class="text-[10px] text-slate-500">₹{{ number_format($policy->sum_insured) }}</span>
                        </div>
                    </div>

                    <!-- Final Calculated Premium -->
                    <div class="p-4 rounded-2xl bg-gradient-to-r from-indigo-500/10 via-sky-500/10 to-indigo-500/5 border border-indigo-200/50 dark:border-indigo-800/50 flex items-center justify-between">
                        <div>
                            <span class="text-xs text-slate-500 font-semibold">Accepted Premium Snapshot</span>
                            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-0.5">
                                ₹{{ number_format($policy->calculated_premium, 2) }}
                            </div>
                        </div>
                        <div class="text-right text-[11px] font-mono text-slate-500">
                            {{ $policy->base_daily_rate }} × {{ $policy->travel_days }} × {{ $policy->age_multiplier }} × {{ $policy->destination_multiplier }} × {{ $policy->coverage_multiplier }}
                        </div>
                    </div>
                </div>

                <!-- Provider References & Identity Card -->
                <div class="glass-panel p-6">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200/50 dark:border-slate-800/50">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            Identifiers & Provider References
                        </h2>
                        <span class="text-xs font-mono text-slate-400">Attempts: {{ $policy->attempt_count }}</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
                        <div class="p-3 rounded-xl bg-white/50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Policy Number</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white block mt-1">
                                {{ $policy->policy_number ?? 'Not yet issued' }}
                            </span>
                        </div>

                        <div class="p-3 rounded-xl bg-white/50 dark:bg-slate-900/50 border border-slate-200/60 dark:border-slate-800/60">
                            <span class="text-slate-400 text-[10px] uppercase font-bold block">Provider Reference</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-white block mt-1">
                                {{ $policy->provider_reference ?? 'Pending dispatch' }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer Trip Dates -->
                    <div class="mt-4 pt-4 border-t border-slate-200/50 dark:border-slate-800/50 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Trip Dates</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold block mt-0.5">
                                {{ $policy->trip_start->format('d M Y') }} – {{ $policy->trip_end->format('d M Y') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Date of Birth</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold block mt-0.5">
                                {{ $policy->date_of_birth->format('d M Y') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-bold">Issued Timestamp</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold block mt-0.5">
                                {{ $policy->issued_at ? $policy->issued_at->format('d M Y H:i:s') : 'Pending' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Issuance Attempts Table -->
                <div class="glass-panel p-6">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200/50 dark:border-slate-800/50">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            Issuance Attempts Ledger
                        </h2>
                        <span class="text-xs font-mono text-slate-400">Total: {{ $policy->attempts->count() }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs font-mono">
                            <thead>
                                <tr class="text-slate-400 border-b border-slate-200/60 dark:border-slate-800/60 pb-2">
                                    <th class="pb-2">#</th>
                                    <th class="pb-2">Idempotency Key</th>
                                    <th class="pb-2">Scenario</th>
                                    <th class="pb-2">Status</th>
                                    <th class="pb-2">Duration</th>
                                    <th class="pb-2 text-right">Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/50 dark:divide-slate-800/50">
                                @forelse ($policy->attempts as $att)
                                    <tr>
                                        <td class="py-2.5 font-bold">{{ $att->attempt_number }}</td>
                                        <td class="py-2.5 text-slate-600 dark:text-slate-400 text-[11px]">{{ $att->idempotency_key }}</td>
                                        <td class="py-2.5 text-slate-500">{{ $att->scenario ?? 'DEFAULT' }}</td>
                                        <td class="py-2.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                                @if($att->status === 'SUCCESS') bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300
                                                @elseif($att->status === 'TIMEOUT') bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300
                                                @else bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 @endif">
                                                {{ $att->status }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 text-slate-500">{{ $att->duration_ms ? $att->duration_ms . 'ms' : '—' }}</td>
                                        <td class="py-2.5 text-right text-slate-400 text-[11px]">{{ $att->created_at->format('H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-4 text-center text-slate-400 text-xs">No attempts dispatched yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Vertical Audit Timeline (5 cols) -->
            <div class="lg:col-span-5 glass-panel p-6 sticky top-24">
                <div class="flex items-center justify-between pb-3 mb-5 border-b border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Audit State Timeline</h2>
                    </div>
                    <span class="text-xs font-mono text-slate-400">{{ $policy->stateHistory->count() }} transitions</span>
                </div>

                <x-timeline :items="$policy->stateHistory" />
            </div>
        </div>
    </div>
</x-app-layout>
