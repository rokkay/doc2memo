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
                <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Tamaño</p>
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

    @if($document->document_type === 'pca' && $document->extractedCriteria->isNotEmpty())
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Criterios extraídos (PCA)</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $document->extractedCriteria->count() }} criterios</p>
            </div>

            <div class="grid max-h-[24rem] grid-cols-1 gap-3 overflow-y-auto px-4 py-4 sm:px-6 xl:grid-cols-2">
                @foreach($document->extractedCriteria as $criterion)
                    @php
                        $variant = match($criterion->priority) {
                            'mandatory' => 'error',
                            'preferable' => 'warning',
                            default => 'success',
                        };

                        $priorityLabel = match($criterion->priority) {
                            'mandatory' => 'Obligatorio',
                            'preferable' => 'Preferente',
                            default => 'Opcional',
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
                            <x-ui.badge :variant="$variant">{{ $priorityLabel }}</x-ui.badge>
                        </div>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $criterion->description }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($document->document_type === 'ppt' && $document->extractedSpecifications->isNotEmpty())
        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-4 py-4 sm:px-6 dark:border-slate-800">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Especificaciones extraídas (PPT)</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $document->extractedSpecifications->count() }} especificaciones</p>
            </div>

            <div class="max-h-[24rem] space-y-2 overflow-y-auto px-4 py-4 sm:px-6">
                @foreach($document->extractedSpecifications as $spec)
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
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($document->extracted_text)
        @php
            $extractedTextLength = mb_strlen($document->extracted_text);
        @endphp

        <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <details class="group overflow-hidden rounded-3xl">
                <summary class="list-none cursor-pointer bg-gradient-to-r from-slate-100 to-white px-4 py-4 marker:content-none sm:px-6 dark:from-slate-900 dark:to-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h8M8 15h5M6 3h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2z" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">Texto extraído</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ number_format($extractedTextLength) }} caracteres</p>
                            </div>
                        </div>

                        <div class="inline-flex items-center gap-2">
                            <span class="hidden rounded-full bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 sm:inline dark:bg-slate-800 dark:text-slate-300">Mostrar contenido completo</span>
                            <svg class="h-5 w-5 text-slate-500 transition duration-200 group-open:rotate-180 dark:text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </div>
                </summary>

                <div class="border-t border-slate-200 bg-slate-50/80 px-4 py-3 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    Revisa el texto original extraído por OCR para validar criterios y especificaciones detectadas.
                </div>

                <div class="max-h-[28rem] overflow-y-auto bg-white p-4 sm:p-6 dark:bg-slate-900">
                    <pre class="whitespace-pre-wrap text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $document->extracted_text }}</pre>
                </div>
            </details>
        </section>
    @endif
</div>
