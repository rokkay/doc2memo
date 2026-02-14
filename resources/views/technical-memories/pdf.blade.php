<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $technicalMemory->title ?: 'Memoria Técnica' }}</title>
    <style>
        :root {
            --ink: #111827;
            --muted: #374151;
            --line: #b9c8e6;
            --line-soft: #d7deec;
            --accent: #1d4ed8;
        }

        html {
            -webkit-print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: var(--ink);
            line-height: 1.55;
            font-size: 11.25px;
            background: #ffffff;
        }

        .document {
            margin: 0;
        }

        .cover {
            margin: 0;
            border-bottom: 1px solid var(--line);
            padding: 18px 0 20px;
            text-align: center;
            page-break-after: always;
        }

        .cover-kicker {
            margin: 0 0 6px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-size: 9.5px;
            font-weight: 700;
        }

        .cover-title {
            margin: 0 0 10px;
            font-size: 26px;
            font-weight: 800;
            line-height: 1.25;
            color: #111827;
        }

        .cover-meta {
            margin: 0 auto;
            padding: 0;
            color: var(--muted);
            border: 0;
            background: transparent;
            font-size: 10.5px;
            line-height: 1.6;
            max-width: 480px;
        }

        .sections {
            padding: 8px 0 0;
        }

        .toc {
            padding: 16px 0 2px;
            page-break-after: always;
        }

        .toc-title {
            margin: 0 0 12px;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.25;
            color: #0f172a;
        }

        .toc-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .toc-item {
            margin: 0;
            padding: 4px 0;
            border-bottom: 1px dotted var(--line-soft);
            page-break-inside: avoid;
        }

        .toc-link-row {
            display: block;
            text-decoration: none;
            color: #1f2937;
        }

        .toc-link-label {
            color: #1f2937;
            font-size: 11px;
            line-height: 1.4;
            display: inline-block;
            max-width: 86%;
        }

        .toc-link-page {
            float: right;
            color: #1f2937;
            font-size: 10.5px;
            font-weight: 700;
        }

        .toc-link-row .toc-link-page::before {
            content: target-counter(attr(href), page);
        }

        .toc-item.is-subsection .toc-link-label {
            padding-left: 16px;
            color: #4b5563;
            font-size: 10.6px;
        }

        .section-anchor {
            display: block;
            position: relative;
            top: -2px;
            visibility: hidden;
        }

        .section {
            margin-top: 18px;
            page-break-inside: auto;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 26px;
            line-height: 1.2;
            color: #0f172a;
            font-weight: 800;
            border: 0;
            padding: 0;
            page-break-after: avoid;
        }

        .markdown p {
            margin: 0 0 9px;
        }

        .markdown ul,
        .markdown ol {
            margin: 0 0 10px 16px;
            padding-left: 10px;
        }

        .markdown li + li {
            margin-top: 4px;
        }

        .markdown h1,
        .markdown h2,
        .markdown h3,
        .markdown h4 {
            margin: 12px 0 8px;
            line-height: 1.35;
            color: #111827;
            page-break-after: avoid;
        }

        .markdown h1 {
            font-size: 24px;
            font-weight: 800;
        }

        .markdown h2 {
            font-size: 19px;
            font-weight: 700;
        }

        .markdown h3 {
            font-size: 15px;
            font-weight: 700;
        }

        .markdown h4 {
            font-size: 13px;
            font-weight: 700;
        }

        .markdown blockquote {
            margin: 10px 0;
            padding: 8px 10px;
            border-left: 3px solid #9ca3af;
            background: #f9fafb;
            color: #1f2937;
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
            background: #111827;
            color: #e5e7eb;
            overflow: hidden;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .markdown table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            page-break-inside: avoid;
        }

        .markdown th,
        .markdown td {
            border: 1px solid var(--line);
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        .markdown th {
            background: #eff3f9;
            font-weight: 700;
        }

        .section + .section {
            border-top: 1px dashed var(--line-soft);
            padding-top: 14px;
        }
    </style>
</head>
<body>
    <main class="document">
        <section class="cover">
            <p class="cover-kicker">Documento técnico</p>
            <h1 class="cover-title">{{ $technicalMemory->title ?: 'Memoria Técnica' }}</h1>
            <p class="cover-meta">
                <strong>Licitación:</strong> {{ $technicalMemory->tender->reference_number ?: $technicalMemory->tender->title }}<br>
                @if($technicalMemory->generated_at)
                    <strong>Generada el:</strong> {{ $technicalMemory->generated_at->format('d/m/Y H:i') }}<br>
                @endif
                <strong>Secciones incluidas:</strong> {{ count($sections) }}
            </p>
        </section>

        <section class="toc">
            <h2 class="toc-title">Indice</h2>
            <ol class="toc-list">
                @foreach($toc as $tocEntry)
                    <li class="toc-item {{ $tocEntry['level'] === 2 ? 'is-subsection' : '' }}">
                        <a class="toc-link-row" href="#{{ $tocEntry['id'] }}">
                            <span class="toc-link-label">{{ $tocEntry['title'] }}</span>
                            <span class="toc-link-page"></span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="sections">
            @foreach($sections as $section)
                <section class="section">
                    <span id="{{ $section['id'] }}" class="section-anchor"></span>
                    <h2 class="section-title">{{ $section['title'] }}</h2>
                    <div class="markdown">
                        {!! $section['html'] !!}
                    </div>
                </section>
            @endforeach
        </section>
    </main>
</body>
</html>
