<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Bibliotheque\Models\LibraryPerson;
use App\Modules\Bibliotheque\Models\LibraryWork;
use App\Modules\Bibliotheque\Services\LibraryService;
use App\Modules\Stages\Support\StudyLevel;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Site vitrine : liste, fiche et contact des travaux publiés. */
class LibraryPublicController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly LibraryService $library) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'annee'  => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'niveau' => ['nullable', Rule::in(array_keys(StudyLevel::CYCLES))],
            'type'   => ['nullable', Rule::in(array_keys(LibraryWork::KINDS))],
            'page'   => ['nullable', 'integer', 'min:1'],
        ]);
        $page = $this->library->publicQuery($filters);

        return $this->successResponse([
            'items'  => collect($page->items())->map(fn (LibraryWork $w) => $this->library->publicCard($w))->values(),
            'total'  => $page->total(), 'page' => $page->currentPage(), 'pages' => $page->lastPage(),
            'facets' => $this->library->facets() + [
                'kinds'  => LibraryWork::KINDS,
                'cycles_labels' => collect(StudyLevel::CYCLES)->map(fn ($c) => $c[0]),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $work = $this->visible($request, $slug);
        $work->increment('views');

        return $this->successResponse($this->library->publicDetail($work));
    }

    public function contact(Request $request, string $slug): JsonResponse
    {
        $work = $this->visible($request, $slug);
        $data = $request->validate([
            'person_id' => ['required', 'integer'],
            'nom'       => ['required', 'string', 'min:2', 'max:120'],
            'email'     => ['required', 'email', 'max:254'],
            'telephone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-.]{8,30}$/'],
            'message'   => ['required', 'string', 'min:20', 'max:2000'],
            'site'      => ['prohibited'], // champ piège : rempli par les robots seulement
        ], [
            'message.min'     => 'Votre message est trop court (20 caractères au moins).',
            'telephone.regex' => 'Numéro de téléphone invalide.',
        ]);
        $person = LibraryPerson::where('work_id', $work->id)->findOrFail($data['person_id']);

        try {
            $this->library->contact($work, $person, array_map(fn ($v) => is_string($v) ? trim(strip_tags($v)) : $v, $data), (string) $request->ip());
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }

        return $this->successResponse(null, "Votre message a été transmis à {$person->name}. Sa réponse vous parviendra directement.");
    }

    private function visible(Request $request, string $slug): LibraryWork
    {
        $work = LibraryWork::where('slug', $slug)->first();
        abort_unless($work && $work->isVisibleWith($request->query('cle') ?? $request->input('cle')), 404);

        return $work;
    }
}
