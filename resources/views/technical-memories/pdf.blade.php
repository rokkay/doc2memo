<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $technicalMemory->title ?: 'Memoria Técnica' }}</title>
    <style>
        html {
            -webkit-print-color-adjust: exact;
        }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
            line-height: 1.55;
            font-size: 12px;
        }

        .cover {
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .cover-band {
            background: linear-gradient(120deg, #e0f2fe 0%, #f0f9ff 45%, #ffffff 100%);
            padding: 18px 20px;
            border-bottom: 1px solid #cbd5e1;
        }

        .cover-kicker {
            margin: 0 0 6px;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 10px;
            font-weight: 700;
        }

        .cover-title {
            margin: 0;
            font-size: 24px;
            line-height: 1.3;
        }

        .cover-meta {
            padding: 12px 20px;
            color: #334155;
            font-size: 11px;
            line-height: 1.6;
        }

        .section {
            margin-top: 24px;
            page-break-inside: auto;
        }

        .section-title {
            margin: 0 0 10px;
            color: #0369a1;
            font-size: 16px;
            line-height: 1.4;
            border-left: 3px solid #0ea5e9;
            padding-left: 8px;
        }

        .markdown p {
            margin: 0 0 10px;
        }

        .markdown ul,
        .markdown ol {
            margin: 0 0 10px 18px;
        }

        .markdown h1,
        .markdown h2,
        .markdown h3,
        .markdown h4 {
            margin: 14px 0 8px;
            line-height: 1.4;
        }

        .markdown blockquote {
            margin: 10px 0;
            padding: 8px 10px;
            border-left: 3px solid #7dd3fc;
            background: #f8fafc;
            color: #334155;
        }

        .markdown code {
            background: #e2e8f0;
            padding: 1px 4px;
            border-radius: 4px;
            font-size: 11px;
        }

        .markdown pre {
            margin: 10px 0;
            border-radius: 8px;
            padding: 10px;
            background: #0f172a;
            color: #e2e8f0;
            overflow: hidden;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .markdown table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .markdown th,
        .markdown td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        .markdown th {
            background: #e2e8f0;
            font-weight: 700;
        }

        .section + .section {
            border-top: 1px dashed #cbd5e1;
            padding-top: 18px;
        }
    </style>
</head>
<body>
    <section class="cover">
        <div class="cover-band">
            <p class="cover-kicker">Documento técnico</p>
            <h1 class="cover-title">{{ $technicalMemory->title ?: 'Memoria Técnica' }}</h1>
        </div>
        <div class="cover-meta">
            <strong>Licitación:</strong> {{ $technicalMemory->tender->reference_number ?: $technicalMemory->tender->title }}<br>
            @if($technicalMemory->generated_at)
                <strong>Generada el:</strong> {{ $technicalMemory->generated_at->format('d/m/Y H:i') }}<br>
            @endif
            <strong>Secciones incluidas:</strong> {{ count($sections) }}
        </div>
    </section>

    @foreach($sections as $section)
        <section class="section">
            <h2 class="section-title">{{ $section['title'] }}</h2>
            <x-markdown class="markdown">
                {{ $section['content'] }}
            </x-markdown>
        </section>
    @endforeach
</body>
</html>
