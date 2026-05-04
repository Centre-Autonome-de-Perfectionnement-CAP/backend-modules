<?php

namespace App\Modules\Demandes\Traits;

use App\Modules\Demandes\Services\DocumentRequestHistoryService;

/**
 * Injecter dans tout controller qui exécute des transitions.
 * Expose logHistory() et logMail() comme raccourcis courts.
 */
trait RecordsDocumentHistory
{
    private function historyService(): DocumentRequestHistoryService
    {
        return app(DocumentRequestHistoryService::class);
    }

    protected function logHistory(
        int     $documentRequestId,
        string  $actionType,
        ?string $statusBefore = null,
        ?string $statusAfter  = null,
        ?string $comment      = null
    ): void {
        $this->historyService()->record(
            $documentRequestId, $actionType, $statusBefore, $statusAfter, $comment
        );
    }

    protected function logMail(int $documentRequestId, string $subject): void
    {
        $this->historyService()->recordMail($documentRequestId, $subject);
    }
}
