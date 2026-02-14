<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Doc2Memo - Generador de Memorias Técnicas')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased flex flex-col dark:bg-slate-950 dark:text-slate-100">
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-sky-100 via-cyan-50 to-transparent dark:from-slate-900 dark:via-slate-900/40 dark:to-transparent"></div>

    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur dark:border-slate-800 dark:bg-slate-900/85">
        <nav class="mx-auto flex h-16 w-full max-w-[90rem] items-center justify-between px-4 sm:px-6 lg:px-8 2xl:max-w-none 2xl:px-10">
            <a href="{{ route('tenders.index') }}" class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-sm font-semibold text-sky-700 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300">
                <span class="inline-flex h-2 w-2 rounded-full bg-sky-500"></span>
                Doc2Memo
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <button id="theme-toggle" type="button" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-md border border-slate-300 text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white" aria-label="Cambiar tema">
                    <svg id="theme-toggle-sun" class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2"></path>
                        <path d="M12 20v2"></path>
                        <path d="m4.93 4.93 1.41 1.41"></path>
                        <path d="m17.66 17.66 1.41 1.41"></path>
                        <path d="M2 12h2"></path>
                        <path d="M20 12h2"></path>
                        <path d="m6.34 17.66-1.41 1.41"></path>
                        <path d="m19.07 4.93-1.41 1.41"></path>
                    </svg>
                    <svg id="theme-toggle-moon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3a7.5 7.5 0 1 0 9 9A9 9 0 1 1 12 3Z"></path>
                    </svg>
                </button>
                <a href="{{ route('tenders.index') }}" class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                    Licitaciones
                </a>
                <a href="{{ route('technical-memories.operational-metrics') }}" class="rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                    Metricas
                </a>
                <a href="{{ route('tenders.create') }}" class="inline-flex cursor-pointer items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-sky-700">
                    Nueva Licitación
                </a>
            </div>
        </nav>
    </header>

    <main class="mx-auto w-full max-w-[90rem] flex-1 px-4 py-6 sm:px-6 lg:px-8 2xl:max-w-none 2xl:px-10">
        {{-- Flash Messages --}}
        @if(session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif

        @if(session('error'))
            <x-ui.alert type="error" :message="session('error')" />
        @endif

        @if(session('info'))
            <x-ui.alert type="info" :message="session('info')" />
        @endif

        @if(session('warning'))
            <x-ui.alert type="warning" :message="session('warning')" />
        @endif

        @yield('content')
    </main>

    <footer class="mt-auto border-t border-slate-200 bg-white/85 dark:border-slate-800 dark:bg-slate-900/90">
        <div class="mx-auto max-w-[90rem] px-4 py-6 sm:px-6 lg:px-8 2xl:max-w-none 2xl:px-10">
            <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                Doc2Memo - Generador de Memorias Técnicas con IA
            </p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
