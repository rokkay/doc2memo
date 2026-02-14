<?php

declare(strict_types=1);

namespace App\Livewire\TechnicalMemories;

use App\Actions\TechnicalMemories\GetOperationalMetricsAction;
use Carbon\CarbonImmutable;
use Illuminate\View\View;
use Livewire\Component;

class OperationalMetrics extends Component
{
    public string $from_date = '';

    public string $to_date = '';

    /**
     * @var array{global:array<string,int|float>,dailyTrend:array<int,array<string,int|float|string>>,memories:array<int,array<string,int|float|string>>,topProblematicSections:array<int,array<string,int|float|string>>}
     */
    public array $metrics = [
        'global' => [],
        'dailyTrend' => [],
        'memories' => [],
        'topProblematicSections' => [],
    ];

    public function mount(GetOperationalMetricsAction $getOperationalMetricsAction): void
    {
        $this->from_date = now()->subDays(7)->toDateString();
        $this->to_date = now()->toDateString();

        $this->refreshMetrics($getOperationalMetricsAction);
    }

    public function updatedFromDate(GetOperationalMetricsAction $getOperationalMetricsAction): void
    {
        $this->refreshMetrics($getOperationalMetricsAction);
    }

    public function updatedToDate(GetOperationalMetricsAction $getOperationalMetricsAction): void
    {
        $this->refreshMetrics($getOperationalMetricsAction);
    }

    public function render(): View
    {
        return view('livewire.technical-memories.operational-metrics');
    }

    private function refreshMetrics(GetOperationalMetricsAction $getOperationalMetricsAction): void
    {
        $from = CarbonImmutable::parse($this->from_date)->startOfDay();
        $to = CarbonImmutable::parse($this->to_date)->endOfDay();
        $data = $getOperationalMetricsAction($from, $to);

        $this->metrics = [
            'global' => $data->global,
            'dailyTrend' => $data->dailyTrend,
            'memories' => $data->memories,
            'topProblematicSections' => $data->topProblematicSections,
        ];
    }
}
