<div>
    <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Licitaciones</h1>
                <a href="{{ route('tenders.create') }}" class="inline-flex cursor-pointer items-center rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-sky-700">
                    Nueva Licitación
                </a>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <div class="flex-1">
                    <label for="search" class="sr-only">Buscar</label>
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        id="search"
                        placeholder="Buscar por título, empresa o referencia..."
                        class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
                    >
                </div>
                <div class="sm:w-48">
                    <label for="statusFilter" class="sr-only">Filtrar por estado</label>
                    <select
                        wire:model.live="statusFilter"
                        id="statusFilter"
                        class="w-full rounded-md border border-slate-300 bg-white px-4 py-2 text-slate-900 shadow-sm transition focus:border-sky-500 focus:ring-sky-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:[color-scheme:dark]"
                    >
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="analyzing">Analizando</option>
                        <option value="completed">Completado</option>
                        <option value="failed">Error</option>
                    </select>
                </div>
            </div>

            @if($tenders->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="mt-4 text-slate-500 dark:text-slate-400">No hay licitaciones registradas.</p>
                    <a href="{{ route('tenders.create') }}" class="mt-4 inline-block cursor-pointer text-sky-700 hover:text-sky-800">
                        Crear primera licitación
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full table-fixed divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/70">
                            <tr>
                                <th class="w-[38%] px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Título</th>
                                <th class="w-[24%] px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Empresa</th>
                                <th class="w-[14%] px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                                <th class="w-[10%] px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Documentos</th>
                                <th class="w-[14%] px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-900/50 dark:divide-slate-800">
                            @foreach($tenders as $tender)
                                <tr wire:key="tender-{{ $tender->id }}">
                                    <td class="px-6 py-4 align-top">
                                        <div class="line-clamp-2 break-words text-sm font-medium leading-5 text-gray-900 dark:text-slate-100" title="{{ $tender->title }}">{{ $tender->title }}</div>
                                        @if($tender->reference_number)
                                            <div class="truncate text-sm text-gray-500 dark:text-slate-400" title="Ref: {{ $tender->reference_number }}">Ref: {{ $tender->reference_number }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 align-top text-sm text-gray-500 dark:text-slate-400">
                                        <span class="block truncate" title="{{ $tender->issuing_company ?? 'N/A' }}">{{ $tender->issuing_company ?? 'N/A' }}</span>
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap">
                                        @php
                                            $variant = match($tender->status) {
                                                'pending' => 'secondary',
                                                'analyzing' => 'info',
                                                'completed' => 'success',
                                                'failed' => 'error',
                                                default => 'default',
                                            };
                                        @endphp
                                        <x-ui.badge :variant="$variant">
                                            {{ ucfirst($tender->status) }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $tender->documents_count }}
                                    </td>
                                    <td class="px-6 py-4 align-top whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('tenders.show', $tender) }}" class="text-sky-700 hover:text-sky-900">
                                            Ver detalles
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $tenders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
