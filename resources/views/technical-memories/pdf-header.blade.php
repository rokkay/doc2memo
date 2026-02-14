<style>
    .pdf-header {
        width: 100%;
        border-bottom: 1px solid #b9c8e6;
        padding: 4px 0 10px;
        font-family: "DejaVu Sans", sans-serif;
        font-size: 9px;
        color: #4b5563;
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
        color: #1d4ed8;
        font-weight: 600;
    }

    .pdf-header-left {
        color: #1d4ed8;
        font-weight: 700;
    }
</style>

<div class="pdf-header">
    <div class="pdf-header-row">
        <div class="pdf-header-left">
            MEMORIA TECNICA
        </div>
        <div class="pdf-header-right">
            {{ $technicalMemory->tender->reference_number ?: 'Propuesta tecnica' }}
        </div>
    </div>
</div>
