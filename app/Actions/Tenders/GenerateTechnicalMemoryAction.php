<?php

declare(strict_types=1);

namespace App\Actions\Tenders;

use App\Ai\Agents\TechnicalMemoryGenerator;
use App\Models\Tender;

final class GenerateTechnicalMemoryAction
{
    public function __invoke(Tender $tender): void
    {
        $pcaData = [
            'criteria' => $tender->extractedCriteria()
                ->orderBy('id')
                ->get()
                ->map(fn ($item) => [
                    'section_number' => $item->section_number,
                    'section_title' => $item->section_title,
                    'description' => $item->description,
                    'priority' => $item->priority,
                    'metadata' => $item->metadata,
                ])
                ->all(),
            'insights' => $tender->documentInsights()
                ->where('document_id', optional($tender->pcaDocument)->id)
                ->orderByDesc('importance')
                ->orderBy('id')
                ->get()
                ->map(fn ($item) => [
                    'section_reference' => $item->section_reference,
                    'topic' => $item->topic,
                    'requirement_type' => $item->requirement_type,
                    'importance' => $item->importance,
                    'statement' => $item->statement,
                    'evidence_excerpt' => $item->evidence_excerpt,
                ])
                ->all(),
        ];

        $pptData = [
            'specifications' => $tender->extractedSpecifications()
                ->orderBy('id')
                ->get()
                ->map(fn ($item) => [
                    'section_number' => $item->section_number,
                    'section_title' => $item->section_title,
                    'technical_description' => $item->technical_description,
                    'requirements' => $item->requirements,
                    'deliverables' => $item->deliverables,
                    'metadata' => $item->metadata,
                ])
                ->all(),
            'insights' => $tender->documentInsights()
                ->where('document_id', optional($tender->pptDocument)->id)
                ->orderByDesc('importance')
                ->orderBy('id')
                ->get()
                ->map(fn ($item) => [
                    'section_reference' => $item->section_reference,
                    'topic' => $item->topic,
                    'requirement_type' => $item->requirement_type,
                    'importance' => $item->importance,
                    'statement' => $item->statement,
                    'evidence_excerpt' => $item->evidence_excerpt,
                ])
                ->all(),
        ];

        $memoryData = (new TechnicalMemoryGenerator($pcaData, $pptData))->generate();

        $tender->technicalMemory()->updateOrCreate(
            ['tender_id' => $tender->id],
            [
                'title' => (string) ($memoryData['title'] ?? 'Memoria Tecnica'),
                'introduction' => (string) ($memoryData['introduction'] ?? ''),
                'company_presentation' => (string) ($memoryData['company_presentation'] ?? ''),
                'technical_approach' => (string) ($memoryData['technical_approach'] ?? ''),
                'methodology' => (string) ($memoryData['methodology'] ?? ''),
                'team_structure' => (string) ($memoryData['team_structure'] ?? ''),
                'timeline' => (string) ($memoryData['timeline'] ?? ''),
                'quality_assurance' => (string) ($memoryData['quality_assurance'] ?? ''),
                'risk_management' => (string) ($memoryData['risk_management'] ?? ''),
                'compliance_matrix' => (string) ($memoryData['compliance_matrix'] ?? ''),
                'full_report_markdown' => (string) ($memoryData['full_report_markdown'] ?? ''),
                'status' => 'generated',
                'generated_file_path' => null,
                'generated_at' => now(),
            ]
        );
    }
}
