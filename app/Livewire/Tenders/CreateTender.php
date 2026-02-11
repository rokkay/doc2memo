<?php

declare(strict_types=1);

namespace App\Livewire\Tenders;

use App\Actions\Tenders\AnalyzeTenderDocumentsAction;
use App\Livewire\Forms\TenderForm;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use League\Flysystem\UnableToRetrieveMetadata;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class CreateTender extends Component
{
    use WithFileUploads;

    public TenderForm $form;

    public ?TemporaryUploadedFile $pcaFile = null;

    public ?TemporaryUploadedFile $pptFile = null;

    public bool $isSubmitting = false;

    protected AnalyzeTenderDocumentsAction $analyzeTenderDocumentsAction;

    public function boot(AnalyzeTenderDocumentsAction $analyzeTenderDocumentsAction): void
    {
        $this->analyzeTenderDocumentsAction = $analyzeTenderDocumentsAction;
    }

    protected function rules(): array
    {
        return [
            'pcaFile' => ['required', 'file', 'mimes:pdf,md,txt', 'max:10240'],
            'pptFile' => ['required', 'file', 'mimes:pdf,md,txt', 'max:10240'],
        ];
    }

    protected function messages(): array
    {
        return [
            'pcaFile.required' => 'El archivo PCA es obligatorio.',
            'pcaFile.file' => 'El archivo PCA debe ser un archivo válido.',
            'pcaFile.mimes' => 'El archivo PCA debe ser PDF, Markdown o TXT.',
            'pcaFile.max' => 'El archivo PCA no debe superar los 10MB.',
            'pptFile.required' => 'El archivo PPT es obligatorio.',
            'pptFile.file' => 'El archivo PPT debe ser un archivo válido.',
            'pptFile.mimes' => 'El archivo PPT debe ser PDF, Markdown o TXT.',
            'pptFile.max' => 'El archivo PPT no debe superar los 10MB.',
        ];
    }

    public function save(): void
    {
        $this->isSubmitting = true;

        if ($this->pcaFile && ! $this->pcaFile->exists()) {
            $this->pcaFile = null;
            $this->addError('pcaFile', 'El archivo PCA temporal ha expirado. Vuelve a subirlo antes de guardar.');
            $this->isSubmitting = false;

            return;
        }

        if ($this->pptFile && ! $this->pptFile->exists()) {
            $this->pptFile = null;
            $this->addError('pptFile', 'El archivo PPT temporal ha expirado. Vuelve a subirlo antes de guardar.');
            $this->isSubmitting = false;

            return;
        }

        try {
            $this->validate();
        } catch (UnableToRetrieveMetadata) {
            if ($this->pcaFile && ! $this->pcaFile->exists()) {
                $this->pcaFile = null;
                $this->addError('pcaFile', 'El archivo PCA temporal ha expirado. Vuelve a subirlo antes de guardar.');
            }

            if ($this->pptFile && ! $this->pptFile->exists()) {
                $this->pptFile = null;
                $this->addError('pptFile', 'El archivo PPT temporal ha expirado. Vuelve a subirlo antes de guardar.');
            }

            $this->isSubmitting = false;

            return;
        }

        try {
            DB::transaction(function (): void {
                $tender = Tender::create([
                    'title' => $this->form->title,
                    'issuing_company' => $this->form->issuing_company,
                    'reference_number' => $this->form->reference_number,
                    'deadline_date' => $this->form->deadline_date,
                    'description' => $this->form->description,
                    'status' => 'pending',
                ]);

                $this->storeDocument($tender, $this->pcaFile, 'pca');
                $this->storeDocument($tender, $this->pptFile, 'ppt');

                ($this->analyzeTenderDocumentsAction)($tender);
            });

            session()->flash('success', 'Licitación creada correctamente.');
            $this->redirect(route('tenders.index'));
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al crear la licitación: '.$e->getMessage(),
            ]);
            $this->isSubmitting = false;
        }
    }

    private function storeDocument(Tender $tender, TemporaryUploadedFile $file, string $documentType): void
    {
        $originalFilename = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        $storedFilename = uniqid().'_'.$originalFilename;
        $path = $file->storeAs("documents/{$tender->id}", $storedFilename, 'local');

        Document::create([
            'tender_id' => $tender->id,
            'document_type' => $documentType,
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'status' => 'uploaded',
        ]);
    }

    public function removePcaFile(): void
    {
        $this->pcaFile = null;
        $this->resetValidation('pcaFile');
    }

    public function removePptFile(): void
    {
        $this->pptFile = null;
        $this->resetValidation('pptFile');
    }

    public function render(): View
    {
        return view('livewire.tenders.create-tender');
    }
}
