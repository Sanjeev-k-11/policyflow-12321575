@props([
    'items',
])

<div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200 dark:before:bg-slate-800">
    @forelse ($items as $entry)
        <div class="relative group">
            
            <div class="absolute -left-[1.45rem] top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 shadow-sm transition-transform group-hover:scale-125
                @if($entry->to_status === 'ISSUED') bg-emerald-500 shadow-emerald-500/50
                @elseif($entry->to_status === 'FAILED') bg-rose-500 shadow-rose-500/50
                @elseif($entry->to_status === 'NEEDS_RECONCILIATION') bg-orange-500 animate-pulse
                @elseif($entry->to_status === 'PENDING_CONFIRMATION') bg-amber-500
                @elseif($entry->to_status === 'SUBMITTED') bg-indigo-500
                @elseif($entry->to_status === 'VALIDATED') bg-sky-500
                @else bg-slate-400 @endif">
            </div>

            <div class="bg-white/50 dark:bg-slate-900/50 rounded-2xl p-4 border border-slate-200/60 dark:border-slate-800/60 hover:bg-white/80 dark:hover:bg-slate-900/80 transition-colors shadow-2xs">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-bold font-mono tracking-wider text-slate-800 dark:text-slate-200">
                            {{ $entry->trigger }}
                        </span>
                        <span class="text-slate-400 dark:text-slate-600">→</span>
                        <x-status-badge :status="$entry->to_status" size="sm" />
                    </div>

                    <time class="text-[11px] font-mono text-slate-400 dark:text-slate-500">
                        {{ $entry->created_at->format('M d, Y H:i:s') }}
                    </time>
                </div>

                @if ($entry->reason)
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                        {{ $entry->reason }}
                    </p>
                @endif

                @if ($entry->metadata)
                    <div class="mt-2 text-[11px] font-mono bg-slate-100/70 dark:bg-slate-950/70 rounded-xl p-2.5 text-slate-600 dark:text-slate-400 border border-slate-200/50 dark:border-slate-800/50 overflow-x-auto">
                        <pre class="whitespace-pre-wrap">{{ json_encode($entry->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <p class="text-xs text-slate-400">No transition history recorded yet.</p>
    @endforelse
</div>
