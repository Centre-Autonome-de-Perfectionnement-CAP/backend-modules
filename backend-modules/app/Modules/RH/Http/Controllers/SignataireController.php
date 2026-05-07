<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Signataire;
use App\Modules\RH\Http\Requests\CreateSignataireRequest;
use App\Modules\RH\Http\Requests\UpdateSignataireRequest;
use App\Modules\RH\Http\Resources\SignataireResource;
use App\Modules\RH\Services\SignataireService;

class SignataireController extends Controller
{
    public function __construct(private SignataireService $service) {}

    public function index()
    {
        $signataires = $this->service->getAll();
        return response()->json([
            'success' => true,
            'data' => SignataireResource::collection($signataires),
        ]);
    }

    public function store(CreateSignataireRequest $request)
    {
        $signataire = $this->service->create($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Signataire créé avec succès',
            'data' => new SignataireResource($signataire),
        ], 201);
    }

    public function show(Signataire $signataire)
    {
        return response()->json([
            'success' => true,
            'data' => new SignataireResource($signataire->load('role')),
        ]);
    }

    public function update(UpdateSignataireRequest $request, Signataire $signataire)
    {
        $signataire = $this->service->update($signataire, $request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Signataire mis à jour avec succès',
            'data' => new SignataireResource($signataire),
        ]);
    }

    public function destroy(Signataire $signataire)
    {
        $this->service->delete($signataire);
        return response()->json([
            'success' => true,
            'message' => 'Signataire supprimé avec succès',
        ]);
    }
}
