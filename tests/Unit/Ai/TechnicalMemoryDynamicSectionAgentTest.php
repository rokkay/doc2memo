<?php

declare(strict_types=1);

use App\Ai\Agents\TechnicalMemoryDynamicSectionAgent;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;

it('extracts content from structured array responses', function (): void {
    $agent = new TechnicalMemoryDynamicSectionAgent(section: [], context: []);

    $content = $agent->extractContent([
        'content' => "### Encabezado\n\nTexto",
    ]);

    expect($content)->toBe("### Encabezado\n\nTexto");
});

it('extracts content from json text agent responses', function (): void {
    $agent = new TechnicalMemoryDynamicSectionAgent(section: [], context: []);

    $response = new AgentResponse(
        invocationId: 'invocation-id',
        text: json_encode(['content' => '### Seccion'.PHP_EOL.PHP_EOL.'Contenido']) ?: '',
        usage: new Usage(0, 0),
        meta: new Meta(provider: 'openai', model: 'gpt-5-mini'),
    );

    $content = $agent->extractContent($response);

    expect($content)->toBe("### Seccion\n\nContenido");
});

it('falls back to raw response text when json decoding is not possible', function (): void {
    $agent = new TechnicalMemoryDynamicSectionAgent(section: [], context: []);

    $response = new AgentResponse(
        invocationId: 'invocation-id',
        text: "### Seccion\n\nTexto plano",
        usage: new Usage(0, 0),
        meta: new Meta(provider: 'openai', model: 'gpt-5-mini'),
    );

    $content = $agent->extractContent($response);

    expect($content)->toBe("### Seccion\n\nTexto plano");
});
