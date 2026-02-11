<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class TenderForm extends Form
{
    public string $title = '';

    public ?string $issuing_company = null;

    public ?string $reference_number = null;

    public ?string $deadline_date = null;

    public ?string $description = null;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'issuing_company' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'deadline_date' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'title' => 'título',
            'issuing_company' => 'empresa emisora',
            'reference_number' => 'número de referencia',
            'deadline_date' => 'fecha límite',
            'description' => 'descripción',
        ];
    }
}
