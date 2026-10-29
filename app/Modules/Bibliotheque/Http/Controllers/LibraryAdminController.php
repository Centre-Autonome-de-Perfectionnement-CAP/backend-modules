<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Bibliotheque\Models\LibraryPerson;
use App\Modules\Bibliotheque\Models\LibraryWork;
use App\Modules\Bibliotheque\Services\LibraryService;
use App\Modules\Stages\Support\StudyLevel;
use App\Support\UploadLimit;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Progiciel : bibliothèque des travaux (responsable de division uniquement). */
class LibraryAdminController extends Controller
{
    use ApiResponse;

    private const FILE_RULE = ['file', 'mimes:pdf', 'mimetypes:application/pdf'];

    public function __construct(private readonly LibraryService $library) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(LibraryWork::STATUSES))],
            'q'      => ['nullable', 'string', 'max:100'],
        ]);

        return $this->successResponse([
            'works'  => $this->library->adminList($data['status'] ?? null, $data['q'] ?? null),
            'counts' => $this->library->counts(),
        ]);
    }

    public function candidates(): JsonResponse
    {
        return $this->successResponse($this->library->candidates());
    }

    public function show(int $id): JsonResponse
    {
        return $this->successResponse($this->library->detail(LibraryWork::findOrFail($id)));
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['stage_report', 'defense'])],
            'id'   => ['required', 'integer', 'min:1'],
        ]);

        return $this->guard(fn () => $this->successResponse(
            $this->library->detail($this->library->import($data['type'], (int) $data['id'], $request->user())),
            'Travail ajouté en brouillon : relisez la fiche puis publiez-la.', 201,
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules($request) + [
            'kind'    => ['required', Rule::in(array_keys(LibraryWork::KINDS))],
            'fichier' => ['required', ...self::FILE_RULE, UploadLimit::rule(UploadLimit::REPORT_KB)],
        ], $this->messages());

        return $this->guard(fn () => $this->successResponse(
            $this->library->detail($this->library->createManual($this->clean($data), $request->file('fichier'), $request->user())),
            'Travail ajouté en brouillon.', 201,
        ));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $work = LibraryWork::findOrFail($id);
        $data = $request->validate($this->rules($request) + [
            'kind' => ['required', Rule::in(array_keys(LibraryWork::KINDS))],
        ], $this->messages());

        return $this->guard(fn () => $this->successResponse($this->library->detail($this->library->update($work, $this->clean($data))), 'Fiche enregistrée.'));
    }

    public function file(int $id): JsonResponse
    {
        $work = LibraryWork::findOrFail($id);
        $doc = $this->library->document($work);
        if (!$doc) {
            return $this->errorResponse('Aucun fichier pour ce travail.', 404, 'LIBRARY_NO_FILE');
        }

        return response()->json([
            'name' => ($work->slug ?: 'travail') . '.pdf', 'mime' => 'application/pdf',
            'content' => base64_encode(Storage::disk($doc['disk'])->get($doc['path'])),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function replaceFile(Request $request, int $id): JsonResponse
    {
        $request->validate(['fichier' => ['required', ...self::FILE_RULE, UploadLimit::rule(UploadLimit::REPORT_KB)]], $this->messages());

        return $this->successResponse($this->library->detail($this->library->replaceFile(LibraryWork::findOrFail($id), $request->file('fichier'))), 'Fichier remplacé.');
    }

    public function publish(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'visibility'   => ['required', Rule::in(['public', 'link'])],
            'downloadable' => ['required', 'boolean'],
            'license'      => ['required', Rule::in(array_keys(LibraryWork::LICENSES))],
        ]);

        return $this->guard(fn () => $this->successResponse(
            $this->library->detail($this->library->publish(LibraryWork::findOrFail($id), $data['visibility'], (bool) $data['downloadable'], $data['license'], $request->user())),
            $data['visibility'] === 'public' ? 'Publié : visible par tous et trouvable par les moteurs de recherche.' : 'Publié par lien : seules les personnes qui ont le lien le voient.',
        ));
    }

    public function unpublish(int $id): JsonResponse
    {
        return $this->successResponse($this->library->detail($this->library->unpublish(LibraryWork::findOrFail($id))), 'Retiré de la publication.');
    }

    public function renewLink(int $id): JsonResponse
    {
        return $this->successResponse($this->library->detail($this->library->renewLink(LibraryWork::findOrFail($id))), 'Nouveau lien créé : l\'ancien ne fonctionne plus.');
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->guard(function () use ($id) {
            $this->library->delete(LibraryWork::findOrFail($id));

            return $this->successResponse(null, 'Travail supprimé.');
        });
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function rules(Request $request): array
    {
        $years = StudyLevel::CYCLES[$request->input('cycle')][1] ?? 0;

        return [
            'title'         => ['required', 'string', 'min:5', 'max:300'],
            'summary'       => ['nullable', 'string', 'max:3000'],
            'keywords'      => ['nullable', 'string', 'max:400'],
            'year'          => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'etablissement' => ['nullable', 'string', 'max:150'],
            'universite'    => ['nullable', 'string', 'max:150'],
            'filiere'       => ['nullable', 'string', 'max:150'],
            'cycle'         => ['nullable', Rule::in(array_keys(StudyLevel::CYCLES))],
            'annee_etude'   => $years ? ['nullable', 'integer', 'min:1', 'max:' . $years] : ['nullable', 'prohibited'],
            'people'        => ['required', 'array', 'min:1', 'max:12'],
            'people.*.role' => ['required', Rule::in(array_keys(LibraryPerson::ROLES))],
            'people.*.name' => ['required', 'string', 'min:2', 'max:150'],
            'people.*.fonction' => ['nullable', 'string', 'max:150'],
            'people.*.email'    => ['nullable', 'email', 'max:254'],
            'people.*.phone'    => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-.]{8,30}$/'],
        ];
    }

    private function messages(): array
    {
        return [
            'people.required'  => 'Ajoutez au moins une personne (l\'auteur).',
            'people.*.name.required' => 'Chaque personne doit avoir un nom.',
            'people.*.phone.regex'   => 'Numéro de téléphone invalide.',
            'fichier.mimes'    => 'Le travail doit être un fichier PDF.',
            'fichier.max'      => UploadLimit::message(UploadLimit::REPORT_KB),
        ];
    }

    /** Textes sans balises. */
    private function clean(array $data): array
    {
        foreach (['title', 'summary', 'keywords', 'etablissement', 'universite', 'filiere'] as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim(strip_tags((string) $data[$k])) ?: null;
            }
        }
        $data['people'] = array_map(fn (array $p) => array_map(fn ($v) => is_string($v) ? (trim(strip_tags($v)) ?: null) : $v, $p), $data['people']);

        return $data;
    }

    private function guard(callable $fn): JsonResponse
    {
        try {
            return $fn();
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }
    }
}
