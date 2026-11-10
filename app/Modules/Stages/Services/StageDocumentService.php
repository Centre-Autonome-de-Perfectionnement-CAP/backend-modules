<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Documents et références : fichiers (modèles, règlement, tutoriels…) et
 * liens ajoutés par le personnel, visibles de tous les stagiaires en stage
 * et du personnel. Les nouveautés sont annoncées dans le récapitulatif du
 * soir (ProcessStageReports).
 */
class StageDocumentService
{
    public const CATEGORIES = [
        'modeles'    => 'Modèles',
        'reglement'  => 'Règlement et procédures',
        'tutoriels'  => 'Tutoriels et formations',
        'outils'     => 'Outils',
        'autre'      => 'Autre',
    ];
    public const MAX_FILE_KB = TaskService::MAX_FILE_KB;
    private const DISK = 'local';

    public function __construct(private readonly StageNotifier $notifier) {}

    public function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    public function ready(): bool
    {
        if (Cache::get('stage-feedback-ready')) {
            return true;
        }
        $ok = Schema::hasTable('stage_documents') && Schema::hasTable('stage_evaluation_results');
        if ($ok) {
            Cache::put('stage-feedback-ready', true, 3600);
        }

        return $ok;
    }

    public function assertReady(): void
    {
        if (!$this->ready()) {
            throw new BusinessException('Cet espace sera disponible dans quelques minutes.', 'STAGE_FEEDBACK_NOT_READY', 503);
        }
    }

    public function list(): Collection
    {
        $this->assertReady();
        $authors = User::whereIn('id', DB::table('stage_documents')->pluck('created_by')->filter()->unique())->get()->keyBy('id');

        return DB::table('stage_documents')->orderByDesc('id')->get()->map(fn ($d) => [
            'id' => $d->id, 'title' => $d->title, 'description' => $d->description,
            'category' => $d->category, 'category_label' => self::CATEGORIES[$d->category] ?? 'Autre',
            'kind' => $d->kind, 'url' => $d->kind === 'link' ? $d->url : null,
            'file_name' => $d->file_name, 'size' => $d->size,
            'by' => ($u = $authors[$d->created_by] ?? null) ? trim($u->first_name . ' ' . $u->last_name) : null,
            'created_at' => $d->created_at,
        ]);
    }

    /** @param array{title: string, description?: ?string, category: string, url?: ?string} $data */
    public function create(array $data, ?UploadedFile $file, User $by): int
    {
        $this->assertReady();
        $url = isset($data['url']) ? trim((string) $data['url']) : '';
        if (!$file && $url === '') {
            throw new BusinessException('Ajoutez un fichier ou un lien.', 'STAGE_DOC_EMPTY', 422);
        }
        if ($url !== '' && !preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }
        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new BusinessException('Le lien n\'est pas une adresse valide.', 'STAGE_DOC_URL', 422);
        }

        return DB::transaction(function () use ($data, $file, $url, $by) {
            $id = DB::table('stage_documents')->insertGetId([
                'title' => trim($data['title']), 'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'category' => $data['category'], 'kind' => $file ? 'file' : 'link', 'url' => $file ? null : $url,
                'created_by' => $by->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($file) {
                $name = mb_substr(preg_replace('/[^\pL\pN ._()-]+/u', '-', $file->getClientOriginalName()), 0, 200);
                $path = $file->storeAs("stages/documents/{$id}", bin2hex(random_bytes(8)) . '.' . strtolower($file->getClientOriginalExtension()), self::DISK);
                DB::table('stage_documents')->where('id', $id)->update([
                    'path' => $path, 'file_name' => $name, 'mime' => $file->getMimeType(), 'size' => $file->getSize(),
                ]);
            }

            return $id;
        });
    }

    public function delete(int $id): void
    {
        $this->assertReady();
        $doc = DB::table('stage_documents')->where('id', $id)->first();
        if (!$doc) {
            throw new BusinessException('Ce document n\'existe plus.', 'STAGE_DOC_NOT_FOUND', 404);
        }
        DB::table('stage_documents')->where('id', $id)->delete();
        if ($doc->path) {
            $this->disk()->deleteDirectory("stages/documents/{$id}");
        }
    }

    /** @return array{0: string, 1: string, 2: ?string} chemin, nom, type */
    public function file(int $id): array
    {
        $this->assertReady();
        $doc = DB::table('stage_documents')->where('id', $id)->where('kind', 'file')->first();
        if (!$doc || !$doc->path || !$this->disk()->exists($doc->path)) {
            throw new BusinessException('Ce document n\'existe plus.', 'STAGE_DOC_NOT_FOUND', 404);
        }

        return [$doc->path, $doc->file_name, $doc->mime];
    }

    /** Récapitulatif du soir : annonce à tous les documents ajoutés dans la journée. */
    public function announceNew(): int
    {
        if (!$this->ready()) {
            return 0;
        }
        $docs = DB::table('stage_documents')->whereNull('announced_at')->orderBy('id')->get(['id', 'title', 'category']);
        if ($docs->isEmpty()) {
            return 0;
        }
        DB::table('stage_documents')->whereIn('id', $docs->pluck('id'))->update(['announced_at' => now()]);
        try {
            $this->notifier->toEveryone(
                $docs->count() > 1 ? 'Nouveaux documents (' . $docs->count() . ')' : 'Nouveau document',
                [$docs->map(fn ($d) => '- ' . $d->title . ' (' . (self::CATEGORIES[$d->category] ?? 'Autre') . ')')->implode("\n"),
                    'Retrouvez-les dans « Documents utiles ».'],
                $this->notifier->spaceUrl('/stage/documents'),
                rtrim((string) config('app.url'), '/') . '/services/stages/documents',
                'stage-documents',
            );
        } catch (\Throwable $e) {
            Log::error('[Stages] Annonce des documents impossible', ['error' => $e->getMessage()]);
        }

        return $docs->count();
    }
}
