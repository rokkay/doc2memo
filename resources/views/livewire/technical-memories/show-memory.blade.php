@php
    $view = $this->viewData;
    $memory = $tender->technicalMemory;
    $isGenerating = $view->isGenerating;
@endphp

<div @if(! $view->hasMemory || $isGenerating) wire:poll.2s.visible="refreshMemory" @endif>
    @if(! $view->hasMemory)
        <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">No hay memoria técnica generada</h3>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">La memoria técnica aún no ha sido generada para esta licitación.</p>
            <a href="{{ route('tenders.show', $tender) }}" class="mt-6 inline-flex items-center rounded-lg bg-sky-100 px-4 py-2 text-sm font-medium text-sky-700 hover:bg-sky-200 dark:bg-sky-900/40 dark:text-sky-300 dark:hover:bg-sky-900/60">
                Volver a la licitación
            </a>
        </div>
    @else
        <div class="space-y-6">
            @if($isGenerating)
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
                    <p class="text-sm font-medium text-sky-900 dark:text-sky-200">Generando memoria técnica por secciones dinámicas de juicio de valor.</p>
                    @if($view->inProgressSections !== [])
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Secciones en generación:</span>
                            @foreach($view->inProgressSections as $progressSection)
                                <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-1 text-xs font-medium text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">
                                    {{ $progressSection['title'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                    @if($view->failedSections !== [])
                        <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
                            Hay secciones con error. Puedes regenerarlas individualmente.
                        </div>
                    @endif
                    <div class="mt-3">
                        <div class="mb-1 flex items-center justify-between text-xs font-medium text-sky-800 dark:text-sky-200">
                            <span>Progreso de generación</span>
                            <span>{{ $view->progressPercent }}%</span>
                        </div>
                        <progress
                            class="h-2 w-full overflow-hidden rounded-full [&::-webkit-progress-bar]:rounded-full [&::-webkit-progress-bar]:bg-sky-100 [&::-webkit-progress-value]:rounded-full [&::-webkit-progress-value]:bg-sky-600 dark:[&::-webkit-progress-bar]:bg-sky-900/40"
                            value="{{ $view->progressPercent }}"
                            max="100"
                        ></progress>
                    </div>
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="bg-gradient-to-r from-cyan-100 via-sky-50 to-white px-4 py-6 sm:px-6 dark:from-sky-950/40 dark:via-slate-900 dark:to-slate-900">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <div class="inline-flex items-center rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-200">
                                Memoria Técnica Dinámica
                            </div>
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $memory->title ?: 'Memoria Técnica' }}</h1>
                            @if($memory->generated_at)
                                <p class="text-sm text-slate-600 dark:text-slate-300">Generada el {{ $memory->generated_at->format('d/m/Y H:i') }}</p>
                            @else
                                <p class="text-sm text-slate-600 dark:text-slate-300">Generación en curso</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-2" x-data="{ copied: false, copyMarkdown() { navigator.clipboard.writeText(this.$refs.markdown.value).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1500); }); } }">
                            <textarea x-ref="markdown" class="hidden">{{ $view->markdownExport }}</textarea>
                            <a href="{{ route('tenders.show', $tender) }}" class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">Volver</a>
                            <button type="button" @click="copyMarkdown" class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-emerald-100 px-4 py-2 text-sm font-medium text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-200 dark:hover:bg-emerald-900/60">
                                <span x-show="! copied">Copiar Markdown</span>
                                <span x-show="copied" x-cloak>Copiado</span>
                            </button>
                            <a href="{{ route('technical-memories.download-markdown', $memory) }}" class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">Descargar Markdown</a>
                            <a href="{{ route('technical-memories.download', $memory) }}" class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">Descargar PDF</a>
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white/80 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Secciones completadas</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $view->completedCount }}/{{ $view->totalCount }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white/80 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Puntos juicio de valor</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ number_format($view->totalPoints, 2, ',', '.') }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white/80 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Criterios JV</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $this->judgmentCriteriaCount }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @if($view->latestRunStatus !== null)
                <section class="overflow-hidden rounded-2xl border border-indigo-200 bg-white shadow-sm dark:border-indigo-900/60 dark:bg-slate-900">
                    <div class="bg-indigo-50 px-4 py-3 dark:bg-indigo-950/30">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-indigo-800 dark:text-indigo-300">Métricas operativas internas</h3>
                        <p class="mt-1 text-xs text-indigo-700 dark:text-indigo-400">Última ejecución de generación técnica.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Estado última ejecución</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $view->latestRunStatus }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Duración última ejecución</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $view->latestRunDurationMs !== null ? $view->latestRunDurationMs.' ms' : 'N/D' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Duración media por sección</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $view->avgSectionDurationMs !== null ? $view->avgSectionDurationMs.' ms' : 'N/D' }}</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">First pass rate</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($view->firstPassRate, 1, ',', '.') }}%</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Retry rate</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($view->retryRate, 1, ',', '.') }}%</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Failure rate</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($view->failureRate, 1, ',', '.') }}%</p>
                        </div>
                    </div>
                </section>
            @endif

            <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-900/60 dark:bg-slate-900">
                <div class="bg-emerald-50 px-4 py-3 dark:bg-emerald-950/30">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Matriz de juicio de valor</h3>
                            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">Solo criterios evaluables por juicio de valor (Sobre B).</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button wire:click.prevent="setCriteriaPriorityFilter('all')" type="button" class="inline-flex cursor-pointer items-center rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $this->criteriaPriorityFilter === 'all' ? 'bg-emerald-100 text-emerald-800 ring-emerald-300 dark:bg-emerald-900/40 dark:text-emerald-200 dark:ring-emerald-700' : 'bg-white text-emerald-700 ring-emerald-300 hover:bg-emerald-100 dark:bg-slate-900 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/30' }}">Todos</button>
                            <button wire:click.prevent="setCriteriaPriorityFilter('mandatory')" type="button" class="inline-flex cursor-pointer items-center rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $this->criteriaPriorityFilter === 'mandatory' ? 'bg-rose-100 text-rose-800 ring-rose-300 dark:bg-rose-900/40 dark:text-rose-200 dark:ring-rose-700' : 'bg-white text-rose-700 ring-rose-300 hover:bg-rose-100 dark:bg-slate-900 dark:text-rose-300 dark:ring-rose-800 dark:hover:bg-rose-900/30' }}">Obligatorios</button>
                            <button wire:click.prevent="setCriteriaPriorityFilter('preferable')" type="button" class="inline-flex cursor-pointer items-center rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $this->criteriaPriorityFilter === 'preferable' ? 'bg-amber-100 text-amber-800 ring-amber-300 dark:bg-amber-900/40 dark:text-amber-200 dark:ring-amber-700' : 'bg-white text-amber-700 ring-amber-300 hover:bg-amber-100 dark:bg-slate-900 dark:text-amber-300 dark:ring-amber-800 dark:hover:bg-amber-900/30' }}">Preferentes</button>
                            <button wire:click.prevent="setCriteriaPriorityFilter('optional')" type="button" class="inline-flex cursor-pointer items-center rounded-md px-2.5 py-1.5 text-xs font-semibold ring-1 {{ $this->criteriaPriorityFilter === 'optional' ? 'bg-slate-200 text-slate-800 ring-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:ring-slate-600' : 'bg-white text-slate-700 ring-slate-300 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-slate-800' }}">Opcionales</button>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-400">Mostrando {{ count($this->matrixRows) }} de {{ $this->judgmentCriteriaCount }} criterios</p>
                </div>

                <div class="max-h-[26rem] overflow-auto bg-white dark:bg-slate-900">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-800/95">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Criterio</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Prioridad</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Puntos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @foreach($this->matrixRows as $row)
                                <tr>
                                    <td class="px-3 py-3 align-top text-slate-700 dark:text-slate-200">{{ $row['section'] }}</td>
                                    <td class="px-3 py-3 align-top text-slate-700 dark:text-slate-200">{{ ucfirst($row['priority']) }}</td>
                                    <td class="px-3 py-3 align-top text-slate-700 dark:text-slate-200">{{ $row['points'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                <aside class="xl:col-span-3">
                    <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Índice dinámico</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach($view->sections as $section)
                                <li>
                                    <a href="#{{ $section['anchor'] }}" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                                        {{ $section['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>

                <div class="space-y-4 xl:col-span-9">
                    @foreach($view->sections as $section)
                        <article id="{{ $section['anchor'] }}" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ $section['title'] }}</h2>
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    @if($section['status'] === 'completed')
                                        <span class="rounded-full bg-emerald-100 px-2 py-1 font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Completada</span>
                                    @elseif($section['status'] === 'failed')
                                        <span class="rounded-full bg-rose-100 px-2 py-1 font-semibold text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">Error</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-1 font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Generando...</span>
                                    @endif
                                    <span class="rounded-full bg-sky-100 px-2 py-1 font-semibold text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">{{ number_format($section['points'], 2, ',', '.') }} pts</span>
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">{{ number_format($section['weight'], 2, ',', '.') }}%</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $section['criteria_count'] }} criterios</span>
                                    <button
                                        type="button"
                                        wire:click="regenerateSection({{ $section['id'] }})"
                                        wire:loading.attr="disabled"
                                        wire:target="regenerateSection({{ $section['id'] }})"
                                        class="inline-flex cursor-pointer items-center rounded-full bg-amber-100 px-2 py-1 font-semibold text-amber-800 transition hover:bg-amber-200 dark:bg-amber-900/40 dark:text-amber-300 dark:hover:bg-amber-900/60"
                                    >
                                        <span wire:loading.remove wire:target="regenerateSection({{ $section['id'] }})">Regenerar sección</span>
                                        <span wire:loading wire:target="regenerateSection({{ $section['id'] }})">Reencolando...</span>
                                    </button>
                                </div>
                            </div>

                            @if($section['content'] === '')
                                @if($section['status'] === 'failed')
                                    <p class="mt-3 text-sm text-rose-600 dark:text-rose-400">La sección falló durante la generación. Puedes relanzarla con el botón de regenerar.</p>
                                @else
                                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Sección pendiente de generación.</p>
                                @endif
                            @else
                                @if($section['evidence'] !== [])
                                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs dark:border-slate-700 dark:bg-slate-800/60">
                                        <p class="font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Evidencias de evaluación usadas</p>
                                        <ul class="mt-2 space-y-2">
                                            @foreach($section['evidence'] as $evidence)
                                                <li>
                                                    <p class="font-medium text-slate-700 dark:text-slate-200">{{ $evidence['label'] }}</p>
                                                    @if(! empty($evidence['reference']))
                                                        <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Origen: {{ $evidence['reference'] }}</p>
                                                    @endif
                                                    <p class="text-slate-600 dark:text-slate-300">{{ $evidence['detail'] }}</p>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <x-markdown class="mt-3 space-y-4 text-sm leading-7 text-slate-700 dark:text-slate-200 [&_a]:text-cyan-700 [&_a]:underline [&_code]:rounded [&_code]:bg-slate-100 [&_code]:px-1 [&_code]:py-0.5 dark:[&_code]:bg-slate-800 dark:[&_code]:text-slate-100 [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:leading-7 [&_h3]:text-slate-900 dark:[&_h3]:text-slate-100 [&_h4]:mt-4 [&_h4]:text-base [&_h4]:font-semibold [&_h4]:text-slate-800 dark:[&_h4]:text-slate-200 [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6 [&_table]:w-full [&_table]:border [&_table]:border-slate-300 [&_th]:bg-slate-100 [&_th]:font-semibold [&_th]:text-left [&_th]:border [&_th]:border-slate-300 [&_th]:px-3 [&_th]:py-2 [&_td]:border [&_td]:border-slate-300 [&_td]:px-3 [&_td]:py-2 dark:[&_table]:border-slate-700 dark:[&_th]:border-slate-700 dark:[&_th]:bg-slate-800 dark:[&_td]:border-slate-700">
                                    {{ $section['content'] }}
                                </x-markdown>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

        </div>
    @endif
</div>
