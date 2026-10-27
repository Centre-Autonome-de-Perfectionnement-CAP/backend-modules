<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Bibliotheque\Models\LibraryPerson;
use App\Modules\Bibliotheque\Models\LibraryWork;
use App\Modules\Bibliotheque\Services\LibraryService;
use App\Modules\Stages\Support\StudyLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pages publiques des travaux, rendues par le serveur : les moteurs de
 * recherche (et Google Scholar) lisent tout le contenu sans JavaScript.
 * « Par lien » : visible avec la clé, jamais référencé.
 */
class LibraryPageController extends Controller
{
    public function __construct(private readonly LibraryService $library) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q'      => ['nullable', 'string', 'max:100'],
            'annee'  => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'niveau' => ['nullable', 'string', 'in:' . implode(',', array_keys(StudyLevel::CYCLES))],
            'type'   => ['nullable', 'string', 'in:' . implode(',', array_keys(LibraryWork::KINDS))],
        ]);

        return view('bibliotheque::index', [
            'works'   => $this->library->publicQuery($filters),
            'facets'  => $this->library->facets(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $work = $this->visible($request, $slug);
        $work->increment('views');

        return view('bibliotheque::show', [
            'work'   => $work->load('people'),
            'key'    => $work->visibility === 'link' ? $work->share_token : null,
            'url'    => $this->library->publicUrl($work),
        ]);
    }

    public function pdf(Request $request, string $slug): StreamedResponse
    {
        $work = $this->visible($request, $slug);
        abort_unless($work->downloadable && $work->file_path && Storage::disk(LibraryService::DISK)->exists($work->file_path), 404);
        $work->increment('downloads');

        return Storage::disk(LibraryService::DISK)->response($work->file_path, $work->slug . '.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            ...($work->visibility === 'link' ? ['X-Robots-Tag' => 'noindex'] : []),
        ], 'inline');
    }

    public function contact(Request $request, string $slug): RedirectResponse
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
            'message.min' => 'Votre message est trop court (20 caractères au moins).',
            'telephone.regex' => 'Numéro de téléphone invalide.',
        ]);
        $person = LibraryPerson::where('work_id', $work->id)->findOrFail($data['person_id']);

        try {
            $this->library->contact($work, $person, array_map(fn ($v) => is_string($v) ? trim(strip_tags($v)) : $v, $data), (string) $request->ip());
        } catch (BusinessException $e) {
            return back()->withErrors(['message' => $e->getMessage()])->withInput();
        }

        return back()->with('sent', "Votre message a été transmis à {$person->name}. Sa réponse vous parviendra directement.");
    }

    /** Plan du site pour les moteurs de recherche (travaux publics uniquement). */
    public function sitemap(): Response
    {
        $works = LibraryWork::where('status', 'published')->where('visibility', 'public')->orderByDesc('published_at')
            ->get(['slug', 'updated_at']);

        return response()->view('bibliotheque::sitemap', ['works' => $works, 'base' => rtrim((string) config('app.url'), '/')])
            ->header('Content-Type', 'application/xml');
    }

    private function visible(Request $request, string $slug): LibraryWork
    {
        $work = LibraryWork::where('slug', $slug)->first();
        abort_unless($work && $work->isVisibleWith($request->query('cle') ?? $request->input('cle')), 404);

        return $work;
    }
}
