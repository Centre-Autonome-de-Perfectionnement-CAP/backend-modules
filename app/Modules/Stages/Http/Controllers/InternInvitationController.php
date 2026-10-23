<?php

namespace App\Modules\Stages\Http\Controllers;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Modules\Stages\Services\InternAuthService;
use App\Modules\Stages\Services\InternInvitationService;
use App\Modules\Stages\Services\StageSettingsService;
use App\Modules\Stages\Support\BusinessHours;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Support\UploadLimit;

/**
 * Stagiaires arrivés avant le système : invitations (responsable de
 * division) et dossier complété par le stagiaire (lien public).
 */
class InternInvitationController extends Controller
{
    use ApiResponse;

    private const TOKEN_RULE = ['required', 'string', 'size:48', 'alpha_num'];

    public function __construct(
        private readonly InternInvitationService $service,
        private readonly InternAuthService $auth,
        private readonly StageSettingsService $settings,
    ) {}

    // ── Responsable de division ─────────────────────────────────────────

    public function index(): JsonResponse
    {
        return $this->successResponse($this->service->list());
    }

    public function invite(Request $request): JsonResponse
    {
        $data = $request->validate(['numeros' => ['required', 'string', 'max:2000']]);
        $phones = preg_split('/[\n,;]+/', $data['numeros']) ?: [];

        return $this->guard(function () use ($phones, $request) {
            $r = $this->service->invite($phones, $request->user());
            $parts = [];
            if ($r['invited']) $parts[] = count($r['invited']) . ' invitation(s) envoyée(s) sur WhatsApp';
            if ($r['already_intern']) $parts[] = count($r['already_intern']) . ' numéro(s) ont déjà un stage en cours';
            if ($r['invalid']) $parts[] = count($r['invalid']) . ' numéro(s) invalide(s)';

            return $this->successResponse($r, implode(' · ', $parts) . '.');
        });
    }

    public function resend(int $id): JsonResponse
    {
        return $this->guard(function () use ($id) {
            $this->service->resend($id);

            return $this->successResponse(null, 'Nouveau lien envoyé sur WhatsApp (valable ' . InternInvitationService::TTL_DAYS . ' jours).');
        });
    }

    public function revoke(int $id): JsonResponse
    {
        return $this->guard(function () use ($id) {
            $this->service->revoke($id);

            return $this->successResponse(null, 'Invitation annulée : le lien ne fonctionne plus.');
        });
    }

    // ── Stagiaire (public) ──────────────────────────────────────────────

    public function show(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => self::TOKEN_RULE]);
        $inv  = $this->service->findValid($data['token']);
        if (!$inv) {
            return $this->errorResponse("Ce lien n'est plus valable. Demandez-en un nouveau au responsable de division.", 410, 'STAGE_INVITE_INVALID');
        }

        return $this->successResponse([
            'phone'   => $this->service->maskPhone($inv->phone),
            'service' => $this->settings->get('default_service'),
            'today'   => BusinessHours::today()->toDateString(),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $text = fn (int $max, bool $required = true) => [$required ? 'required' : 'nullable', 'string', "max:{$max}"];
        $data = $request->validate([
            'token'                 => self::TOKEN_RULE,
            'civilite'              => ['required', Rule::in(['M.', 'Mme', 'Mlle'])],
            'nom'                   => $text(80),
            'prenoms'               => $text(100),
            'date_naissance'        => ['required', 'date', 'before:-14 years', 'after:1930-01-01'],
            'lieu_naissance'        => $text(100),
            'nationalite'           => $text(60),
            'email'                 => ['required', 'email', 'max:254'],
            'etablissement_origine' => $text(150),
            'filiere_niveau'        => $text(150),
            'theme'                 => $text(200, false),
            'service'               => $text(150, false),
            'date_debut'            => ['required', 'date_format:Y-m-d'],
            'date_fin'              => ['required', 'date_format:Y-m-d', 'after_or_equal:date_debut'],
            'password'              => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'note_papier'           => ['nullable', 'file', UploadLimit::rule(), 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png'],
            'consentement'          => ['accepted'],
        ], [
            'password.confirmed'      => 'Les deux mots de passe ne correspondent pas.',
            'date_fin.after_or_equal' => 'La date de fin doit suivre la date de début.',
            'consentement.accepted'   => 'Vous devez accepter le traitement de vos données.',
        ]);
        foreach (['nom', 'prenoms', 'lieu_naissance', 'nationalite', 'etablissement_origine', 'filiere_niveau', 'theme', 'service'] as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim(strip_tags((string) $data[$k])) ?: null;
            }
        }

        return $this->guard(function () use ($data, $request) {
            $r = $this->service->register($data['token'], $data, $request->file('note_papier'));

            return $this->successResponse([
                'token'     => $this->auth->open($r['account'], $request),
                'reference' => $r['request']->reference,
            ], 'Votre stage est enregistré. Bienvenue dans votre espace stagiaire.', 201);
        });
    }

    private function guard(callable $fn): JsonResponse
    {
        try {
            return $fn();
        } catch (BusinessException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode(), $e->getErrorCode());
        }
    }
}
