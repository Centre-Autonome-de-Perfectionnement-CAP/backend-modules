<?php

namespace App\Modules\Inscription\Services;

use App\Modules\Inscription\Models\EntryDiploma;
use Illuminate\Database\Eloquent\Collection;

class EntryDiplomaService
{
    /**
     * Récupérer tous les diplômes d'entrée
     *
     * @return Collection
     */
    public function getAllDiplomas(): Collection
    {
        return EntryDiploma::orderBy('name')->get();
    }

    /**
     * Récupérer un diplôme d'entrée par ID
     *
     * @param int $id
     * @return EntryDiploma|null
     */
    public function getDiplomaById(int $id): ?EntryDiploma
    {
        return EntryDiploma::find($id);
    }

    /**
     * Rechercher des diplômes par nom
     *
     * @param string $search
     * @return Collection
     */
    public function searchDiplomas(string $search): Collection
    {
        return EntryDiploma::where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->get();
    }
}
