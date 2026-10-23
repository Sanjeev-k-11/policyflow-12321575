@props([
    'title' => null,
    'subtitle' => null,
    'class' => '',
    'hover' => true,
])

<div {{ $attributes->merge(['class' => 'glass-panel p-6 ' . ($hover ? 'glass-panel-hover ' : '') . $class]) }}>
    @if ($title || isset($headerAction))
        <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-200/50 dark:border-slate-800/50">
            <div>
                @if ($title)
                    <h3 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ $title }}
                    </h3>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
            @if (isset($headerAction))
                <div>
                    {{ $headerAction }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}
</div>
