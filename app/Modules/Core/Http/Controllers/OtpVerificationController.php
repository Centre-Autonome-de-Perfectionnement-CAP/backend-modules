<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\EmailOtpVerification;
use App\Modules\Core\Services\MailService;
use App\Rules\ValidRealEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OtpVerificationController extends Controller
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * Envoie un code OTP à 6 chiffres par email.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'max:150', new ValidRealEmail()],
            'purpose' => ['nullable', 'string', 'max:50'],
        ], [
            'email.required' => "L'adresse email est obligatoire.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = trim(mb_strtolower($request->input('email')));
        $purpose = $request->input('purpose', 'general');
        $matricule = trim($request->input('matricule', ''));

        // Vérification de l'unicité de l'email pour les anciens étudiants (dossiers non rejetés)
        if ($purpose === 'legacy_student' && !empty($matricule)) {
            $otherStudent = \App\Modules\LegacyStudent\Models\LegacyStudent::whereRaw('LOWER(email) = ?', [$email])
                ->where('status', '!=', 'rejected')
                ->where('matricule', '!=', $matricule)
                ->first();

            if ($otherStudent) {
                return response()->json([
                    'success' => false,
                    'message' => "Cette adresse email est déjà associée à un autre dossier étudiant. Chaque étudiant doit obligatoirement utiliser sa propre adresse email.",
                    'errors' => [
                        'email' => ["Cette adresse email est déjà associée à un autre dossier étudiant."],
                    ],
                ], 422);
            }
        }

        // Anti-flood : Vérifie si un code a été envoyé il y a moins de 30 secondes
        $recent = EmailOtpVerification::where('email', $email)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->first();

        if ($recent) {
            $secondsLeft = 30 - now()->diffInSeconds($recent->created_at);
            return response()->json([
                'success' => false,
                'message' => "Veuillez patienter {$secondsLeft}s avant de demander un nouveau code.",
                'retry_after' => $secondsLeft,
            ], 429);
        }

        // Génération du code à 6 chiffres
        $code = (string) random_int(100000, 999999);

        // Expiration des anciens codes non vérifiés pour cette adresse
        EmailOtpVerification::where('email', $email)
            ->whereNull('verified_at')
            ->delete();

        // Enregistrement du nouveau code
        $otp = EmailOtpVerification::create([
            'email' => $email,
            'code' => $code,
            'purpose' => $purpose,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        // Envoi de l'email
        $mailSent = false;
        try {
            $mailSent = $this->mailService->sendEmail(
                $email,
                "Code de vérification CAP : {$code}",
                'core::emails.otp-verification',
                [
                    'code' => $code,
                    'email' => $email,
                ]
            );
        } catch (\Throwable $e) {
            Log::error("[OTP EMAIL] Erreur lors de l'envoi du mail OTP à {$email}: " . $e->getMessage());
        }

        Log::info("[OTP EMAIL] Code généré pour {$email}: {$code} (Envoi SMTP: " . ($mailSent ? 'OK' : 'Échec / Log') . ")");

        return response()->json([
            'success' => true,
            'message' => "Un code de vérification à 6 chiffres a été envoyé à {$email}.",
            'expires_in' => 600,
        ]);
    }

    /**
     * Vérifie le code OTP saisi par l'utilisateur.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ], [
            'email.required' => "L'adresse email est requise.",
            'code.required' => "Le code de vérification est requis.",
            'code.size' => "Le code doit comporter exactement 6 chiffres.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = trim(mb_strtolower($request->input('email')));
        $code = trim($request->input('code'));

        $otp = EmailOtpVerification::where('email', $email)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => "Le code a expiré ou est introuvable. Veuillez cliquer sur 'Renvoyer le code'.",
            ], 422);
        }

        if ($otp->attempts >= 5) {
            return response()->json([
                'success' => false,
                'message' => "Nombre maximum de tentatives dépassé (5/5). Veuillez générer un nouveau code.",
            ], 422);
        }

        if ($otp->code !== $code) {
            $otp->increment('attempts');
            $remaining = 5 - $otp->attempts;
            return response()->json([
                'success' => false,
                'message' => "Code de vérification incorrect. ({$remaining} tentative(s) restante(s))",
            ], 422);
        }

        // Code valide !
        $token = Str::uuid()->toString();
        $otp->update([
            'verified_at' => now(),
            'token' => $token,
        ]);

        Log::info("[OTP EMAIL] Adresse email vérifiée avec succès : {$email}");

        return response()->json([
            'success' => true,
            'verified' => true,
            'verification_token' => $token,
            'message' => "Adresse email vérifiée avec succès !",
        ]);
    }
}
