<?php

namespace App\Livewire\Tenders;

use App\Enums\TechnicalMemorySectionStatus;
use App\Jobs\GenerateTechnicalMemory;
use App\Models\Tender;
use App\Services\DocumentAnalysisService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TenderDetail extends Component
{
    public Tender $tender;

    protected DocumentAnalysisService $documentAnalysisService;

    public bool $isAnalyzing = false;

    public bool $isGeneratingMemory = false;

    public ?string $errorMessage = null;

    public function mount(Tender $tender): void
    {
        $this->tender = $tender;

        $this->loadTenderRelations();
    }

    public function boot(
        DocumentAnalysisService $documentAnalysisService,
    ): void {
        $this->documentAnalysisService = $documentAnalysisService;
    }

    #[Computed]
    public function statusConfig(): array
    {
        return match ($this->tender->status) {
            'pending' => ['label' => 'Pendiente', 'variant' => 'secondary'],
            'analyzing' => ['label' => 'Analizando', 'variant' => 'info'],
            'completed' => ['label' => 'Completado', 'variant' => 'success'],
            'failed' => ['label' => 'Error', 'variant' => 'error'],
            default => ['label' => ucfirst($this->tender->status), 'variant' => 'default'],
        };
    }

    #[Computed]
    public function statusLabel(): string
    {
        return $this->statusConfig['label'];
    }

    #[Computed]
    public function statusVariant(): string
    {
        return $this->statusConfig['variant'];
    }

    #[Computed]
    public function processingDocuments(): Collection
    {
        return $this->tender->documents->where('status', 'processing');
    }

    #[Computed]
    public function failedDocuments(): Collection
    {
        return $this->tender->documents->where('status', 'failed');
    }

    #[Computed]
    public function hasAnalyzableDocuments(): bool
    {
        return $this->tender->documents
            ->whereIn('status', ['uploaded', 'failed'])
            ->isNotEmpty();
    }

    /**
     * @return array{
     *   has_sections:bool,
     *   pending_count:int,
     *   generating_count:int,
     *   completed_count:int,
     *   failed_count:int,
     *   total_count:int,
     *   generating_titles:array<int,string>,
     *   pending_titles:array<int,string>
     * }
     */
    #[Computed]
    public function memoryProgress(): array
    {
        $memory = $this->tender->technicalMemory;

        if (! $memory) {
            return [
                'has_sections' => false,
                'pending_count' => 0,
                'generating_count' => 0,
                'completed_count' => 0,
                'failed_count' => 0,
                'total_count' => 0,
                'generating_titles' => [],
                'pending_titles' => [],
            ];
        }

        $sections = $memory->sections;
        $pendingSections = $sections->where('status', TechnicalMemorySectionStatus::Pending);
        $generatingSections = $sections->where('status', TechnicalMemorySectionStatus::Generating);
        $completedSections = $sections->where('status', TechnicalMemorySectionStatus::Completed);
        $failedSections = $sections->where('status', TechnicalMemorySectionStatus::Failed);

        return [
            'has_sections' => $sections->isNotEmpty(),
            'pending_count' => $pendingSections->count(),
            'generating_count' => $generatingSections->count(),
            'completed_count' => $completedSections->count(),
            'failed_count' => $failedSections->count(),
            'total_count' => $sections->count(),
            'generating_titles' => $generatingSections
                ->pluck('section_title')
                ->map(fn ($title): string => trim((string) $title))
                ->filter()
                ->values()
                ->take(3)
                ->all(),
            'pending_titles' => $pendingSections
                ->pluck('section_title')
                ->map(fn ($title): string => trim((string) $title))
                ->filter()
                ->values()
                ->take(3)
                ->all(),
        ];
    }

    public function analyzeDocuments(): void
    {
        $this->isAnalyzing = true;
        $this->errorMessage = null;

        try {
            $this->documentAnalysisService->analyzeTender($this->tender);

            $this->loadTenderRelations();

            $this->dispatch('analysis-completed');
        } catch (\Throwable $e) {
            $this->errorMessage = 'Error al analizar los documentos: '.$e->getMessage();
        } finally {
            $this->isAnalyzing = false;
        }
    }

    public function retryDocument(int $documentId): void
    {
        $this->isAnalyzing = true;
        $this->errorMessage = null;

        try {
            $document = $this->tender->documents->firstWhere('id', $documentId);

            if ($document === null) {
                $this->errorMessage = 'Documento no encontrado para reintento.';

                return;
            }

            $this->documentAnalysisService->analyzeDocument($this->tender, $document);

            $this->loadTenderRelations();

            $this->dispatch('analysis-completed');
        } catch (\Throwable $e) {
            $this->errorMessage = 'Error al reintentar el análisis: '.$e->getMessage();
        } finally {
            $this->isAnalyzing = false;
        }
    }

    public function generateMemory(): void
    {
        $this->isGeneratingMemory = true;
        $this->errorMessage = null;

        try {
            $this->tender->technicalMemory()->updateOrCreate(
                ['tender_id' => $this->tender->id],
                [
                    'title' => 'Generando memoria técnica...',
                    'status' => 'draft',
                    'generated_file_path' => null,
                    'generated_at' => null,
                ]
            );

            GenerateTechnicalMemory::dispatch($this->tender);

            $this->loadTenderRelations();

            $this->dispatch('memory-generated');
        } catch (\Throwable $e) {
            $this->errorMessage = 'Error al generar la memoria técnica: '.$e->getMessage();
        } finally {
            $this->isGeneratingMemory = false;
        }
    }

    private function loadTenderRelations(): void
    {
        $this->tender->refresh();
        $this->tender->load([
            'documents',
            'extractedCriteria',
            'extractedSpecifications',
            'technicalMemory.sections',
        ]);
    }

    public function render(): View
    {
        return view('livewire.tenders.tender-detail');
    }
}
