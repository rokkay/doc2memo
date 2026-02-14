<div class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Metricas operativas</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Calidad, tiempos y coste estimado de generacion tecnica.</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Desde
                    <input type="date" wire:model.live="from_date" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </label>

                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Hasta
                    <input type="date" wire:model.live="to_date" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                </label>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">First pass</p>
            <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float) ($metrics['global']['first_pass_rate'] ?? 0), 1, ',', '.') }}%</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Retry</p>
            <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float) ($metrics['global']['retry_rate'] ?? 0), 1, ',', '.') }}%</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Failure</p>
            <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float) ($metrics['global']['failure_rate'] ?? 0), 1, ',', '.') }}%</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Duracion media</p>
            <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ (int) ($metrics['global']['avg_duration_ms'] ?? 0) }} ms</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Coste estimado</p>
            <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ number_format((float) ($metrics['global']['estimated_cost_usd'] ?? 0), 4, ',', '.') }} USD</p>
            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Valor estimado, no facturacion real.</p>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">Memorias recientes</h2>
            </div>
            <div class="max-h-[22rem] overflow-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Memoria</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Intentos</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Coste estimado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($metrics['memories'] as $memory)
                            <tr>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ $memory['memory_title'] }}</td>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ (int) $memory['attempts'] }}</td>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ number_format((float) $memory['estimated_cost_usd'], 4, ',', '.') }} USD</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-3 py-4 text-sm text-slate-500 dark:text-slate-400">Sin datos para el rango seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">Secciones problematicas</h2>
            </div>
            <div class="max-h-[22rem] overflow-auto">
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
                            <tr>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ $section['section_title'] }}</td>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ (int) $section['retry_count'] }}</td>
                                <td class="px-3 py-2 text-slate-700 dark:text-slate-200">{{ (int) $section['failure_count'] }}</td>
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
</div>
