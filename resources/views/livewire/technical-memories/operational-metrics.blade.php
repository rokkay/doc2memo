@php
    $firstPassRate = (float) ($metrics['global']['first_pass_rate'] ?? 0);
    $retryRate = (float) ($metrics['global']['retry_rate'] ?? 0);
    $failureRate = (float) ($metrics['global']['failure_rate'] ?? 0);
    $avgDurationMs = (int) ($metrics['global']['avg_duration_ms'] ?? 0);
    $totalAiCostUsd = (float) ($metrics['global']['estimated_total_ai_cost_usd'] ?? 0);
    $generationCostUsd = (float) ($metrics['global']['estimated_cost_usd'] ?? 0);
    $dynamicCostUsd = (float) ($metrics['global']['estimated_dynamic_cost_usd'] ?? 0);
    $styleCostUsd = (float) ($metrics['global']['estimated_style_editor_cost_usd'] ?? 0);
    $documentAnalysisCostUsd = (float) ($metrics['global']['estimated_document_analysis_cost_usd'] ?? 0);
    $documentAnalyzerCostUsd = (float) ($metrics['global']['estimated_document_analyzer_cost_usd'] ?? 0);
    $dedicatedExtractorCostUsd = (float) ($metrics['global']['estimated_dedicated_extractor_cost_usd'] ?? 0);
    $analyzedDocuments = (int) ($metrics['global']['analyzed_documents'] ?? 0);
@endphp

<div class="space-y-8 pb-3">
    <section class="relative overflow-hidden rounded-[2rem] border border-slate-300/70 bg-gradient-to-br from-stone-50 via-white to-amber-50 p-6 shadow-[0_24px_60px_-36px_rgba(15,23,42,0.55)] dark:border-slate-700/60 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900">
        <div class="pointer-events-none absolute -left-20 -top-20 h-64 w-64 rounded-full bg-amber-300/25 blur-3xl dark:bg-amber-500/20"></div>
        <div class="pointer-events-none absolute -right-20 -bottom-20 h-64 w-64 rounded-full bg-cyan-300/20 blur-3xl dark:bg-cyan-500/20"></div>

        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl space-y-2">
                <p class="font-mono text-xs uppercase tracking-[0.25em] text-slate-600 dark:text-slate-300">Control room</p>
                <h1 class="font-serif text-3xl font-semibold leading-tight text-slate-900 dark:text-slate-100 md:text-4xl">Metricas operativas de memoria tecnica</h1>
                <p class="text-sm text-slate-700 dark:text-slate-300">Panel unificado para monitorizar calidad, tiempos y consumo AI por categoria. Todo el coste estimado se calcula desde la misma entidad y se visualiza como una unica lectura accionable.</p>
            </div>

            <div class="grid w-full max-w-xl grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="rounded-2xl border border-slate-300/80 bg-white/75 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-400">
                    Desde
                    <input type="date" wire:model.live="from_date" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </label>

                <label class="rounded-2xl border border-slate-300/80 bg-white/75 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-400">
                    Hasta
                    <input type="date" wire:model.live="to_date" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </label>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="group rounded-2xl border border-emerald-300/60 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-700/40 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-300">First pass</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($firstPassRate, 1, ',', '.') }}%</p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-950/60">
                <div class="h-full rounded-full bg-emerald-500 transition-all duration-700" @style(['width: '.max(4, min(100, $firstPassRate)).'%'])></div>
            </div>
        </article>

        <article class="group rounded-2xl border border-amber-300/60 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-amber-700/40 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-700 dark:text-amber-300">Retry</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($retryRate, 1, ',', '.') }}%</p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-amber-100 dark:bg-amber-950/60">
                <div class="h-full rounded-full bg-amber-500 transition-all duration-700" @style(['width: '.max(4, min(100, $retryRate)).'%'])></div>
            </div>
        </article>

        <article class="group rounded-2xl border border-rose-300/60 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-rose-700/40 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-[0.2em] text-rose-700 dark:text-rose-300">Failure</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($failureRate, 1, ',', '.') }}%</p>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-rose-100 dark:bg-rose-950/60">
                <div class="h-full rounded-full bg-rose-500 transition-all duration-700" @style(['width: '.max(4, min(100, $failureRate)).'%'])></div>
            </div>
        </article>

        <article class="group rounded-2xl border border-cyan-300/60 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-cyan-700/40 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300">Duracion media</p>
            <p class="mt-2 font-mono text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ $avgDurationMs }}<span class="ml-1 text-lg font-medium">ms</span></p>
            <p class="mt-3 text-xs text-slate-600 dark:text-slate-300">Rendimiento consolidado del rango seleccionado.</p>
        </article>
    </section>

    <section class="relative overflow-hidden rounded-3xl border border-slate-300/80 bg-white p-5 shadow-[0_20px_45px_-30px_rgba(15,23,42,0.6)] dark:border-slate-700 dark:bg-slate-900">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-20 bg-gradient-to-r from-amber-200/40 via-transparent to-cyan-200/30 dark:from-amber-900/25 dark:to-cyan-900/25"></div>

        <div class="relative grid grid-cols-1 gap-5 lg:grid-cols-[1.2fr_1fr]">
            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-950/50">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Coste AI unificado</p>
                <p class="mt-2 font-mono text-4xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format($totalAiCostUsd, 4, ',', '.') }} <span class="text-xl font-medium">USD</span></p>
                <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">Documentos analizados: {{ $analyzedDocuments }}</p>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Total agregado para el rango de fechas seleccionado.</p>
            </div>

            <div class="grid grid-cols-1 gap-2 text-sm">
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-950/50">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Generacion dinamica</p>
                    <p class="font-mono text-base font-semibold text-slate-900 dark:text-slate-100">{{ number_format($dynamicCostUsd, 4, ',', '.') }} USD</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-950/50">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Edicion de estilo</p>
                    <p class="font-mono text-base font-semibold text-slate-900 dark:text-slate-100">{{ number_format($styleCostUsd, 4, ',', '.') }} USD</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-950/50">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Document analyzer</p>
                    <p class="font-mono text-base font-semibold text-slate-900 dark:text-slate-100">{{ number_format($documentAnalyzerCostUsd, 4, ',', '.') }} USD</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-800 dark:bg-slate-950/50">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Extractor dedicado</p>
                    <p class="font-mono text-base font-semibold text-slate-900 dark:text-slate-100">{{ number_format($dedicatedExtractorCostUsd, 4, ',', '.') }} USD</p>
                </div>
            </div>
        </div>

        <div class="relative mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950/50">
                <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Subtotal generacion</p>
                <p class="mt-1 font-mono text-lg font-semibold text-slate-900 dark:text-slate-100">{{ number_format($generationCostUsd, 4, ',', '.') }} USD</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950/50">
                <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Subtotal analisis documental</p>
                <p class="mt-1 font-mono text-lg font-semibold text-slate-900 dark:text-slate-100">{{ number_format($documentAnalysisCostUsd, 4, ',', '.') }} USD</p>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <h2 class="font-serif text-lg font-semibold text-slate-900 dark:text-slate-100">Memorias recientes</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Resumen de intentos y coste por memoria.</p>
            </div>
            <div class="max-h-[24rem] overflow-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Memoria</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Intentos</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Generacion</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Edicion</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Coste</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($metrics['memories'] as $memory)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ $memory['memory_title'] }}</td>
                                <td class="px-3 py-2 font-mono text-slate-700 dark:text-slate-200">{{ (int) $memory['attempts'] }}</td>
                                <td class="px-3 py-2 font-mono text-slate-700 dark:text-slate-200">{{ number_format((float) ($memory['estimated_dynamic_cost_usd'] ?? 0), 4, ',', '.') }} USD</td>
                                <td class="px-3 py-2 font-mono text-slate-700 dark:text-slate-200">{{ number_format((float) ($memory['estimated_style_editor_cost_usd'] ?? 0), 4, ',', '.') }} USD</td>
                                <td class="px-3 py-2 font-mono text-slate-700 dark:text-slate-200">{{ number_format((float) $memory['estimated_cost_usd'], 4, ',', '.') }} USD</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-sm text-slate-500 dark:text-slate-400">Sin datos para el rango seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <h2 class="font-serif text-lg font-semibold text-slate-900 dark:text-slate-100">Secciones problematicas</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Puntos de friccion con mas fallos y reintentos.</p>
            </div>
            <div class="max-h-[24rem] overflow-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Seccion</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Retries</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Fallos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($metrics['topProblematicSections'] as $section)
                            <tr class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50">
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ $section['section_title'] }}</td>
                                <td class="px-3 py-2 font-mono text-slate-700 dark:text-slate-200">{{ (int) $section['retry_count'] }}</td>
                                <td class="px-3 py-2 font-mono text-slate-700 dark:text-slate-200">{{ (int) $section['failure_count'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-sm text-slate-500 dark:text-slate-400">Sin incidencias para el rango seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-serif text-lg font-semibold text-slate-900 dark:text-slate-100">Tendencia diaria de coste AI</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Ultimos dias del rango seleccionado</p>
        </div>

        <div class="mt-4 space-y-3">
            @forelse($metrics['dailyTrend'] as $day)
                @php
                    $dayCost = (float) ($day['estimated_cost_usd'] ?? 0);
                    $dayDynamic = (float) ($day['estimated_dynamic_cost_usd'] ?? 0);
                    $dayStyle = (float) ($day['estimated_style_editor_cost_usd'] ?? 0);
                    $barWidth = $totalAiCostUsd > 0 ? min(100, max(3, ($dayCost / $totalAiCostUsd) * 100)) : 3;
                @endphp
                <div class="grid grid-cols-1 gap-2 rounded-2xl border border-slate-200 p-3 dark:border-slate-800 md:grid-cols-[140px_1fr_auto] md:items-center">
                    <p class="font-mono text-xs text-slate-500 dark:text-slate-400">{{ $day['date'] }}</p>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-amber-400" @style(['width: '.$barWidth.'%'])></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                        <span class="font-mono text-slate-900 dark:text-slate-100">{{ number_format($dayCost, 4, ',', '.') }} USD</span>
                        <span>Gen {{ number_format($dayDynamic, 4, ',', '.') }}</span>
                        <span>Ed {{ number_format($dayStyle, 4, ',', '.') }}</span>
                    </div>
                </div>
            @empty
                <p class="rounded-2xl border border-slate-200 px-3 py-4 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">Sin tendencia diaria para el rango seleccionado.</p>
            @endforelse
        </div>
    </section>
</div>
