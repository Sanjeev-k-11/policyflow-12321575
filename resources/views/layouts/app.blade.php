<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | PolicyFlow Insurance Engine</title>
    <meta name="description" content="Fault-tolerant insurance issuance and reconciliation engine with deterministic pricing, idempotent intake, and zero-duplicate guarantees.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        // Check dark mode preference
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased selection:bg-indigo-500 selection:text-white"
      x-data="{
          darkMode: document.documentElement.classList.contains('dark'),
          toggleDarkMode() {
              this.darkMode = !this.darkMode;
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
                  localStorage.setItem('color-theme', 'dark');
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.setItem('color-theme', 'light');
              }
          },
          toast: { show: false, message: '', type: 'success' },
          showToast(msg, type = 'success') {
              this.toast.message = msg;
              this.toast.type = type;
              this.toast.show = true;
              setTimeout(() => { this.toast.show = false; }, 4000);
          }
      }"
      @toast.window="showToast($event.detail.message, $event.detail.type)">

    <!-- Background mesh gradient with floating color blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none -z-10">
        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 bg-grid-pattern opacity-60"></div>

        <!-- Floating ambient light blobs -->
        <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-indigo-500/15 blur-3xl animate-blob-float"></div>
        <div class="absolute top-1/4 -right-32 w-[32rem] h-[32rem] rounded-full bg-sky-400/15 blur-3xl animate-blob-float-slow"></div>
        <div class="absolute bottom-10 left-1/3 w-80 h-80 rounded-full bg-emerald-400/12 blur-3xl animate-blob-float"></div>
        <div class="absolute -bottom-20 right-10 w-96 h-96 rounded-full bg-peach-300/12 blur-3xl animate-blob-float-slow"></div>
    </div>

    <!-- Header / Navigation Bar -->
    <header class="sticky top-0 z-40 backdrop-blur-md bg-white/70 dark:bg-slate-900/70 border-b border-white/80 dark:border-slate-800/80 shadow-sm">
        <div class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-10">
            <div class="flex items-center justify-between h-16">
                <!-- Brand logo & tag -->
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-sky-400 flex items-center justify-center text-white shadow-md shadow-indigo-500/25 group-hover:scale-105 transition-transform duration-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-slate-900 via-indigo-950 to-indigo-800 dark:from-white dark:via-slate-200 dark:to-indigo-300 bg-clip-text text-transparent">
                                POLICY<span class="text-indigo-600 dark:text-indigo-400">FLOW</span>
                            </span>
                            <span class="hidden sm:inline-block ml-2 px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                Engine v1.0
                            </span>
                        </div>
                    </a>

                    <!-- Navigation Links -->
                    <nav class="hidden md:flex space-x-1">
                        <a href="{{ route('dashboard') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-50/80 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('requests.index') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('requests.index') ? 'bg-indigo-50/80 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60' }}">
                            Policy Requests
                        </a>
                        <a href="{{ route('requests.create') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('requests.create') ? 'bg-indigo-50/80 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60' }}">
                            + New Request
                        </a>
                        <a href="{{ route('mock-lab') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('mock-lab') ? 'bg-indigo-50/80 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/60 dark:hover:bg-slate-800/60' }}">
                            🧪 Mock Insurer Lab
                        </a>
                    </nav>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center space-x-3">
                    <!-- Engine Status Pill -->
                    <div class="hidden sm:flex items-center space-x-2 px-3 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200/70 dark:border-emerald-800/70 text-xs font-semibold text-emerald-700 dark:text-emerald-400 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Engine Active</span>
                    </div>

                    <!-- Dark Mode Toggle Button -->
                    <button @click="toggleDarkMode()" type="button" aria-label="Toggle Dark Mode" class="p-2.5 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 bg-white/60 dark:bg-slate-800/60 hover:bg-white dark:hover:bg-slate-800 border border-slate-200/70 dark:border-slate-700/70 transition-all shadow-xs">
                        <svg x-show="!darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="darkMode" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>

                    <!-- Quick Intake CTA -->
                    <a href="{{ route('requests.create') }}" class="hidden sm:inline-flex items-center space-x-2 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-sky-500 hover:from-indigo-500 hover:to-sky-400 shadow-md shadow-indigo-500/20 hover:shadow-indigo-500/35 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>New Intake</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-10 py-8">
        {{ $slot }}
    </main>

    <!-- Floating Global Toast -->
    <div x-cloak x-show="toast.show" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-3"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-3"
         class="fixed bottom-6 right-6 z-50 max-w-md w-full shadow-2xl rounded-2xl p-4 border text-sm font-semibold flex items-center space-x-3 glass-panel"
         :class="{
             'border-emerald-500/40 text-emerald-800 dark:text-emerald-300': toast.type === 'success',
             'border-rose-500/40 text-rose-800 dark:text-rose-300': toast.type === 'error',
             'border-amber-500/40 text-amber-800 dark:text-amber-300': toast.type === 'warning'
         }">
        <span class="w-2.5 h-2.5 rounded-full" :class="{
            'bg-emerald-500': toast.type === 'success',
            'bg-rose-500': toast.type === 'error',
            'bg-amber-500': toast.type === 'warning'
        }"></span>
        <div class="flex-1" x-text="toast.message"></div>
        <button @click="toast.show = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Footer -->
    <footer class="mt-16 border-t border-slate-200/60 dark:border-slate-800/60 py-8 text-center text-xs text-slate-500 dark:text-slate-400">
        <p>PolicyFlow • Fault-Tolerant Travel Insurance Issuance & Reconciliation Engine</p>
        <p class="mt-1 font-mono text-[11px] text-slate-400">Built for high-reliability idempotency, concurrency row-locking & zero-duplicate guarantees.</p>
    </footer>
</body>
</html>
