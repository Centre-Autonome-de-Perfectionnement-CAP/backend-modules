<?php

use Illuminate\Support\Facades\Route;

// Les routes des modules sont enregistrées par chaque ServiceProvider dans
// app/Modules/*/Providers/*ServiceProvider.php (préfixe 'api' + middleware 'api').
// Ce fichier ne contient que les routes transverses (préfixe 'api' automatique).

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

    // Vérification de la liaison/unicité stricte UNIQUEMENT pour la déclaration des anciens étudiants
    if (!empty($email) && $purpose === 'legacy_student') {
        // 1. Vérification dans les anciens étudiants (legacy_students) avec statut actif (pending ou validated)
        $query = \App\Modules\LegacyStudent\Models\LegacyStudent::whereRaw('LOWER(email) = ?', [$email])
            ->where('status', '!=', 'rejected');
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

        // 2. Vérification dans les étudiants récents (personal_information) avec dossier actif non rejeté
        $existingPI = \App\Modules\Inscription\Models\PersonalInformation::whereRaw('LOWER(email) = ?', [$email])
            ->whereHas('pendingStudents', function ($q) {
                $q->where('status', '!=', 'rejected');
            })
            ->first();
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

// FUSION develop/main : les routes OTP /core/otp/send et /core/otp/verify
// (OtpVerificationController) ne sont PAS reprises ici. Sur main, elles étaient
// déjà écrasées par celles du module Core (app/Modules/Core/routes/api.php →
// OtpController, limité à 10 requêtes/min), chargées après ce fichier : même
// URI, donc seule la version du module Core était réellement active. On garde
// ce comportement effectif sans doublon.
