# Doc2Memo Livewire Views Rebuild Implementation Plan

> **REQUIRED SUB-SKILL:** Use executing-plans to implement this plan task-by-task.

**Goal:** Rebuild all Doc2Memo application views using Laravel Livewire v4 with TDD, improving the design and developer experience.

**Architecture:** Convert traditional Blade views with page reloads to reactive Livewire components. Use single-file Livewire components (SFC) for simpler pages and multi-file components (MFC) for complex pages with JavaScript. Follow Laravel 12 conventions and Tailwind CSS v4.

**Tech Stack:** Laravel 12, Livewire 4, Tailwind CSS v4, Pest PHP 4, Alpine.js (bundled with Livewire)

---

## Overview

The Doc2Memo application manages tenders (licitaciones) with the following features:
1. **Tender List** (`/tenders`) - Paginated list of tenders with status badges
2. **Create Tender** (`/tenders/create`) - Form with PCA and PPT file uploads
3. **Tender Detail** (`/tenders/{id}`) - Shows tender info, documents, criteria, specs, actions
4. **Document Detail** (`/documents/{id}`) - Shows document info and extracted content
5. **Technical Memory** (`/tenders/{id}/technical-memory`) - Shows generated memory sections

### Models
- `Tender` - Main entity with status, documents
- `Document` - PCA/PPT files with extracted text
- `ExtractedCriterion` - Criteria from PCA analysis
- `ExtractedSpecification` - Specifications from PPT analysis  
- `TechnicalMemory` - Generated memory with sections

### Current Routes to Replace
- `GET /tenders` → `TenderController@index`
- `GET /tenders/create` → `TenderController@create`
- `POST /tenders` → `TenderController@store`
- `GET /tenders/{tender}` → `TenderController@show`
- `POST /tenders/{tender}/analyze` → `TenderController@analyze`
- `POST /tenders/{tender}/generate-memory` → `TenderController@generateMemory`
- `GET /documents/{document}` → `DocumentController@show`
- `GET /tenders/{tender}/technical-memory` → `TechnicalMemoryController@show`

---

## Task 1: Setup - Create Base Layout and Utilities

**Purpose:** Create shared layout components and utility Livewire components before building page components.

**Files:**
- Create: `resources/views/components/ui/alert.blade.php`
- Create: `resources/views/components/ui/badge.blade.php`
- Create: `resources/views/components/ui/button.blade.php`
- Create: `app/Livewire/Forms/TenderForm.php`
- Modify: `resources/views/layouts/app.blade.php`

**Step 1: Create UI Alert Component**

Create `resources/views/components/ui/alert.blade.php`:
```blade
@props(['type' => 'info', 'message'])

@php
$classes = match($type) {
    'success' => 'bg-green-50 border-green-500 text-green-800',
    'error' => 'bg-red-50 border-red-500 text-red-800',
    'warning' => 'bg-yellow-50 border-yellow-500 text-yellow-800',
    'info' => 'bg-blue-50 border-blue-500 text-blue-800',
    default => 'bg-gray-50 border-gray-500 text-gray-800',
};

$icons = match($type) {
    'success' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
    'error' => '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>',
    'warning' => '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>',
    default => '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zm-1 8a1 1 0 100 2h.01a1 1 0 100-2H10z" clip-rule="evenodd"/>',
};
@endphp

<div {{ $attributes->merge(['class' => 'mb-4 border-l-4 px-4 py-4 rounded shadow-sm flex items-start ' . $classes]) }}>
    <svg class="w-5 h-5 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
        {!! $icons !!}
    </svg>
    <div>
        <p class="font-medium">{{ $slot }}</p>
        @if(isset($message))
            <p class="text-sm mt-1">{{ $message }}</p>
        @endif
    </div>
</div>
```

**Step 2: Create UI Badge Component**

Create `resources/views/components/ui/badge.blade.php`:
```blade
@props(['variant' => 'default'])

@php
$classes = match($variant) {
    'success' => 'bg-green-100 text-green-800',
    'error' => 'bg-red-100 text-red-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'info' => 'bg-blue-100 text-blue-800',
    'primary' => 'bg-indigo-100 text-indigo-800',
    'secondary' => 'bg-gray-100 text-gray-800',
    default => 'bg-gray-100 text-gray-800',
};
@endphp

<span {{ $attributes->merge(['class' => 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $classes]) }}>
    {{ $slot }}
</span>
```

**Step 3: Create UI Button Component**

Create `resources/views/components/ui/button.blade.php`:
```blade
@props(['variant' => 'primary', 'type' => 'button', 'disabled' => false])

@php
$baseClasses = 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors';

$variantClasses = match($variant) {
    'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
    'secondary' => 'bg-gray-200 text-gray-700 hover:bg-gray-300 focus:ring-gray-500',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'ghost' => 'bg-transparent text-gray-600 hover:bg-gray-100 focus:ring-gray-500',
    default => 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500',
};

$disabledClasses = $disabled ? ' opacity-50 cursor-not-allowed' : '';
@endphp

<button 
    type="{{ $type }}"
    {{ $disabled ? 'disabled' : '' }}
    {{ $attributes->merge(['class' => $baseClasses . ' ' . $variantClasses . $disabledClasses]) }}
>
    {{ $slot }}
</button>
```

**Step 4: Create TenderForm Livewire Form Object**

Create `app/Livewire/Forms/TenderForm.php`:
```php
<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use Livewire\Attributes\Validate;

class TenderForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:255')]
    public ?string $issuing_company = null;

    #[Validate('nullable|string|max:255')]
    public ?string $reference_number = null;

    #[Validate('nullable|date')]
    public ?string $deadline_date = null;

    #[Validate('nullable|string')]
    public ?string $description = null;
}
```

**Step 5: Update Base Layout**

Modify `resources/views/layouts/app.blade.php`:
- Replace CDN Tailwind with Vite
- Add Livewire styles and scripts
- Keep flash message structure but use new components

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Doc2Memo - Generador de Memorias Técnicas')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('tenders.index') }}" class="text-xl font-bold text-indigo-600">
                        Doc2Memo
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('tenders.index') }}" class="text-gray-600 hover:text-gray-900">
                        Licitaciones
                    </a>
                    <a href="{{ route('tenders.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        Nueva Licitación
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        @if(session('success'))
            <x-ui.alert type="success">{{ session('success') }}</x-ui.alert>
        @endif

        @if(session('error'))
            <x-ui.alert type="error">{{ session('error') }}</x-ui.alert>
        @endif

        @if(session('warning'))
            <x-ui.alert type="warning">{{ session('warning') }}</x-ui.alert>
        @endif

        @if(session('info'))
            <x-ui.alert type="info">{{ session('info') }}</x-ui.alert>
        @endif

        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                Doc2Memo - Generador de Memorias Técnicas con IA
            </p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
```

**Step 6: Run Tests**

```bash
php artisan test --compact
```

Expected: Tests pass (no new tests yet, just ensuring nothing is broken)

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: add base UI components and form objects for Livewire rebuild"
```

---

## Task 2: Tender List Component (Index Page)

**Purpose:** Replace the tenders index page with a reactive Livewire component featuring search, filtering, and pagination.

**Files:**
- Create: `app/Livewire/Tenders/TenderList.php`
- Create: `resources/views/livewire/tenders/tender-list.blade.php`
- Create: `tests/Feature/Livewire/Tenders/TenderListTest.php`
- Modify: `resources/views/tenders/index.blade.php` (simplify to use component)

**Step 1: Write Failing Test**

Create `tests/Feature/Livewire/Tenders/TenderListTest.php`:
```php
<?php

use App\Livewire\Tenders\TenderList;
use App\Models\Tender;
use Livewire\Livewire;

beforeEach(function () {
    // Assuming authentication is needed
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
});

it('renders successfully', function () {
    Livewire::test(TenderList::class)
        ->assertSuccessful();
});

it('displays tenders list', function () {
    $tender = Tender::factory()->create(['title' => 'Test Tender']);

    Livewire::test(TenderList::class)
        ->assertSee('Test Tender');
});

it('can search tenders by title', function () {
    Tender::factory()->create(['title' => 'Unique Search Term']);
    Tender::factory()->create(['title' => 'Other Tender']);

    Livewire::test(TenderList::class)
        ->set('search', 'Unique')
        ->assertSee('Unique Search Term')
        ->assertDontSee('Other Tender');
});

it('can filter tenders by status', function () {
    Tender::factory()->create(['title' => 'Pending Tender', 'status' => 'pending']);
    Tender::factory()->create(['title' => 'Completed Tender', 'status' => 'completed']);

    Livewire::test(TenderList::class)
        ->set('statusFilter', 'pending')
        ->assertSee('Pending Tender')
        ->assertDontSee('Completed Tender');
});

it('displays empty state when no tenders', function () {
    Livewire::test(TenderList::class)
        ->assertSee('No hay licitaciones registradas');
});

it('has link to create new tender', function () {
    Livewire::test(TenderList::class)
        ->assertSee('Nueva Licitación');
});
```

**Step 2: Run Test to Verify It Fails**

```bash
php artisan test --compact --filter=TenderListTest
```

Expected: FAIL - Component class not found

**Step 3: Create Livewire Component**

Create `app/Livewire/Tenders/TenderList.php`:
```php
<?php

namespace App\Livewire\Tenders;

use App\Models\Tender;
use Livewire\Component;
use Livewire\WithPagination;

class TenderList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tenders = Tender::query()
            ->withCount('documents')
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('issuing_company', 'like', '%' . $this->search . '%')
                    ->orWhere('reference_number', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.tenders.tender-list', [
            'tenders' => $tenders,
        ]);
    }
}
```

**Step 4: Create Blade View**

Create `resources/views/livewire/tenders/tender-list.blade.php`:
```blade
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold text-gray-900">Licitaciones</h1>
            <a href="{{ route('tenders.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Nueva Licitación
            </a>
        </div>

        {{-- Filters --}}
        <div class="mb-6 flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input 
                    wire:model.live.debounce.300ms="search" 
                    type="text" 
                    placeholder="Buscar licitaciones..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>
            <div class="sm:w-48">
                <select 
                    wire:model.live="statusFilter"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Todos los estados</option>
                    <option value="pending">Pendiente</option>
                    <option value="analyzing">Analizando</option>
                    <option value="completed">Completado</option>
                    <option value="failed">Error</option>
                </select>
            </div>
        </div>

        @if($tenders->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay licitaciones</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza creando una nueva licitación.</p>
                <div class="mt-6">
                    <a href="{{ route('tenders.create') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                        Crear primera licitación →
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documentos</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($tenders as $tender)
                            <tr wire:key="tender-{{ $tender->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $tender->title }}</div>
                                    @if($tender->reference_number)
                                        <div class="text-sm text-gray-500">Ref: {{ $tender->reference_number }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $tender->issuing_company ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusConfig = match($tender->status) {
                                            'pending' => ['label' => 'Pendiente', 'variant' => 'secondary'],
                                            'analyzing' => ['label' => 'Analizando', 'variant' => 'info'],
                                            'completed' => ['label' => 'Completado', 'variant' => 'success'],
                                            'failed' => ['label' => 'Error', 'variant' => 'error'],
                                            default => ['label' => ucfirst($tender->status), 'variant' => 'default'],
                                        };
                                    @endphp
                                    <x-ui.badge :variant="$statusConfig['variant']">
                                        {{ $statusConfig['label'] }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $tender->documents_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('tenders.show', $tender) }}" class="text-indigo-600 hover:text-indigo-900">
                                        Ver detalles
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $tenders->links() }}
            </div>
        @endif
    </div>
</div>
```

**Step 5: Update Index View**

Replace `resources/views/tenders/index.blade.php`:
```blade
@extends('layouts.app')

@section('title', 'Licitaciones - Doc2Memo')

@section('content')
    <livewire:tenders.tender-list />
@endsection
```

**Step 6: Run Tests**

```bash
php artisan test --compact --filter=TenderListTest
```

Expected: All tests pass

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: add Livewire TenderList component with search and filter"
```

---

## Task 3: Create Tender Component with File Upload

**Purpose:** Replace the tender create form with a Livewire component supporting real-time validation and drag-and-drop file uploads.

**Files:**
- Create: `app/Livewire/Tenders/CreateTender.php`
- Create: `resources/views/livewire/tenders/create-tender.blade.php`
- Create: `tests/Feature/Livewire/Tenders/CreateTenderTest.php`
- Modify: `resources/views/tenders/create.blade.php`
- Modify: `routes/web.php`

**Step 1: Write Failing Test**

Create `tests/Feature/Livewire/Tenders/CreateTenderTest.php`:
```php
<?php

use App\Livewire\Tenders\CreateTender;
use App\Models\Tender;
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
    Storage::fake('local');
});

it('renders successfully', function () {
    Livewire::test(CreateTender::class)
        ->assertSuccessful();
});

it('validates required title', function () {
    Livewire::test(CreateTender::class)
        ->set('form.title', '')
        ->set('pcaFile', UploadedFile::fake()->create('test.pdf', 100))
        ->set('pptFile', UploadedFile::fake()->create('test2.pdf', 100))
        ->call('save')
        ->assertHasErrors(['form.title' => 'required']);
});

it('validates pca file is required', function () {
    Livewire::test(CreateTender::class)
        ->set('form.title', 'Test Tender')
        ->set('pptFile', UploadedFile::fake()->create('test.pdf', 100))
        ->call('save')
        ->assertHasErrors(['pcaFile' => 'required']);
});

it('validates ppt file is required', function () {
    Livewire::test(CreateTender::class)
        ->set('form.title', 'Test Tender')
        ->set('pcaFile', UploadedFile::fake()->create('test.pdf', 100))
        ->call('save')
        ->assertHasErrors(['pptFile' => 'required']);
});

it('validates file types', function () {
    Livewire::test(CreateTender::class)
        ->set('form.title', 'Test Tender')
        ->set('pcaFile', UploadedFile::fake()->create('test.exe', 100))
        ->set('pptFile', UploadedFile::fake()->create('test2.exe', 100))
        ->call('save')
        ->assertHasErrors(['pcaFile', 'pptFile']);
});

it('creates tender with documents', function () {
    $pcaFile = UploadedFile::fake()->create('pca.pdf', 100, 'application/pdf');
    $pptFile = UploadedFile::fake()->create('ppt.pdf', 100, 'application/pdf');

    Livewire::test(CreateTender::class)
        ->set('form.title', 'New Test Tender')
        ->set('form.issuing_company', 'Test Company')
        ->set('form.reference_number', 'REF-123')
        ->set('form.description', 'Test description')
        ->set('pcaFile', $pcaFile)
        ->set('pptFile', $pptFile)
        ->call('save')
        ->assertRedirect();

    $this->assertDatabaseHas('tenders', [
        'title' => 'New Test Tender',
        'issuing_company' => 'Test Company',
        'reference_number' => 'REF-123',
    ]);

    $tender = Tender::where('title', 'New Test Tender')->first();
    expect($tender->documents)->toHaveCount(2);
});

it('shows validation errors in real-time', function () {
    Livewire::test(CreateTender::class)
        ->set('form.title', 'ab')  // Too short won't trigger, but empty will
        ->assertHasNoErrors()
        ->set('form.title', '')
        ->assertHasErrors(['form.title']);
});
```

**Step 2: Run Test to Verify It Fails**

```bash
php artisan test --compact --filter=CreateTenderTest
```

Expected: FAIL - Component class not found

**Step 3: Create Livewire Component**

Create `app/Livewire/Tenders/CreateTender.php`:
```php
<?php

namespace App\Livewire\Tenders;

use App\Livewire\Forms\TenderForm;
use App\Models\Document;
use App\Models\Tender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateTender extends Component
{
    use WithFileUploads;

    public TenderForm $form;

    public $pcaFile = null;
    public $pptFile = null;

    public bool $isSubmitting = false;

    protected function rules(): array
    {
        return [
            'pcaFile' => 'required|file|mimes:pdf,md,txt|max:10240',
            'pptFile' => 'required|file|mimes:pdf,md,txt|max:10240',
        ];
    }

    protected function messages(): array
    {
        return [
            'pcaFile.required' => 'El archivo PCA es obligatorio.',
            'pcaFile.mimes' => 'El archivo PCA debe ser PDF, Markdown o TXT.',
            'pcaFile.max' => 'El archivo PCA no puede superar los 10MB.',
            'pptFile.required' => 'El archivo PPT es obligatorio.',
            'pptFile.mimes' => 'El archivo PPT debe ser PDF, Markdown o TXT.',
            'pptFile.max' => 'El archivo PPT no puede superar los 10MB.',
        ];
    }

    public function save(): void
    {
        $this->isSubmitting = true;

        $this->form->validate();
        $this->validate();

        try {
            DB::transaction(function () {
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

                return $tender;
            });

            session()->flash('success', 'Licitación creada correctamente. Los documentos se analizarán automáticamente.');
            $this->redirect(route('tenders.index'));

        } catch (\Exception $e) {
            $this->isSubmitting = false;
            session()->flash('error', 'Error al crear la licitación: ' . $e->getMessage());
        }
    }

    private function storeDocument(Tender $tender, $file, string $type): void
    {
        $originalName = $file->getClientOriginalName();
        $storedName = uniqid() . '_' . $originalName;
        $path = $file->storeAs('documents/' . $tender->id, $storedName, 'local');

        Document::create([
            'tender_id' => $tender->id,
            'document_type' => $type,
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'uploaded',
        ]);
    }

    public function removePcaFile(): void
    {
        $this->pcaFile = null;
    }

    public function removePptFile(): void
    {
        $this->pptFile = null;
    }

    public function render()
    {
        return view('livewire.tenders.create-tender');
    }
}
```

**Step 4: Create Blade View**

Create `resources/views/livewire/tenders/create-tender.blade.php`:
```blade
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Nueva Licitación</h1>
            <span class="ml-4 text-sm text-gray-500">Los campos marcados con * son obligatorios</span>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                {{-- Title --}}
                <div class="sm:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Título de la Licitación <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        wire:model.live="form.title"
                        id="title"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('form.title') border-red-500 @enderror"
                        placeholder="Ej: Licitación suministro de equipos informáticos 2024"
                    >
                    @error('form.title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Issuing Company --}}
                <div>
                    <label for="issuing_company" class="block text-sm font-medium text-gray-700">
                        Empresa Emisora
                    </label>
                    <input 
                        type="text" 
                        wire:model="form.issuing_company"
                        id="issuing_company"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Ej: Xunta de Galicia"
                    >
                </div>

                {{-- Reference Number --}}
                <div>
                    <label for="reference_number" class="block text-sm font-medium text-gray-700">
                        Número de Referencia
                    </label>
                    <input 
                        type="text" 
                        wire:model="form.reference_number"
                        id="reference_number"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Ej: EXP-2024-1234"
                    >
                </div>

                {{-- Deadline Date --}}
                <div>
                    <label for="deadline_date" class="block text-sm font-medium text-gray-700">
                        Fecha Límite de Presentación
                    </label>
                    <input 
                        type="date" 
                        wire:model="form.deadline_date"
                        id="deadline_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>

                {{-- Description --}}
                <div class="sm:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Descripción
                    </label>
                    <textarea 
                        wire:model="form.description"
                        id="description"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Descripción breve del objeto de la licitación..."
                    ></textarea>
                </div>
            </div>

            {{-- File Uploads --}}
            <div class="border-t border-gray-200 pt-6">
                <h2 class="text-lg font-medium text-gray-900 mb-2">Documentos</h2>
                <p class="text-sm text-gray-500 mb-4">Sube ambos documentos para comenzar el análisis automático con IA.</p>

                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    {{-- PCA File Upload --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            PCA (Pliego de Condiciones Administrativas) <span class="text-red-500">*</span>
                        </label>
                        <div 
                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-md transition-colors
                                {{ $pcaFile ? 'border-green-400 bg-green-50' : 'border-gray-300 hover:border-indigo-400' }}"
                        >
                            <div class="space-y-1 text-center">
                                @if($pcaFile)
                                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm text-green-700 font-medium">{{ $pcaFile->getClientOriginalName() }}</p>
                                    <button type="button" wire:click="removePcaFile" class="text-sm text-red-600 hover:text-red-800">
                                        Eliminar archivo
                                    </button>
                                @else
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="pca-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                            <span>Seleccionar archivo</span>
                                            <input id="pca-upload" type="file" wire:model="pcaFile" accept=".pdf,.md,.txt" class="sr-only">
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, Markdown o TXT hasta 10MB</p>
                                @endif
                            </div>
                        </div>
                        <div wire:loading wire:target="pcaFile" class="mt-2 text-sm text-indigo-600">
                            Subiendo archivo...
                        </div>
                        @error('pcaFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- PPT File Upload --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700">
                            PPT (Pliego de Prescripciones Técnicas) <span class="text-red-500">*</span>
                        </label>
                        <div 
                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-md transition-colors
                                {{ $pptFile ? 'border-green-400 bg-green-50' : 'border-gray-300 hover:border-indigo-400' }}"
                        >
                            <div class="space-y-1 text-center">
                                @if($pptFile)
                                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm text-green-700 font-medium">{{ $pptFile->getClientOriginalName() }}</p>
                                    <button type="button" wire:click="removePptFile" class="text-sm text-red-600 hover:text-red-800">
                                        Eliminar archivo
                                    </button>
                                @else
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="ppt-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                            <span>Seleccionar archivo</span>
                                            <input id="ppt-upload" type="file" wire:model="pptFile" accept=".pdf,.md,.txt" class="sr-only">
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, Markdown o TXT hasta 10MB</p>
                                @endif
                            </div>
                        </div>
                        <div wire:loading wire:target="pptFile" class="mt-2 text-sm text-indigo-600">
                            Subiendo archivo...
                        </div>
                        @error('pptFile')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <a href="{{ route('tenders.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition-colors">
                    Cancelar
                </a>
                <button 
                    type="submit" 
                    class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700 transition-colors flex items-center"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    @disabled($isSubmitting)
                >
                    <svg wire:loading.remove wire:target="save" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <svg wire:loading wire:target="save" class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="save">Crear Licitación</span>
                    <span wire:loading wire:target="save">Creando...</span>
                </button>
            </div>
        </form>
    </div>
</div>
```

**Step 5: Update Create View**

Replace `resources/views/tenders/create.blade.php`:
```blade
@extends('layouts.app')

@section('title', 'Nueva Licitación - Doc2Memo')

@section('content')
    <livewire:tenders.create-tender />
@endsection
```

**Step 6: Run Tests**

```bash
php artisan test --compact --filter=CreateTenderTest
```

Expected: All tests pass

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: add Livewire CreateTender component with file uploads"
```

---

## Task 4: Tender Detail Component (Show Page)

**Purpose:** Replace the tender show page with a reactive Livewire component that auto-refreshes during analysis and shows real-time status updates.

**Files:**
- Create: `app/Livewire/Tenders/TenderDetail.php`
- Create: `resources/views/livewire/tenders/tender-detail.blade.php`
- Create: `tests/Feature/Livewire/Tenders/TenderDetailTest.php`
- Modify: `resources/views/tenders/show.blade.php`

**Step 1: Write Failing Test**

Create `tests/Feature/Livewire/Tenders/TenderDetailTest.php`:
```php
<?php

use App\Livewire\Tenders\TenderDetail;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\TechnicalMemory;
use App\Models\Tender;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
});

it('renders successfully with tender', function () {
    $tender = Tender::factory()->create();

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSuccessful()
        ->assertSee($tender->title);
});

it('displays tender information', function () {
    $tender = Tender::factory()->create([
        'title' => 'Test Tender',
        'issuing_company' => 'Test Company',
        'reference_number' => 'REF-123',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Tender')
        ->assertSee('Test Company')
        ->assertSee('REF-123');
});

it('displays documents list', function () {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
        'original_filename' => 'test.pdf',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('test.pdf');
});

it('displays extracted criteria', function () {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create(['tender_id' => $tender->id, 'document_type' => 'pca']);
    ExtractedCriterion::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'section_title' => 'Test Criterion',
        'description' => 'Test description',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Criterion')
        ->assertSee('Test description');
});

it('displays extracted specifications', function () {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create(['tender_id' => $tender->id, 'document_type' => 'ppt']);
    ExtractedSpecification::factory()->create([
        'tender_id' => $tender->id,
        'document_id' => $document->id,
        'section_title' => 'Test Spec',
        'technical_description' => 'Technical description',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Spec')
        ->assertSee('Technical description');
});

it('displays technical memory when available', function () {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Test Memory',
        'introduction' => 'Test introduction',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Test Memory')
        ->assertSee('Test introduction');
});

it('shows analyze button when documents are uploaded', function () {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'uploaded',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Analizar Documentos');
});

it('can trigger document analysis', function () {
    $tender = Tender::factory()->create();
    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'uploaded',
    ]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->call('analyzeDocuments')
        ->assertDispatched('documents-analyzing');
});

it('can generate technical memory when analysis is complete', function () {
    $tender = Tender::factory()->create(['status' => 'completed']);
    Document::factory()->create([
        'tender_id' => $tender->id,
        'status' => 'analyzed',
    ]);
    ExtractedCriterion::factory()->create(['tender_id' => $tender->id]);

    Livewire::test(TenderDetail::class, ['tender' => $tender])
        ->assertSee('Generar Memoria Técnica');
});
```

**Step 2: Run Test to Verify It Fails**

```bash
php artisan test --compact --filter=TenderDetailTest
```

Expected: FAIL - Component class not found

**Step 3: Create Livewire Component**

Create `app/Livewire/Tenders/TenderDetail.php`:
```php
<?php

namespace App\Livewire\Tenders;

use App\Models\Tender;
use App\Services\DocumentAnalysisService;
use App\Services\TechnicalMemoryGenerationService;
use Livewire\Component;

class TenderDetail extends Component
{
    public Tender $tender;
    public bool $isAnalyzing = false;
    public bool $isGeneratingMemory = false;
    public ?string $errorMessage = null;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function mount(Tender $tender): void
    {
        $this->tender = $tender->load([
            'documents',
            'extractedCriteria',
            'extractedSpecifications',
            'technicalMemory',
        ]);
    }

    public function analyzeDocuments(): void
    {
        $this->isAnalyzing = true;
        $this->errorMessage = null;

        try {
            $service = app(DocumentAnalysisService::class);
            $service->analyzeTenderDocuments($this->tender);

            $this->tender->refresh();
            $this->dispatch('documents-analyzing');
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al analizar documentos: ' . $e->getMessage();
        }

        $this->isAnalyzing = false;
    }

    public function generateMemory(): void
    {
        $this->isGeneratingMemory = true;
        $this->errorMessage = null;

        try {
            $service = app(TechnicalMemoryGenerationService::class);
            $service->generateForTender($this->tender);

            $this->tender->refresh();
            $this->dispatch('memory-generated');
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al generar memoria: ' . $e->getMessage();
        }

        $this->isGeneratingMemory = false;
    }

    public function getStatusLabelProperty(): string
    {
        return match($this->tender->status) {
            'pending' => 'Pendiente',
            'analyzing' => 'Analizando',
            'completed' => 'Completado',
            'failed' => 'Error',
            default => ucfirst($this->tender->status),
        };
    }

    public function getStatusVariantProperty(): string
    {
        return match($this->tender->status) {
            'pending' => 'secondary',
            'analyzing' => 'info',
            'completed' => 'success',
            'failed' => 'error',
            default => 'default',
        };
    }

    public function render()
    {
        return view('livewire.tenders.tender-detail');
    }
}
```

**Step 4: Create Blade View**

Create `resources/views/livewire/tenders/tender-detail.blade.php`:
```blade
<div class="space-y-6" wire:poll.10s="$refresh">
    
    {{-- Status Alert Banner --}}
    @if($tender->status === 'analyzing')
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center">
            <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <div>
                <p class="font-medium text-blue-900">Análisis en progreso</p>
                <p class="text-sm text-blue-700">La IA está analizando los documentos. Esta página se actualiza automáticamente.</p>
            </div>
        </div>
    @elseif($tender->status === 'completed' && !$tender->technicalMemory)
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center">
            <svg class="h-5 w-5 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-medium text-green-900">¡Análisis completado!</p>
                <p class="text-sm text-green-700">Los documentos han sido analizados. Ahora puedes generar la Memoria Técnica.</p>
            </div>
        </div>
    @endif

    @if($errorMessage)
        <x-ui.alert type="error" :message="$errorMessage" />
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
            <button wire:click="analyzeDocuments" wire:loading.attr="disabled" class="mt-3 ml-8 text-sm bg-red-100 hover:bg-red-200 text-red-800 px-3 py-1 rounded">
                Reintentar análisis
            </button>
        </div>
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
                        <span class="text-sm text-gray-900 ml-1">{{ $tender->issuing_company }}</span>
                    </div>
                @endif
                @if($tender->deadline_date)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Fecha Límite:</span>
                        <span class="text-sm text-gray-900 ml-1">{{ $tender->deadline_date->format('d/m/Y') }}</span>
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
                                            @php
                                                $docStatusConfig = match($document->status) {
                                                    'uploaded' => ['label' => 'Subido', 'variant' => 'secondary'],
                                                    'processing' => ['label' => 'Procesando', 'variant' => 'info'],
                                                    'analyzed' => ['label' => 'Analizado', 'variant' => 'success'],
                                                    'failed' => ['label' => 'Error', 'variant' => 'error'],
                                                    default => ['label' => ucfirst($document->status), 'variant' => 'default'],
                                                };
                                            @endphp
                                            <x-ui.badge :variant="$docStatusConfig['variant']">
                                                {{ $docStatusConfig['label'] }}
                                            </x-ui.badge>
                                            @if($document->analyzed_at)
                                                <span class="text-xs text-gray-400">{{ $document->analyzed_at->diffForHumans() }}</span>
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

                    @if($tender->documents->whereIn('status', ['uploaded', 'failed'])->isNotEmpty())
                        <button 
                            wire:click="analyzeDocuments" 
                            wire:loading.attr="disabled"
                            class="mt-4 w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center justify-center"
                        >
                            <svg wire:loading.remove class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <svg wire:loading class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ $tender->documents->where('status', 'failed')->isNotEmpty() ? 'Reintentar Análisis' : 'Analizar Documentos' }}
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
                                <p class="text-sm text-gray-600 mb-3">¡Los documentos han sido analizados! Ahora puedes generar la Memoria Técnica.</p>
                                <button 
                                    wire:click="generateMemory"
                                    wire:loading.attr="disabled"
                                    class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center justify-center"
                                >
                                    <svg wire:loading.remove class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <svg wire:loading class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Generar Memoria Técnica
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
                            <button wire:click="analyzeDocuments" class="mt-4 text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                Iniciar análisis ahora →
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Extracted Criteria --}}
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
                                @php
                                    $priorityConfig = match($criterion->priority) {
                                        'mandatory' => ['label' => 'Obligatorio', 'variant' => 'error'],
                                        'preferable' => ['label' => 'Preferible', 'variant' => 'warning'],
                                        default => ['label' => ucfirst($criterion->priority), 'variant' => 'success'],
                                    };
                                @endphp
                                <x-ui.badge :variant="$priorityConfig['variant']">
                                    {{ $priorityConfig['label'] }}
                                </x-ui.badge>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">{{ $criterion->description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Extracted Specifications --}}
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

**Step 5: Update Show View**

Replace `resources/views/tenders/show.blade.php`:
```blade
@extends('layouts.app')

@section('title', $tender->title . ' - Doc2Memo')

@section('content')
    <livewire:tenders.tender-detail :tender="$tender" />
@endsection
```

**Step 6: Run Tests**

```bash
php artisan test --compact --filter=TenderDetailTest
```

Expected: All tests pass

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: add Livewire TenderDetail component with real-time updates"
```

---

## Task 5: Document Detail Component

**Purpose:** Replace the document show page with a Livewire component for viewing document details and extracted content.

**Files:**
- Create: `app/Livewire/Documents/DocumentDetail.php`
- Create: `resources/views/livewire/documents/document-detail.blade.php`
- Create: `tests/Feature/Livewire/Documents/DocumentDetailTest.php`
- Modify: `resources/views/documents/show.blade.php`

**Step 1: Write Failing Test**

Create `tests/Feature/Livewire/Documents/DocumentDetailTest.php`:
```php
<?php

use App\Livewire\Documents\DocumentDetail;
use App\Models\Document;
use App\Models\ExtractedCriterion;
use App\Models\ExtractedSpecification;
use App\Models\Tender;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
});

it('renders successfully with document', function () {
    $document = Document::factory()->create();

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSuccessful()
        ->assertSee($document->original_filename);
});

it('displays document information', function () {
    $document = Document::factory()->create([
        'original_filename' => 'test-document.pdf',
        'document_type' => 'pca',
        'file_size' => 1024,
        'status' => 'analyzed',
    ]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('test-document.pdf')
        ->assertSee('PCA')
        ->assertSee('1 KB')
        ->assertSee('Analizado');
});

it('displays extracted text when available', function () {
    $document = Document::factory()->create([
        'extracted_text' => 'This is the extracted text content.',
    ]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Texto Extraído')
        ->assertSee('This is the extracted text content.');
});

it('displays extracted criteria for PCA documents', function () {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'pca',
    ]);
    ExtractedCriterion::factory()->create([
        'document_id' => $document->id,
        'tender_id' => $tender->id,
        'section_title' => 'Test Criterion',
        'description' => 'Criterion description',
    ]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Criterios Extraídos')
        ->assertSee('Test Criterion')
        ->assertSee('Criterion description');
});

it('displays extracted specifications for PPT documents', function () {
    $tender = Tender::factory()->create();
    $document = Document::factory()->create([
        'tender_id' => $tender->id,
        'document_type' => 'ppt',
    ]);
    ExtractedSpecification::factory()->create([
        'document_id' => $document->id,
        'tender_id' => $tender->id,
        'section_title' => 'Test Specification',
        'technical_description' => 'Technical spec description',
    ]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Especificaciones Extraídas')
        ->assertSee('Test Specification')
        ->assertSee('Technical spec description');
});

it('has link back to tender', function () {
    $tender = Tender::factory()->create(['title' => 'Parent Tender']);
    $document = Document::factory()->create(['tender_id' => $tender->id]);

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Volver');
});

it('has download link', function () {
    $document = Document::factory()->create();

    Livewire::test(DocumentDetail::class, ['document' => $document])
        ->assertSee('Descargar');
});
```

**Step 2: Run Test to Verify It Fails**

```bash
php artisan test --compact --filter=DocumentDetailTest
```

Expected: FAIL - Component class not found

**Step 3: Create Livewire Component**

Create `app/Livewire/Documents/DocumentDetail.php`:
```php
<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Livewire\Component;

class DocumentDetail extends Component
{
    public Document $document;

    public function mount(Document $document): void
    {
        $this->document = $document->load([
            'tender',
            'extractedCriteria',
            'extractedSpecifications',
        ]);
    }

    public function getDocumentTypeLabelProperty(): string
    {
        return match($this->document->document_type) {
            'pca' => 'Pliego de Condiciones Administrativas',
            'ppt' => 'Pliego de Prescripciones Técnicas',
            default => strtoupper($this->document->document_type),
        };
    }

    public function getStatusLabelProperty(): string
    {
        return match($this->document->status) {
            'uploaded' => 'Subido',
            'processing' => 'Procesando',
            'analyzed' => 'Analizado',
            'failed' => 'Error',
            default => ucfirst($this->document->status),
        };
    }

    public function getStatusVariantProperty(): string
    {
        return match($this->document->status) {
            'uploaded' => 'secondary',
            'processing' => 'info',
            'analyzed' => 'success',
            'failed' => 'error',
            default => 'default',
        };
    }

    public function render()
    {
        return view('livewire.documents.document-detail');
    }
}
```

**Step 4: Create Blade View**

Create `resources/views/livewire/documents/document-detail.blade.php`:
```blade
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $document->original_filename }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $this->documentTypeLabel }}
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('tenders.show', $document->tender) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                    Volver
                </a>
                <a href="{{ route('documents.download', $document) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    Descargar
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-gray-50 rounded-lg p-4">
                <h2 class="text-lg font-medium text-gray-900 mb-3">Información del Documento</h2>
                <dl class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <dt class="text-sm font-medium text-gray-500">Tipo:</dt>
                        <dd class="text-sm text-gray-900 font-medium">{{ strtoupper($document->document_type) }}</dd>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <dt class="text-sm font-medium text-gray-500">Tamaño:</dt>
                        <dd class="text-sm text-gray-900">{{ number_format($document->file_size / 1024, 2) }} KB</dd>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-200">
                        <dt class="text-sm font-medium text-gray-500">Estado:</dt>
                        <dd>
                            <x-ui.badge :variant="$this->statusVariant">
                                {{ $this->statusLabel }}
                            </x-ui.badge>
                        </dd>
                    </div>
                    @if($document->analyzed_at)
                        <div class="flex justify-between py-2">
                            <dt class="text-sm font-medium text-gray-500">Analizado:</dt>
                            <dd class="text-sm text-gray-900">{{ $document->analyzed_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if($document->extracted_text)
                <div class="lg:col-span-2">
                    <h2 class="text-lg font-medium text-gray-900 mb-3">Texto Extraído</h2>
                    <div class="bg-gray-50 rounded-md p-4 max-h-96 overflow-y-auto border border-gray-200">
                        <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono">{{ $document->extracted_text }}</pre>
                    </div>
                </div>
            @endif
        </div>

        @if($document->document_type === 'pca' && $document->extractedCriteria->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Criterios Extraídos ({{ $document->extractedCriteria->count() }})
                </h2>
                <div class="space-y-3">
                    @foreach($document->extractedCriteria as $criterion)
                        <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start">
                                <h3 class="text-sm font-medium text-gray-900">
                                    @if($criterion->section_number)
                                        {{ $criterion->section_number }} -
                                    @endif
                                    {{ $criterion->section_title }}
                                </h3>
                                @php
                                    $priorityConfig = match($criterion->priority) {
                                        'mandatory' => ['label' => 'Obligatorio', 'variant' => 'error'],
                                        'preferable' => ['label' => 'Preferible', 'variant' => 'warning'],
                                        default => ['label' => ucfirst($criterion->priority), 'variant' => 'success'],
                                    };
                                @endphp
                                <x-ui.badge :variant="$priorityConfig['variant']">
                                    {{ $priorityConfig['label'] }}
                                </x-ui.badge>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">{{ $criterion->description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($document->document_type === 'ppt' && $document->extractedSpecifications->isNotEmpty())
            <div class="mt-8">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Especificaciones Extraídas ({{ $document->extractedSpecifications->count() }})
                </h2>
                <div class="space-y-3">
                    @foreach($document->extractedSpecifications as $spec)
                        <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h3 class="text-sm font-medium text-gray-900">
                                @if($spec->section_number)
                                    {{ $spec->section_number }} -
                                @endif
                                {{ $spec->section_title }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">{{ $spec->technical_description }}</p>
                            @if($spec->requirements)
                                <div class="mt-2 bg-gray-50 p-2 rounded">
                                    <span class="text-xs font-medium text-gray-500">Requisitos:</span>
                                    <p class="text-sm text-gray-600">{{ $spec->requirements }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
```

**Step 5: Update Document Show View**

Replace `resources/views/documents/show.blade.php`:
```blade
@extends('layouts.app')

@section('title', $document->original_filename . ' - Doc2Memo')

@section('content')
    <livewire:documents.document-detail :document="$document" />
@endsection
```

**Step 6: Run Tests**

```bash
php artisan test --compact --filter=DocumentDetailTest
```

Expected: All tests pass

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: add Livewire DocumentDetail component"
```

---

## Task 6: Technical Memory Component

**Purpose:** Replace the technical memory show page with a Livewire component for viewing generated memories.

**Files:**
- Create: `app/Livewire/TechnicalMemories/ShowMemory.php`
- Create: `resources/views/livewire/technical-memories/show-memory.blade.php`
- Create: `tests/Feature/Livewire/TechnicalMemories/ShowMemoryTest.php`
- Modify: `resources/views/technical-memories/show.blade.php`

**Step 1: Write Failing Test**

Create `tests/Feature/Livewire/TechnicalMemories/ShowMemoryTest.php`:
```php
<?php

use App\Livewire\TechnicalMemories\ShowMemory;
use App\Models\TechnicalMemory;
use App\Models\Tender;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
});

it('renders successfully with technical memory', function () {
    $tender = Tender::factory()->create();
    $memory = TechnicalMemory::factory()->create(['tender_id' => $tender->id]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSuccessful()
        ->assertSee($memory->title);
});

it('displays all memory sections when available', function () {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'title' => 'Test Memory',
        'introduction' => 'Introduction content',
        'company_presentation' => 'Company presentation content',
        'technical_approach' => 'Technical approach content',
        'methodology' => 'Methodology content',
        'team_structure' => 'Team structure content',
        'timeline' => 'Timeline content',
        'quality_assurance' => 'Quality assurance content',
        'risk_management' => 'Risk management content',
        'compliance_matrix' => 'Compliance matrix content',
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Test Memory')
        ->assertSee('Introduction content')
        ->assertSee('Company presentation content')
        ->assertSee('Technical approach content')
        ->assertSee('Methodology content')
        ->assertSee('Team structure content')
        ->assertSee('Timeline content')
        ->assertSee('Quality assurance content')
        ->assertSee('Risk management content')
        ->assertSee('Compliance matrix content');
});

it('displays generation date', function () {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'generated_at' => now(),
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Generada el');
});

it('has link back to tender', function () {
    $tender = Tender::factory()->create(['title' => 'Parent Tender']);
    TechnicalMemory::factory()->create(['tender_id' => $tender->id]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Volver');
});

it('shows download button when file path exists', function () {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'generated_file_path' => 'memories/test.pdf',
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('Descargar PDF');
});

it('hides download button when no file path', function () {
    $tender = Tender::factory()->create();
    TechnicalMemory::factory()->create([
        'tender_id' => $tender->id,
        'generated_file_path' => null,
    ]);

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertDontSee('Descargar PDF');
});

it('shows empty state when no memory exists', function () {
    $tender = Tender::factory()->create();

    Livewire::test(ShowMemory::class, ['tender' => $tender])
        ->assertSee('No hay memoria técnica generada');
});
```

**Step 2: Run Test to Verify It Fails**

```bash
php artisan test --compact --filter=ShowMemoryTest
```

Expected: FAIL - Component class not found

**Step 3: Create Livewire Component**

Create `app/Livewire/TechnicalMemories/ShowMemory.php`:
```php
<?php

namespace App\Livewire\TechnicalMemories;

use App\Models\Tender;
use Livewire\Component;

class ShowMemory extends Component
{
    public Tender $tender;

    public function mount(Tender $tender): void
    {
        $this->tender = $tender->load('technicalMemory');
    }

    public function render()
    {
        return view('livewire.technical-memories.show-memory');
    }
}
```

**Step 4: Create Blade View**

Create `resources/views/livewire/technical-memories/show-memory.blade.php`:
```blade
<div class="bg-white shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        @if(!$tender->technicalMemory)
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay memoria técnica generada</h3>
                <p class="mt-1 text-sm text-gray-500">La memoria técnica aún no ha sido generada para esta licitación.</p>
                <div class="mt-6">
                    <a href="{{ route('tenders.show', $tender) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                        Volver a la licitación
                    </a>
                </div>
            </div>
        @else
            @php
                $memory = $tender->technicalMemory;
            @endphp
            
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $memory->title }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Generada el {{ $memory->generated_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('tenders.show', $tender) }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                        Volver
                    </a>
                    @if($memory->generated_file_path)
                        <a href="{{ route('technical-memories.download', $memory) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Descargar PDF
                        </a>
                    @endif
                </div>
            </div>

            <div class="prose max-w-none space-y-8">
                @if($memory->introduction)
                    <section class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">1</span>
                            Introducción
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->introduction)) !!}</div>
                    </section>
                @endif

                @if($memory->company_presentation)
                    <section class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">2</span>
                            Presentación de la Empresa
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->company_presentation)) !!}</div>
                    </section>
                @endif

                @if($memory->technical_approach)
                    <section class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">3</span>
                            Enfoque Técnico
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->technical_approach)) !!}</div>
                    </section>
                @endif

                @if($memory->methodology)
                    <section class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">4</span>
                            Metodología
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->methodology)) !!}</div>
                    </section>
                @endif

                @if($memory->team_structure)
                    <section class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">5</span>
                            Estructura del Equipo
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->team_structure)) !!}</div>
                    </section>
                @endif

                @if($memory->timeline)
                    <section class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">6</span>
                            Cronograma
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->timeline)) !!}</div>
                    </section>
                @endif

                @if($memory->quality_assurance)
                    <section class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">7</span>
                            Aseguramiento de Calidad
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->quality_assurance)) !!}</div>
                    </section>
                @endif

                @if($memory->risk_management)
                    <section class="border-b border-gray-200 pb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">8</span>
                            Gestión de Riesgos
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->risk_management)) !!}</div>
                    </section>
                @endif

                @if($memory->compliance_matrix)
                    <section>
                        <h2 class="text-xl font-bold text-gray-900 mb-3 flex items-center">
                            <span class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold mr-3">9</span>
                            Matriz de Cumplimiento
                        </h2>
                        <div class="text-gray-700 leading-relaxed pl-11">{!! nl2br(e($memory->compliance_matrix)) !!}</div>
                    </section>
                @endif
            </div>
        @endif
    </div>
</div>
```

**Step 5: Update Technical Memory Show View**

Replace `resources/views/technical-memories/show.blade.php`:
```blade
@extends('layouts.app')

@section('title', 'Memoria Técnica - ' . $tender->title)

@section('content')
    <livewire:technical-memories.show-memory :tender="$tender" />
@endsection
```

**Step 6: Run Tests**

```bash
php artisan test --compact --filter=ShowMemoryTest
```

Expected: All tests pass

**Step 7: Commit**

```bash
git add -A
git commit -m "feat: add Livewire ShowMemory component for technical memories"
```

---

## Task 7: Cleanup and Final Verification

**Purpose:** Remove old Blade views and run full test suite to ensure everything works.

**Files:**
- Delete: `resources/views/tenders/index.blade.php` (old)
- Delete: `resources/views/tenders/show.blade.php` (old)
- Delete: `resources/views/tenders/create.blade.php` (old)
- Delete: `resources/views/documents/show.blade.php` (old)
- Delete: `resources/views/technical-memories/show.blade.php` (old)

**Step 1: Run Full Test Suite**

```bash
php artisan test --compact
```

Expected: All tests pass

**Step 2: Run Pint to Check Code Style**

```bash
vendor/bin/pint --dirty
```

Expected: No errors

**Step 3: Commit**

```bash
git add -A
git commit -m "chore: remove old Blade views after Livewire migration"
```

---

## Summary

This implementation plan converts all traditional Blade views to reactive Livewire components:

1. **TenderList** - Paginated list with search and filter
2. **CreateTender** - Form with real-time validation and file uploads
3. **TenderDetail** - Detail view with auto-refresh during analysis
4. **DocumentDetail** - Document view with extracted content
5. **ShowMemory** - Technical memory display

All components include:
- Real-time validation
- Loading states
- Error handling
- Responsive design with Tailwind CSS
- Comprehensive test coverage with Pest

Total new files: ~15
Total tests: ~25

---

## Post-Implementation Checklist

- [ ] All Livewire components render correctly
- [ ] File uploads work with progress indicators
- [ ] Auto-refresh works during document analysis
- [ ] Search and filters work without page reload
- [ ] All tests pass
- [ ] Code style passes Pint checks
- [ ] No JavaScript errors in browser console
- [ ] Mobile responsive design works
