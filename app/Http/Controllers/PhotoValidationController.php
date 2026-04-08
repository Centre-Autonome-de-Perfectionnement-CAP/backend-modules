<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiResponse;

/**
 * Contrôleur pour la validation des photos d'identité
 * Fait le proxy vers l'API externe pour éviter les problèmes CORS
 */
class PhotoValidationController extends Controller
{
    use ApiResponse;

    /**
     * URL de l'API externe de validation
     */
    private const VALIDATION_API_URL = 'https://spodoptera.xyz/detection-image/validate';

    /**
     * Valide une photo d'identité via l'API externe
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validatePhoto(Request $request)
    {
        try {
            // Vérifier qu'un fichier image a été uploadé
            if (!$request->hasFile('image')) {
                return $this->errorResponse(
                    'Aucune image fournie',
                    400,
                    'NO_IMAGE'
                );
            }

            $image = $request->file('image');

            // Vérifier que c'est bien une image
            if (!$image->isValid() || !str_starts_with($image->getMimeType(), 'image/')) {
                return $this->errorResponse(
                    'Le fichier doit être une image valide',
                    400,
                    'INVALID_IMAGE'
                );
            }

            Log::info('Validation photo - Début', [
                'filename' => $image->getClientOriginalName(),
                'size' => $image->getSize(),
                'mime' => $image->getMimeType(),
            ]);

            // Appeler l'API externe avec le fichier
            $response = Http::timeout(30)
                ->attach(
                    'file',  // L'API externe attend 'file' pas 'image'
                    file_get_contents($image->getRealPath()),
                    $image->getClientOriginalName()
                )
                ->post(self::VALIDATION_API_URL);

            // Vérifier si la requête a réussi
            if ($response->failed()) {
                Log::error('Validation photo - Échec API externe', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $this->errorResponse(
                    'Erreur lors de la validation de la photo',
                    500,
                    'VALIDATION_API_ERROR'
                );
            }

            // Récupérer la réponse de l'API
            $result = $response->json();

            Log::info('Validation photo - Résultat', [
                'success' => $result['success'] ?? false,
                'errors' => $result['errors'] ?? [],
            ]);

            // Retourner le résultat tel quel
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Validation photo - Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Une erreur est survenue lors de la validation',
                500,
                'INTERNAL_ERROR'
            );
        }
    }
}
