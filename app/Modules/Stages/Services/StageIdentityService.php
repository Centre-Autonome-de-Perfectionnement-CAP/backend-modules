<?php

namespace App\Modules\Stages\Services;

use App\Exceptions\BusinessException;
use App\Modules\Stages\Models\StageRequest;
use App\Modules\Stages\Support\BusinessHours;
use App\Modules\Stages\Support\StageStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Une même personne ne doit pas avoir deux demandes ou deux stages en même temps.
 *
 * Deux demandes sont de la même personne si elles partagent :
 *  - le NPI, ou
 *  - le numéro WhatsApp (8 derniers chiffres), ou
 *  - l'adresse e-mail, ou
 *  - la date de naissance, le nom et au moins un prénom.
 *
 * Il n'y a pas de table « personne » : la personne est reconstituée à la volée
 * depuis ses demandes. Elle peut refaire un stage plus tard (historique) ;
 * seule une demande ou un stage encore en cours bloque un nouveau dépôt.
 */
class StageIdentityService
{
    public const NPI_LENGTH = 10;

    /** Demandes qui ne comptent pas comme un passage : jamais retenue, expirée, jamais présentée. */
    private const NEVER_HAPPENED = [StageStatus::REJECTED, StageStatus::EXPIRED, StageStatus::WITHDRAWN];

    /** NPI saisi : chiffres seuls (espaces et séparateurs retirés), null si vide. */
    public static function cleanNpi(mixed $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits === '' || $digits === null ? null : $digits;
    }

    /** Demande ou stage encore en cours : bloque une nouvelle demande de la même personne. */
    public static function isLive(StageRequest $r): bool
    {
        if (in_array($r->status, StageStatus::ACTIVE, true)) {
            return true;
        }

        return $r->status === StageStatus::ACCEPTED
            && (!$r->date_fin || $r->date_fin->toDateString() >= BusinessHours::today()->toDateString());
    }

    /**
     * Refuse l'enregistrement si la même personne a déjà une demande ou un stage en cours.
     *
     * @param array{npi?: ?string, whatsapp?: ?string, email?: ?string, nom?: ?string, prenoms?: ?string, date_naissance?: ?string} $identity
     */
    public function assertFree(array $identity, ?int $exceptId = null): void
    {
        $live = $this->matches($identity, $exceptId)->first(fn (array $m) => self::isLive($m['request']));
        if ($live) {
            throw new BusinessException(
                'Une demande ou un stage est déjà en cours pour cette personne (même NPI, numéro, e-mail ou identité). '
                . 'Suivez la demande existante avec sa référence, ou contactez le Centre.',
                'STAGE_PERSON_ALREADY_ACTIVE',
                409,
            );
        }
    }

    /**
     * Demandes de la même personne.
     *
     * @param array<string, mixed> $identity
     * @return Collection<int, array{request: StageRequest, why: string[]}>
     */
    public function matches(array $identity, ?int $exceptId = null): Collection
    {
        $npi   = self::cleanNpi($identity['npi'] ?? null);
        $tail  = $this->phoneTail($identity['whatsapp'] ?? null);
        $email = $this->email($identity['email'] ?? null);
        $nom   = $this->ascii($identity['nom'] ?? null);
        $birth = $identity['date_naissance'] ?? null;
        $birth = $birth ? substr((string) $birth, 0, 10) : null;

        $query = StageRequest::query()->whereNotIn('status', self::NEVER_HAPPENED)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->where(function ($w) use ($npi, $tail, $email, $birth, $identity) {
                $any = false;
                if ($npi) {
                    $w->orWhere('npi', $npi);
                    $any = true;
                }
                if ($tail) {
                    $w->orWhere('whatsapp', 'like', '%' . $tail);
                    $any = true;
                }
                if ($email) {
                    $w->orWhere('email', $email);
                    $any = true;
                }
                if ($birth && !empty($identity['nom'])) {
                    $w->orWhere(fn ($i) => $i->whereDate('date_naissance', $birth)->where('nom', trim((string) $identity['nom'])));
                    $any = true;
                }
                if (!$any) {
                    $w->whereRaw('1 = 0');
                }
            });

        $prenoms = $this->firstNames($identity['prenoms'] ?? null);

        return $query->get()->map(function (StageRequest $r) use ($npi, $tail, $email, $nom, $birth, $prenoms) {
            $why = [];
            if ($npi && $r->npi === $npi) {
                $why[] = 'npi';
            }
            if ($tail && $this->phoneTail($r->whatsapp) === $tail) {
                $why[] = 'telephone';
            }
            if ($email && $this->email($r->email) === $email) {
                $why[] = 'email';
            }
            if ($birth && $r->date_naissance?->toDateString() === $birth && $this->ascii($r->nom) === $nom
                && array_intersect($prenoms, $this->firstNames($r->prenoms))) {
                $why[] = 'identite';
            }

            return ['request' => $r, 'why' => $why];
        })->filter(fn (array $m) => $m['why'])->values();
    }

    /** Autres demandes de la même personne que ce dossier (historique). */
    public function otherStays(StageRequest $r): Collection
    {
        return $this->matches([
            'npi' => $r->npi, 'whatsapp' => $r->whatsapp, 'email' => $r->email, 'nom' => $r->nom,
            'prenoms' => $r->prenoms, 'date_naissance' => $r->date_naissance?->toDateString(),
        ], $r->id);
    }

    /**
     * Doublons probables : groupes de demandes de la même personne dont au moins
     * deux sont en cours ou se chevauchent. Un second stage plus tard n'en est pas un.
     *
     * @return array<int, array<string, mixed>>
     */
    public function duplicateGroups(): array
    {
        $rows = StageRequest::query()->whereNotIn('status', self::NEVER_HAPPENED)
            ->get(['id', 'reference', 'status', 'nom', 'prenoms', 'npi', 'whatsapp', 'email', 'date_naissance', 'date_debut', 'date_fin', 'etablissement_origine', 'created_at']);

        // Union-find : deux demandes qui partagent un critère sont de la même personne.
        $parent = $rows->pluck('id')->mapWithKeys(fn ($id) => [$id => $id])->all();
        $find = function (int $x) use (&$parent, &$find): int {
            while ($parent[$x] !== $x) {
                $parent[$x] = $parent[$parent[$x]];
                $x = $parent[$x];
            }

            return $x;
        };
        $union = function (int $a, int $b) use (&$parent, $find): void {
            $ra = $find($a);
            $rb = $find($b);
            if ($ra !== $rb) {
                $parent[$ra] = $rb;
            }
        };

        $buckets = [];
        foreach ($rows as $r) {
            $keys = array_filter([
                $r->npi ? 'n:' . $r->npi : null,
                ($t = $this->phoneTail($r->whatsapp)) ? 'p:' . $t : null,
                ($e = $this->email($r->email)) ? 'e:' . $e : null,
            ]);
            foreach ($keys as $k) {
                isset($buckets[$k]) ? $union($buckets[$k], $r->id) : $buckets[$k] = $r->id;
            }
        }
        foreach ($rows->filter(fn ($r) => $r->date_naissance)->groupBy(fn ($r) => $r->date_naissance->toDateString() . '|' . $this->ascii($r->nom)) as $same) {
            foreach ($same as $i => $a) {
                foreach ($same->slice($i + 1) as $b) {
                    if (array_intersect($this->firstNames($a->prenoms), $this->firstNames($b->prenoms))) {
                        $union($a->id, $b->id);
                    }
                }
            }
        }

        $today = BusinessHours::today()->toDateString();
        $groups = [];
        foreach ($rows->groupBy(fn ($r) => $find($r->id)) as $members) {
            if ($members->count() < 2 || !$this->simultaneous($members, $today)) {
                continue;
            }
            $groups[] = [
                'people' => $members->sortBy('created_at')->map(fn ($r) => [
                    'id' => $r->id, 'reference' => $r->reference, 'nom_complet' => $r->fullName(),
                    'status' => $r->status, 'status_label' => StageStatus::label($r->status),
                    'etablissement' => $r->etablissement_origine,
                    'date_debut' => $r->date_debut?->toDateString(), 'date_fin' => $r->date_fin?->toDateString(),
                    'live' => self::isLive($r),
                ])->values()->all(),
                'shared' => $this->sharedCriteria($members),
            ];
        }
        usort($groups, fn ($a, $b) => count($b['people']) <=> count($a['people']));

        return $groups;
    }

    // ── Interne ──────────────────────────────────────────────────────────

    /** Au moins deux demandes en cours, ou deux stages dont les dates se chevauchent. */
    private function simultaneous(Collection $members, string $today): bool
    {
        if ($members->filter(fn ($r) => self::isLive($r))->count() >= 2) {
            return true;
        }
        $dated = $members->filter(fn ($r) => $r->date_debut && $r->date_fin)->values();
        foreach ($dated as $i => $a) {
            foreach ($dated->slice($i + 1) as $b) {
                if ($a->date_debut <= $b->date_fin && $b->date_debut <= $a->date_fin) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return string[] critères communs à tout le groupe ou à une partie (pour l'affichage) */
    private function sharedCriteria(Collection $members): array
    {
        $out = [];
        $dup = fn (Collection $c) => $c->filter()->duplicates()->isNotEmpty();
        if ($dup($members->map(fn ($r) => $r->npi))) {
            $out[] = 'npi';
        }
        if ($dup($members->map(fn ($r) => $this->phoneTail($r->whatsapp)))) {
            $out[] = 'telephone';
        }
        if ($dup($members->map(fn ($r) => $this->email($r->email)))) {
            $out[] = 'email';
        }
        if (!$out || $members->groupBy(fn ($r) => $r->date_naissance?->toDateString() . '|' . $this->ascii($r->nom))->contains(fn ($g) => $g->count() > 1)) {
            $out[] = 'identite';
        }

        return array_values(array_unique($out));
    }

    private function phoneTail(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return strlen($digits) >= 8 ? substr($digits, -8) : null;
    }

    private function email(?string $email): ?string
    {
        $e = mb_strtolower(trim((string) $email));

        return $e === '' ? null : $e;
    }

    private function ascii(?string $s): string
    {
        return Str::lower(Str::ascii(trim((string) $s)));
    }

    /** @return string[] prénoms (sans accent, minuscules, 2 lettres au moins) */
    private function firstNames(?string $prenoms): array
    {
        return array_values(array_unique(array_filter(
            preg_split('/[\s,;\-\']+/', $this->ascii($prenoms)) ?: [],
            fn ($p) => mb_strlen($p) >= 2,
        )));
    }
}
