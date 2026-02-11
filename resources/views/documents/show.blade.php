@extends('layouts.app')

@section('title', $document->original_filename . ' - Doc2Memo')

@section('content')
    <livewire:documents.document-detail :document="$document" />
@endsection
