<div class="space-y-6">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-sky-100 via-cyan-50 to-white px-4 py-6 sm:px-6 dark:from-sky-950/40 dark:via-slate-900 dark:to-slate-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="space-y-2">
                    <div class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700 dark:bg-sky-900/40 dark:text-sky-200">
                        Gestion de Licitaciones
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Licitaciones</h1>
                    <p class="text-sm text-slate-600 dark:text-slate-300">Centraliza seguimiento, estado documental y acceso rápido a memoria técnica.</p>
                </div>

                <a href="{{ route('tenders.create') }}" class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                    Nueva Licitación
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 border-t border-slate-200 px-4 py-4 sm:grid-cols-2 sm:px-6 dark:border-slate-800">
            <div>
                <label for="search" class="sr-only">Buscar</label>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    id="search"
                    placeholder="Buscar por titulo, empresa o referencia..."
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
                >
            </div>

            <div class="sm:justify-self-end sm:w-56">
                <label for="statusFilter" class="sr-only">Filtrar por estado</label>
                <select
                    wire:model.live="statusFilter"
                    id="statusFilter"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-slate-900 shadow-sm transition focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:[color-scheme:dark]"
                >
                    <option value="">Todos los estados</option>
                    <option value="pending">Pendiente</option>
                    <option value="analyzing">Analizando</option>
                    <option value="completed">Completado</option>
                    <option value="failed">Error</option>
                </select>
            </div>
        </div>
    </section>

    @if($tenders->isEmpty())
        <section class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <svg class="mx-auto h-12 w-12 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-4 text-slate-600 dark:text-slate-300">No hay licitaciones registradas.</p>
            <a href="{{ route('tenders.create') }}" class="mt-4 inline-flex items-center rounded-lg bg-sky-100 px-3 py-1.5 text-sm font-medium text-sky-700 hover:bg-sky-200 dark:bg-sky-900/40 dark:text-sky-300 dark:hover:bg-sky-900/60">
                Crear primera licitación
            </a>
        </section>
    @else
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full table-fixed divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                        <tr>
                            <th class="w-[38%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Titulo</th>
                            <th class="w-[24%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Empresa</th>
                            <th class="w-[14%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Estado</th>
                            <th class="w-[10%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Documentos</th>
                            <th class="w-[14%] px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-slate-900/50">
                        @foreach($tenders as $tender)
                            @php
                                $variant = match($tender->status) {
                                    'pending' => 'secondary',
                                    'analyzing' => 'info',
                                    'completed' => 'success',
                                    'failed' => 'error',
                                    default => 'default',
                                };
                            @endphp

                            <tr wire:key="tender-{{ $tender->id }}" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-6 py-4 align-top">
                                    <div class="line-clamp-2 break-words text-sm font-semibold leading-5 text-slate-900 dark:text-slate-100" title="{{ $tender->title }}">{{ $tender->title }}</div>
                                    @if($tender->reference_number)
                                        <div class="truncate text-sm text-slate-500 dark:text-slate-400" title="Ref: {{ $tender->reference_number }}">Ref: {{ $tender->reference_number }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-slate-600 dark:text-slate-300">
                                    <span class="block truncate" title="{{ $tender->issuing_company ?? 'N/A' }}">{{ $tender->issuing_company ?? 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4 align-top whitespace-nowrap">
                                    <x-ui.badge :variant="$variant">{{ ucfirst($tender->status) }}</x-ui.badge>
                                </td>
                                <td class="px-6 py-4 align-top whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ $tender->documents_count }}</td>
                                <td class="px-6 py-4 align-top whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('tenders.show', $tender) }}" class="inline-flex items-center rounded-lg bg-sky-100 px-3 py-1.5 text-sky-800 transition hover:bg-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:hover:bg-sky-900/50">
                                        Ver detalles
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 lg:hidden">
                @foreach($tenders as $tender)
                    @php
                        $variant = match($tender->status) {
                            'pending' => 'secondary',
                            'analyzing' => 'info',
                            'completed' => 'success',
                            'failed' => 'error',
                            default => 'default',
                        };
                    @endphp

                    <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700" wire:key="mobile-tender-{{ $tender->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $tender->title }}</h2>
                                @if($tender->reference_number)
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ref: {{ $tender->reference_number }}</p>
                                @endif
                            </div>
                            <x-ui.badge :variant="$variant">{{ ucfirst($tender->status) }}</x-ui.badge>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-sm text-slate-600 dark:text-slate-300">
                            <span>{{ $tender->issuing_company ?? 'N/A' }}</span>
                            <span>{{ $tender->documents_count }} docs</span>
                        </div>

                        <a href="{{ route('tenders.show', $tender) }}" class="mt-3 inline-flex items-center rounded-lg bg-sky-100 px-3 py-1.5 text-sm font-medium text-sky-800 hover:bg-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:hover:bg-sky-900/50">
                            Ver detalles
                        </a>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                {{ $tenders->links() }}
            </div>
        </section>
    @endif
</div>
