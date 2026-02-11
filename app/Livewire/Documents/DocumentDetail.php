<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DocumentDetail extends Component
{
    public Document $document;

    public function mount(Document $document): void
    {
        $this->document = $document->load(['tender', 'extractedCriteria', 'extractedSpecifications']);
    }

    #[Computed]
    public function documentTypeLabel(): string
    {
        return match ($this->document->document_type) {
            'pca' => 'Pliego de Condiciones Administrativas',
            'ppt' => 'Pliego de Prescripciones Técnicas',
            default => strtoupper($this->document->document_type),
        };
    }

    #[Computed]
    public function statusConfig(): array
    {
        return match ($this->document->status) {
            'uploaded' => ['label' => 'Subido', 'variant' => 'secondary'],
            'processing' => ['label' => 'Procesando', 'variant' => 'info'],
            'analyzed' => ['label' => 'Analizado', 'variant' => 'success'],
            'failed' => ['label' => 'Error', 'variant' => 'error'],
            default => ['label' => ucfirst($this->document->status), 'variant' => 'default'],
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

    public function render(): View
    {
        return view('livewire.documents.document-detail');
    }
}
