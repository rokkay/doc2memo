<div wire:poll.10s="$refresh" class="space-y-6">
    @if($tender->status === 'analyzing')
        <x-ui.alert type="info" message="La IA está analizando los documentos. Esta página se actualizará automáticamente cuando el análisis termine." />
    @elseif($tender->status === 'completed' && ! $tender->technicalMemory)
        <x-ui.alert type="success" message="Los documentos han sido analizados. Ahora puedes generar la Memoria Técnica." />
    @endif

    @if($this->processingDocuments->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex items-center mb-2">
                <svg class="animate-spin h-5 w-5 text-amber-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="font-medium text-amber-900">Procesando documentos...</p>
            </div>
            <ul class="ml-8 text-sm text-amber-800 space-y-1">
                @foreach($this->processingDocuments as $doc)
                    <li class="flex items-center" wire:key="processing-doc-{{ $doc->id }}">
                        <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></span>
                        {{ $doc->original_filename }} ({{ $doc->document_type === 'pca' ? 'PCA' : 'PPT' }})
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($this->failedDocuments->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center mb-2">
                <svg class="h-5 w-5 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p class="font-medium text-red-900">Error en el análisis</p>
            </div>
            <ul class="ml-8 text-sm text-red-800 space-y-1">
                @foreach($this->failedDocuments as $doc)
                    <li wire:key="failed-doc-{{ $doc->id }}">{{ $doc->original_filename }} - <a href="{{ route('documents.show', $doc) }}" class="underline">Ver detalles</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($errorMessage)
        <x-ui.alert type="error" :message="$errorMessage" />
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $tender->title }}</h1>
                    @if($tender->reference_number)
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Referencia: {{ $tender->reference_number }}</p>
                    @endif
                </div>
                <x-ui.badge :variant="$this->statusVariant">
                    {{ $this->statusLabel }}
                </x-ui.badge>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if($tender->issuing_company)
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-slate-400">Empresa Emisora:</span>
                        <span class="text-sm text-gray-900 dark:text-slate-100">{{ $tender->issuing_company }}</span>
                    </div>
                @endif
                @if($tender->deadline_date)
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-slate-400">Plazo:</span>
                        <span class="text-sm text-gray-900 dark:text-slate-100">{{ $tender->deadline_date }}</span>
                    </div>
                @endif
            </div>

            @if($tender->description)
                <div class="mt-4">
                    <span class="text-sm font-medium text-gray-500 dark:text-slate-400">Descripción:</span>
                    <p class="mt-1 text-sm text-gray-900 dark:text-slate-100">{{ $tender->description }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100">Documentos</h2>

                @if($tender->documents->isEmpty())
                    <p class="text-gray-500 dark:text-slate-400">No hay documentos cargados.</p>
                @else
                    <div class="space-y-4">
                        @foreach($tender->documents as $document)
                            <div class="border rounded-lg p-4 dark:border-slate-700 {{ $document->status === 'processing' ? 'border-blue-300 bg-blue-50 dark:border-sky-800 dark:bg-sky-950/30' : '' }}" wire:key="document-{{ $document->id }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                                {{ $document->document_type === 'pca' ? 'PCA' : 'PPT' }}
                                            </h3>
                                            @if($document->status === 'processing')
                                                <svg class="animate-spin ml-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            @elseif($document->status === 'analyzed')
                                                <svg class="ml-2 h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                            @elseif($document->status === 'failed')
                                                <svg class="ml-2 h-4 w-4 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1 dark:text-slate-400">{{ $document->original_filename }}</p>
                                        <div class="flex items-center mt-2 space-x-2">
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
                                            <x-ui.badge :variant="$badgeVariant">
                                                {{ $badgeLabel }}
                                            </x-ui.badge>
                                            @if($document->analyzed_at)
                                                <span class="text-xs text-gray-400 dark:text-slate-500">
                                                    {{ $document->analyzed_at->diffForHumans() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col space-y-2">
                                        <a
                                            href="{{ route('documents.download', $document) }}"
                                            class="inline-flex items-center justify-center rounded-md bg-sky-100 px-3 py-1.5 text-sm font-medium text-sky-800 transition hover:bg-sky-200 dark:bg-sky-900/30 dark:text-sky-200 dark:hover:bg-sky-900/50"
                                        >
                                            <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M7 10l5 5m0 0l5-5m-5 5V3"/>
                                            </svg>
                                            Descargar
                                        </a>
                                        @if($document->status === 'analyzed')
                                            <a
                                                href="{{ route('documents.show', $document) }}"
                                                class="inline-flex items-center justify-center rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                            >
                                                Ver extracción
                                            </a>
                                        @elseif(in_array($document->status, ['uploaded', 'failed'], true))
                                            <button
                                                wire:click="retryDocument({{ $document->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="retryDocument({{ $document->id }})"
                                                class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-left text-sm font-medium transition {{ $document->status === 'failed' ? 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-200 dark:hover:bg-red-900/50' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:hover:bg-emerald-900/50' }}"
                                            >
                                                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m14.356 2A8.001 8.001 0 005.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-14.357-2m14.357 2H15"/>
                                                </svg>
                                                <span wire:loading.remove wire:target="retryDocument({{ $document->id }})">
                                                    {{ $document->status === 'failed' ? 'Reintentar análisis' : 'Analizar' }}
                                                </span>
                                                <span wire:loading wire:target="retryDocument({{ $document->id }})">Analizando...</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100">Acciones</h2>

                @if($tender->extractedCriteria->isNotEmpty() || $tender->extractedSpecifications->isNotEmpty())
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm p-2 bg-gray-50 dark:bg-slate-800 rounded">
                            <span class="text-gray-600 dark:text-slate-300">Criterios PCA extraídos:</span>
                            <span class="font-medium {{ $tender->extractedCriteria->isNotEmpty() ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-slate-500' }}">
                                {{ $tender->extractedCriteria->count() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm p-2 bg-gray-50 dark:bg-slate-800 rounded">
                            <span class="text-gray-600 dark:text-slate-300">Especificaciones PPT extraídas:</span>
                            <span class="font-medium {{ $tender->extractedSpecifications->isNotEmpty() ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-slate-500' }}">
                                {{ $tender->extractedSpecifications->count() }}
                            </span>
                        </div>

                        @if($tender->technicalMemory && $tender->technicalMemory->status === 'draft')
                            <div class="mt-4 p-4 bg-sky-50 border border-sky-200 rounded-md dark:bg-sky-950/30 dark:border-sky-900">
                                <div class="flex items-center mb-2">
                                    <svg class="animate-spin h-5 w-5 text-sky-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="font-medium text-sky-900 dark:text-sky-200">Generando memoria técnica</p>
                                </div>
                                <p class="text-sm text-sky-800 dark:text-sky-300">La IA está redactando la memoria. La vista se actualizará automáticamente cuando termine.</p>
                            </div>
                        @elseif($tender->technicalMemory)
                            <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
                                <div class="flex items-center mb-2">
                                    <svg class="h-5 w-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="font-medium text-green-900">Memoria Técnica Generada</p>
                                </div>
                                <p class="text-sm text-green-800 mb-2">{{ $tender->technicalMemory->title }}</p>
                                @if($tender->technicalMemory->generated_at)
                                    <p class="text-sm text-green-800 mb-3">
                                        Generada el {{ $tender->technicalMemory->generated_at->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                                <a href="{{ route('technical-memories.show', $tender) }}" class="inline-flex items-center text-sky-700 hover:text-sky-900 text-sm font-medium">
                                    Ver Memoria Técnica
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        @elseif($tender->status === 'completed')
                            <div class="mt-4">
                                <p class="text-sm text-gray-600 dark:text-slate-300 mb-3">Los documentos han sido analizados. Ahora puedes generar la Memoria Técnica.</p>
                                <button
                                    wire:click="generateMemory"
                                    wire:loading.attr="disabled"
                                    class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center justify-center"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span wire:loading.remove wire:target="generateMemory">Generar Memoria Técnica</span>
                                    <span wire:loading wire:target="generateMemory">Generando...</span>
                                </button>
                            </div>
                        @elseif($tender->status === 'analyzing')
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md dark:bg-sky-950/30 dark:border-sky-900">
                                <div class="flex items-center">
                                    <svg class="animate-spin h-5 w-5 text-blue-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-sm text-blue-800 dark:text-sky-300">Esperando a que termine el análisis...</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">
                            Los documentos deben ser analizados antes de poder generar la memoria técnica.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($tender->extractedCriteria->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100">
                    Criterios Extraídos (PCA)
                    <span class="ml-2 text-sm text-gray-500 dark:text-slate-400">({{ $tender->extractedCriteria->count() }} criterios)</span>
                </h2>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @foreach($tender->extractedCriteria as $criterion)
                        <div class="border rounded-lg p-4" wire:key="criterion-{{ $criterion->id }}">
                            <div class="flex justify-between items-start">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                    @if($criterion->section_number)
                                        {{ $criterion->section_number }} -
                                    @endif
                                    {{ $criterion->section_title }}
                                </h3>
                                @php
                                    $priorityVariant = match($criterion->priority) {
                                        'mandatory' => 'error',
                                        'preferable' => 'warning',
                                        'optional' => 'success',
                                        default => 'default',
                                    };
                                @endphp
                                <x-ui.badge :variant="$priorityVariant">
                                    {{ ucfirst($criterion->priority) }}
                                </x-ui.badge>
                            </div>
                            <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">{{ $criterion->description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($tender->extractedSpecifications->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100">
                    Especificaciones Técnicas Extraídas (PPT)
                    <span class="ml-2 text-sm text-gray-500 dark:text-slate-400">({{ $tender->extractedSpecifications->count() }} especificaciones)</span>
                </h2>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @foreach($tender->extractedSpecifications as $spec)
                        <div class="border rounded-lg p-4" wire:key="spec-{{ $spec->id }}">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                @if($spec->section_number)
                                    {{ $spec->section_number }} -
                                @endif
                                {{ $spec->section_title }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">{{ $spec->technical_description }}</p>
                            @if($spec->requirements)
                                <div class="mt-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Requisitos:</span>
                                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $spec->requirements }}</p>
                                </div>
                            @endif
                            @if($spec->deliverables)
                                <div class="mt-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Entregables:</span>
                                    <p class="text-sm text-gray-600 dark:text-slate-300">{{ $spec->deliverables }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
