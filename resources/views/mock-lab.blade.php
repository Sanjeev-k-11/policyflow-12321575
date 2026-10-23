<x-app-layout>
    <x-slot name="title">Mock Insurer Laboratory</x-slot>

    <div class="max-w-6xl mx-auto"
         x-data="{
             selectedScenario: 'SUCCESS',
             selectedPolicyId: '{{ $recentPolicies->first()?->id }}',
             isRunning: false,
             labOutput: null,

             // Run test issuance scenario
             runScenario() {
                 this.isRunning = true;
                 this.labOutput = null;

                 // First generate a quick unique intake request for this test
                 const clientReqId = 'LAB-' + Math.floor(1000 + Math.random() * 9000);
                 const payload = {
                     client_request_id: clientReqId,
                     agent_id: 'AGENT-LAB',
                     customer_name: 'Test Candidate',
                     date_of_birth: '1995-01-01',
                     destination: 'INTERNATIONAL',
                     trip_start: '2026-11-01',
                     trip_end: '2026-11-05',
                     plan_code: 'PLUS',
                     sum_insured: 300000,
                     scenario: this.selectedScenario
                 };

                 fetch('/api/policy-requests', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                     },
                     body: JSON.stringify(payload)
                 })
                 .then(r => r.json())
                 .then(intakeRes => {
                     const policyId = intakeRes.data.id;
                     // Now dispatch submit with chosen scenario
                     return fetch('/api/policy-requests/' + policyId + '/submit?scenario=' + this.selectedScenario, {
                         method: 'POST',
                         headers: {
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                         }
                     })
                     .then(async r => {
                         const submitRes = await r.json();
                         return {
                             intake: intakeRes,
                             submit: submitRes,
                             policy_id: policyId,
                             status_code: r.status
                         };
                     });
                 })
                 .then(finalOutput => {
                     this.isRunning = false;
                     this.labOutput = finalOutput;
                     $dispatch('toast', { message: 'Scenario [' + this.selectedScenario + '] executed!', type: 'success' });
                 })
                 .catch(err => {
                     this.isRunning = false;
                     $dispatch('toast', { message: 'Lab execution failed', type: 'error' });
                 });
             },

             // Test duplicate callback delivery 3 times
             testDuplicateCallback() {
                 this.isRunning = true;
                 this.labOutput = null;

                 fetch('/api/mock-insurer/simulate-duplicate-callback', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                     },
                     body: JSON.stringify({
                         provider_reference: 'REF-LAB-CALLBACK-' + Math.floor(1000 + Math.random() * 9000)
                     })
                 })
                 .then(r => r.json())
                 .then(data => {
                     this.isRunning = false;
                     this.labOutput = data;
                     $dispatch('toast', { message: 'Duplicate callback test delivered 3 times!', type: 'success' });
                 })
                 .catch(() => {
                     this.isRunning = false;
                     $dispatch('toast', { message: 'Failed to simulate duplicate callback', type: 'error' });
                 });
             }
         }">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center space-x-3">
                <span>🧪 Mock Insurer Reliability Lab</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Deterministic test bench for evaluators and reviewers to test failure, timeout, and duplicate reliability live.
            </p>
        </div>

        <!-- Scenario Selector Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
            <!-- 1. SUCCESS -->
            <button type="button" @click="selectedScenario = 'SUCCESS'"
                    class="glass-panel p-4 text-left transition-all relative overflow-hidden"
                    :class="selectedScenario === 'SUCCESS' ? 'ring-2 ring-emerald-500 border-emerald-500 shadow-md' : 'opacity-80 hover:opacity-100'">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 flex items-center justify-center font-bold text-sm mb-2">
                    ✓
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">SUCCESS</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">
                    Simulates immediate confirmation and unique policy_number issuance.
                </p>
            </button>

            <!-- 2. TIMEOUT_AFTER_ISSUE -->
            <button type="button" @click="selectedScenario = 'TIMEOUT_AFTER_ISSUE'"
                    class="glass-panel p-4 text-left transition-all relative overflow-hidden"
                    :class="selectedScenario === 'TIMEOUT_AFTER_ISSUE' ? 'ring-2 ring-orange-500 border-orange-500 shadow-md' : 'opacity-80 hover:opacity-100'">
                <div class="w-8 h-8 rounded-xl bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300 flex items-center justify-center font-bold text-sm mb-2">
                    ⏳
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">TIMEOUT_AFTER_ISSUE</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">
                    Remote policy issued, but client times out. Engine flags NEEDS_RECONCILIATION.
                </p>
            </button>

            <!-- 3. DUPLICATE_CALLBACK -->
            <button type="button" @click="selectedScenario = 'DUPLICATE_CALLBACK'"
                    class="glass-panel p-4 text-left transition-all relative overflow-hidden"
                    :class="selectedScenario === 'DUPLICATE_CALLBACK' ? 'ring-2 ring-indigo-500 border-indigo-500 shadow-md' : 'opacity-80 hover:opacity-100'">
                <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 flex items-center justify-center font-bold text-sm mb-2">
                    🔄
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">DUPLICATE_CALLBACK</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">
                    Dispatches identical webhook event 3 times; processed once idempotently.
                </p>
            </button>

            <!-- 4. DELAYED_CALLBACK -->
            <button type="button" @click="selectedScenario = 'DELAYED_CALLBACK'"
                    class="glass-panel p-4 text-left transition-all relative overflow-hidden"
                    :class="selectedScenario === 'DELAYED_CALLBACK' ? 'ring-2 ring-amber-500 border-amber-500 shadow-md' : 'opacity-80 hover:opacity-100'">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300 flex items-center justify-center font-bold text-sm mb-2">
                    ⏱️
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">DELAYED_CALLBACK</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">
                    Returns PENDING acknowledgement. Engine transitions to PENDING_CONFIRMATION.
                </p>
            </button>

            <!-- 5. TEMPORARY_FAILURE -->
            <button type="button" @click="selectedScenario = 'TEMPORARY_FAILURE'"
                    class="glass-panel p-4 text-left transition-all relative overflow-hidden"
                    :class="selectedScenario === 'TEMPORARY_FAILURE' ? 'ring-2 ring-rose-500 border-rose-500 shadow-md' : 'opacity-80 hover:opacity-100'">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 flex items-center justify-center font-bold text-sm mb-2">
                    ⚠️
                </div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white">TEMPORARY_FAILURE</h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">
                    Insurer gateway error. Policy marks FAILED, allowing safe retry.
                </p>
            </button>
        </div>

        <!-- Action Bench & Terminal Output -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Controls Card (4 cols) -->
            <div class="lg:col-span-4 glass-panel p-6 space-y-5">
                <div class="pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Selected Scenario</span>
                    <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5" x-text="selectedScenario"></div>
                </div>

                <div class="space-y-3">
                    <button type="button" @click="runScenario()" :disabled="isRunning"
                            class="w-full py-3 px-4 rounded-xl text-xs font-extrabold text-white bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-md shadow-indigo-500/25 transition-all flex items-center justify-center space-x-2 disabled:opacity-50">
                        <svg x-show="isRunning" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>🚀 Fire Scenario Test Request</span>
                    </button>

                    <button type="button" @click="testDuplicateCallback()" :disabled="isRunning"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 transition-colors flex items-center justify-center space-x-2">
                        <span>⚡ Test 3x Duplicate Webhook</span>
                    </button>
                </div>

                <div class="pt-4 border-t border-slate-200/50 dark:border-slate-800/50 text-[11px] text-slate-500 space-y-2">
                    <div class="font-bold text-slate-700 dark:text-slate-300">How reviewers can verify:</div>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Watch the live response payload update on the right.</li>
                        <li>For TIMEOUT_AFTER_ISSUE, navigate to the generated request to see the reconciliation action.</li>
                        <li>For DUPLICATE_CALLBACK, notice delivery #1 processed while #2 & #3 are safely ignored.</li>
                    </ul>
                </div>
            </div>

            <!-- Terminal / Live Result Card (8 cols) -->
            <div class="lg:col-span-8 glass-panel p-6">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200/50 dark:border-slate-800/50">
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                        <span class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200">Execution Output & Payloads</span>
                    </div>

                    <template x-if="labOutput && labOutput.policy_id">
                        <a :href="'/requests/' + labOutput.policy_id" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                            Inspect Request Audit Trail →
                        </a>
                    </template>
                </div>

                <div class="bg-slate-950 rounded-2xl p-4 font-mono text-xs text-slate-200 min-h-[380px] max-h-[500px] overflow-y-auto border border-slate-800 shadow-inner">
                    <template x-if="isRunning">
                        <div class="flex items-center space-x-2 text-indigo-400 py-6">
                            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Dispatching request and awaiting simulated external insurer response...</span>
                        </div>
                    </template>

                    <template x-if="!isRunning && labOutput">
                        <pre class="whitespace-pre-wrap leading-relaxed" x-text="JSON.stringify(labOutput, null, 2)"></pre>
                    </template>

                    <template x-if="!isRunning && !labOutput">
                        <div class="text-slate-500 py-24 text-center">
                            <span class="text-3xl block mb-2">🔬</span>
                            Select a scenario above and click <strong>"Fire Scenario Test Request"</strong> or <strong>"Test 3x Duplicate Webhook"</strong> to view live execution logs and reliability handling.
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
