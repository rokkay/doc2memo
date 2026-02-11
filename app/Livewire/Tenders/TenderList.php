<?php

namespace App\Livewire\Tenders;

use App\Models\Tender;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TenderList extends Component
{
    use WithPagination;

    #[Url(as: 'search', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $tenders = Tender::withCount('documents')
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('issuing_company', 'like', '%'.$this->search.'%')
                        ->orWhere('reference_number', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, function ($query): void {
                $query->where('status', $this->statusFilter);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.tenders.tender-list', [
            'tenders' => $tenders,
        ]);
    }
}
