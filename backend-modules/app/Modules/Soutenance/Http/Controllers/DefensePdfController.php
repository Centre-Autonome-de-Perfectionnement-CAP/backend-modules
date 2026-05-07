<?php

namespace App\Modules\Soutenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Soutenance\Http\Requests\GenerateQuitusRequest;
use App\Modules\Soutenance\Http\Requests\GenerateCorrectionRequest;
use App\Modules\Soutenance\Services\DefenseSubmissionService;
use Illuminate\Http\Response;

class DefensePdfController extends Controller
{
    public function __construct(
        protected DefenseSubmissionService $defenseSubmissionService
    ) {}

    public function generateQuitus(GenerateQuitusRequest $request)
    {
        $pdf = $this->defenseSubmissionService->generateQuitusPdf($request->validated());
        
        return $pdf->download('quitus.pdf');
    }

    public function generateCorrection(GenerateCorrectionRequest $request)
    {
        $pdf = $this->defenseSubmissionService->generateCorrectionPdf($request->validated());
        
        return $pdf->download('correction.pdf');
    }
}
