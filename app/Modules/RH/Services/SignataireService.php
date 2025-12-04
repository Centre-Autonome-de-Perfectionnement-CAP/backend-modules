<?php

namespace App\Modules\RH\Services;

use App\Models\Signataire;

class SignataireService
{
    public function getAll()
    {
        return Signataire::with('role')->orderBy('created_at', 'desc')->get();
    }

    public function create(array $data)
    {
        return Signataire::create($data);
    }

    public function update(Signataire $signataire, array $data)
    {
        $signataire->update($data);
        return $signataire->fresh('role');
    }

    public function delete(Signataire $signataire)
    {
        return $signataire->delete();
    }
}
