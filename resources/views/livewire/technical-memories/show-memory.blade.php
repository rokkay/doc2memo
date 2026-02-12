@if(! $tender->technicalMemory)
    <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">No hay memoria tecnica generada</h3>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">La memoria tecnica aun no ha sido generada para esta licitacion.</p>
        <a href="{{ route('tenders.show', $tender) }}" class="mt-6 inline-flex items-center rounded-lg bg-sky-100 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-200 dark:bg-sky-900/40 dark:text-sky-300 dark:hover:bg-sky-900/60">
            Volver a la licitacion
        </a>
    </div>
@else
    @php
        $memory = $tender->technicalMemory;

        $sections = collect([
            ['id' => 'introduccion', 'title' => '1. Introduccion', 'content' => $memory->introduction],
            ['id' => 'presentacion', 'title' => '2. Presentacion de la Empresa', 'content' => $memory->company_presentation],
            ['id' => 'enfoque', 'title' => '3. Enfoque Tecnico', 'content' => $memory->technical_approach],
            ['id' => 'metodologia', 'title' => '4. Metodologia', 'content' => $memory->methodology],
            ['id' => 'equipo', 'title' => '5. Estructura del Equipo', 'content' => $memory->team_structure],
            ['id' => 'cronograma', 'title' => '6. Cronograma', 'content' => $memory->timeline],
            ['id' => 'calidad', 'title' => '7. Aseguramiento de Calidad', 'content' => $memory->quality_assurance],
            ['id' => 'riesgos', 'title' => '8. Gestion de Riesgos', 'content' => $memory->risk_management],
            ['id' => 'cumplimiento', 'title' => '9. Matriz de Cumplimiento', 'content' => $memory->compliance_matrix],
        ])->filter(fn (array $section): bool => filled($section['content']))->values();
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="bg-gradient-to-r from-cyan-100 via-sky-50 to-white px-4 py-6 sm:px-6 dark:from-sky-950/40 dark:via-slate-900 dark:to-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <div class="inline-flex items-center rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-200">
                            Memoria Tecnica
                        </div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $memory->title }}</h1>
                        <p class="text-sm text-slate-600 dark:text-slate-300">Generada el {{ $memory->generated_at?->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('tenders.show', $tender) }}" class="inline-flex items-center rounded-lg bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">
                            Volver
                        </a>
                        @if($memory->generated_file_path)
                            <a href="{{ route('technical-memories.download', $memory) }}" class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">
                                Descargar PDF
                            </a>
                        @endif
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-white/80 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600 dark:text-slate-300">Secciones completadas</span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $sections->count() }}/9</span>
                    </div>
                    <div class="mt-2 grid grid-cols-9 gap-1">
                        @for($index = 1; $index <= 9; $index++)
                            <span class="h-2 rounded-full {{ $index <= $sections->count() ? 'bg-cyan-500' : 'bg-slate-200 dark:bg-slate-700' }}"></span>
                        @endfor
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="xl:col-span-3">
                <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Indice</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach($sections as $section)
                            <li>
                                <a href="#{{ $section['id'] }}" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                                    {{ $section['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>

            <div class="space-y-4 xl:col-span-9">
                @foreach($sections as $section)
                    <article id="{{ $section['id'] }}" class="memory-section scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ $section['title'] }}</h2>

                        @if($section['id'] === 'cronograma')
                            @php
                                $timelinePlan = is_array($memory->timeline_plan ?? null)
                                    ? $memory->timeline_plan
                                    : [];

                                $timelineTasks = collect($timelinePlan['tasks'] ?? [])
                                    ->filter(fn (mixed $task): bool => is_array($task))
                                    ->map(function (array $task): array {
                                        $startWeek = max(1, (int) ($task['start_week'] ?? 1));
                                        $endWeek = max($startWeek, (int) ($task['end_week'] ?? $startWeek));

                                        return [
                                            'id' => (string) ($task['id'] ?? ''),
                                            'title' => (string) ($task['title'] ?? ''),
                                            'lane' => (string) ($task['lane'] ?? 'General'),
                                            'start_week' => $startWeek,
                                            'end_week' => $endWeek,
                                            'depends_on' => collect($task['depends_on'] ?? [])
                                                ->map(fn (mixed $dependency): string => (string) $dependency)
                                                ->filter(fn (string $dependency): bool => $dependency !== '')
                                                ->values()
                                                ->all(),
                                        ];
                                    })
                                    ->filter(fn (array $task): bool => $task['id'] !== '' && $task['title'] !== '')
                                    ->values();

                                $timelineMilestones = collect($timelinePlan['milestones'] ?? [])
                                    ->filter(fn (mixed $milestone): bool => is_array($milestone))
                                    ->map(fn (array $milestone): array => [
                                        'title' => (string) ($milestone['title'] ?? ''),
                                        'week' => max(1, (int) ($milestone['week'] ?? 1)),
                                    ])
                                    ->filter(fn (array $milestone): bool => $milestone['title'] !== '')
                                    ->values();

                                $ganttTotalWeeks = max(
                                    (int) ($timelinePlan['total_weeks'] ?? 0),
                                    (int) ($timelineTasks->max('end_week') ?? 0),
                                    (int) ($timelineMilestones->max('week') ?? 0)
                                );

                                $weekLabelStep = match (true) {
                                    $ganttTotalWeeks > 40 => 8,
                                    $ganttTotalWeeks > 24 => 4,
                                    $ganttTotalWeeks > 16 => 2,
                                    default => 1,
                                };

                            @endphp

                            @if($timelineTasks->isNotEmpty() && $ganttTotalWeeks > 0)
                                <div class="mt-4 rounded-xl border border-cyan-200 bg-cyan-50/60 p-4 dark:border-cyan-900/70 dark:bg-cyan-950/20">
                                    <div class="mb-3 flex items-center justify-between">
                                        <h3 class="text-sm font-semibold uppercase tracking-wide text-cyan-800 dark:text-cyan-300">Diagrama de cronograma</h3>
                                        <span class="text-xs text-cyan-700 dark:text-cyan-400">{{ $ganttTotalWeeks }} semanas estimadas</span>
                                    </div>

                                    <div class="rounded-lg border border-cyan-200/70 bg-white/70 p-3 dark:border-cyan-900/60 dark:bg-slate-900/40">
                                        <div class="mb-2 hidden grid-cols-[13rem,1fr] gap-2 sm:grid">
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Actividad</span>
                                            <div class="grid auto-cols-fr grid-flow-col gap-1 text-[10px] text-slate-500 dark:text-slate-400">
                                                @for($week = 1; $week <= $ganttTotalWeeks; $week++)
                                                    <span class="text-center whitespace-nowrap">
                                                        {{ $week === 1 || $week === $ganttTotalWeeks || $week % $weekLabelStep === 0 ? 'S'.$week : '' }}
                                                    </span>
                                                @endfor
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            @foreach($timelineTasks as $task)
                                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-[13rem,1fr] sm:items-center">
                                                    <div>
                                                        <p class="text-sm text-slate-700 dark:text-slate-200">{{ $task['title'] }}</p>
                                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $task['lane'] }}</p>
                                                    </div>
                                                    <div class="space-y-1">
                                                        <div class="grid auto-cols-fr grid-flow-col gap-1 overflow-hidden rounded-lg bg-white p-1 ring-1 ring-cyan-100 dark:bg-slate-900/70 dark:ring-cyan-900/60">
                                                            @for($week = 1; $week <= $ganttTotalWeeks; $week++)
                                                                <span class="h-4 rounded-sm {{ $week >= $task['start_week'] && $week <= $task['end_week'] ? 'bg-cyan-500/80' : 'bg-slate-200 dark:bg-slate-700' }}"></span>
                                                            @endfor
                                                        </div>
                                                        <p class="text-xs text-slate-600 dark:text-slate-300">Semanas {{ $task['start_week'] }}-{{ $task['end_week'] }}</p>
                                                        @if(! empty($task['depends_on']))
                                                            <p class="text-xs text-slate-500 dark:text-slate-400">Depende de: {{ implode(', ', $task['depends_on']) }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if($timelineMilestones->isNotEmpty())
                                        <div class="mt-4 border-t border-cyan-200 pt-3 dark:border-cyan-900/60">
                                            <p class="text-xs font-semibold uppercase tracking-wide text-cyan-800 dark:text-cyan-300">Hitos</p>
                                            <ul class="mt-2 space-y-1 text-sm text-slate-700 dark:text-slate-200">
                                                @foreach($timelineMilestones as $milestone)
                                                    <li>Semana {{ $milestone['week'] }}: {{ $milestone['title'] }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endif

                        <div class="mt-3 text-sm leading-7 text-slate-700 dark:text-slate-200">{!! nl2br(e($section['content'])) !!}</div>
                    </article>
                @endforeach

            </div>
        </section>

        <a href="#" class="fixed bottom-6 right-6 z-20 inline-flex items-center rounded-full border border-slate-300 bg-white/95 px-4 py-2 text-sm font-medium text-slate-700 shadow-md backdrop-blur transition hover:-translate-y-0.5 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:hover:bg-slate-800">
            Volver arriba
        </a>
    </div>
@endif
