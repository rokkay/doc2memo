<div class="rounded-2xl border border-slate-200 bg-white/95 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
    <div class="px-4 py-5 sm:p-6">
        @if(! $tender->technicalMemory)
            <div class="text-center py-12">
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100">No hay memoria tecnica generada</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">La memoria tecnica aun no ha sido generada para esta licitacion.</p>
                <div class="mt-6">
                    <a href="{{ route('tenders.show', $tender) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-sky-700 bg-sky-100 hover:bg-sky-200 dark:bg-sky-900/40 dark:text-sky-300 dark:hover:bg-sky-900/60">
                        Volver a la licitacion
                    </a>
                </div>
            </div>
        @else
            @php
                $memory = $tender->technicalMemory;
            @endphp

            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $memory->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                        Generada el {{ $memory->generated_at?->format('d/m/Y H:i') }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('tenders.show', $tender) }}" class="bg-slate-200 text-slate-700 px-4 py-2 rounded-md hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600">
                        Volver
                    </a>
                    @if($memory->generated_file_path)
                        <a href="{{ route('technical-memories.download', $memory) }}" class="bg-sky-600 text-white px-4 py-2 rounded-md hover:bg-sky-700">
                            Descargar PDF
                        </a>
                    @endif
                </div>
            </div>

            <div class="space-y-8">
                @if($memory->introduction)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">1. Introduccion</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->introduction)) !!}</div>
                    </section>
                @endif

                @if($memory->company_presentation)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">2. Presentacion de la Empresa</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->company_presentation)) !!}</div>
                    </section>
                @endif

                @if($memory->technical_approach)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">3. Enfoque Tecnico</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->technical_approach)) !!}</div>
                    </section>
                @endif

                @if($memory->methodology)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">4. Metodologia</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->methodology)) !!}</div>
                    </section>
                @endif

                @if($memory->team_structure)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">5. Estructura del Equipo</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->team_structure)) !!}</div>
                    </section>
                @endif

                @if($memory->timeline)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">6. Cronograma</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->timeline)) !!}</div>
                    </section>
                @endif

                @if($memory->quality_assurance)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">7. Aseguramiento de Calidad</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->quality_assurance)) !!}</div>
                    </section>
                @endif

                @if($memory->risk_management)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">8. Gestion de Riesgos</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->risk_management)) !!}</div>
                    </section>
                @endif

                @if($memory->compliance_matrix)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3">9. Matriz de Cumplimiento</h2>
                        <div class="text-gray-700 leading-relaxed">{!! nl2br(e($memory->compliance_matrix)) !!}</div>
                    </section>
                @endif
            </div>
        @endif
    </div>
</div>
