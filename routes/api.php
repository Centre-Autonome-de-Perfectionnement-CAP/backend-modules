<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route globale de vérification d'adresse email en temps réel (DNS / Format / Unicité)
Route::match(['get', 'post'], '/core/validate-email', function (\Illuminate\Http\Request $request) {
    $email = trim(mb_strtolower((string) ($request->get('email') ?? $request->input('email', ''))));
    $matricule = trim((string) ($request->get('matricule') ?? $request->input('matricule', '')));
    $purpose = (string) ($request->get('purpose') ?? $request->input('purpose', ''));

    $result = \App\Rules\ValidRealEmail::analyzeEmail((string) $email);
    if (!$result['valid']) {
        return response()->json($result, 422);
    }

    // Vérification de l'unicité avant envoi de l'OTP
    if (!empty($email)) {
        // 1. Vérification dans les anciens étudiants (legacy_students)
        $query = \App\Modules\LegacyStudent\Models\LegacyStudent::whereRaw('LOWER(email) = ?', [$email]);
        if (!empty($matricule)) {
            $query->where('matricule', '!=', $matricule);
        }
        $existingLegacy = $query->first();
        if ($existingLegacy) {
            return response()->json([
                'valid' => false,
                'message' => "Cette adresse email est déjà associée à un autre dossier étudiant. Chaque étudiant doit obligatoirement utiliser sa propre adresse email.",
                'is_duplicate' => true,
            ], 422);
        }

        // 2. Vérification dans les étudiants récents (personal_information)
        $existingPI = \App\Modules\Inscription\Models\PersonalInformation::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($existingPI) {
            return response()->json([
                'valid' => false,
                'message' => "Cette adresse email est déjà associée à un autre dossier étudiant. Chaque étudiant doit obligatoirement utiliser sa propre adresse email.",
                'is_duplicate' => true,
            ], 422);
        }
    }

    return response()->json($result, 200);
})->name('api.core.validate-email');

// Routes de validation d'email par code OTP à 6 chiffres
Route::post('/core/otp/send', [\App\Modules\Core\Http\Controllers\OtpVerificationController::class, 'sendOtp'])->name('api.core.otp.send');
Route::post('/core/otp/verify', [\App\Modules\Core\Http\Controllers\OtpVerificationController::class, 'verifyOtp'])->name('api.core.otp.verify');

