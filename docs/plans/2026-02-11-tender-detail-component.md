# TenderDetail Livewire Component Implementation Plan

> **Goal:** Replace the tender show page with a reactive Livewire component that auto-refreshes during analysis and shows real-time status updates.

**Architecture:** Create a single Livewire component that handles all tender detail display, document analysis actions, and technical memory generation. The component uses `wire:poll` for auto-refresh when analysis is in progress.

**Tech Stack:** Livewire 4, Pest PHP, Tailwind CSS

---

## Task 1: Create ExtractedCriterionFactory

**Files:**
- Create: `database/factories/ExtractedCriterionFactory.php`

**Step 1: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExtractedCriterionFactory extends Factory
{
    protected $model = ExtractedCriterion::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'document_id' => Document::factory(),
            'section_number' => fake()->optional()->regexify('[0-9]\.[0-9]'),
            'section_title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['mandatory', 'preferable', 'optional']),
            'metadata' => null,
        ];
    }
}
```

---

## Task 2: Create ExtractedSpecificationFactory

**Files:**
- Create: `database/factories/ExtractedSpecificationFactory.php`

**Step 1: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\ExtractedSpecification;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExtractedSpecificationFactory extends Factory
{
    protected $model = ExtractedSpecification::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'document_id' => Document::factory(),
            'section_number' => fake()->optional()->regexify('[0-9]\.[0-9]'),
            'section_title' => fake()->sentence(4),
            'technical_description' => fake()->paragraph(),
            'requirements' => fake()->optional()->paragraph(),
            'deliverables' => fake()->optional()->paragraph(),
            'metadata' => null,
        ];
    }
}
```

---

## Task 3: Create TechnicalMemoryFactory

**Files:**
- Create: `database/factories/TechnicalMemoryFactory.php`

**Step 1: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\TechnicalMemory;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Factories\Factory;

class TechnicalMemoryFactory extends Factory
{
    protected $model = TechnicalMemory::class;

    public function definition(): array
    {
        return [
            'tender_id' => Tender::factory(),
            'title' => fake()->sentence(6),
            'introduction' => fake()->optional()->paragraph(),
            'company_presentation' => fake()->optional()->paragraph(),
            'technical_approach' => fake()->optional()->paragraph(),
            'methodology' => fake()->optional()->paragraph(),
            'team_structure' => fake()->optional()->paragraph(),
            'timeline' => fake()->optional()->paragraph(),
            'quality_assurance' => fake()->optional()->paragraph(),
            'risk_management' => fake()->optional()->paragraph(),
            'compliance_matrix' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['draft', 'generated', 'reviewed', 'final']),
            'generated_file_path' => fake()->optional()->filePath(),
            'generated_at' => fake()->optional()->dateTime(),
        ];
    }
}
```

---

## Task 4: Create TenderDetail Livewire Component

**Files:**
- Create: `app/Livewire/Tenders/TenderDetail.php`
- Create: `resources/views/livewire/tenders/tender-detail.blade.php`

**Step 1: Create the component class**

```php
<?php

namespace App\Livewire\Tenders;

use App\Models\Tender;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TenderDetail extends Component
{
    public Tender $tender;

    public bool $isAnalyzing = false;

    public bool $isGeneratingMemory = false;

    public ?string $errorMessage = null;

    public function mount(Tender $tender): void
    {
        $this->tender = $tender->load([
            'documents',
            'extractedCriteria',
            'extractedSpecifications',
            'technicalMemory',
        ]);
    }

    #[Computed]
    public function statusLabel(): string
    {
        return match ($this->tender->status) {
            'pending' => 'Pendiente',
            'analyzing' => 'Analizando',
            'completed' => 'Completado',
            'failed' => 'Error',
            default => ucfirst($this->tender->status),
        };
    }

    #[Computed]
    public function statusVariant(): string
    {
        return match ($this->tender->status) {
            'pending' => 'secondary',
            'analyzing' => 'info',
            'completed' => 'success',
            'failed' => 'error',
            default => 'default',
        };
    }

    public function analyzeDocuments(): void
    {
        $this->isAnalyzing = true;
        $this->errorMessage = null;

        try {
            // Call DocumentAnalysisService
            app(\App\Services\DocumentAnalysisService::class)->analyzeTender($this->tender);
            
            // Refresh tender data
            $this->tender->refresh();
            $this->tender->load([
                'documents',
                'extractedCriteria',
                'extractedSpecifications',
                'technicalMemory',
            ]);

            $this->dispatch('analysis-completed');
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al analizar los documentos: ' . $e->getMessage();
        } finally {
            $this->isAnalyzing = false;
        }
    }

    public function generateMemory(): void
    {
        $this->isGeneratingMemory = true;
        $this->errorMessage = null;

        try {
            // Call TechnicalMemoryGenerationService
            app(\App\Services\TechnicalMemoryGenerationService::class)->generate($this->tender);
            
            // Refresh tender data
            $this->tender->refresh();
            $this->tender->load([
                'documents',
                'extractedCriteria',
                'extractedSpecifications',
                'technicalMemory',
            ]);

            $this->dispatch('memory-generated');
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al generar la memoria técnica: ' . $e->getMessage();
        } finally {
            $this->isGeneratingMemory = false;
        }
    }

    public function render(): View
    {
        return view('livewire.tenders.tender-detail');
    }
}
```

**Step 2: Create the blade view**

Create `resources/views/livewire/tenders/tender-detail.blade.php`:

```blade
<div wire:poll.10s="$refresh" class="space-y-6">
    {{-- Status Alert Banner --}}
    @if($tender->status === 'analyzing')
        <x-ui.alert type="info" message="La IA está analizando los documentos. Esta página se actualizará automáticamente cuando el análisis termine." />
    @elseif($tender->status === 'completed' && !$tender->technicalMemory)
        <x-ui.alert type="success" message="Los documentos han sido analizados. Ahora puedes generar la Memoria Técnica." />
    @endif

    {{-- Processing Documents Alert --}}
    @php
        $processingDocs = $tender->documents->where('status', 'processing');
    @endphp
    @if($processingDocs->isNotEmpty())
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center mb-2">
                <svg class="animate-spin h-5 w-5 text-yellow-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="font-medium text-yellow-900">Procesando documentos...</p>
            </div>
            <ul class="ml-8 text-sm text-yellow-800 space-y-1">
                @foreach($processingDocs as $doc)
                    <li class="flex items-center">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2 animate-pulse"></span>
                        {{ $doc->original_filename }} ({{ $doc->document_type === 'pca' ? 'PCA' : 'PPT' }})
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Failed Documents Alert --}}
    @php
        $failedDocs = $tender->documents->where('status', 'failed');
    @endphp
    @if($failedDocs->isNotEmpty())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center mb-2">
                <svg class="h-5 w-5 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p class="font-medium text-red-900">Error en el análisis</p>
            </div>
            <ul class="ml-8 text-sm text-red-800 space-y-1">
                @foreach($failedDocs as $doc)
                    <li>{{ $doc->original_filename }} - <a href="{{ route('documents.show', $doc) }}" class="underline">Ver detalles</a></li>
                @endforeach
            </ul>
            <button 
                wire:click="analyzeDocuments" 
                wire:loading.attr="disabled"
                class="mt-3 ml-8 text-sm bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded"
            >
                <span wire:loading.remove wire:target="analyzeDocuments">Reintentar análisis</span>
                <span wire:loading wire:target="analyzeDocuments">Analizando...</span>
            </button>
        </div>
    @endif

    {{-- Error Message Alert --}}
    @if($errorMessage)
        <x-ui.alert type="error" :message="$errorMessage" />
    @endif

    {{-- Tender Info Card --}}
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $tender->title }}</h1>
                    @if($tender->reference_number)
                        <p class="mt-1 text-sm text-gray-500">Referencia: {{ $tender->reference_number }}</p>
                    @endif
                </div>
                <x-ui.badge :variant="$this->statusVariant">
                    {{ $this->statusLabel }}
                </x-ui.badge>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if($tender->issuing_company)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Empresa Emisora:</span>
                        <span class="text-sm text-gray-900">{{ $tender->issuing_company }}</span>
                    </div>
                @endif
                @if($tender->deadline_date)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Fecha Límite:</span>
                        <span class="text-sm text-gray-900">{{ $tender->deadline_date->format('d/m/Y') }}</span>
                    </div>
                @endif
            </div>

            @if($tender->description)
                <div class="mt-4">
                    <span class="text-sm font-medium text-gray-500">Descripción:</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $tender->description }}</p>
                </div>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Documents Section --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Documentos</h2>

                @if($tender->documents->isEmpty())
                    <p class="text-gray-500">No hay documentos cargados.</p>
                @else
                    <div class="space-y-4">
                        @foreach($tender->documents as $document)
                            <div class="border rounded-lg p-4 {{ $document->status === 'processing' ? 'border-blue-300 bg-blue-50' : '' }}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h3 class="text-sm font-medium text-gray-900">
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
                                        <p class="text-sm text-gray-500 mt-1">{{ $document->original_filename }}</p>
                                        <div class="flex items-center mt-2 space-x-2">
                                            <x-ui.badge :variant="match($document->status) {
                                                'uploaded' => 'secondary',
                                                'processing' => 'info',
                                                'analyzed' => 'success',
                                                'failed' => 'error',
                                                default => 'default',
                                            }">
                                                {{ match($document->status) {
                                                    'uploaded' => 'Subido',
                                                    'processing' => 'Procesando...',
                                                    'analyzed' => 'Analizado',
                                                    'failed' => 'Error',
                                                    default => ucfirst($document->status),
                                                } }}
                                            </x-ui.badge>
                                            @if($document->analyzed_at)
                                                <span class="text-xs text-gray-400">
                                                    {{ $document->analyzed_at->diffForHumans() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col space-y-2">
                                        <a href="{{ route('documents.download', $document) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            Descargar
                                        </a>
                                        @if($document->status === 'analyzed')
                                            <a href="{{ route('documents.show', $document) }}" class="text-gray-600 hover:text-gray-900 text-sm">
                                                Ver extracción
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($tender->documents->where('status', 'uploaded')->isNotEmpty() || $tender->documents->where('status', 'failed')->isNotEmpty())
                        <button 
                            wire:click="analyzeDocuments"
                            wire:loading.attr="disabled"
                            class="mt-4 w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center justify-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span wire:loading.remove wire:target="analyzeDocuments">
                                {{ $tender->documents->where('status', 'failed')->isNotEmpty() ? 'Reintentar Análisis' : 'Analizar Documentos' }}
                            </span>
                            <span wire:loading wire:target="analyzeDocuments">Analizando...</span>
                        </button>
                    @endif
                @endif
            </div>
        </div>

        {{-- Actions Section --}}
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Acciones</h2>

                @if($tender->extractedCriteria->isNotEmpty() || $tender->extractedSpecifications->isNotEmpty())
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm p-2 bg-gray-50 rounded">
                            <span class="text-gray-600">Criterios PCA extraídos:</span>
                            <span class="font-medium {{ $tender->extractedCriteria->isNotEmpty() ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $tender->extractedCriteria->count() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm p-2 bg-gray-50 rounded">
                            <span class="text-gray-600">Especificaciones PPT extraídas:</span>
                            <span class="font-medium {{ $tender->extractedSpecifications->isNotEmpty() ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $tender->extractedSpecifications->count() }}
                            </span>
                        </div>

                        @if($tender->technicalMemory)
                            <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
                                <div class="flex items-center mb-2">
                                    <svg class="h-5 w-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="font-medium text-green-900">Memoria Técnica Generada</p>
                                </div>
                                <p class="text-sm text-green-800 mb-3">
                                    Generada el {{ $tender->technicalMemory->generated_at->format('d/m/Y H:i') }}
                                </p>
                                <a href="{{ route('technical-memories.show', $tender) }}" class="inline-flex items-center text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                    Ver Memoria Técnica
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        @elseif($tender->status === 'completed')
                            <div class="mt-4">
                                <p class="text-sm text-gray-600 mb-3">Los documentos han sido analizados. Ahora puedes generar la Memoria Técnica.</p>
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
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                <div class="flex items-center">
                                    <svg class="animate-spin h-5 w-5 text-blue-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-sm text-blue-800">Esperando a que termine el análisis...</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-6">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">
                            Los documentos deben ser analizados antes de poder generar la memoria técnica.
                        </p>
                        @if($tender->documents->where('status', 'uploaded')->isNotEmpty())
                            <button 
                                wire:click="analyzeDocuments"
                                wire:loading.attr="disabled"
                                class="mt-4 text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                                Iniciar análisis ahora
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Extracted Criteria Section --}}
    @if($tender->extractedCriteria->isNotEmpty())
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Criterios Extraídos (PCA)
                    <span class="ml-2 text-sm text-gray-500">({{ $tender->extractedCriteria->count() }} criterios)</span>
                </h2>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @foreach($tender->extractedCriteria as $criterion)
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-sm font-medium text-gray-900">
                                    @if($criterion->section_number)
                                        {{ $criterion->section_number }} -
                                    @endif
                                    {{ $criterion->section_title }}
                                </h3>
                                <x-ui.badge :variant="match($criterion->priority) {
                                    'mandatory' => 'error',
                                    'preferable' => 'warning',
                                    'optional' => 'success',
                                    default => 'default',
                                }">
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

    {{-- Extracted Specifications Section --}}
    @if($tender->extractedSpecifications->isNotEmpty())
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Especificaciones Técnicas Extraídas (PPT)
                    <span class="ml-2 text-sm text-gray-500">({{ $tender->extractedSpecifications->count() }} especificaciones)</span>
                </h2>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    @foreach($tender->extractedSpecifications as $spec)
                        <div class="border rounded-lg p-4">
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
                            @if($spec->deliverables)
                                <div class="mt-2">
                                    <span class="text-xs font-medium text-gray-500">Entregables:</span>
                                    <p class="text-sm text-gray-600">{{ $spec->deliverables }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
```

---

## Task 5: Create DocumentAnalysisService

**Files:**
- Create: `app/Services/DocumentAnalysisService.php`

**Step 1: Write the service**

```php
<?php

namespace App\Services;

use App\Models\Tender;

class DocumentAnalysisService
{
    public function analyzeTender(Tender $tender): void
    {
        // Update tender status to analyzing
        $tender->update(['status' => 'analyzing']);

        // Analyze each document that is not yet analyzed
        foreach ($tender->documents()->whereIn('status', ['uploaded', 'failed'])->get() as $document) {
            $this->analyzeDocument($document);
        }

        // Check if all documents are analyzed
        $allAnalyzed = $tender->documents()->where('status', '!=', 'analyzed')->doesntExist();
        $anyFailed = $tender->documents()->where('status', 'failed')->exists();

        // Update tender status based on document analysis results
        if ($anyFailed) {
            $tender->update(['status' => 'failed']);
        } elseif ($allAnalyzed) {
            $tender->update(['status' => 'completed']);
        }
    }

    private function analyzeDocument($document): void
    {
        // Set document to processing
        $document->update(['status' => 'processing']);

        try {
            // This is where the actual AI analysis would happen
            // For now, we'll simulate a successful analysis
            $document->update([
                'status' => 'analyzed',
                'analyzed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $document->update(['status' => 'failed']);
            throw $e;
        }
    }
}
```

---

## Task 6: Create TechnicalMemoryGenerationService

**Files:**
- Create: `app/Services/TechnicalMemoryGenerationService.php`

**Step 1: Write the service**

```php
<?php

namespace App\Services;

use App\Models\Tender;
use App\Models\TechnicalMemory;

class TechnicalMemoryGenerationService
{
    public function generate(Tender $tender): TechnicalMemory
    {
        // This is where the actual AI generation would happen
        // For now, we'll create a basic technical memory structure

        $memory = TechnicalMemory::create([
            'tender_id' => $tender->id,
            'title' => 'Memoria Técnica - ' . $tender->title,
            'introduction' => $this->generateIntroduction($tender),
            'company_presentation' => $this->generateCompanyPresentation($tender),
            'technical_approach' => $this->generateTechnicalApproach($tender),
            'methodology' => $this->generateMethodology($tender),
            'team_structure' => $this->generateTeamStructure($tender),
            'timeline' => $this->generateTimeline($tender),
            'quality_assurance' => $this->generateQualityAssurance($tender),
            'risk_management' => $this->generateRiskManagement($tender),
            'compliance_matrix' => $this->generateComplianceMatrix($tender),
            'status' => 'generated',
            'generated_at' => now(),
        ]);

        return $memory;
    }

    private function generateIntroduction(Tender $tender): string
    {
        return "Introducción a la memoria técnica para la licitación: {$tender->title}";
    }

    private function generateCompanyPresentation(Tender $tender): string
    {
        return "Presentación de la empresa para la licitación.";
    }

    private function generateTechnicalApproach(Tender $tender): string
    {
        return "Enfoque técnico basado en los criterios y especificaciones extraídas.";
    }

    private function generateMethodology(Tender $tender): string
    {
        return "Metodología de trabajo propuesta.";
    }

    private function generateTeamStructure(Tender $tender): string
    {
        return "Estructura del equipo de trabajo.";
    }

    private function generateTimeline(Tender $tender): string
    {
        return "Cronograma de ejecución del proyecto.";
    }

    private function generateQualityAssurance(Tender $tender): string
    {
        return "Plan de aseguramiento de calidad.";
    }

    private function generateRiskManagement(Tender $tender): string
    {
        return "Gestión de riesgos del proyecto.";
    }

    private function generateComplianceMatrix(Tender $tender): string
    {
        return "Matriz de cumplimiento de requisitos.";
    }
}
```

---

## Task 7: Update Show View

**Files:**
- Modify: `resources/views/tenders/show.blade.php`

**Step 1: Replace the entire content**

```blade
@extends('layouts.app')

@section('title', $tender->title . ' - Doc2Memo')

@section('content')
    <livewire:tenders.tender-detail :tender="$tender" />
@endsection
```

---

## Task 8: Create Feature Tests

**Files:**
- Create: `tests/Feature/Livewire/Tenders/TenderDetailTest.php`

**Step 1: Write the test file**

```php
<?php

use App\Livewire\Tenders\TenderDetail;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\TechnicalMemory;
use App\Models\Tender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses()->group('livewire');
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
});

it('renders successfully with tender', function (): void {
    $tender = Tender::factory()->create();

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertOk();
});

it('displays tender information', function (): void {
    $tender = Tender::factory()->create([
        'title' => 'Test Tender Title',
        'issuing_company' => 'Test Company',
        'reference_number' => 'REF-123',
        'description' => 'Test description',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Tender Title')
        ->assertSee('Test Company')
        ->assertSee('REF-123')
        ->assertSee('Test description');
});

it('displays documents list', function (): void {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'original_filename' => 'test-document.pdf',
        'document_type' => 'pca',
        'status' => 'uploaded',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('test-document.pdf')
        ->assertSee('PCA');
});

it('displays extracted criteria', function (): void {
    $tender = Tender::factory()->create();
    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'section_title' => 'Test Criterion',
        'description' => 'Test criterion description',
        'priority' => 'mandatory',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Criterion')
        ->assertSee('Test criterion description')
        ->assertSee('Mandatory');
});

it('displays extracted specifications', function (): void {
    $tender = Tender::factory()->create();
    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'section_title' => 'Test Specification',
        'technical_description' => 'Test technical description',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Specification')
        ->assertSee('Test technical description');
});

it('displays technical memory when available', function (): void {
    $tender = Tender::factory()->create(['status' => 'completed']);
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Test Technical Memory',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Memoria Técnica Generada')
        ->assertSee('Ver Memoria Técnica');
});

it('shows analyze button when documents are uploaded', function (): void {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'uploaded',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Analizar Documentos');
});

it('can trigger document analysis', function (): void {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'uploaded',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('analyzeDocuments')
        ->assertDispatched('analysis-completed');
});

it('can generate technical memory when analysis is complete', function (): void {
    $tender = Tender::factory()->create(['status' => 'completed']);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('generateMemory')
        ->assertDispatched('memory-generated');
});
```

---

## Task 9: Run Tests and Verify

**Step 1: Run the tests**

```bash
php artisan test --compact --filter=TenderDetailTest
```

Expected: All 9 tests pass

**Step 2: Run Pint for code formatting**

```bash
vendor/bin/pint --dirty --format agent
```

Expected: No formatting issues

**Step 3: Commit**

```bash
git add -A
git commit -m "feat: add Livewire TenderDetail component with real-time updates"
```

---

## Summary

This implementation creates:

1. **Three new factories** for testing related models
2. **DocumentAnalysisService** - Service class for analyzing documents
3. **TechnicalMemoryGenerationService** - Service class for generating technical memory
4. **TenderDetail Livewire component** - Reactive component with auto-refresh
5. **Comprehensive test suite** - 9 feature tests covering all functionality
6. **Updated show view** - Simplified to use the Livewire component

The component features:
- Real-time status updates via `wire:poll.10s`
- Loading states for actions
- Status badges and alerts
- Document listing with download/view links
- Extracted criteria/specifications display
- Technical memory generation workflow
