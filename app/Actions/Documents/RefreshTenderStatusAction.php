<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;

final class RefreshTenderStatusAction
{
    public function __invoke(Document $document): void
    {
        $tender = $document->tender->fresh();

        if ($tender->documents()->where('status', 'failed')->exists()) {
            $tender->update(['status' => 'failed']);

            return;
        }

        if ($tender->documents()->where('status', '!=', 'analyzed')->exists()) {
            $tender->update(['status' => 'analyzing']);

            return;
        }

        $tender->update(['status' => 'completed']);
    }
}
