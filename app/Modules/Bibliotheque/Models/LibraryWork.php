<?php

namespace App\Modules\Bibliotheque\Models;

use App\Modules\Stages\Support\StudyLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LibraryWork extends Model
{
    public const KINDS = ['rapport_stage' => 'Rapport de fin de formation', 'memoire' => 'Mémoire'];
    public const STATUSES = ['proposed' => 'Proposé', 'draft' => 'Brouillon', 'published' => 'Publié', 'unpublished' => 'Retiré'];
    public const LICENSES = [
        'tous_droits' => 'Tous droits réservés : reproduction interdite sans l\'accord des auteurs.',
        'cc_by_nc'    => 'Creative Commons BY-NC : partage permis, avec citation des auteurs, sans usage commercial.',
    ];

    protected $table = 'library_works';

    protected $guarded = ['id'];

    /** Adresse lisible tirée du titre ; elle ne change plus une fois publiée (liens partagés). */
    protected static function booted(): void
    {
        static::saving(function (self $w) {
            if (!$w->slug || (!$w->published_at && $w->isDirty('title'))) {
                $w->slug = (Str::limit(Str::slug((string) $w->title), 90, '') ?: 'travail') . '-' . Str::lower(Str::random(5));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'keywords'        => 'array',
            'downloadable'    => 'boolean',
            'authors_consent' => 'boolean',
            'published_at'    => 'datetime',
            'year'            => 'integer',
            'annee_etude'     => 'integer',
        ];
    }

    public function people(): HasMany
    {
        return $this->hasMany(LibraryPerson::class, 'work_id')->orderBy('position')->orderBy('id');
    }

    public function isPublic(): bool
    {
        return $this->status === 'published' && $this->visibility === 'public';
    }

    /** Visible avec ce lien (public, ou « par lien » avec la bonne clé). */
    public function isVisibleWith(?string $key): bool
    {
        return $this->status === 'published'
            && ($this->visibility === 'public' || ($key !== null && hash_equals($this->share_token, $key)));
    }

    /** « Licence 3 Génie informatique » */
    public function formation(): string
    {
        return StudyLevel::formation($this->filiere, $this->cycle, $this->annee_etude);
    }
}
