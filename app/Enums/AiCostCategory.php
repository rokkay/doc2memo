<?php

namespace App\Enums;

enum AiCostCategory: string
{
    case DocumentAnalyzer = 'document_analyzer';
    case DedicatedJudgmentExtractor = 'dedicated_judgment_extractor';
    case DynamicSection = 'dynamic_section';
    case StyleEditor = 'style_editor';
}
