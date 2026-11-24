<?php

namespace Tests\Feature\Verif;

use App\Modules\Stages\Services\StagePdfService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Notes et bulletins : effacés du disque quelques jours après l'envoi, le reste du dossier intact. */
class StagesDocumentsPurgeTest extends TestCase
{
    public function test_sent_notes_and_bulletins_are_purged_after_three_days(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');
        $old = now()->subDays(4)->getTimestamp();
        foreach (['stages/STG-A/notes/note-001-26.pdf', 'stages/STG-A/notes/fin-002-26.pdf', 'stages/STG-A/evaluation.pdf', 'stages/STG-A/cv.pdf', 'stages/STG-B/notes/note-003-26.pdf'] as $f) {
            $disk->put($f, '%PDF');
        }
        foreach (['stages/STG-A/notes/note-001-26.pdf', 'stages/STG-A/notes/fin-002-26.pdf', 'stages/STG-A/evaluation.pdf', 'stages/STG-A/cv.pdf'] as $f) {
            touch($disk->path($f), $old);
        }

        $this->assertSame(3, app(StagePdfService::class)->purgeSentFiles());
        $disk->assertMissing(['stages/STG-A/notes/note-001-26.pdf', 'stages/STG-A/notes/fin-002-26.pdf', 'stages/STG-A/evaluation.pdf']);
        // Pièces du dossier et note envoyée récemment : conservées
        $disk->assertExists(['stages/STG-A/cv.pdf', 'stages/STG-B/notes/note-003-26.pdf']);
    }
}
