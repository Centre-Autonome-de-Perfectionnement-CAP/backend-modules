<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\EntryDiplomaService;

class EntryDiplomaController extends Controller
{
    public function __construct(
        protected EntryDiplomaService $diplomaService
    ) {}

    /**
     * Liste des diplômes d'entrée
     */
    public function index()
    {
        $diplomas = $this->diplomaService->getAllDiplomas();

        return response()->json([
            'success' => true,
            'data' => $diplomas
        ]);
    }
}
