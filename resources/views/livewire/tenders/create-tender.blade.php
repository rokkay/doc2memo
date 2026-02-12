<div class="space-y-6">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="bg-gradient-to-r from-sky-100 via-cyan-50 to-white px-4 py-6 sm:px-6 dark:from-sky-950/40 dark:via-slate-900 dark:to-slate-900">
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Nueva Licitación</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Completa los datos generales y sube los dos pliegos para iniciar el analisis automatico.</p>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Los campos marcados con * son obligatorios.</p>
        </div>

        <form wire:submit="save" class="space-y-6 px-4 py-5 sm:px-6">
            <section class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Datos de la licitacion</h2>

                <div class="mt-4 grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="title" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Título de la Licitación <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            wire:model.live="form.title"
                            id="title"
                            placeholder="Ej: Licitación suministro de equipos informáticos 2024"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600 @error('form.title') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        >
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Usa un título descriptivo para identificar la licitación rápidamente.</p>
                        @error('form.title')
                            <p class="mt-1 flex items-center text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="issuing_company" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Empresa Emisora</label>
                        <input
                            type="text"
                            wire:model.live="form.issuing_company"
                            id="issuing_company"
                            placeholder="Ej: Xunta de Galicia"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600 @error('form.issuing_company') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        >
                        @error('form.issuing_company')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Número de Referencia</label>
                        <input
                            type="text"
                            wire:model.live="form.reference_number"
                            id="reference_number"
                            placeholder="Ej: EXP-2024-1234"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600 @error('form.reference_number') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        >
                        @error('form.reference_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="deadline_date" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Plazo de Presentación</label>
                        <input
                            type="text"
                            wire:model.live="form.deadline_date"
                            id="deadline_date"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-slate-600 @error('form.deadline_date') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        >
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Puedes indicar una fecha exacta o texto libre (ej: 15 días desde la publicación).</p>
                        @error('form.deadline_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Descripción</label>
                        <textarea
                            wire:model.live="form.description"
                            id="description"
                            rows="3"
                            placeholder="Descripción breve del objeto de la licitación..."
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600 @error('form.description') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                        ></textarea>
                        @error('form.description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Documentos</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sube ambos documentos para comenzar el análisis automático con IA.</p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">PCA (Pliego de Condiciones Administrativas) <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            @if($pcaFile)
                                <div class="flex items-center justify-between rounded-xl border border-green-300 bg-green-50 px-4 py-3 dark:border-green-700 dark:bg-green-900/30">
                                    <span class="truncate pr-3 text-sm text-slate-800 dark:text-slate-100">{{ $pcaFile->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removePcaFile" class="inline-flex items-center rounded-md bg-white/70 px-2.5 py-1 text-sm font-medium text-red-700 transition hover:bg-red-100 hover:text-red-800 dark:bg-slate-900/60 dark:text-red-300 dark:hover:bg-red-950/40">
                                        Eliminar archivo
                                    </button>
                                </div>
                            @else
                                <div class="rounded-xl border-2 border-dashed px-6 py-6 text-center transition {{ $errors->has('pcaFile') ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950/30' : 'border-slate-300 bg-slate-50/50 hover:border-sky-400 hover:bg-sky-50/40 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-sky-600 dark:hover:bg-sky-950/20' }}">
                                    <div wire:loading.remove wire:target="pcaFile" class="space-y-2">
                                        <p class="text-sm text-slate-600 dark:text-slate-300">Arrastra o selecciona el archivo PCA</p>
                                        <label for="pcaFile" class="inline-flex cursor-pointer items-center rounded-md border border-sky-200 bg-white px-3 py-1.5 text-sm font-medium text-sky-700 transition hover:border-sky-300 hover:bg-sky-50 dark:border-sky-800 dark:bg-slate-900 dark:text-sky-300 dark:hover:border-sky-700 dark:hover:bg-sky-950/30">
                                            Seleccionar archivo
                                            <input id="pcaFile" type="file" wire:model="pcaFile" accept=".pdf,.md,.txt" class="sr-only">
                                        </label>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">PDF, Markdown o TXT hasta 10MB</p>
                                    </div>
                                    <div wire:loading wire:target="pcaFile" class="flex items-center justify-center gap-2 text-sm text-sky-700 dark:text-sky-300">
                                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Subiendo...
                                    </div>
                                </div>
                            @endif
                        </div>
                        @error('pcaFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">PPT (Pliego de Prescripciones Técnicas) <span class="text-red-500">*</span></label>
                        <div class="mt-1">
                            @if($pptFile)
                                <div class="flex items-center justify-between rounded-xl border border-green-300 bg-green-50 px-4 py-3 dark:border-green-700 dark:bg-green-900/30">
                                    <span class="truncate pr-3 text-sm text-slate-800 dark:text-slate-100">{{ $pptFile->getClientOriginalName() }}</span>
                                    <button type="button" wire:click="removePptFile" class="inline-flex items-center rounded-md bg-white/70 px-2.5 py-1 text-sm font-medium text-red-700 transition hover:bg-red-100 hover:text-red-800 dark:bg-slate-900/60 dark:text-red-300 dark:hover:bg-red-950/40">
                                        Eliminar archivo
                                    </button>
                                </div>
                            @else
                                <div class="rounded-xl border-2 border-dashed px-6 py-6 text-center transition {{ $errors->has('pptFile') ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950/30' : 'border-slate-300 bg-slate-50/50 hover:border-sky-400 hover:bg-sky-50/40 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-sky-600 dark:hover:bg-sky-950/20' }}">
                                    <div wire:loading.remove wire:target="pptFile" class="space-y-2">
                                        <p class="text-sm text-slate-600 dark:text-slate-300">Arrastra o selecciona el archivo PPT</p>
                                        <label for="pptFile" class="inline-flex cursor-pointer items-center rounded-md border border-sky-200 bg-white px-3 py-1.5 text-sm font-medium text-sky-700 transition hover:border-sky-300 hover:bg-sky-50 dark:border-sky-800 dark:bg-slate-900 dark:text-sky-300 dark:hover:border-sky-700 dark:hover:bg-sky-950/30">
                                            Seleccionar archivo
                                            <input id="pptFile" type="file" wire:model="pptFile" accept=".pdf,.md,.txt" class="sr-only">
                                        </label>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">PDF, Markdown o TXT hasta 10MB</p>
                                    </div>
                                    <div wire:loading wire:target="pptFile" class="flex items-center justify-center gap-2 text-sm text-sky-700 dark:text-sky-300">
                                        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Subiendo...
                                    </div>
                                </div>
                            @endif
                        </div>
                        @error('pptFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end dark:border-slate-800">
                <a href="{{ route('tenders.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    Cancelar
                </a>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="cursor-wait"
                    class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <svg wire:loading wire:target="save" class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="save">Crear Licitación y Analizar</span>
                    <span wire:loading wire:target="save">Creando...</span>
                </button>
            </div>
        </form>
    </section>
</div>
