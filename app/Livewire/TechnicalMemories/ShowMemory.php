<?php

namespace App\Livewire\TechnicalMemories;

use App\Models\Tender;
use Illuminate\View\View;
use Livewire\Component;

class ShowMemory extends Component
{
    public Tender $tender;

    public function mount(Tender $tender): void
    {
        $this->tender = $tender->load('technicalMemory');
    }

    public function render(): View
    {
        return view('livewire.technical-memories.show-memory');
    }
}
