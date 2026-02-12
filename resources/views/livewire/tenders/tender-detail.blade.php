<div wire:poll.visible.10s="$refresh" class="space-y-8">
    @if($tender->status === 'analyzing')
        <x-ui.alert type="info" message="La IA está analizando los documentos. Esta página se actualizará automáticamente cuando el análisis termine." />
    @elseif($tender->status === 'completed' && ! $tender->technicalMemory)
        <x-ui.alert type="success" message="Los documentos han sido analizados. Ahora puedes generar la Memoria Técnica." />
    @endif

    @if($this->processingDocuments->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-amber-100/40 p-4 shadow-sm dark:border-amber-900 dark:from-amber-950/40 dark:to-amber-900/20">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 animate-spin text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="font-medium text-amber-900 dark:text-amber-200">Procesando documentos en segundo plano</p>
            </div>
            <ul class="mt-3 space-y-1 pl-8 text-sm text-amber-800 dark:text-amber-300">
                @foreach($this->processingDocuments as $doc)
                    <li class="flex items-center" wire:key="processing-doc-{{ $doc->id }}">
                        <span class="mr-2 h-2 w-2 animate-pulse rounded-full bg-amber-500"></span>
                        {{ $doc->original_filename }} ({{ $doc->document_type === 'pca' ? 'PCA' : 'PPT' }})
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($this->failedDocuments->isNotEmpty())
        <div class="rounded-2xl border border-red-200 bg-gradient-to-r from-red-50 to-red-100/30 p-4 shadow-sm dark:border-red-900 dark:from-red-950/30 dark:to-red-900/20">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <p class="font-medium text-red-900 dark:text-red-200">Error en el análisis</p>
            </div>
            <ul class="mt-3 space-y-1 pl-8 text-sm text-red-800 dark:text-red-300">
                @foreach($this->failedDocuments as $doc)
                    <li wire:key="failed-doc-{{ $doc->id }}">{{ $doc->original_filename }} - <a href="{{ route('documents.show', $doc) }}" class="underline">Ver detalles</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errorMessage)
        <x-ui.alert type="error" :message="$errorMessage" />
    @endif

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-sky-100 via-cyan-50 to-white px-4 py-6 sm:px-6 dark:from-sky-950/40 dark:via-slate-900 dark:to-slate-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-2">
                    <div class="inline-flex items-center rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700 dark:bg-sky-900/40 dark:text-sky-200">
                        Expediente de Licitación
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $tender->title }}</h1>
                    @if($tender->reference_number)
                        <p class="text-sm text-slate-600 dark:text-slate-300">Referencia: {{ $tender->reference_number }}</p>
                    @endif
                </div>
                <x-ui.badge :variant="$this->statusVariant">
                    {{ $this->statusLabel }}
                </x-ui.badge>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white/90 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Documentos</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $tender->documents->count() }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white/90 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Criterios PCA</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $tender->extractedCriteria->count() }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white/90 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Especificaciones PPT</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $tender->extractedSpecifications->count() }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 border-t border-slate-200 px-4 py-4 sm:grid-cols-2 sm:px-6 dark:border-slate-800">
            @if($tender->issuing_company)
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Empresa Emisora</p>
                    <p class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100">{{ $tender->issuing_company }}</p>
                </div>
            @endif
            @if($tender->deadline_date)
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                    <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Plazo</p>
                    <p class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-100">{{ $tender->deadline_date }}</p>
                </div>
            @endif
        </div>

        @if($tender->description)
            <div class="border-t border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Descripción</p>
                <p class="mt-2 text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ $tender->description }}</p>
            </div>
        @endif
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="xl:col-span-8">
            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Documentos</h2>
                        <span class="text-sm text-slate-500 dark:text-slate-400">{{ $tender->documents->count() }} total</span>
                    </div>
                </div>

                <div class="space-y-3 px-4 py-4 sm:px-6">
                    @if($tender->documents->isEmpty())
                        <p class="rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">No hay documentos cargados.</p>
                    @else
                        @foreach($tender->documents as $document)
                            @php
                                $badgeVariant = match($document->status) {
                                    'uploaded' => 'secondary',
                                    'processing' => 'info',
                                    'analyzed' => 'success',
                                    'failed' => 'error',
                                    default => 'default',
                                };

                                $badgeLabel = match($document->status) {
                                    'uploaded' => 'Subido',
                                    'processing' => 'Procesando...',
                                    'analyzed' => 'Analizado',
                                    'failed' => 'Error',
                                    default => ucfirst($document->status),
                                };
                            @endphp

                            <article class="rounded-2xl border border-slate-200 p-4 transition hover:border-sky-300 hover:shadow-sm dark:border-slate-700 dark:hover:border-sky-800 {{ $document->status === 'processing' ? 'bg-sky-50/80 dark:bg-sky-950/20' : 'bg-white dark:bg-slate-900' }}" wire:key="document-{{ $document->id }}">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $document->document_type === 'pca' ? 'PCA' : 'PPT' }}</h3>
                                            <x-ui.badge :variant="$badgeVariant">{{ $badgeLabel }}</x-ui.badge>
                                            @if($document->analyzed_at)
                                                <span class="text-xs text-slate-500 dark:text-slate-400">Actualizado {{ $document->analyzed_at->diffForHumans() }}</span>
                                            @endif
                                        </div>

                                        <p class="mt-2 truncate text-sm text-slate-600 dark:text-slate-300">{{ $document->original_filename }}</p>

                                        @if($document->status === 'processing')
                                            <div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-900/30 dark:text-sky-200">
                                                <svg class="h-3.5 w-3.5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Procesando documento
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('documents.download', $document) }}" class="inline-flex items-center justify-center rounded-lg bg-sky-100 px-3 py-1.5 text-sm font-medium text-sky-800 transition hover:bg-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:hover:bg-sky-900/50">
                                            Descargar
                                        </a>

                                        @if($document->status === 'analyzed')
                                            <a href="{{ route('documents.show', $document) }}" class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                                Ver extracción
                                            </a>
                                        @elseif(in_array($document->status, ['uploaded', 'failed'], true))
                                            <button
                                                wire:click="retryDocument({{ $document->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="retryDocument({{ $document->id }})"
                                                class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $document->status === 'failed' ? 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-200 dark:hover:bg-red-900/50' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:hover:bg-emerald-900/50' }}"
                                            >
                                                <span wire:loading.remove wire:target="retryDocument({{ $document->id }})">{{ $document->status === 'failed' ? 'Reintentar análisis' : 'Analizar' }}</span>
                                                <span wire:loading wire:target="retryDocument({{ $document->id }})">Analizando...</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="xl:col-span-4">
            <div class="sticky top-6 rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Acciones</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sigue el flujo para llegar a una memoria técnica completa.</p>
                </div>

                <div class="space-y-4 px-4 py-4 sm:px-6">
                    <div class="space-y-2 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-300">Criterios PCA extraídos</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $tender->extractedCriteria->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-300">Especificaciones PPT extraídas</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $tender->extractedSpecifications->count() }}</span>
                        </div>
                    </div>

                    @if($tender->technicalMemory && $tender->technicalMemory->status === 'draft')
                        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-sm font-medium text-sky-900 dark:text-sky-200">Generando memoria técnica</p>
                            </div>
                            <p class="mt-2 text-sm text-sky-800 dark:text-sky-300">La IA está redactando la memoria. La vista se actualizará automáticamente cuando termine.</p>
                            <a href="{{ route('technical-memories.show', $tender) }}" class="mt-3 inline-flex items-center rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700">
                                Ver progreso de la memoria
                            </a>
                        </div>
                    @elseif($tender->technicalMemory)
                        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
                            <p class="text-sm font-semibold text-green-900 dark:text-green-200">Memoria Técnica Generada</p>
                            <p class="mt-1 text-sm text-green-800 dark:text-green-300">{{ $tender->technicalMemory->title }}</p>
                            @if($tender->technicalMemory->generated_at)
                                <p class="mt-1 text-xs text-green-700 dark:text-green-400">Generada el {{ $tender->technicalMemory->generated_at->format('d/m/Y H:i') }}</p>
                            @endif
                            <a href="{{ route('technical-memories.show', $tender) }}" class="mt-3 inline-flex items-center rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                                Ver Memoria Técnica
                            </a>
                            <button
                                wire:click="generateMemory"
                                wire:loading.attr="disabled"
                                wire:target="generateMemory"
                                class="mt-2 inline-flex items-center rounded-lg bg-slate-200 px-3 py-1.5 text-sm font-medium text-slate-800 transition hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600"
                            >
                                <span wire:loading.remove wire:target="generateMemory">Regenerar Memoria Técnica</span>
                                <span wire:loading wire:target="generateMemory">Regenerando...</span>
                            </button>
                        </div>
                    @elseif($tender->status === 'completed')
                        <div class="space-y-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <p class="text-sm text-emerald-800 dark:text-emerald-300">Los documentos ya están analizados. Puedes generar la memoria técnica.</p>
                            <button
                                wire:click="generateMemory"
                                wire:loading.attr="disabled"
                                class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700"
                            >
                                <span wire:loading.remove wire:target="generateMemory">Generar Memoria Técnica</span>
                                <span wire:loading wire:target="generateMemory">Generando...</span>
                            </button>
                        </div>
                    @elseif($tender->status === 'analyzing')
                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-sky-900 dark:bg-sky-950/30">
                            <p class="text-sm text-blue-800 dark:text-sky-300">Esperando a que termine el análisis para habilitar la memoria técnica.</p>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Los documentos deben ser analizados antes de poder generar la memoria técnica.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if($tender->extractedCriteria->isNotEmpty() || $tender->extractedSpecifications->isNotEmpty())
        <section class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
            @if($tender->extractedCriteria->isNotEmpty())
                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Criterios Extraídos (PCA)</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $tender->extractedCriteria->count() }} criterios identificados</p>
                    </div>

                    <div class="max-h-[24rem] space-y-2 overflow-y-auto px-4 py-4 sm:px-6">
                        @foreach($tender->extractedCriteria as $criterion)
                            @php
                                $priorityVariant = match($criterion->priority) {
                                    'mandatory' => 'error',
                                    'preferable' => 'warning',
                                    'optional' => 'success',
                                    default => 'default',
                                };

                                $priorityLabel = match($criterion->priority) {
                                    'mandatory' => 'Obligatorio',
                                    'preferable' => 'Preferente',
                                    'optional' => 'Opcional',
                                    default => ucfirst((string) $criterion->priority),
                                };
                            @endphp

                            <article class="rounded-xl border border-slate-200 p-3 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm dark:border-slate-700" wire:key="criterion-{{ $criterion->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                        @if($criterion->section_number)
                                            {{ $criterion->section_number }} -
                                        @endif
                                        {{ $criterion->section_title }}
                                    </h3>
                                    <x-ui.badge :variant="$priorityVariant">{{ $priorityLabel }}</x-ui.badge>
                                </div>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $criterion->description }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($tender->extractedSpecifications->isNotEmpty())
                <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Especificaciones Técnicas Extraídas (PPT)</h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $tender->extractedSpecifications->count() }} especificaciones identificadas</p>
                    </div>

                    <div class="max-h-[24rem] space-y-2 overflow-y-auto px-4 py-4 sm:px-6">
                        @foreach($tender->extractedSpecifications as $spec)
                            <article class="rounded-xl border border-slate-200 p-3 transition duration-200 hover:-translate-y-0.5 hover:shadow-sm dark:border-slate-700" wire:key="spec-{{ $spec->id }}">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                    @if($spec->section_number)
                                        {{ $spec->section_number }} -
                                    @endif
                                    {{ $spec->section_title }}
                                </h3>
                                <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $spec->technical_description }}</p>

                                @if($spec->requirements)
                                    <div class="mt-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/70">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Requisitos</p>
                                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ $spec->requirements }}</p>
                                    </div>
                                @endif

                                @if($spec->deliverables)
                                    <div class="mt-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/70">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Entregables</p>
                                        <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ $spec->deliverables }}</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </section>
    @endif
</div>
