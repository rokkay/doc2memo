<div class="space-y-6">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-sky-100 via-cyan-50 to-white px-4 py-6 sm:px-6 dark:from-sky-950/40 dark:via-slate-900 dark:to-slate-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Detalle de Documento</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $document->original_filename }}</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $this->documentTypeLabel }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('tenders.show', $document->tender) }}" class="inline-flex items-center rounded-lg bg-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">
                        Volver
                    </a>
                    <a href="{{ route('documents.download', $document) }}" class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">
                        Descargar
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 border-t border-slate-200 px-4 py-4 sm:grid-cols-2 lg:grid-cols-4 sm:px-6 dark:border-slate-800">
            <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Tipo</p>
                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $this->documentTypeLabel }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Tamano</p>
                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ number_format($document->file_size / 1024, 2) }} KB</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Estado</p>
                <div class="mt-1">
                    <x-ui.badge :variant="$this->statusVariant">{{ $this->statusLabel }}</x-ui.badge>
                </div>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70">
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Analizado</p>
                <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $document->analyzed_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</p>
            </div>
        </div>
    </section>

    @if($document->extracted_text)
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Texto extraido</h2>
            </div>
            <div class="max-h-[28rem] overflow-y-auto bg-slate-50 p-4 sm:p-6 dark:bg-slate-800/60">
                <pre class="whitespace-pre-wrap text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $document->extracted_text }}</pre>
            </div>
        </section>
    @endif

    @if($document->document_type === 'pca' && $document->extractedCriteria->isNotEmpty())
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Criterios extraidos (PCA)</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $document->extractedCriteria->count() }} criterios</p>
            </div>

            <div class="max-h-[30rem] space-y-3 overflow-y-auto px-4 py-4 sm:px-6">
                @foreach($document->extractedCriteria as $criterion)
                    @php
                        $variant = match($criterion->priority) {
                            'mandatory' => 'error',
                            'preferable' => 'warning',
                            default => 'success',
                        };
                    @endphp

                    <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700" wire:key="criterion-{{ $criterion->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                                @if($criterion->section_number)
                                    {{ $criterion->section_number }} -
                                @endif
                                {{ $criterion->section_title }}
                            </h3>
                            <x-ui.badge :variant="$variant">{{ ucfirst($criterion->priority) }}</x-ui.badge>
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $criterion->description }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($document->document_type === 'ppt' && $document->extractedSpecifications->isNotEmpty())
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Especificaciones extraidas (PPT)</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $document->extractedSpecifications->count() }} especificaciones</p>
            </div>

            <div class="max-h-[30rem] space-y-3 overflow-y-auto px-4 py-4 sm:px-6">
                @foreach($document->extractedSpecifications as $spec)
                    <article class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700" wire:key="spec-{{ $spec->id }}">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            @if($spec->section_number)
                                {{ $spec->section_number }} -
                            @endif
                            {{ $spec->section_title }}
                        </h3>

                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $spec->technical_description }}</p>

                        @if($spec->requirements)
                            <div class="mt-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/70">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Requisitos</p>
                                <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ $spec->requirements }}</p>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
