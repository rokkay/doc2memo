<style>
    .pdf-header {
        width: 100%;
        border-bottom: 1px solid #cbd5e1;
        padding: 0 0 6px;
        font-family: "DejaVu Sans", sans-serif;
        font-size: 10px;
        color: #475569;
    }

    .pdf-header-row {
        width: 100%;
    }

    .pdf-header-left,
    .pdf-header-right {
        display: inline-block;
        vertical-align: top;
        width: 49%;
    }

    .pdf-header-right {
        text-align: right;
    }
</style>

<div class="pdf-header">
    <div class="pdf-header-row">
        <div class="pdf-header-left">
            <strong>{{ $technicalMemory->title ?: 'Memoria Técnica' }}</strong>
        </div>
        <div class="pdf-header-right">
            {{ $technicalMemory->tender->reference_number ?: $technicalMemory->tender->title }}
        </div>
    </div>
</div>
