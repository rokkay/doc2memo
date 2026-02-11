<div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $document->original_filename }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ $this->documentTypeLabel }}</p>
                </div>

                <div class="flex space-x-3">
                    <a href="{{ route('tenders.show', $document->tender) }}" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-md hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">
                        Volver
                    </a>
                    <a href="{{ route('documents.download', $document) }}" class="bg-sky-600 text-white px-4 py-2 rounded-md hover:bg-sky-700">
                        Descargar
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-3 dark:text-slate-100">Informacion del documento</h2>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Tipo:</dt>
                            <dd class="text-sm text-gray-900">{{ $this->documentTypeLabel }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Tamano:</dt>
                            <dd class="text-sm text-gray-900">{{ number_format($document->file_size / 1024, 2) }} KB</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Estado:</dt>
                            <dd>
                                <x-ui.badge :variant="$this->statusVariant">
                                    {{ $this->statusLabel }}
                                </x-ui.badge>
                            </dd>
                        </div>
                        @if($document->analyzed_at)
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-500">Analizado:</dt>
                                <dd class="text-sm text-gray-900">{{ $document->analyzed_at->format('d/m/Y H:i') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                @if($document->extracted_text)
                    <div class="lg:col-span-2">
                        <h2 class="text-lg font-medium text-gray-900 mb-3 dark:text-slate-100">Texto extraido</h2>
                        <div class="bg-gray-50 rounded-md p-4 max-h-96 overflow-y-auto dark:bg-slate-800">
                            <pre class="text-sm text-gray-700 whitespace-pre-wrap dark:text-slate-200">{{ $document->extracted_text }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($document->document_type === 'pca' && $document->extractedCriteria->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100">
                    Criterios extraidos (PCA)
                    <span class="ml-2 text-sm text-gray-500">({{ $document->extractedCriteria->count() }} criterios)</span>
                </h2>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($document->extractedCriteria as $criterion)
                        <div class="border rounded-lg p-4" wire:key="criterion-{{ $criterion->id }}">
                            <div class="flex justify-between items-start">
                                <h3 class="text-sm font-medium text-gray-900">
                                    @if($criterion->section_number)
                                        {{ $criterion->section_number }} -
                                    @endif
                                    {{ $criterion->section_title }}
                                </h3>

                                @php
                                    $variant = match($criterion->priority) {
                                        'mandatory' => 'error',
                                        'preferable' => 'warning',
                                        default => 'success',
                                    };
                                @endphp

                                <x-ui.badge :variant="$variant">
                                    {{ ucfirst($criterion->priority) }}
                                </x-ui.badge>
                            </div>

                            <p class="mt-2 text-sm text-gray-600">{{ $criterion->description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($document->document_type === 'ppt' && $document->extractedSpecifications->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100">
                    Especificaciones extraidas (PPT)
                    <span class="ml-2 text-sm text-gray-500">({{ $document->extractedSpecifications->count() }} especificaciones)</span>
                </h2>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @foreach($document->extractedSpecifications as $spec)
                        <div class="border rounded-lg p-4" wire:key="spec-{{ $spec->id }}">
                            <h3 class="text-sm font-medium text-gray-900">
                                @if($spec->section_number)
                                    {{ $spec->section_number }} -
                                @endif
                                {{ $spec->section_title }}
                            </h3>

                            <p class="mt-2 text-sm text-gray-600">{{ $spec->technical_description }}</p>

                            @if($spec->requirements)
                                <div class="mt-2">
                                    <span class="text-xs font-medium text-gray-500">Requisitos:</span>
                                    <p class="text-sm text-gray-600">{{ $spec->requirements }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
