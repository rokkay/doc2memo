<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported File Types
    |--------------------------------------------------------------------------
    |
    | Configure which file formats are accepted for document processing.
    | You can add new formats here and implement the extraction logic
    | in the ProcessDocument job.
    |
    */
    'file_types' => [
        'pdf' => [
            'mime_types' => ['application/pdf'],
            'max_size' => 10240, // KB
            'extractor' => 'pdf',
        ],
        'md' => [
            'mime_types' => ['text/markdown', 'text/plain'],
            'max_size' => 10240, // KB
            'extractor' => 'markdown',
        ],
        'txt' => [
            'mime_types' => ['text/plain'],
            'max_size' => 10240, // KB
            'extractor' => 'text',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Analysis Configuration
    |--------------------------------------------------------------------------
    */
    'analysis' => [
        // Deduplication settings
        'deduplication' => [
            'enabled' => true,
        ],
    ],
];
