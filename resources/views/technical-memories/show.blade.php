@extends('layouts.app')

@section('title', 'Memoria Técnica - ' . $tender->title)

@section('content')
    <livewire:technical-memories.show-memory :tender="$tender" />
@endsection
