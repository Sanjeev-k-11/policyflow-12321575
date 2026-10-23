<x-app-layout>
    <x-slot name="title">Policy Requests</x-slot>

    
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                Policy Requests
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Full ledger of policy purchase requests, immutable premiums, and issuance state.
            </p>
        </div>

        <a href="{{ route('requests.create') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-md shadow-indigo-500/25 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Create New Request</span>
        </a>
    </div>

    
    <div class="glass-panel p-5 mb-6">
        <form method="GET" action="{{ route('requests.index') }}" class="flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between">
            
            <div class="relative flex-1 max-w-md">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by request ID, customer, agent, policy..."
                       class="w-full glass-input rounded-xl px-4 py-2 text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 pl-10 focus:ring-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            
            <div class="flex flex-wrap items-center gap-1.5 overflow-x-auto pb-1 md:pb-0">
                <a href="{{ route('requests.index') }}" 
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ !request('status') ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/70 dark:bg-slate-800/70 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 border border-slate-200/70 dark:border-slate-700/70' }}">
                    All
                </a>
                @foreach ($statuses as $st)
                    <a href="{{ route('requests.index', ['status' => $st, 'search' => request('search')]) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ request('status') === $st ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white/70 dark:bg-slate-800/70 text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-800 border border-slate-200/70 dark:border-slate-700/70' }}">
                        {{ str_replace('_', ' ', $st) }}
                    </a>
                @endforeach
            </div>
        </form>
    </div>

    
    <div class="glass-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-100/60 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider border-b border-slate-200/60 dark:border-slate-800/60">
                        <th class="px-5 py-3.5">Request ID</th>
                        <th class="px-5 py-3.5">Agent</th>
                        <th class="px-5 py-3.5">Customer & Trip</th>
                        <th class="px-5 py-3.5">Plan / Coverage</th>
                        <th class="px-5 py-3.5">Calculated Premium</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Policy Number</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/50 dark:divide-slate-800/50">
                    @forelse ($requests as $item)
                        <tr class="hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition-colors group">
                            
                            <td class="px-5 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('requests.show', $item->id) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center space-x-1.5">
                                    <span>{{ $item->client_request_id }}</span>
                                    <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <span class="text-[10px] text-slate-400 font-normal block">{{ $item->created_at->format('M d, H:i') }}</span>
                            </td>

                            
                            <td class="px-5 py-4 font-mono font-semibold text-slate-600 dark:text-slate-400">
                                {{ $item->agent_id }}
                            </td>

                            
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-800 dark:text-slate-200">{{ $item->customer_name }}</div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ $item->destination }} • {{ $item->travel_days }} {{ Str::plural('day', $item->travel_days) }}
                                </div>
                            </td>

                            
                            <td class="px-5 py-4">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $item->plan_code }}</span>
                                <span class="text-slate-400 block text-[11px]">₹{{ number_format($item->sum_insured) }}</span>
                            </td>

                            
                            <td class="px-5 py-4 font-mono font-extrabold text-slate-900 dark:text-white text-sm">
                                ₹{{ number_format($item->calculated_premium, 2) }}
                            </td>

                            
                            <td class="px-5 py-4">
                                <x-status-badge :status="$item->status" size="sm" />
                            </td>

                            
                            <td class="px-5 py-4 font-mono text-[11px]">
                                @if ($item->policy_number)
                                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 font-bold">
                                        {{ $item->policy_number }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('requests.show', $item->id) }}" class="inline-flex items-center space-x-1 px-3 py-1.5 rounded-xl text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-white/80 dark:bg-slate-800/80 hover:bg-indigo-600 hover:text-white border border-indigo-200 dark:border-indigo-800 transition-all shadow-2xs">
                                    <span>Details</span>
                                    <span>→</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                No policy requests match the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        
        @if ($requests->hasPages())
            <div class="px-5 py-4 border-t border-slate-200/60 dark:border-slate-800/60 bg-white/30 dark:bg-slate-900/30">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
