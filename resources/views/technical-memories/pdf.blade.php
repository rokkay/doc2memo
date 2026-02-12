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
            margin: 32px;
            font-family: "DejaVu Sans", sans-serif;
            color: #0f172a;
            line-height: 1.55;
            font-size: 12px;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.3;
        }

        .meta {
            margin-top: 8px;
            color: #475569;
            font-size: 11px;
        }

        .section {
            margin-top: 24px;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0 0 10px;
            color: #0369a1;
            font-size: 16px;
        }

        .markdown p {
            margin: 0 0 10px;
        }

        .markdown ul,
        .markdown ol {
            margin: 0 0 10px 18px;
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
    </style>
</head>
<body>
    <h1>{{ $technicalMemory->title ?: 'Memoria Técnica' }}</h1>
    <p class="meta">
        Licitación: {{ $technicalMemory->tender->reference_number ?: $technicalMemory->tender->title }}
        @if($technicalMemory->generated_at)
            | Generada el {{ $technicalMemory->generated_at->format('d/m/Y H:i') }}
        @endif
    </p>

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
