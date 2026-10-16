<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OtpService $otpService
    ) {}

    /**
     * Envoie un code OTP à une adresse email
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'purpose' => 'nullable|string',
            'matricule' => 'nullable|string',
        ]);

        $result = $this->otpService->sendOtp(
            $request->input('email'),
            $request->input('purpose', 'general'),
            $request->input('matricule')
        );

        return response()->json($result);
    }

    /**
     * Vérifie le code OTP saisi
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'purpose' => 'nullable|string',
        ]);

        $result = $this->otpService->verifyOtp(
            $request->input('email'),
            $request->input('code'),
            $request->input('purpose', 'general')
        );

        $status = $result['verified'] ? 200 : 422;
        return response()->json($result, $status);
    }
}
