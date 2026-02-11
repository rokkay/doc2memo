<div>
    <div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center mb-6">
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">Nueva Licitación</h1>
                <span class="ml-4 text-sm text-slate-500 dark:text-slate-400">Los campos marcados con * son obligatorios</span>
            </div>

            <form wire:submit="save" class="space-y-6">
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                            Título de la Licitación <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model.live="form.title" id="title"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition duration-150 placeholder:text-slate-400 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600 @error('form.title') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            placeholder="Ej: Licitación suministro de equipos informáticos 2024">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Usa un título descriptivo para identificar la licitación rápidamente.</p>
                        @error('form.title')
                            <p class="mt-1 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="issuing_company" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Empresa Emisora</label>
                        <input type="text" wire:model.live="form.issuing_company" id="issuing_company"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition duration-150 placeholder:text-slate-400 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600 @error('form.issuing_company') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            placeholder="Ej: Xunta de Galicia">
                        @error('form.issuing_company')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reference_number" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Número de Referencia</label>
                        <input type="text" wire:model.live="form.reference_number" id="reference_number"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition duration-150 placeholder:text-slate-400 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600 @error('form.reference_number') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            placeholder="Ej: EXP-2024-1234">
                        @error('form.reference_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="deadline_date" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Plazo de Presentación</label>
                        <input type="text" wire:model.live="form.deadline_date" id="deadline_date"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition duration-150 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-slate-600 @error('form.deadline_date') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror">
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Puedes indicar una fecha exacta o texto libre (ej: 15 días desde la publicación).</p>
                        @error('form.deadline_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-slate-300">Descripción</label>
                        <textarea wire:model.live="form.description" id="description" rows="3"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-900 shadow-sm transition duration-150 placeholder:text-slate-400 hover:border-slate-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:hover:border-slate-600 @error('form.description') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            placeholder="Descripción breve del objeto de la licitación..."></textarea>
                        @error('form.description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-6 dark:border-slate-800">
                    <h2 class="text-lg font-medium text-slate-900 mb-2 dark:text-slate-100">Documentos</h2>
                    <p class="text-sm text-slate-500 mb-4">Sube ambos documentos para comenzar el análisis automático con IA.</p>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        {{-- PCA File Upload --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                                PCA (Pliego de Condiciones Administrativas) <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                @if($pcaFile)
                                    <div class="flex items-center justify-between rounded-lg border-2 border-green-400 bg-green-50 px-4 py-3 dark:border-green-700 dark:bg-green-900/30">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-sm text-gray-900 dark:text-slate-100">{{ $pcaFile->getClientOriginalName() }}</span>
                                        </div>
                                        <button type="button" wire:click="removePcaFile" class="inline-flex items-center rounded-md bg-white/70 px-2.5 py-1 text-sm font-medium text-red-700 transition hover:bg-red-100 hover:text-red-800 dark:bg-slate-900/60 dark:text-red-300 dark:hover:bg-red-950/40">
                                            Eliminar archivo
                                        </button>
                                    </div>
                                @else
                                    <div class="flex justify-center rounded-lg border-2 border-dashed px-6 pt-5 pb-6 transition-all duration-150 {{ $errors->has('pcaFile') ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950/30' : 'border-slate-300 bg-slate-50/40 hover:border-sky-400 hover:bg-sky-50/40 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-sky-600 dark:hover:bg-sky-950/20' }}">
                                        <div class="space-y-1 text-center">
                                            <svg wire:loading.remove wire:target="pcaFile" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div wire:loading wire:target="pcaFile" class="mx-auto h-12 w-12 flex items-center justify-center">
                                                <svg class="animate-spin h-8 w-8 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex justify-center text-sm text-gray-600 dark:text-slate-400">
                                                <label for="pcaFile" class="relative inline-flex cursor-pointer items-center rounded-md border border-sky-200 bg-white px-3 py-1.5 font-medium text-sky-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-800 focus-within:outline-none focus-within:ring-2 focus-within:ring-sky-500 focus-within:ring-offset-2 dark:border-sky-800 dark:bg-slate-900 dark:text-sky-300 dark:hover:border-sky-700 dark:hover:bg-sky-950/30 dark:hover:text-sky-200 dark:focus-within:ring-offset-slate-900">
                                                    <span>Seleccionar archivo</span>
                                                    <input id="pcaFile" type="file" wire:model="pcaFile" accept=".pdf,.md,.txt" class="sr-only">
                                                </label>
                                            </div>
                                            <p class="text-xs text-gray-500">PDF, Markdown o TXT hasta 10MB</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @error('pcaFile')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- PPT File Upload --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-300">
                                PPT (Pliego de Prescripciones Técnicas) <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                @if($pptFile)
                                    <div class="flex items-center justify-between rounded-lg border-2 border-green-400 bg-green-50 px-4 py-3 dark:border-green-700 dark:bg-green-900/30">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <span class="text-sm text-gray-900 dark:text-slate-100">{{ $pptFile->getClientOriginalName() }}</span>
                                        </div>
                                        <button type="button" wire:click="removePptFile" class="inline-flex items-center rounded-md bg-white/70 px-2.5 py-1 text-sm font-medium text-red-700 transition hover:bg-red-100 hover:text-red-800 dark:bg-slate-900/60 dark:text-red-300 dark:hover:bg-red-950/40">
                                            Eliminar archivo
                                        </button>
                                    </div>
                                @else
                                    <div class="flex justify-center rounded-lg border-2 border-dashed px-6 pt-5 pb-6 transition-all duration-150 {{ $errors->has('pptFile') ? 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-950/30' : 'border-slate-300 bg-slate-50/40 hover:border-sky-400 hover:bg-sky-50/40 dark:border-slate-700 dark:bg-slate-900/40 dark:hover:border-sky-600 dark:hover:bg-sky-950/20' }}">
                                        <div class="space-y-1 text-center">
                                            <svg wire:loading.remove wire:target="pptFile" class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <div wire:loading wire:target="pptFile" class="mx-auto h-12 w-12 flex items-center justify-center">
                                                <svg class="animate-spin h-8 w-8 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                            <div class="flex justify-center text-sm text-gray-600 dark:text-slate-400">
                                                <label for="pptFile" class="relative inline-flex cursor-pointer items-center rounded-md border border-sky-200 bg-white px-3 py-1.5 font-medium text-sky-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-800 focus-within:outline-none focus-within:ring-2 focus-within:ring-sky-500 focus-within:ring-offset-2 dark:border-sky-800 dark:bg-slate-900 dark:text-sky-300 dark:hover:border-sky-700 dark:hover:bg-sky-950/30 dark:hover:text-sky-200 dark:focus-within:ring-offset-slate-900">
                                                    <span>Seleccionar archivo</span>
                                                    <input id="pptFile" type="file" wire:model="pptFile" accept=".pdf,.md,.txt" class="sr-only">
                                                </label>
                                            </div>
                                            <p class="text-xs text-gray-500">PDF, Markdown o TXT hasta 10MB</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @error('pptFile')
                                <p class="mt-1 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <a href="{{ route('tenders.index') }}" class="inline-flex items-center rounded-md border border-slate-300 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-slate-200 hover:shadow active:translate-y-0 active:scale-[0.99] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Cancelar
                    </a>
                    <button type="submit" wire:loading.attr="disabled" wire:loading.class="cursor-wait"
                        class="inline-flex items-center rounded-md bg-sky-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-sky-700 hover:shadow-md active:translate-y-0 active:scale-[0.99] focus:outline-none focus-visible:ring-4 focus-visible:ring-sky-500/30 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-sm">
                        <svg wire:loading.remove wire:target="save" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <svg wire:loading wire:target="save" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="save">Crear Licitación y Analizar</span>
                        <span wire:loading wire:target="save">Creando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
