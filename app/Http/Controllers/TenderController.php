<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateTechnicalMemory;
use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenderController extends Controller
{
    public function index(): View
    {
        return view('tenders.index');
    }

    public function create(): View
    {
        return view('tenders.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'issuing_company' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'deadline_date' => 'nullable|date',
            'reference_number' => 'nullable|string|max:100',
            'pca_file' => 'required|file|mimes:pdf,md,txt|max:10240',
            'ppt_file' => 'required|file|mimes:pdf,md,txt|max:10240',
        ], [
            'title.required' => 'El título de la licitación es obligatorio.',
            'pca_file.required' => 'El archivo PCA (Pliego de Condiciones Administrativas) es obligatorio.',
            'pca_file.mimes' => 'El archivo PCA debe ser PDF, Markdown o TXT.',
            'pca_file.max' => 'El archivo PCA no puede superar los 10MB.',
            'ppt_file.required' => 'El archivo PPT (Pliego de Prescripciones Técnicas) es obligatorio.',
            'ppt_file.mimes' => 'El archivo PPT debe ser PDF, Markdown o TXT.',
            'ppt_file.max' => 'El archivo PPT no puede superar los 10MB.',
        ]);

        try {
            $tender = Tender::query()->create([
                'title' => $validated['title'],
                'issuing_company' => $validated['issuing_company'],
                'description' => $validated['description'],
                'deadline_date' => $validated['deadline_date'],
                'reference_number' => $validated['reference_number'],
                'status' => 'pending',
            ]);

            $pcaDocument = $this->storeDocument($tender, $request->file('pca_file'), 'pca');
            $pptDocument = $this->storeDocument($tender, $request->file('ppt_file'), 'ppt');

            // Start analysis immediately after upload
            foreach ($tender->documents as $document) {
                ProcessDocument::dispatch($document);
            }

            $tender->update(['status' => 'analyzing']);

            return redirect()
                ->route('tenders.show', $tender)
                ->with('success', "¡Licitación '{$tender->title}' creada exitosamente! Los documentos están siendo analizados por la IA. Esto puede tardar unos minutos.");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al crear la licitación: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show(Tender $tender): View
    {
        return view('tenders.show', compact('tender'));
    }

    public function analyze(Tender $tender): RedirectResponse
    {
        $pendingDocuments = $tender->documents->where('status', 'uploaded');

        if ($pendingDocuments->isEmpty()) {
            return redirect()
                ->back()
                ->with('info', 'No hay documentos pendientes de análisis. Todos los documentos ya han sido procesados.');
        }

        $documentCount = 0;
        foreach ($pendingDocuments as $document) {
            ProcessDocument::dispatch($document);
            $document->update(['status' => 'processing']);
            $documentCount++;
        }

        $tender->update(['status' => 'analyzing']);

        $docNames = $pendingDocuments->pluck('original_filename')->implode(', ');

        return redirect()
            ->back()
            ->with('success', "Análisis iniciado para {$documentCount} documento(s): {$docNames}. La IA está extrayendo información. Puedes seguir el progreso en esta página.");
    }

    public function generateMemory(Tender $tender): RedirectResponse
    {
        if ($tender->extractedCriteria->isEmpty() || $tender->extractedSpecifications->isEmpty()) {
            $missing = [];
            if ($tender->extractedCriteria->isEmpty()) {
                $missing[] = 'criterios del PCA';
            }
            if ($tender->extractedSpecifications->isEmpty()) {
                $missing[] = 'especificaciones del PPT';
            }

            return redirect()
                ->back()
                ->with('error', 'No se puede generar la memoria técnica. Faltan: '.implode(' y ', $missing).'. Por favor, analiza ambos documentos primero.');
        }

        if ($tender->technicalMemory) {
            return redirect()
                ->back()
                ->with('info', 'Ya existe una memoria técnica generada para esta licitación. Puedes verla en la pestaña "Memoria Técnica".');
        }

        GenerateTechnicalMemory::dispatch($tender);

        return redirect()
            ->back()
            ->with('success', '¡Generación de la Memoria Técnica iniciada! La IA está creando el documento basándose en '.$tender->extractedCriteria->count().' criterios del PCA y '.$tender->extractedSpecifications->count().' especificaciones del PPT. Esto puede tardar unos minutos.');
    }

    private function storeDocument(Tender $tender, $file, string $documentType): Document
    {
        $originalFilename = $file->getClientOriginalName();
        $storedFilename = uniqid().'_'.$originalFilename;
        $filePath = $file->storeAs('documents', $storedFilename, 'local');

        return Document::query()->create([
            'tender_id' => $tender->id,
            'document_type' => $documentType,
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'uploaded',
        ]);
    }
}
