@php
    $memory = $tender->technicalMemory;
    $isGenerating = $memory && $memory->status === 'draft';
@endphp

<div @if(! $memory || $isGenerating) wire:poll.2s.visible="refreshMemory" @endif>
    @if(! $memory)
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

            $totalCharacters = (int) $sections->sum(fn (array $section): int => mb_strlen((string) $section['content']));
            $estimatedReadingMinutes = max(1, (int) ceil($totalCharacters / 1100));

            $buildInsight = function (?string $content): ?string {
                if (! filled($content)) {
                    return null;
                }

                $firstParagraph = collect(preg_split('/\n\s*\n/u', trim((string) $content)) ?: [])->first();

                if (! is_string($firstParagraph) || trim($firstParagraph) === '') {
                    return null;
                }

                $normalized = preg_replace('/\s+/u', ' ', trim($firstParagraph)) ?? trim($firstParagraph);

                if ($normalized === '') {
                    return null;
                }

                if (mb_strlen($normalized) <= 170) {
                    return $normalized;
                }

                return rtrim(mb_substr($normalized, 0, 170)).'...';
            };

            $executiveInsights = collect([
                $buildInsight($memory->introduction),
                $buildInsight($memory->technical_approach),
                $buildInsight($memory->methodology),
                $buildInsight($memory->compliance_matrix),
            ])->filter()->take(4)->values();

            $timelinePlan = is_array($memory->timeline_plan ?? null)
                ? $memory->timeline_plan
                : [];

            $timelineTasksCount = count($timelinePlan['tasks'] ?? []);
            $timelineMilestonesCount = count($timelinePlan['milestones'] ?? []);

            $criteriaCount = $tender->extractedCriteria->count();
            $specificationsCount = $tender->extractedSpecifications->count();

            $allCriteriaMatrixRows = $tender->extractedCriteria
                ->map(function ($criterion): array {
                    $description = trim((string) $criterion->description);
                    $fragments = collect(preg_split('/\n+|\s*;\s*/u', $description) ?: [])
                        ->map(function (string $fragment): string {
                            $fragment = trim($fragment);
                            $fragment = preg_replace('/^[-*]\s+/', '', $fragment) ?? $fragment;
                            $fragment = preg_replace('/^\d+[\)\.-]?\s+/', '', $fragment) ?? $fragment;

                            return trim($fragment);
                        })
                        ->filter(fn (string $fragment): bool => $fragment !== '')
                        ->values();

                    if ($fragments->isEmpty()) {
                        $fragments = collect([$description]);
                    }

                    return [
                        'section' => trim((string) ($criterion->section_number ? $criterion->section_number.' - '.$criterion->section_title : $criterion->section_title)),
                        'priority' => (string) $criterion->priority,
                        'points' => $fragments->take(2)->values()->all(),
                        'priority_order' => match ((string) $criterion->priority) {
                            'mandatory' => 1,
                            'preferable' => 2,
                            default => 3,
                        },
                    ];
                })
                ->filter(fn (array $row): bool => $row['section'] !== '')
                ->sortBy('priority_order')
                ->values();

            $criteriaMatrixRows = $allCriteriaMatrixRows
                ->when($this->criteriaPriorityFilter !== 'all', fn ($rows) => $rows->where('priority', $this->criteriaPriorityFilter))
                ->values();
        @endphp

        <div class="space-y-6">
            @if($isGenerating)
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm font-medium text-sky-900 dark:text-sky-200">Generando memoria tecnica por secciones</p>
                    </div>
                    <p class="mt-2 text-sm text-sky-800 dark:text-sky-300">Cada seccion se ira habilitando automaticamente en cuanto este lista.</p>
                </div>
            @endif

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="bg-gradient-to-r from-cyan-100 via-sky-50 to-white px-4 py-6 sm:px-6 dark:from-sky-950/40 dark:via-slate-900 dark:to-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-2">
                        <div class="inline-flex items-center rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-200">
                            Memoria Tecnica
                        </div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $memory->title ?: 'Memoria Tecnica' }}</h1>
                        @if($memory->generated_at)
                            <p class="text-sm text-slate-600 dark:text-slate-300">Generada el {{ $memory->generated_at->format('d/m/Y H:i') }}</p>
                        @else
                            <p class="text-sm text-slate-600 dark:text-slate-300">Generacion en curso</p>
                        @endif
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

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Resumen ejecutivo</h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">Vista rapida para revisar alcance, esfuerzo y criterios antes de entrar al detalle.</p>
            </div>

            <div class="grid grid-cols-1 gap-3 border-b border-slate-200 px-4 py-4 sm:grid-cols-2 xl:grid-cols-4 sm:px-6 dark:border-slate-800">
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Tiempo estimado lectura</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $estimatedReadingMinutes }} min</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Criterios de evaluacion</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $criteriaCount }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Especificaciones tecnicas</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $specificationsCount }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Cronograma estructurado</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $timelineTasksCount }} tareas / {{ $timelineMilestonesCount }} hitos</p>
                </div>
            </div>

            @if($executiveInsights->isNotEmpty())
                <div class="px-4 py-4 sm:px-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Puntos clave</p>
                    <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-200">
                        @foreach($executiveInsights as $insight)
                            <li class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800/60">{{ $insight }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-12" x-data="{ expandAllDetails() { this.$root.querySelectorAll('[data-section-detail]').forEach((el) => { el.open = true }) }, collapseAllDetails() { this.$root.querySelectorAll('[data-section-detail]').forEach((el) => { el.open = false }) } }">
            <aside class="xl:col-span-3">
                <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Indice</h2>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <button type="button" @click="expandAllDetails" class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-slate-100 px-2 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            Expandir detalles
                        </button>
                        <button type="button" @click="collapseAllDetails" class="inline-flex cursor-pointer items-center justify-center rounded-lg bg-slate-100 px-2 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            Contraer detalles
                        </button>
                    </div>
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

                        @if($section['id'] === 'cumplimiento' && $criteriaMatrixRows->isNotEmpty())
                            <div class="mt-4 overflow-hidden rounded-xl border border-emerald-200 dark:border-emerald-900/60">
                                <div class="bg-emerald-50 px-4 py-3 dark:bg-emerald-950/30">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h3 class="text-sm font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Matriz de verificacion rapida</h3>
                                            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Cruce directo entre criterios detectados y puntos de evaluacion para agilizar la revision.</p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button wire:click.prevent="setCriteriaPriorityFilter('all')" type="button" class="inline-flex cursor-pointer items-center rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $this->criteriaPriorityFilter === 'all' ? 'bg-emerald-100 text-emerald-800 ring-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-700' : 'bg-white text-emerald-700 ring-emerald-300 hover:bg-emerald-100 dark:bg-slate-900 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/30' }}">
                                                Todos
                                            </button>
                                            <button wire:click.prevent="setCriteriaPriorityFilter('mandatory')" type="button" class="inline-flex cursor-pointer items-center rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $this->criteriaPriorityFilter === 'mandatory' ? 'bg-rose-100 text-rose-800 ring-rose-300 dark:bg-rose-900/40 dark:text-rose-200 dark:ring-rose-700' : 'bg-white text-rose-700 ring-rose-300 hover:bg-rose-100 dark:bg-slate-900 dark:text-rose-300 dark:ring-rose-800 dark:hover:bg-rose-900/30' }}">
                                                Obligatorios
                                            </button>
                                            <button wire:click.prevent="setCriteriaPriorityFilter('preferable')" type="button" class="inline-flex cursor-pointer items-center rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $this->criteriaPriorityFilter === 'preferable' ? 'bg-amber-100 text-amber-800 ring-amber-300 dark:bg-amber-900/40 dark:text-amber-200 dark:ring-amber-700' : 'bg-white text-amber-700 ring-amber-300 hover:bg-amber-100 dark:bg-slate-900 dark:text-amber-300 dark:ring-amber-800 dark:hover:bg-amber-900/30' }}">
                                                Preferentes
                                            </button>
                                            <button wire:click.prevent="setCriteriaPriorityFilter('optional')" type="button" class="inline-flex cursor-pointer items-center rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $this->criteriaPriorityFilter === 'optional' ? 'bg-slate-200 text-slate-800 ring-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:ring-slate-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800' }}">
                                                Opcionales
                                            </button>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-400">Mostrando {{ $criteriaMatrixRows->count() }} de {{ $allCriteriaMatrixRows->count() }} criterios</p>
                                </div>

                                <div class="max-h-[28rem] overflow-auto bg-white dark:bg-slate-900">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/95">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Criterio</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Prioridad</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Puntos de evaluacion</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                                            @foreach($criteriaMatrixRows as $row)
                                                @php
                                                    $priorityStyle = match ($row['priority']) {
                                                        'mandatory' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                                        'preferable' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                                        default => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                    };

                                                    $priorityLabel = match ($row['priority']) {
                                                        'mandatory' => 'Obligatorio',
                                                        'preferable' => 'Preferente',
                                                        default => 'Opcional',
                                                    };
                                                @endphp

                                                <tr>
                                                    <td class="px-3 py-3 align-top text-slate-700 dark:text-slate-200">{{ $row['section'] }}</td>
                                                    <td class="px-3 py-3 align-top">
                                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $priorityStyle }}">{{ $priorityLabel }}</span>
                                                    </td>
                                                    <td class="px-3 py-3 align-top text-slate-700 dark:text-slate-200">
                                                        <ul class="space-y-1">
                                                            @foreach($row['points'] as $point)
                                                                <li class="flex items-start gap-2">
                                                                    <span class="mt-1 inline-flex h-1.5 w-1.5 rounded-full bg-slate-400 dark:bg-slate-500"></span>
                                                                    <span>{{ $point }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        @php
                            $content = trim((string) $section['content']);
                            $contentLength = mb_strlen($content);
                            $paragraphs = collect(preg_split('/\n\s*\n/u', $content) ?: [])->map(fn (string $paragraph): string => trim($paragraph))->filter(fn (string $paragraph): bool => $paragraph !== '')->values();
                            $alwaysVisibleParagraphs = $paragraphs->take(2);
                            $collapsibleParagraphs = $paragraphs->slice(2);
                            $shouldCollapse = $contentLength > 2600 && $collapsibleParagraphs->isNotEmpty();
                        @endphp

                        <div class="mt-3 text-sm leading-7 text-slate-700 dark:text-slate-200">
                            @foreach($alwaysVisibleParagraphs as $paragraph)
                                <p class="mb-4">{!! nl2br(e($paragraph)) !!}</p>
                            @endforeach

                            @if(! $shouldCollapse)
                                @foreach($collapsibleParagraphs as $paragraph)
                                    <p class="mb-4">{!! nl2br(e($paragraph)) !!}</p>
                                @endforeach
                            @endif
                        </div>

                        @if($shouldCollapse)
                            <details data-section-detail class="group mt-1 rounded-xl border border-slate-200 bg-slate-50/80 dark:border-slate-700 dark:bg-slate-800/40">
                                <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-slate-700 marker:content-none dark:text-slate-200">
                                    <span class="inline-flex items-center gap-2">
                                        Expandir detalle tecnico de esta seccion
                                        <span class="text-xs text-slate-500 dark:text-slate-400">({{ $collapsibleParagraphs->count() }} bloques adicionales)</span>
                                    </span>
                                </summary>

                                <div class="border-t border-slate-200 px-4 py-4 text-sm leading-7 text-slate-700 dark:border-slate-700 dark:text-slate-200">
                                    @foreach($collapsibleParagraphs as $paragraph)
                                        <p class="mb-4">{!! nl2br(e($paragraph)) !!}</p>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </article>
                @endforeach

            </div>
        </section>

        <a href="#" class="fixed bottom-6 right-6 z-20 inline-flex items-center rounded-full border border-slate-300 bg-white/95 px-4 py-2 text-sm font-medium text-slate-700 shadow-md backdrop-blur transition hover:-translate-y-0.5 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:hover:bg-slate-800">
            Volver arriba
        </a>
        </div>
    @endif
</div>
