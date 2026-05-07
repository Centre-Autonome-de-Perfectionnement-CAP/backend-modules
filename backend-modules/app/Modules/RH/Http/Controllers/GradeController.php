<?php

namespace App\Modules\RH\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Grade;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class GradeController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $grades = Grade::orderBy('name')->get();
        return $this->successResponse($grades, 'Grades récupérés avec succès');
    }
}
