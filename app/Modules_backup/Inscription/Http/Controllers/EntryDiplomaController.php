<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inscription\Services\EntryDiplomaService;
use App\Traits\ApiResponse;

class EntryDiplomaController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected EntryDiplomaService $diplomaService
    ) {}

    /**
     * Liste des diplômes d'entrée
     */
    public function index()
    {
        $diplomas = $this->diplomaService->getAllDiplomas();

        return $this->successResponse($diplomas
        , 'Données récupérées avec succès');
    }
}
