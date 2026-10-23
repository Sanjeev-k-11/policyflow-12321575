@props([
    'status',
    'size' => 'md', // sm, md, lg
])

@php
    $status = strtoupper($status);

    $colors = [
        'RECEIVED' => 'bg-slate-100 text-slate-700 border-slate-300/80 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700 dot-slate-500',
        'VALIDATED' => 'bg-sky-50 text-sky-700 border-sky-300/80 dark:bg-sky-950/70 dark:text-sky-300 dark:border-sky-800 dot-sky-500',
        'SUBMITTED' => 'bg-indigo-50 text-indigo-700 border-indigo-300/80 dark:bg-indigo-950/70 dark:text-indigo-300 dark:border-indigo-800 dot-indigo-500',
        'PENDING_CONFIRMATION' => 'bg-amber-50 text-amber-700 border-amber-300/80 dark:bg-amber-950/70 dark:text-amber-300 dark:border-amber-800 dot-amber-500 animate-pulse-subtle',
        'ISSUED' => 'bg-emerald-50 text-emerald-700 border-emerald-300/80 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border-emerald-800 dot-emerald-500',
        'FAILED' => 'bg-rose-50 text-rose-700 border-rose-300/80 dark:bg-rose-950/70 dark:text-rose-300 dark:border-rose-800 dot-rose-500',
        'NEEDS_RECONCILIATION' => 'bg-orange-50 text-orange-800 border-orange-300/80 dark:bg-orange-950/70 dark:text-orange-300 dark:border-orange-800 dot-orange-500 animate-pulse',
    ];

    $dotColors = [
        'RECEIVED' => 'bg-slate-500',
        'VALIDATED' => 'bg-sky-500',
        'SUBMITTED' => 'bg-indigo-500',
        'PENDING_CONFIRMATION' => 'bg-amber-500',
        'ISSUED' => 'bg-emerald-500',
        'FAILED' => 'bg-rose-500',
        'NEEDS_RECONCILIATION' => 'bg-orange-500',
    ];

    $classes = $colors[$status] ?? 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dot-slate-500';
    $dotClass = $dotColors[$status] ?? 'bg-slate-500';

    $sizeClasses = [
        'sm' => 'px-2 py-0.5 text-[10px]',
        'md' => 'px-2.5 py-1 text-xs',
        'lg' => 'px-3.5 py-1.5 text-sm',
    ][$size] ?? 'px-2.5 py-1 text-xs';
@endphp

<span class="inline-flex items-center space-x-1.5 rounded-full font-semibold border shadow-2xs transition-all {{ $classes }} {{ $sizeClasses }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }}"></span>
    <span class="tracking-wide">{{ str_replace('_', ' ', $status) }}</span>
</span>
