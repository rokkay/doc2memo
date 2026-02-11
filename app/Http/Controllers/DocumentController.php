<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function download(Document $document): StreamedResponse
    {
        return Storage::download($document->file_path, $document->original_filename);
    }

    public function show(Document $document)
    {
        return view('documents.show', compact('document'));
    }
}
