@props([
    'label',
    'value',
    'subtext' => null,
    'icon' => 'activity',
    'color' => 'indigo', // indigo, emerald, amber, rose, sky, orange
    'prefix' => '',
])

@php
    $colorClasses = [
        'indigo' => 'from-indigo-600 to-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 border-indigo-200/60 dark:border-indigo-800/60',
        'emerald' => 'from-emerald-600 to-teal-500 text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200/60 dark:border-emerald-800/60',
        'amber' => 'from-amber-600 to-amber-500 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 border-amber-200/60 dark:border-amber-800/60',
        'rose' => 'from-rose-600 to-rose-500 text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 border-rose-200/60 dark:border-rose-800/60',
        'sky' => 'from-sky-600 to-sky-500 text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/60 border-sky-200/60 dark:border-sky-800/60',
        'orange' => 'from-orange-600 to-amber-500 text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/60 border-orange-200/60 dark:border-orange-800/60',
    ][$color] ?? 'from-indigo-600 to-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 border-indigo-200/60 dark:border-indigo-800/60';
@endphp

<div class="glass-panel glass-panel-hover p-6 relative overflow-hidden"
     x-data="{ 
         current: 0, 
         target: {{ is_numeric($value) ? $value : 0 }},
         init() {
             let step = Math.max(1, Math.floor(this.target / 30));
             let timer = setInterval(() => {
                 this.current += step;
                 if (this.current >= this.target) {
                     this.current = this.target;
                     clearInterval(timer);
                 }
             }, 25);
         }
     }">
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ $label }}
            </p>
            <div class="mt-2 flex items-baseline space-x-1">
                @if ($prefix)
                    <span class="text-xl font-bold text-slate-500 dark:text-slate-400 font-mono">{{ $prefix }}</span>
                @endif
                <span class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white font-mono"
                      @if(is_numeric($value)) x-text="current.toLocaleString()" @endif>
                    {{ !is_numeric($value) ? $value : 0 }}
                </span>
            </div>
            @if ($subtext)
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                    {{ $subtext }}
                </p>
            @endif
        </div>

        <div class="w-12 h-12 rounded-2xl border flex items-center justify-center shadow-xs {{ $colorClasses }}">
            @if ($icon === 'activity')
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            @elseif ($icon === 'check-circle')
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif ($icon === 'clock')
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif ($icon === 'alert-triangle')
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            @elseif ($icon === 'refresh-cw')
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            @elseif ($icon === 'currency')
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @else
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            @endif
        </div>
    </div>
</div>
