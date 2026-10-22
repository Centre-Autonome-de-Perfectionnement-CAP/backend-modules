<?php

namespace App\Modules\Stages\Services;

use App\Modules\Stages\Models\StageAttestation;
use App\Modules\Stages\Models\StageNote;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;

/**
 * PDF du module Stages : attestation de stage et notes de service.
 * Tout le texte reste du texte (polices intégrées), seuls les logos sont des
 * images. Aucune ressource distante : rien n'est chargé hors du projet.
 */
class StagePdfService
{
    public function __construct(private readonly StageSettingsService $settings) {}

    /** @return array{name: string, content: string} */
    public function attestation(StageAttestation $a, ?CarbonImmutable $documentDate = null): array
    {
        $req = $a->stage;
        $c   = $a->content ?: app(AttestationService::class)->content($req, $a->overrides);
        $o   = $a->overrides ?? [];
        $masculin = $req->civilite === 'M.';
        $pays = $c['pays_naissance'] ?? $o['pays_naissance'] ?? $this->defaultCountry($req->nationalite);
        $date = $documentDate
            ?? ($a->document_date ? CarbonImmutable::parse($a->document_date->toDateString()) : BusinessHours::today());

        $html = view('stages::pdfs.attestation', [
            'd' => [
                'nom_complet'    => $c['nom_complet'] ?? trim($req->civilite . ' ' . $req->fullName()),
                'masculin'       => $masculin,
                'civilite'       => $req->civilite,
                'nom'            => mb_strtoupper($c['nom'] ?? $req->nom),
                'prenoms'        => $c['prenoms'] ?? $req->prenoms,
                'date_naissance_long' => $this->longDate($c['date_naissance'] ?? $req->date_naissance, false),
                'lieu_naissance' => $c['lieu_naissance'] ?? $req->lieu_naissance,
                'pays_naissance' => $pays,
                'pays_naissance_html' => $pays ? $this->smallCaps($pays) : '',
                'debut_long'     => $this->longDate($c['date_debut'], true),
                'fin_long'       => $this->longDate($c['date_fin'], true),
                'type_stage'     => $c['type_stage'] ?? $o['type_stage'] ?? 'académique',
                'structure'      => $c['structure'] ?? $o['structure'] ?? $this->settings->get('attestation_structure'),
            ],
            'apercu'   => $a->status === 'a_valider',
            'suffixe'  => trim((string) $this->settings->get('numero_suffixe'), '/'),
            'ville'    => $this->settings->get('pdf_ville'),
            'date'     => $date->format('d/m/Y'),
            'signataire_titre' => $this->settings->get('signataire_titre'),
            'signataire_nom'   => $this->settings->get('signataire_nom'),
            'footer'   => $this->settings->get('pdf_pied_de_page'),
        ])->render();

        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $this->ascii(($c['nom'] ?? $req->nom) . '-' . ($c['prenoms'] ?? $req->prenoms)));

        return ['name' => "Attestation de stage - {$slug}.pdf", 'content' => $this->render($html)];
    }

    /** @return array{name: string, content: string} */
    public function note(StageNote $note): array
    {
        $req  = StageRequest::findOrFail($note->stage_request_id);
        $s    = $note->snapshot ?? [];
        $masculin = ($s['civilite'] ?? $req->civilite) === 'M.';
        $initial  = $note->kind === 'rectificative'
            ? StageNote::where('stage_request_id', $req->id)->where('kind', 'initiale')->first()
            : null;
        $previous = $note->kind === 'rectificative'
            ? StageNote::where('stage_request_id', $req->id)->where('version', '<', $note->version)->orderByDesc('version')->first()
            : null;
        $issued = CarbonImmutable::parse($note->issued_at)->setTimezone(BusinessHours::TZ);
        $h = $s['horaires'] ?? $req->horaires ?? [];

        $html = view('stages::pdfs.note', [
            'note'      => $note,
            'rectif'    => $note->kind === 'rectificative',
            'initial'   => $initial ? ['number' => $initial->number, 'date' => CarbonImmutable::parse($initial->issued_at)->setTimezone(BusinessHours::TZ)->format('d/m/Y')] : null,
            'changes'   => $previous ? $this->changes($previous->snapshot ?? [], $s) : [],
            'd'         => [
                'appel'      => $masculin ? 'Monsieur' : (($s['civilite'] ?? '') === 'Mme' ? 'Madame' : 'Mademoiselle'),
                'masculin'   => $masculin,
                'nom'        => mb_strtoupper($s['nom'] ?? $req->nom),
                'prenoms'    => $s['prenoms'] ?? $req->prenoms,
                'formation'  => $s['formation'] ?? $req->filiere_niveau,
                'etablissement' => $s['etablissement'] ?? $req->etablissement_origine,
                'duree'      => $this->durationWords($s['date_debut'] ?? null, $s['date_fin'] ?? null),
                'debut'      => $this->longDate($s['date_debut'] ?? null, false),
                'fin'        => $this->longDate($s['date_fin'] ?? null, false),
                'service'    => $s['service'] ?? $req->service,
                'lieu'       => $s['lieu'] ?? $req->lieu,
                'horaires'   => $this->hoursSentence($h),
            ],
            'numero'    => $note->number,
            'suffixe'   => trim((string) $this->settings->get('numero_suffixe'), '/'),
            'ville'     => $this->settings->get('pdf_ville'),
            'date'      => $issued->format('d/m/Y'),
            'signataire_titre' => $this->settings->get('signataire_titre'),
            'signataire_nom'   => $this->settings->get('signataire_nom'),
            'footer'    => $this->settings->get('pdf_pied_de_page'),
        ])->render();

        return [
            'name'    => 'Note de service ' . str_replace('/', '-', $note->number) . ' - ' . preg_replace('/[^A-Za-z0-9-]+/', '-', $this->ascii(($s['nom'] ?? $req->nom))) . '.pdf',
            'content' => $this->render($html),
        ];
    }

    // ── Interne ──────────────────────────────────────────────────────────

    private function render(string $html): string
    {
        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled'         => false,
                'isPhpEnabled'            => false,
                'isJavascriptEnabled'     => false,
                'isFontSubsettingEnabled' => true,
                'isHtml5ParserEnabled'    => true,
                'dpi'                     => 96,
                'chroot'                  => base_path(),
            ])
            ->output();
    }

    /** « Lundi 11 Août 2025 » (jour en option), « 07 Janvier 2006 ». */
    public function longDate(mixed $date, bool $withDay): string
    {
        if (!$date) {
            return '…';
        }
        $d = CarbonImmutable::parse($date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date)->locale('fr');
        $txt = ($withDay ? mb_convert_case($d->isoFormat('dddd'), MB_CASE_TITLE) . ' ' : '')
            . $d->format('d') . ' ' . mb_convert_case($d->isoFormat('MMMM'), MB_CASE_TITLE) . ' ' . $d->format('Y');

        return $txt;
    }

    /** Petites capitales (comme « Rép. du BENIN » du modèle) - texte uniquement. */
    private function smallCaps(string $text): string
    {
        $out = '';
        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            $up = mb_strtoupper($ch);
            $out .= $ch !== $up && mb_strtolower($ch) === $ch
                ? '<span class="sc">' . e($up) . '</span>'
                : e($ch);
        }

        return $out;
    }

    private function defaultCountry(?string $nationalite): ?string
    {
        return $nationalite && preg_match('/b[ée]nin/iu', $nationalite) ? 'Rép. du BENIN' : null;
    }

    /** « deux (02) mois et quatre (04) jours ». */
    public function durationWords(?string $start, ?string $end): string
    {
        if (!$start || !$end) {
            return '…';
        }
        $diff = CarbonImmutable::parse($start)->diff(CarbonImmutable::parse($end)->addDay());
        $months = $diff->y * 12 + $diff->m;
        $parts = [];
        if ($months) {
            $parts[] = $this->words($months) . ' (' . str_pad((string) $months, 2, '0', STR_PAD_LEFT) . ') mois';
        }
        if ($diff->d) {
            $parts[] = $this->words($diff->d) . ' (' . str_pad((string) $diff->d, 2, '0', STR_PAD_LEFT) . ') jour' . ($diff->d > 1 ? 's' : '');
        }

        return implode(' et ', $parts) ?: 'un (01) jour';
    }

    private function words(int $n): string
    {
        $u = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize'];
        if ($n <= 16) {
            return $u[$n];
        }
        if ($n < 20) {
            return 'dix-' . $u[$n - 10];
        }
        $t = [2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante'];
        $tens = intdiv($n, 10);
        $r = $n % 10;
        if ($tens >= 2 && $tens <= 6) {
            return $t[$tens] . ($r === 0 ? '' : ($r === 1 ? ' et un' : '-' . $u[$r]));
        }

        return (string) $n;
    }

    private function hoursSentence(array $h): ?string
    {
        if (empty($h['arrivee']) || empty($h['sortie'])) {
            return null;
        }
        $f = fn (string $v) => str_replace(':', 'h', $v);

        return !empty($h['pause_debut']) && !empty($h['pause_fin'])
            ? "de {$f($h['arrivee'])} à {$f($h['pause_debut'])} et de {$f($h['pause_fin'])} à {$f($h['sortie'])}"
            : "de {$f($h['arrivee'])} à {$f($h['sortie'])}";
    }

    /** Ce qui a changé d'une note à l'autre (note rectificative). */
    private function changes(array $before, array $after): array
    {
        $out = [];
        if (($before['date_debut'] ?? null) !== ($after['date_debut'] ?? null) || ($before['date_fin'] ?? null) !== ($after['date_fin'] ?? null)) {
            $out[] = ['Période', 'du ' . $this->longDate($before['date_debut'] ?? null, false) . ' au ' . $this->longDate($before['date_fin'] ?? null, false),
                'du ' . $this->longDate($after['date_debut'] ?? null, false) . ' au ' . $this->longDate($after['date_fin'] ?? null, false)
                . ' (' . $this->durationWords($after['date_debut'] ?? null, $after['date_fin'] ?? null) . ')'];
        }
        if (($before['service'] ?? null) !== ($after['service'] ?? null)) {
            $out[] = ['Service', (string) ($before['service'] ?? '—'), (string) ($after['service'] ?? '—')];
        }
        if (($before['lieu'] ?? null) !== ($after['lieu'] ?? null)) {
            $out[] = ['Lieu', (string) ($before['lieu'] ?? '—'), (string) ($after['lieu'] ?? '—')];
        }
        if (($before['horaires'] ?? null) != ($after['horaires'] ?? null)) {
            $out[] = ['Horaires', (string) $this->hoursSentence($before['horaires'] ?? []), (string) $this->hoursSentence($after['horaires'] ?? [])];
        }

        return $out;
    }

    private function ascii(string $s): string
    {
        return (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    }
}
