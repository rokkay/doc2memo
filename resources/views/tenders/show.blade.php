@extends('layouts.app')

@section('title', $tender->title . ' - Doc2Memo')

@section('content')
    <livewire:tenders.tender-detail :tender="$tender" />
@endsection
