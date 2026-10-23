<x-app-layout>
    <x-slot name="title">New Policy Intake</x-slot>

    <div class="w-full space-y-6"
         x-data="{
             form: {
                 client_request_id: 'AGT-' + new Date().getFullYear() + '-' + Math.floor(1000 + Math.random() * 9000),
                 agent_id: 'AGENT-17',
                 customer_name: 'Sample Customer',
                 date_of_birth: '1998-04-10',
                 destination: 'INTERNATIONAL',
                 trip_start: '2026-11-10',
                 trip_end: '2026-11-14',
                 plan_code: 'PLUS',
                 sum_insured: 300000,
                 scenario: 'SUCCESS'
             },
             pricing: {
                 travel_days: 5,
                 age_at_trip_start: 28,
                 base_daily_rate: '20.00',
                 age_multiplier: '1.00',
                 destination_multiplier: '1.80',
                 coverage_multiplier: '1.25',
                 calculated_premium: '225.00'
             },
             errors: {},
             submitting: false,
             calculating: false,

             // Live recalculation on input change
             calculateLive() {
                 if (!this.form.plan_code || !this.form.sum_insured || !this.form.destination || !this.form.date_of_birth || !this.form.trip_start || !this.form.trip_end) {
                     return;
                 }
                 this.calculating = true;
                 fetch('/api/pricing/preview', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                     },
                     body: JSON.stringify(this.form)
                 })
                 .then(r => r.json())
                 .then(res => {
                     this.calculating = false;
                     if (res.success) {
                         this.pricing = res.data;
                         this.errors.trip_end = null;
                     } else if (res.message) {
                         if (res.message.includes('Trip end date')) {
                             this.errors.trip_end = [res.message];
                         }
                     }
                 })
                 .catch(() => { this.calculating = false; });
             },

             // Submit request to intake endpoint
             submitForm() {
                 this.submitting = true;
                 this.errors = {};
                 fetch('/api/policy-requests', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'Accept': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                     },
                     body: JSON.stringify(this.form)
                 })
                 .then(async r => {
                     const data = await r.json();
                     this.submitting = false;
                     if (r.status === 201 || r.status === 200) {
                         $dispatch('toast', {
                             message: data.is_duplicate ? 'Duplicate intake resolved safely (200 OK).' : 'New policy intake created successfully (201 Created)!',
                             type: 'success'
                         });
                         setTimeout(() => {
                             window.location.href = '/requests/' + data.data.id;
                         }, 800);
                     } else if (r.status === 422) {
                         this.errors = data.errors || {};
                         $dispatch('toast', { message: 'Validation failed. Please correct form fields.', type: 'error' });
                     } else {
                         $dispatch('toast', { message: data.message || 'Submission error', type: 'error' });
                     }
                 })
                 .catch(err => {
                     this.submitting = false;
                     $dispatch('toast', { message: 'Network error submitting request', type: 'error' });
                 });
             }
         }"
         x-init="calculateLive()">

        <!-- Breadcrumb / Title -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <a href="{{ route('requests.index') }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center space-x-1 mb-1">
                    <span>← Back to Requests</span>
                </a>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Policy Request Intake
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Submit customer details to compute an immutable snapshot and dispatch issuance.
                </p>
            </div>

            <!-- Fast Spec Preset Button -->
            <button type="button" @click="
                form.client_request_id = 'AGT-2026-0001';
                form.agent_id = 'AGENT-17';
                form.customer_name = 'Sample Customer';
                form.date_of_birth = '1998-04-10';
                form.destination = 'INTERNATIONAL';
                form.trip_start = '2026-11-10';
                form.trip_end = '2026-11-14';
                form.plan_code = 'PLUS';
                form.sum_insured = 300000;
                calculateLive();
                $dispatch('toast', { message: 'Specification sample loaded (₹225.00 preview)', type: 'success' });
            " class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 transition-colors shadow-2xs">
                ⚡ Load Challenge Example (₹225.00)
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <!-- Left Form Area (7 cols) -->
            <div class="lg:col-span-7 glass-panel p-6">
                <form @submit.prevent="submitForm()" class="space-y-5">
                    <!-- Client Request ID & Agent ID -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                                Client Request ID <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="form.client_request_id" required
                                   class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 dark:text-slate-100">
                            <template x-if="errors.client_request_id">
                                <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.client_request_id[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                                Agent ID <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="form.agent_id" required
                                   class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-800 dark:text-slate-100">
                            <template x-if="errors.agent_id">
                                <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.agent_id[0]"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Customer Name & Date of Birth -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                                Customer Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" x-model="form.customer_name" required placeholder="e.g. Alice Sharma"
                                   class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-800 dark:text-slate-100">
                            <template x-if="errors.customer_name">
                                <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.customer_name[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                                Date of Birth <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" x-model="form.date_of_birth" @change="calculateLive()" required
                                   class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-mono text-slate-800 dark:text-slate-100">
                            <template x-if="errors.date_of_birth">
                                <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.date_of_birth[0]"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Destination & Plan Code -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                                Destination <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="form.destination" @change="calculateLive()"
                                    class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 dark:text-slate-100">
                                <option value="DOMESTIC">DOMESTIC (1.00x)</option>
                                <option value="INTERNATIONAL">INTERNATIONAL (1.80x)</option>
                            </select>
                            <template x-if="errors.destination">
                                <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.destination[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                                Plan Code <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="form.plan_code" @change="calculateLive()"
                                    class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 dark:text-slate-100">
                                @foreach ($plans as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <template x-if="errors.plan_code">
                                <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.plan_code[0]"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Coverage Sum Insured -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                            Coverage (Sum Insured) <span class="text-rose-500">*</span>
                        </label>
                        <select x-model="form.sum_insured" @change="calculateLive()"
                                class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 dark:text-slate-100">
                            @foreach ($coverages as $amount => $label)
                                <option value="{{ $amount }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <template x-if="errors.sum_insured">
                            <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.sum_insured[0]"></p>
                        </template>
                    </div>

                    <!-- Trip Start & Trip End -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                                Trip Start <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" x-model="form.trip_start" @change="calculateLive()" required
                                   class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-mono text-slate-800 dark:text-slate-100">
                            <template x-if="errors.trip_start">
                                <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.trip_start[0]"></p>
                            </template>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                                Trip End <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" x-model="form.trip_end" @change="calculateLive()" required
                                   class="w-full glass-input rounded-xl px-3.5 py-2.5 text-xs font-mono text-slate-800 dark:text-slate-100">
                            <template x-if="errors.trip_end">
                                <p class="text-[11px] text-rose-500 mt-1 font-medium" x-text="errors.trip_end[0]"></p>
                            </template>
                        </div>
                    </div>

                    <!-- Mock Insurer Scenario Flag (Dev Testing) -->
                    <div class="p-3.5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/40 border border-indigo-200/50 dark:border-indigo-800/50">
                        <label class="block text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300 mb-1">
                            🧪 Insurer Simulation Scenario
                        </label>
                        <select x-model="form.scenario"
                                class="w-full glass-input rounded-xl px-3.5 py-2 text-xs font-mono text-slate-800 dark:text-slate-100">
                            @foreach ($scenarios as $sc)
                                <option value="{{ $sc }}">{{ $sc }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-indigo-500 dark:text-indigo-400 mt-1">
                            Configures mock insurer outcome for this request to test reliability cases live.
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" :disabled="submitting"
                                class="w-full py-3 px-4 rounded-xl text-sm font-extrabold text-white bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-lg shadow-indigo-500/25 transition-all flex items-center justify-center space-x-2 disabled:opacity-50">
                            <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="submitting ? 'Processing Intake...' : 'Create Policy Request (POST /api/policy-requests)'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right Live Premium Preview Card (5 cols) -->
            <div class="lg:col-span-5 glass-panel p-6 sticky top-24">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200/50 dark:border-slate-800/50">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        Live Pricing Calculator
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        Decimal-Safe BCMath
                    </span>
                </div>

                <!-- Big Premium Callout -->
                <div class="p-5 rounded-2xl bg-gradient-to-tr from-indigo-600/10 via-sky-500/10 to-indigo-500/5 border border-indigo-200/60 dark:border-indigo-800/60 text-center mb-6">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Estimated Total Premium</span>
                    <div class="text-4xl font-black tracking-tight text-slate-900 dark:text-white font-mono mt-1">
                        ₹<span x-text="pricing.calculated_premium"></span>
                    </div>
                    <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold block mt-1">
                        Inclusive of all multiplier factors
                    </span>
                </div>

                <!-- Multiplier Breakdown Grid -->
                <div class="space-y-3 text-xs">
                    <div class="flex justify-between items-center py-1.5 border-b border-slate-200/40 dark:border-slate-800/40">
                        <span class="text-slate-500">Plan Base Daily Rate:</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200">₹<span x-text="pricing.base_daily_rate"></span> / day</span>
                    </div>

                    <div class="flex justify-between items-center py-1.5 border-b border-slate-200/40 dark:border-slate-800/40">
                        <span class="text-slate-500">Travel Days (inclusive):</span>
                        <span class="font-mono font-bold text-slate-800 dark:text-slate-200"><span x-text="pricing.travel_days"></span> days</span>
                    </div>

                    <div class="flex justify-between items-center py-1.5 border-b border-slate-200/40 dark:border-slate-800/40">
                        <span class="text-slate-500">Age at Trip Start (<span x-text="pricing.age_at_trip_start"></span> yrs):</span>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400"><span x-text="pricing.age_multiplier"></span>x</span>
                    </div>

                    <div class="flex justify-between items-center py-1.5 border-b border-slate-200/40 dark:border-slate-800/40">
                        <span class="text-slate-500">Destination (<span x-text="form.destination"></span>):</span>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400"><span x-text="pricing.destination_multiplier"></span>x</span>
                    </div>

                    <div class="flex justify-between items-center py-1.5 border-b border-slate-200/40 dark:border-slate-800/40">
                        <span class="text-slate-500">Coverage Multiplier:</span>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400"><span x-text="pricing.coverage_multiplier"></span>x</span>
                    </div>
                </div>

                <!-- Formula Representation -->
                <div class="mt-5 p-3 rounded-xl bg-slate-100/70 dark:bg-slate-900/70 text-[11px] font-mono text-slate-600 dark:text-slate-400 border border-slate-200/50 dark:border-slate-800/50 leading-relaxed">
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">Formula:</span><br>
                    <span x-text="pricing.base_daily_rate"></span> × <span x-text="pricing.travel_days"></span> × <span x-text="pricing.age_multiplier"></span> × <span x-text="pricing.destination_multiplier"></span> × <span x-text="pricing.coverage_multiplier"></span> = ₹<span x-text="pricing.calculated_premium"></span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
