<?php

namespace App\Modules\Bibliotheque\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bibliotheque\Models\LibraryWork;
use App\Modules\Bibliotheque\Services\LibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pages des travaux : la page est celle du site vitrine (même en-tête, même
 * style), mais le serveur y écrit déjà le titre, la description et les
 * balises Google Scholar / schema.org : les moteurs de recherche les lisent
 * sans exécuter l'application. « Par lien » : jamais référencé.
 */
class LibraryPageController extends Controller
{
    public function __construct(private readonly LibraryService $library) {}

    public function index(): Response
    {
        return $this->page(view('bibliotheque::head', [
            'title' => 'Travaux des stagiaires et étudiants',
            'description' => 'Rapports de fin de formation et mémoires réalisés au Centre Autonome de Perfectionnement (CAP) de l\'EPAC, Université d\'Abomey-Calavi.',
            'canonical' => url('/travaux'), 'robots' => request()->getQueryString() ? 'noindex, follow' : null, 'work' => null,
        ])->render());
    }

    public function show(Request $request, int $id): Response
    {
        $work = LibraryWork::with('people')->find($id);
        if (!$work || !$work->isVisibleWith($request->query('cle'))) {
            return $this->page(view('bibliotheque::head', [
                'title' => 'Travail introuvable', 'description' => '', 'canonical' => null, 'robots' => 'noindex', 'work' => null,
            ])->render(), 404);
        }
        $d = $this->library->publicDetail($work);

        return $this->page(view('bibliotheque::head', [
            'title' => $d['title'], 'description' => Str::limit($d['summary'] ?: $d['kind_label'] . ' - ' . $d['authors']->implode(', '), 300),
            'canonical' => $work->isPublic() ? $d['url'] : null, 'robots' => $work->isPublic() ? null : 'noindex, nofollow',
            'work' => $work->isPublic() ? $d : null,
        ])->render());
    }

    /** Ancienne adresse avec le titre : redirection définitive vers /travaux/{id}. */
    public function legacy(Request $request, string $slug): RedirectResponse|Response
    {
        $work = LibraryWork::where('slug', $slug)->first();
        $cle = $request->query('cle');
        if (!$work || !$work->isVisibleWith(is_string($cle) ? $cle : null)) {
            return $this->show($request, 0);
        }

        return redirect()->to($this->library->publicUrl($work) . (is_string($cle) ? '?cle=' . urlencode($cle) : ''), 301);
    }

    public function pdf(Request $request, int $id): StreamedResponse
    {
        $work = LibraryWork::find($id);
        $doc = $work && $work->isVisibleWith($request->query('cle')) && $work->downloadable ? $this->library->document($work) : null;
        abort_unless($doc, 404);
        $work->increment('downloads');

        // Affiché aussi dans la fiche du site (même origine).
        return Storage::disk($doc['disk'])->response($doc['path'], $work->slug . '.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            ...($work->visibility === 'link' ? ['X-Robots-Tag' => 'noindex'] : []),
        ], 'inline');
    }

    /** Plan du site pour les moteurs de recherche (travaux publics uniquement). */
    public function sitemap(): Response
    {
        $works = LibraryWork::where('status', 'published')->where('visibility', 'public')->orderByDesc('published_at')
            ->get(['id', 'updated_at']);

        return response()->view('bibliotheque::sitemap', ['works' => $works, 'base' => rtrim((string) config('app.url'), '/')])
            ->header('Content-Type', 'application/xml');
    }

    /** Page du site vitrine, avec les balises propres à cette page. */
    private function page(string $head, int $status = 200): Response
    {
        $html = (string) file_get_contents(public_path('app-cap/index.html'));
        $html = preg_replace('#<title>.*?</title>#s', '', $html, 1);
        $html = str_replace('</head>', $head . "\n</head>", $html);

        return response($html, $status)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
