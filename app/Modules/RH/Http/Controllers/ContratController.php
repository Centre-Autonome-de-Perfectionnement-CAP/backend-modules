<?php

namespace App\Modules\RH\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Contrat;
use App\Modules\Cours\Models\CourseElementProfessor;
use App\Modules\RH\Http\Resources\ProfessorResource;
use Illuminate\Support\Facades\Validator;
use App\Modules\Cours\Models\Program;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Modules\RH\Models\Professor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Modules\RH\Services\ContratStorageService;

class ContratController extends Controller
{
    public function __construct(
        private readonly ContratStorageService $storage
    ) {}

    /**
     * AJOUT (16/08/2026) — contrôle de rôle fin, absent jusqu'ici (route
     * protégée par auth:sanctum seul, n'importe quel compte connecté
     * pouvait gérer les contrats). Pattern identique à
     * WhatsAppAdminController::assertAdmin().
     *
     * ⚠️ 'admin' et 'rh' n'ont été retrouvés dans AUCUN seeder de rôles
     * de ce dépôt (RoleSeeder.php, RoleSeederCompleted.php, UsersSeeder.php) —
     * à vérifier en base avant mise en production. 'responsable-division'
     * est confirmé partout ailleurs dans le code (WorkflowConstants, etc.).
     */
    private const ADMIN_ROLES       = ['admin', 'chef-cap', 'rh'];
    private const MONOGRAPHIE_ROLES = ['admin', 'chef-cap', 'responsable-division', 'chef-division', 'rh', 'rd-fad', 'rd-fc'];

    private function assertAdmin(Request $request): void
    {
        $user = $request->user();
        $slug = $user?->roles?->first()?->slug;

        if (!$user || !in_array($slug, self::ADMIN_ROLES, true)) {
            abort(403, 'Accès réservé aux administrateurs (admin, chef-cap, rh).');
        }
    }

    private function assertCanManageMonographie(Request $request, ?Contrat $contrat = null): void
    {
        $user = $request->user();
        $slug = $user?->roles?->first()?->slug;

        if (!$user || !in_array($slug, self::MONOGRAPHIE_ROLES, true)) {
            abort(403, 'Accès réservé aux administrateurs et responsables de division.');
        }

        // AJOUT — cloisonnement par division : rd-fad/rd-fc ne peuvent agir
        // que sur les contrats de LEUR division.
        $divisionByRole = ['rd-fad' => 'RD-FAD', 'rd-fc' => 'RD-FC'];
        if ($contrat && isset($divisionByRole[$slug]) && $contrat->division !== $divisionByRole[$slug]) {
            abort(403, 'Ce contrat appartient à une autre division.');
        }
    }

    /**
     * Sérialise un contrat en ajoutant le professor via ProfessorResource.
     */
    private function serializeContrat(Contrat $contrat): array
    {
        $data = $contrat->toArray();
        if ($contrat->relationLoaded('professor') && $contrat->professor) {
            $data['professor'] = (new ProfessorResource($contrat->professor))->toArray(request());
        }
        return $data;
    }

    private function processAndStoreSignature(Request $request, Contrat $contrat): string
    {
        // Délègue le stockage au ContratStorageService.
        // CORRECTIF : removeWhiteBackground() était appelée ici mais jamais
        // définie dans ce controller → fatal error. Elle est maintenant dans
        // ContratStorageService::removeWhiteBackground().

        // ── Source 1 : image base64 (canvas dessiné à la main) ──────────────
        if ($request->filled('signature_data')) {
            $base64Data = $request->input('signature_data');

            if (str_contains($base64Data, ',')) {
                $base64Data = explode(',', $base64Data, 2)[1];
            }

            $imageData = base64_decode($base64Data);

            if ($imageData === false) {
                throw new \Exception('Données base64 invalides.');
            }

            $imageData = $this->storage->removeWhiteBackground($imageData);

            return $this->storage->storeSignature(
                $contrat->contrat_number,
                $imageData,
                $contrat->professor_signature_path
            );
        }

        // ── Source 2 : fichier uploadé ────────────────────────────────────────
        if ($request->hasFile('signature_file')) {
            $file      = $request->file('signature_file');
            $imageData = file_get_contents($file->getRealPath());
            $imageData = $this->storage->removeWhiteBackground($imageData, $file->getMimeType());

            return $this->storage->storeSignature(
                $contrat->contrat_number,
                $imageData,
                $contrat->professor_signature_path
            );
        }

        throw new \Exception('Aucune source de signature valide.');
    }


    // ─── EMAIL DE TRANSFERT ───────────────────────────────────────────────────
    /**
     * CORRECTIF (16/08/2026 — résolution merge Benoite) :
     *
     * Avant : le frontend (Contrats.tsx) ouvrait un lien whatsapp://
     * (repli wa.me) après cet appel — l'ADMIN devait cliquer "Envoyer"
     * lui-même, depuis son propre numéro WhatsApp personnel. Aucune trace
     * dans wa_message_log, aucun lien avec le module WhatsApp du projet.
     *
     * Maintenant : l'envoi WhatsApp (texte + PDF du contrat en pièce jointe)
     * se fait ICI, côté serveur, juste après l'email, via notre module
     * WhatsApp/Baileys. Zéro configuration supplémentaire : le module est
     * auto-tagué "RH" par détection de namespace (voir
     * WhatsAppBridgeClient::detectCallingModule()). Visible et filtrable
     * dans l'onglet admin WhatsApp, avec retry en cas d'échec.
     *
     * Un échec WhatsApp ne fait JAMAIS échouer l'envoi (l'email reste le
     * canal de référence, WhatsApp est un plus) — juste loggué.
     */
    public function sendTransferEmail(Request $request, \App\Modules\WhatsApp\Services\WhatsAppBridgeClient $whatsapp, $id)
    {
        $this->assertAdmin($request);

        $contrat = Contrat::with([
            'professor',
            'academicYear',
            'cycle',
            'courseElementProfessors.courseElement',
        ])->find($id);

        if (!$contrat) {
            return response()->json(['success' => false, 'message' => 'Contrat introuvable'], 404);
        }

        if ($contrat->status !== 'transfered') {
            return response()->json([
                'success' => false,
                'message' => "Le contrat doit être en statut transféré pour envoyer l'email",
            ], 400);
        }

        if (empty($contrat->uuid)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat ne possède pas de token UUID. Veuillez le régénérer.',
            ], 400);
        }

        try {
            $professor    = $contrat->professor;
            $frontendBase = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

            // Lien direct vers le contrat (pour signer)
            $contratUrl = "{$frontendBase}/services/notes/professor/contrats/{$contrat->uuid}";

            // Lien de connexion à l'espace professeur (dynamique selon l'env)
            $loginUrl = "{$frontendBase}/services/professor/login";

            // Libellé lisible de la division
            $divisionLabel = match ($contrat->division) {
                'RD-FAD' => 'Formation à Distance (RD-FAD)',
                'RD-FC'  => 'Formation Continue (RD-FC)',
                default  => $contrat->division ?? '—',
            };

            // Regroupement lisible
            $regroupementLabel = match ($contrat->regroupement) {
                '1'     => 'Regroupement I',
                '2'     => 'Regroupement II',
                default => '—',
            };

            // Période
            $startDate = $contrat->start_date
                ? \Carbon\Carbon::parse($contrat->start_date)->format('d/m/Y')
                : '—';
            $endDate = $contrat->end_date
                ? \Carbon\Carbon::parse($contrat->end_date)->format('d/m/Y')
                : '—';

            // Programmes : "CODE : Intitulé"
            $programmes = $contrat->courseElementProfessors
                ->map(fn($cep) => $cep->courseElement
                    ? trim($cep->courseElement->code . ' : ' . $cep->courseElement->name)
                    : null)
                ->filter()
                ->values()
                ->toArray();

            $details = [
                'title'              => "Contrat N°{$contrat->contrat_number} — Signature requise",
                'professor_name'     => $professor->full_name,
                'professor_email'    => $professor->email,
                'contrat_number'     => $contrat->contrat_number,
                'academic_year'      => $contrat->academicYear?->academic_year ?? '—',
                'amount'             => number_format($contrat->amount, 0, ',', ' '),
                'start_date'         => $startDate,
                'end_date'           => $endDate,
                'division'           => $contrat->division ?? '—',
                'division_label'     => $divisionLabel,
                'cycle'              => $contrat->cycle?->name ?? '—',
                'regroupement'       => $contrat->regroupement === '1' ? 'I' : ($contrat->regroupement === '2' ? 'II' : '—'),
                'regroupement_label' => $regroupementLabel,
                'programmes'         => $programmes,
                'contrat_url'        => $contratUrl,
                'login_url'          => $loginUrl,
                'link_expiry_hours'  => 72,
            ];

            Mail::to($professor->email)->send(new \App\Mail\ContratTransferred($details));

            // ── Notification WhatsApp (texte + PDF), non bloquante ────────────
            if (!empty($professor->phone)) {
                $whatsappSent = $whatsapp->send(
                    $professor->phone,
                    $this->buildTransferWhatsAppMessage($details),
                    context: "contrat-transfert:{$contrat->contrat_number}",
                );

                if ($whatsappSent && $contrat->pdf_path && Storage::disk('public')->exists($contrat->pdf_path)) {
                    $whatsapp->sendFile(
                        $professor->phone,
                        disk:     'public',
                        path:     $contrat->pdf_path,
                        fileName: "Contrat_{$contrat->contrat_number}.pdf",
                        caption:  'Votre contrat en pièce jointe.',
                        context:  "contrat-transfert-pdf:{$contrat->contrat_number}",
                    );
                }
            } else {
                Log::info("[RH] Pas d'envoi WhatsApp pour le contrat {$contrat->contrat_number} — professeur sans numéro de téléphone renseigné.");
            }

            return response()->json([
                'success' => true,
                'message' => 'Email envoyé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'envoi de l'email " . $e->getMessage(),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    // ─── helpers ──────────────────────────────────────────────────────────────

    /**
     * Message WhatsApp envoyé automatiquement au professeur lors du transfert
     * d'un contrat — remplace l'ancien lien whatsapp://wa.me côté frontend
     * (Contrats.tsx) qui nécessitait une action manuelle de l'admin.
     */
    private function buildTransferWhatsAppMessage(array $details): string
    {
        // Ligne des programmes : "- CODE : Intitulé"
        $programmesLines = !empty($details['programmes'])
            ? implode("\n", array_map(fn($p) => "- {$p}", $details['programmes']))
            : '- (aucun programme renseigné)';

        return
            "Bonjour {$details['professor_name']},\n\n" .

            "Le Centre Autonome de Perfectionnement (CAP) de l'École Polytechnique " .
            "d'Abomey-Calavi vous a adressé un contrat d'enseignement pour l'année " .
            "académique {$details['academic_year']}. Veuillez en prendre connaissance " .
            "et procéder à sa validation dans les meilleurs délais.\n\n" .

            "Votre contrat de prestation N° {$details['contrat_number']} est disponible " .
            "et en attente de votre signature.\n\n" .

            "Détails du contrat :\n" .
            "Division : {$details['division_label']}\n" .
            "Regroupement : {$details['regroupement_label']}\n" .
            "Cycle : {$details['cycle']}\n" .
            "Période : du {$details['start_date']} au {$details['end_date']}\n" .
            "Montant : {$details['amount']} FCFA\n\n" .

            "Programmes concernés :\n" .
            "{$programmesLines}\n\n" .

            "Procédure de signature :\n" .
            "1. Connectez-vous à votre espace sur le lien ci-dessous\n" .
            "2. Consultez votre e-mail ({$details['professor_email']}) pour le lien de signature sécurisé\n" .
            "3. Vérifiez toutes les informations du contrat\n" .
            "4. Signez électroniquement\n\n" .

            "Accéder à votre espace :\n" .
            "{$details['login_url']}\n\n" .

            "⚠️ Attention : Ce lien est valable pendant {$details['link_expiry_hours']} heures " .
            "à compter de la réception de ce message. Passé ce délai, veuillez contacter " .
            "le service RH du CAP.\n\n" .

            "Ce lien est strictement personnel et confidentiel.\n\n" .

            "Cordialement,\n" .
            "Service des Ressources Humaines — CAP-EPAC";
    }

    private function formatContrat(Contrat $c): array
    {
        $c->load([
            'professor',
            'cycle',
            'academicYear',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ]);

        // Lire TOUTES les colonnes de contrat_programs en une requête
        // car $p->pivot ne charge que les colonnes déclarées dans withPivot()
        // et le modèle Contrat ne déclare pas number_monographie / amount_monographie
        $pivotData = \DB::table('contrat_programs')
            ->where('contrat_id', $c->id)
            ->get()
            ->keyBy('course_element_professor_id');

        return array_merge($c->toArray(), [
            'academic_year'             => $c->academicYear,
            'course_element_professors' => $c->courseElementProfessors->map(function ($p) use ($pivotData) {
                $pivot = $pivotData->get($p->id);
                return [
                    'id'                  => $p->id,
                    'is_primary'          => $p->is_primary ?? false,
                    'label'               => $p->label ?? ($p->courseElement->name ?? ''),
                    'hours'               => $pivot->hours              ?? 0,
                    'amount_program'      => $pivot->amount_program      ?? null,
                    'number_monographie'  => $pivot->number_monographie ?? null,
                    'amount_monographie'  => $pivot->amount_monographie  ?? null,
                    'course_element' => $p->courseElement ? [
                        'id'           => $p->courseElement->id,
                        'name'         => $p->courseElement->name,
                        'code'         => $p->courseElement->code,
                        'hours'        => $p->courseElement->hours ?? 0,
                        'teaching_unit' => $p->courseElement->teachingUnit ? [
                            'id'   => $p->courseElement->teachingUnit->id,
                            'name' => $p->courseElement->teachingUnit->name,
                            'code' => $p->courseElement->teachingUnit->code ?? '',
                        ] : null,
                    ] : null,
                    'class_group' => $p->classGroup ? [
                        'id'   => $p->classGroup->id,
                        'name' => $p->classGroup->name,
                    ] : null,
                ];
            })->values()->all(),
        ]);
    }

    // ─── INDEX ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->assertCanManageMonographie($request);

        $query = Contrat::with([
            'professor',
            'cycle',
            'academicYear',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ]);

        // AJOUT — cloisonnement par division : un responsable de division
        // (rd-fad / rd-fc) ne voit que les contrats de SA division.
        // admin/chef-cap/rh/chef-division/responsable-division (générique)
        // gardent une vue complète, intentionnellement.
        $slug = $request->user()?->roles?->first()?->slug;
        $divisionByRole = ['rd-fad' => 'RD-FAD', 'rd-fc' => 'RD-FC'];
        if (isset($divisionByRole[$slug])) {
            $query->where('division', $divisionByRole[$slug]);
        }

        $contrats = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $contrats->map(fn($c) => $this->formatContrat($c)),
        ]);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->assertAdmin($request);

        $validated = $request->validate([
            'division'                       => 'required|string',
            'professor_id'                   => 'required|integer|exists:professors,id',
            'academic_year_id'               => 'required|integer|exists:academic_years,id',
            'cycle_id'                       => 'required|integer|exists:cycles,id',
            'regroupement'                   => 'required|string',
            'start_date'                     => 'required|date',
            'end_date'                       => 'nullable|date|after_or_equal:start_date',
            'notes'                          => 'nullable|string',
            'course_element_professor_ids'   => 'required|array|min:1',
            'course_element_professor_ids.*' => 'integer|exists:course_element_professor,id',
            'program_amounts'                => 'nullable|array',
            'program_amounts.*'              => 'nullable|numeric|min:0',
            'program_hours'                  => 'nullable|array',
            'program_hours.*'                => 'nullable|numeric|min:0',
            'amount'                         => 'nullable|numeric|min:0',
        ], [
            'course_element_professor_ids.required' => 'Veuillez ajouter au moins un cours au contrat.',
            'course_element_professor_ids.min'      => 'Veuillez ajouter au moins un cours au contrat.',
            'cycle_id.required'                     => 'Le cycle est obligatoire.',
            'division.required'                     => 'La division est obligatoire.',
            'regroupement.required'                 => 'Le regroupement est obligatoire.',
        ]);

        // ── Anti-doublon strict : vérifier si un contrat actif identique existe déjà ──
        $existingDuplicate = Contrat::where('professor_id', $validated['professor_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('cycle_id', $validated['cycle_id'])
            ->where('division', $validated['division'])
            ->where('regroupement', $validated['regroupement'])
            ->whereNotIn('status', ['cancelled', 'resiliated'])
            ->first();

        if ($existingDuplicate) {
            return response()->json([
                'success' => false,
                'message' => "Un contrat actif (N° {$existingDuplicate->contrat_number}) existe déjà pour ce professeur avec la même année académique, le même cycle, le même regroupement et la même division.",
            ], 422);
        }

        // ── Vérification de la cohérence relationnelle des cours ──
        $cepsWithHours = \App\Modules\Cours\Models\CourseElementProfessor::with([
            'courseElement',
            'classGroup.department.cycle',
        ])
        ->whereIn('id', $validated['course_element_professor_ids'])
        ->get()
        ->keyBy('id');

        foreach ($validated['course_element_professor_ids'] as $cepId) {
            $cep = $cepsWithHours->get($cepId);
            if (!$cep) {
                return response()->json([
                    'success' => false,
                    'message' => "Le cours sélectionné (#{$cepId}) est introuvable.",
                ], 422);
            }
            if ($cep->professor_id != $validated['professor_id']) {
                return response()->json([
                    'success' => false,
                    'message' => "Le cours \"{$cep->courseElement?->name}\" n'est pas assigné à cet enseignant.",
                ], 422);
            }
            $courseCycleId = $cep->classGroup?->department?->cycle_id;
            if ($courseCycleId && $courseCycleId != $validated['cycle_id']) {
                $courseCycleName = $cep->classGroup?->department?->cycle?->name ?? "Cycle #{$courseCycleId}";
                return response()->json([
                    'success' => false,
                    'message' => "Incohérence : le cours \"{$cep->courseElement?->name}\" appartient au cycle {$courseCycleName} et ne peut pas être ajouté à un contrat de cycle différent.",
                ], 422);
            }
        }

        // Calcul automatique du montant total depuis program_amounts et program_hours
        $programAmounts = $request->input('program_amounts', []);
        $programAmounts = is_array($programAmounts) ? array_combine(
            array_map('strval', array_keys($programAmounts)),
            array_values($programAmounts)
        ) : [];

        $programHours = $request->input('program_hours', []);
        $programHours = is_array($programHours) ? array_combine(
            array_map('strval', array_keys($programHours)),
            array_values($programHours)
        ) : [];

        $totalAmount = 0;
        foreach ($validated['course_element_professor_ids'] as $cepId) {
            $amt = isset($programAmounts[(string)$cepId]) ? (float) $programAmounts[(string)$cepId] : 0;
            $hrs = isset($programHours[(string)$cepId]) && $programHours[(string)$cepId] !== ''
                ? (float) $programHours[(string)$cepId]
                : ($cepsWithHours[$cepId]?->courseElement?->hours ?? 0);

            if ($amt > 0) {
                $totalAmount += ($hrs > 0 ? $amt * $hrs : $amt);
            }
        }

        if ($totalAmount > 0) {
            $validated['amount'] = $totalAmount;
        } elseif (isset($validated['amount'])) {
            $validated['amount'] = (float) $validated['amount'];
        } else {
            $validated['amount'] = 0;
        }

        // Génération du numéro de contrat
        $lastContrat = Contrat::latest('id')->first();

        if ($lastContrat) {
            $nextNumber = $lastContrat->id + 1;
        } else {
            $nextNumber = 1;
        }

        // Formatage sur 5 caractères avec des zéros devant
        $validated['contrat_number'] = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        $validated['status'] = 'pending';

        $contrat = Contrat::create($validated);

        // Attachement des programmes avec amount_program, hours et program_id par pivot
        $ceps = Program::whereIn('course_element_professor_id', $validated['course_element_professor_ids'])
            ->pluck('id', 'id');

        $syncData = [];
        foreach ($validated['course_element_professor_ids'] as $cepId) {
            $amt   = isset($programAmounts[(string)$cepId]) ? (float) $programAmounts[(string)$cepId] : null;
            $hours = isset($programHours[(string)$cepId]) && $programHours[(string)$cepId] !== ''
                ? (float) $programHours[(string)$cepId]
                : ($cepsWithHours[$cepId]?->courseElement?->hours ?? null);

            // Si les heures ont été saisies/modifiées, les enregistrer aussi sur l'ECUE
            if ($hours !== null && $cepsWithHours[$cepId]?->courseElement) {
                $cepsWithHours[$cepId]->courseElement->update(['hours' => (int) $hours]);
            }

            $syncData[$cepId] = [
                'amount_program' => $amt,
                'hours'          => $hours,
                'program_id'     => $ceps[$cepId] ?? null,
            ];
        }
        $contrat->courseElementProfessors()->sync($syncData);

        return response()->json([
            'success' => true,
            'data'    => $this->formatContrat($contrat->fresh()),
        ], 201);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────

    public function show(Request $request, $id)
    {
        $contrat = Contrat::findOrFail($id);
        $this->assertCanManageMonographie($request, $contrat);

        return response()->json([
            'success' => true,
            'data'    => $this->formatContrat($contrat),
        ]);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $this->assertAdmin($request);

        $contrat = Contrat::findOrFail($id);

        // ── Verrouillage : un contrat validé ou autorisé ne peut plus être modifié ─
        if ($contrat->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat est verrouillé car il a déjà été validé ou autorisé. Aucune modification n\'est possible.',
            ], 403);
        }

        $validated = $request->validate([
            'division'                       => 'required|string',
            'professor_id'                   => 'required|integer|exists:professors,id',
            'academic_year_id'               => 'required|integer|exists:academic_years,id',
            'cycle_id'                       => 'required|integer|exists:cycles,id',
            'regroupement'                   => 'required|string',
            'start_date'                     => 'required|date',
            'end_date'                       => 'nullable|date|after_or_equal:start_date',
            'notes'                          => 'nullable|string',
            'status'                         => 'sometimes|string|in:pending,transfered,signed,ongoing,completed,cancelled',
            'course_element_professor_ids'   => 'required|array|min:1',
            'course_element_professor_ids.*' => 'integer|exists:course_element_professor,id',
            'program_amounts'                => 'nullable|array',
            'program_amounts.*'              => 'nullable|numeric|min:0',
            'program_hours'                  => 'nullable|array',
            'program_hours.*'                => 'nullable|numeric|min:0',
            'amount'                         => 'nullable|numeric|min:0',
        ], [
            'course_element_professor_ids.required' => 'Veuillez ajouter au moins un cours au contrat.',
            'course_element_professor_ids.min'      => 'Veuillez ajouter au moins un cours au contrat.',
            'cycle_id.required'                     => 'Le cycle est obligatoire.',
            'division.required'                     => 'La division est obligatoire.',
            'regroupement.required'                 => 'Le regroupement est obligatoire.',
        ]);

        // ── Anti-doublon strict sur modification ──
        $existingDuplicate = Contrat::where('professor_id', $validated['professor_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('cycle_id', $validated['cycle_id'])
            ->where('division', $validated['division'])
            ->where('regroupement', $validated['regroupement'])
            ->whereNotIn('status', ['cancelled', 'resiliated'])
            ->where('id', '!=', $contrat->id)
            ->first();

        if ($existingDuplicate) {
            return response()->json([
                'success' => false,
                'message' => "Un autre contrat actif (N° {$existingDuplicate->contrat_number}) existe déjà pour cette combinaison de critères.",
            ], 422);
        }

        // ── Vérification de la cohérence relationnelle des cours ──
        $ids = $validated['course_element_professor_ids'] ?? [];
        $cepsWithHours = \App\Modules\Cours\Models\CourseElementProfessor::with([
            'courseElement',
            'classGroup.department.cycle',
        ])
        ->whereIn('id', $ids)
        ->get()
        ->keyBy('id');

        foreach ($ids as $cepId) {
            $cep = $cepsWithHours->get($cepId);
            if (!$cep) {
                return response()->json([
                    'success' => false,
                    'message' => "Le cours sélectionné (#{$cepId}) est introuvable.",
                ], 422);
            }
            if ($cep->professor_id != $validated['professor_id']) {
                return response()->json([
                    'success' => false,
                    'message' => "Le cours \"{$cep->courseElement?->name}\" n'est pas assigné à cet enseignant.",
                ], 422);
            }
            $courseCycleId = $cep->classGroup?->department?->cycle_id;
            if ($courseCycleId && $courseCycleId != $validated['cycle_id']) {
                $courseCycleName = $cep->classGroup?->department?->cycle?->name ?? "Cycle #{$courseCycleId}";
                return response()->json([
                    'success' => false,
                    'message' => "Incohérence : le cours \"{$cep->courseElement?->name}\" appartient au cycle {$courseCycleName} et ne peut pas être ajouté à un contrat de cycle différent.",
                ], 422);
            }
        }

        // Calcul automatique du montant total depuis program_amounts et program_hours
        $programAmounts = $request->input('program_amounts', []);
        $programAmounts = is_array($programAmounts) ? array_combine(
            array_map('strval', array_keys($programAmounts)),
            array_values($programAmounts)
        ) : [];

        $programHours = $request->input('program_hours', []);
        $programHours = is_array($programHours) ? array_combine(
            array_map('strval', array_keys($programHours)),
            array_values($programHours)
        ) : [];

        $totalAmount = 0;
        foreach ($ids as $cepId) {
            $amt = isset($programAmounts[(string)$cepId]) ? (float) $programAmounts[(string)$cepId] : 0;
            $hrs = isset($programHours[(string)$cepId]) && $programHours[(string)$cepId] !== ''
                ? (float) $programHours[(string)$cepId]
                : ($cepsWithHours[$cepId]?->courseElement?->hours ?? 0);

            if ($amt > 0) {
                $totalAmount += ($hrs > 0 ? $amt * $hrs : $amt);
            }
        }

        if ($totalAmount > 0) {
            $validated['amount'] = $totalAmount;
        } elseif (isset($validated['amount'])) {
            $validated['amount'] = (float) $validated['amount'];
        } else {
            $validated['amount'] = $contrat->amount ?? 0;
        }

        $contrat->update($validated);

        // ── Si l'admin remet le contrat en "pending" (relance après rejet),
        //    on efface le motif de rejet pour repartir proprement ──────────────
        if (($validated['status'] ?? null) === 'pending') {
            $contrat->update(['rejection_reason' => null]);
        }

        if (array_key_exists('course_element_professor_ids', $validated)) {
            $ceps = $ids
                ? Program::whereIn('course_element_professor_id', $ids)->pluck('id', 'id')
                : collect();

            $syncData = [];
            foreach ($ids as $cepId) {
                $amt   = isset($programAmounts[(string)$cepId]) ? (float) $programAmounts[(string)$cepId] : null;
                $hours = isset($programHours[(string)$cepId]) && $programHours[(string)$cepId] !== ''
                    ? (float) $programHours[(string)$cepId]
                    : ($cepsWithHours[$cepId]?->courseElement?->hours ?? null);

                // Si les heures ont été saisies/modifiées, les enregistrer aussi sur l'ECUE
                if ($hours !== null && isset($cepsWithHours[$cepId]) && $cepsWithHours[$cepId]->courseElement) {
                    $cepsWithHours[$cepId]->courseElement->update(['hours' => (int) $hours]);
                }

                $syncData[$cepId] = [
                    'amount_program' => $amt,
                    'hours'          => $hours,
                    'program_id'     => $ceps[$cepId] ?? null,
                ];
            }
            $contrat->courseElementProfessors()->sync($syncData);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────

    public function destroy(Request $request, $id)
    {
        $this->assertAdmin($request);

        $contrat = Contrat::findOrFail($id);

        // ── Verrouillage ────────────────────────────────────────────────────
        if ($contrat->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat est verrouillé (validé ou autorisé) et ne peut pas être supprimé.',
            ], 403);
        }

        $contrat->delete();

        return response()->json(['success' => true, 'message' => 'Contrat supprimé.']);
    }

    // ─── SHOW BY TOKEN ────────────────────────────────────────────────────────

    public function showByToken($token)
    {
        $contrat = Contrat::where('uuid', $token)->firstOrFail();
        return response()->json([
            'success' => true,
            'data'    => $this->formatContrat($contrat),
        ]);
    }

    // ─── VALIDATE BY TOKEN (signature électronique du professeur) ─────────────

    public function validateByToken(Request $request, $token)
    {
        $contrat = Contrat::where('uuid', $token)->firstOrFail();

        if ($contrat->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat a déjà été validé.',
            ], 422);
        }

        $request->validate([
            'signature_type' => 'required|in:drawn,uploaded,manual',
            'signature_data' => 'nullable|string',   // base64 pour 'drawn'
            'signature_file' => 'nullable|file|image|max:2048', // fichier pour 'uploaded'
        ]);

        $signaturePath = null;
        $signatureType = $request->input('signature_type');

        if ($signatureType === 'drawn' && $request->filled('signature_data')) {
            $dataUrl   = $request->input('signature_data');
            $base64    = preg_replace('/^data:image\/\w+;base64,/', '', $dataUrl);
            $binary    = base64_decode($base64);
            // removeWhiteBackground + stockage via le service
            $binary        = $this->storage->removeWhiteBackground($binary);
            $signaturePath = $this->storage->storeSignature(
                $contrat->contrat_number,
                $binary,
                $contrat->professor_signature_path
            );

        } elseif ($signatureType === 'uploaded' && $request->hasFile('signature_file')) {
            $file      = $request->file('signature_file');
            $imageData = file_get_contents($file->getRealPath());
            $imageData = $this->storage->removeWhiteBackground($imageData, $file->getMimeType());
            $signaturePath = $this->storage->storeSignature(
                $contrat->contrat_number,
                $imageData,
                $contrat->professor_signature_path
            );
        }
        // Pour 'manual' (signer après impression) : pas de signature numérique

        $contrat->update([
            'is_validated'             => true,
            'validation_date'          => now(),
            'status'                   => 'signed',
            'professor_signature_path' => $signaturePath,
            'professor_signature_type' => $signatureType !== 'manual' ? $signatureType : null,
            'professor_signed_at'      => now(),
        ]);

        // Générer et stocker le PDF du contrat après validation
        $this->generateAndStorePdf($contrat);

        return response()->json([
            'success' => true,
            'message' => 'Contrat validé avec succès.',
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── REJECT BY TOKEN ──────────────────────────────────────────────────────

    public function rejectByToken(Request $request, $token)
    {
        $contrat = Contrat::where('uuid', $token)->firstOrFail();

        if ($contrat->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat a déjà été validé et ne peut plus être rejeté.',
            ], 422);
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:10|max:1000',
        ]);

        $contrat->update([
            'status'           => 'cancelled',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        // ── Notifier l'admin par email du rejet ───────────────────────────────
        try {
            $professor = $contrat->professor;
            $adminEmail = config('mail.admin_email', config('mail.from.address'));

        } catch (\Exception $e) {
            // Ne bloque pas la réponse si l'email échoue
            Log::warning("Email de notification de rejet non envoyé pour contrat #{$contrat->id} : " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Contrat rejeté. Le motif a été transmis au CAP.',
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── DOWNLOAD BY TOKEN ────────────────────────────────────────────────────

    public function downloadByToken($token)
    {
        $contrat = Contrat::where('uuid', $token)->firstOrFail();

        // Si un PDF stocké existe, le retourner directement
        if ($contrat->pdf_path && Storage::disk('public')->exists($contrat->pdf_path)) {
            return Storage::disk('public')->download(
                $contrat->pdf_path,
                'Contrat_' . $contrat->contrat_number . '.pdf'
            );
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun PDF disponible pour ce contrat.',
        ], 404);
    }

    // ─── STREAM PDF CONTRAT (admin/prof connecté, par ID) ─────────────────────
    //
    // Remplace l'accès direct via le symlink /storage/contrats/xxx.pdf
    // qui retournait une page blanche car le fichier était servi sans les
    // bons headers ou était vide (DomPDF échouait sur les logos relatifs).
    // Cette route streame le fichier via Laravel avec les headers corrects.

    public function streamPdf(Request $request, $id)
    {
        $this->assertCanManageMonographie($request);

        $contrat = Contrat::findOrFail($id);

        if (!$contrat->pdf_path || !Storage::disk('public')->exists($contrat->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun PDF disponible pour ce contrat.',
            ], 404);
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';
        $filename    = 'Contrat_' . $contrat->contrat_number . '.pdf';

        return Storage::disk('public')->response(
            $contrat->pdf_path,
            $filename,
            ['Content-Type' => 'application/pdf'],
            $disposition
        );
    }

    // ─── STREAM SUPPORT DE COURS (admin/prof connecté) ────────────────────────
    //
    // Les supports sont stockés dans supports/{uuid}-support-{contratId}-{programId}.pdf
    // Accessible au professeur et à l'admin via auth:sanctum.

    public function streamSupport(Request $request, $contratId, $programId, $index)
    {
        $pivot = \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->first();

        if (!$pivot) {
            return response()->json(['success' => false, 'message' => 'Programme introuvable.'], 404);
        }

        $supports = json_decode($pivot->course_support_file ?? '[]', true) ?? [];
        $index    = (int) $index;

        if (!isset($supports[$index])) {
            return response()->json(['success' => false, 'message' => 'Support introuvable.'], 404);
        }

        $filePath = $supports[$index]['file'] ?? null;

        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Fichier introuvable sur le serveur.'], 404);
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';
        $filename    = ($supports[$index]['title'] ?? 'support') . '.pdf';

        return Storage::disk('public')->response(
            $filePath,
            $filename,
            ['Content-Type' => 'application/pdf'],
            $disposition
        );
    }

    // ─── STREAM FACTURE NORMALISÉE ─────────────────────────────────────────────

    public function streamFacture(Request $request, $contratId, $index)
    {
        $contrat = Contrat::findOrFail($contratId);

        // Vérifier que l'utilisateur est le professeur du contrat ou un admin
        $user      = $request->user();
        $professor = Professor::where('email', $user->email)->first();
        $isOwner   = $professor && $professor->id === $contrat->professor_id;
        $userSlug  = $user?->roles?->first()?->slug;
        $isAdmin   = in_array($userSlug, self::ADMIN_ROLES + self::MONOGRAPHIE_ROLES, true);

        if (!$isOwner && !$isAdmin) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        $factures = $contrat->factures_normalisees ?? [];
        $index    = (int) $index;

        if (!isset($factures[$index])) {
            return response()->json(['success' => false, 'message' => 'Facture introuvable.'], 404);
        }

        $item = $factures[$index];

        // Rétrocompatibilité : ancienne entrée string
        if (is_string($item)) {
            $filePath = 'factures_normalisees/' . $item;
            $name     = $item;
        } else {
            $filePath = $item['path'] ?? null;
            $name     = $item['name'] ?? 'facture';
        }

        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'Fichier introuvable sur le serveur.'], 404);
        }

        $ext         = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'pdf';
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';
        $mimeType    = Storage::disk('public')->mimeType($filePath) ?: 'application/octet-stream';

        return Storage::disk('public')->response(
            $filePath,
            $name,
            ['Content-Type' => $mimeType],
            $disposition
        );
    }

    // ─── AUTHORIZE (admin) ────────────────────────────────────────────────────

    public function authorizeContrat(Request $request, $id)
    {
        $this->assertAdmin($request);

        $contrat = Contrat::findOrFail($id);

        if (!$contrat->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Le contrat doit d\'abord être validé (signé) par le professeur avant d\'être autorisé.',
            ], 422);
        }

        $contrat->update([
            'is_authorized'      => true,
            'authorization_date' => now(),
            'status'             => 'ongoing',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contrat autorisé avec succès.',
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── UPLOAD PDF FINAL (admin remplace le PDF par un fichier uploadé) ──────

    public function uploadPdf(Request $request, $id)
    {
        $this->assertAdmin($request);

        $contrat = Contrat::findOrFail($id);

        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // max 10 Mo
        ]);

        // Supprimer l'ancien PDF s'il existe (quel que soit son chemin legacy)
        if ($contrat->pdf_path) {
            $this->storage->delete($contrat->pdf_path);
        }

        $pdfPath = $this->storage->storePdfUpload($contrat->contrat_number, $request->file('pdf_file'));

        $contrat->update([
            'pdf_path'        => $pdfPath,
            'pdf_uploaded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PDF mis à jour avec succès.',
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── PROFESSOR PROGRAMS ───────────────────────────────────────────────────

    public function professorPrograms(Request $request, $professorId)
    {
        // assertAdmin retiré intentionnellement : cette route est également
        // appelée depuis le formulaire de création de contrat où l'utilisateur
        // connecté peut être admin, rh ou responsable-division. La protection
        // est assurée par auth:sanctum sur la route (déplacée dans le groupe protégé).

        $professor = Professor::findOrFail($professorId);

        $programs = \App\Modules\Cours\Models\CourseElementProfessor::with([
            'courseElement.teachingUnit',
            'classGroup.academicYear',
            'classGroup.department.cycle',
        ])->where('professor_id', $professorId)->get();

        return response()->json([
            'success' => true,
            'data'    => $programs->map(function ($p) {
                $element      = $p->courseElement;
                $classGroup   = $p->classGroup;
                $department   = $classGroup?->department;
                $cycle        = $department?->cycle;
                $academicYear = $classGroup?->academicYear;

                return [
                    'id'                 => $p->id,
                    'is_primary'         => $p->is_primary ?? false,
                    'label'              => $element?->name ?? '',
                    'academic_year_id'   => $academicYear?->id ?? null,
                    'academic_year_name' => $academicYear?->academic_year ?? $academicYear?->libelle ?? null,
                    'cycle_id'           => $cycle?->id ?? null,
                    'cycle_name'         => $cycle?->name ?? null,
                    'department_id'      => $department?->id ?? null,
                    'department_name'    => $department?->name ?? null,
                    'class_group_id'     => $classGroup?->id ?? null,
                    'class_group_name'   => $classGroup?->group_name ?? $classGroup?->name ?? null,
                    'course_element'     => $element ? [
                        'id'            => $element->id,
                        'name'          => $element->name,
                        'code'          => $element->code,
                        'hours'         => $element->hours ?? 0,
                        'credits'       => $element->credits ?? null,
                        'teaching_unit' => $element->teachingUnit ? [
                            'id'   => $element->teachingUnit->id,
                            'name' => $element->teachingUnit->name,
                            'code' => $element->teachingUnit->code ?? '',
                        ] : null,
                    ] : null,
                    'class_group'        => $classGroup ? [
                        'id'         => $classGroup->id,
                        'name'       => $classGroup->name ?? $classGroup->group_name,
                        'group_name' => $classGroup->group_name,
                    ] : null,
                ];
            })->values(),
        ]);
    }

    // ─── MY CONTRATS (professeur connecté) ────────────────────────────────────

    public function myContrats(Request $request)
    {
        $user = $request->user();

        // Rechercher le professeur lié à l'utilisateur connecté
        $professor = Professor::where('email', $user->email)->first();

        if (!$professor) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $contrats = Contrat::with([
            'professor',
            'cycle',
            'academicYear',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])->where('professor_id', $professor->id)->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $contrats->map(fn($c) => $this->formatContrat($c)),
        ]);
    }

    // ─── GENERATE AND STORE PDF ───────────────────────────────────────────────

    private function generateAndStorePdf(Contrat $contrat): void
    {
        try {
            $contrat->load([
                'professor',
                'cycle',
                'academicYear',
                'courseElementProfessors.courseElement.teachingUnit',
                'courseElementProfessors.classGroup',
            ]);

            if (!$contrat) {
                throw new \Exception('Contrat invalide');
            }

            $html = view('pdf.contrat', ['contrat' => $contrat])->render();

            if (empty($html)) {
                throw new \Exception('Le rendu HTML est vide');
            }

            // ───────────── DomPDF ─────────────
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                try {
                    $pdf      = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                    $pdfPath  = $this->storage->storePdfContent($contrat->contrat_number, $pdf->output());
                    $contrat->update([
                        'pdf_path'        => $pdfPath,
                        'pdf_uploaded_at' => now(),
                    ]);
                    Log::info("PDF généré avec DomPDF pour contrat #{$contrat->id}");
                    return;
                } catch (\Exception $e) {
                    Log::error("Erreur DomPDF contrat #{$contrat->id} : " . $e->getMessage());
                }
            }

            // ───────────── Snappy ─────────────
            if (app()->bound('snappy.pdf')) {
                try {
                    $snappy  = app('snappy.pdf');
                    $output  = $snappy->getOutputFromHtml($html);
                    $pdfPath = $this->storage->storePdfContent($contrat->contrat_number, $output);
                    $contrat->update([
                        'pdf_path'        => $pdfPath,
                        'pdf_uploaded_at' => now(),
                    ]);
                    Log::info("PDF généré avec Snappy pour contrat #{$contrat->id}");
                    return;
                } catch (\Exception $e) {
                    Log::error("Erreur Snappy contrat #{$contrat->id} : " . $e->getMessage());
                }
            }

            Log::warning("Aucune librairie PDF disponible pour contrat #{$contrat->id}");

        } catch (\Throwable $e) {
            Log::error("Erreur globale génération PDF contrat #{$contrat->id} : " . $e->getMessage());
        }
    }
  public function myFactures(Request $request)
{
    $user = $request->user();

    $professor = Professor::where('email', $user->email)->first();

    if (!$professor) {
        return response()->json(['success' => true, 'data' => []]);
    }

    $contrats = Contrat::where('professor_id', $professor->id)
        ->whereNotNull('factures_normalisees')
        ->where('factures_normalisees', '!=', '[]')
        ->with(['academicYear', 'cycle'])
        ->latest()
        ->get();

    $data = $contrats->map(function ($c) {
        $factures = array_map(function ($item) {
            if (is_string($item)) {
                return [
                    'name' => $item,
                    'path' => 'factures_normalisees/' . $item,
                    'type' => 'facture',
                    'url'  => \Storage::disk('public')->url('factures_normalisees/' . $item),
                ];
            }
            return $item;
        }, $c->factures_normalisees ?? []);

        return [
            'id'             => $c->id,
            'contrat_number' => $c->contrat_number,
            'status'         => $c->status,
            'amount'         => $c->amount,
            'start_date'     => $c->start_date,
            'end_date'       => $c->end_date,
            'academic_year'  => $c->academicYear?->academic_year,
            'cycle'          => $c->cycle?->name,
            'factures'       => $factures,
            'uploaded_at'    => $c->updated_at,
        ];
    });

    return response()->json(['success' => true, 'data' => $data]);
}

    public function listProgramSupports(Request $request, $contratId, $programId)
    {
        // Accessible au professeur et à l'admin — pas d'assertAdmin.

        $contrat = \App\Modules\RH\Models\Contrat::findOrFail($contratId);

        // Récupérer la ligne pivot dans contrat_programs
        $pivot = \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->first();

        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Programme introuvable pour ce contrat.',
            ], 404);
        }

        $supports = json_decode($pivot->course_support_file ?? '[]', true) ?? [];

        // Reconstruire les URLs publiques
        $supports = array_map(function ($s) {
            if (!empty($s['file']) && Storage::disk('public')->exists($s['file'])) {
                $s['url'] = Storage::disk('public')->url($s['file']);
            }
            return $s;
        }, $supports);

        return response()->json([
            'success'            => true,
            'data'               => array_values($supports),
            'number_monographie' => $pivot->number_monographie ?? null,
            'amount_monographie' => $pivot->amount_monographie  ?? null,
        ]);
    }

    // ─── AJOUT d'un support ───────────────────────────────────────────────────

    /**
     * POST /api/rh/contrats/{contratId}/programs/{programId}/supports
     *
     * Body (multipart/form-data) :
     *   - title    : string (obligatoire)
     *   - pdf_file : file PDF (obligatoire)
     */
    public function addProgramSupport(Request $request, $contratId, $programId)
    {
        // Pas d'assertAdmin ici : le professeur connecté doit pouvoir ajouter
        // ses propres supports de cours sur ses contrats.
        // La vérification d'appartenance se fait via la table contrat_programs.

        $contrat = \App\Modules\RH\Models\Contrat::findOrFail($contratId);

        $request->validate([
            'title'    => 'required|string|max:255',
            'pdf_file' => 'required|file|mimes:pdf|max:20480', // max 20 Mo
        ]);

        // Récupérer la ligne pivot
        $pivot = \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->first();

        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Programme introuvable pour ce contrat.',
            ], 404);
        }

        // Charger les supports existants
        $supports = json_decode($pivot->course_support_file ?? '[]', true) ?? [];

        // Stocker le fichier PDF via le service (structure normalisée)
        $title = $request->input('title');
        $path  = $this->storage->storeSupportFile(
            $contrat->contrat_number,
            (int) $programId,
            $request->file('pdf_file'),
            $title
        );

        // Ajouter l'entrée au tableau
        $supports[] = [
            'title' => $title,
            'file'  => $path,
        ];

        // Mettre à jour la colonne JSON + updated_by
        \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->update([
                'course_support_file' => json_encode(array_values($supports)),
                'updated_by'          => 'professor',
                'updated_at'          => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Support de cours ajouté avec succès.',
            'data'    => [
                'title' => $title,
                'file'  => $path,
                'url'   => $this->storage->url($path),
            ],
        ], 201);
    }

    // ─── SUPPRESSION d'un support ─────────────────────────────────────────────

    /**
     * DELETE /api/rh/contrats/{contratId}/programs/{programId}/supports/{index}
     *
     * Supprime l'entrée à l'index {index} du tableau JSON et efface le fichier.
     */
    public function deleteProgramSupport(Request $request, $contratId, $programId, $index)
    {
        // Accessible au professeur et à l'admin — pas d'assertAdmin.

        $pivot = \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->first();

        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Programme introuvable pour ce contrat.',
            ], 404);
        }

        $supports = json_decode($pivot->course_support_file ?? '[]', true) ?? [];
        $index    = (int) $index;

        if (!isset($supports[$index])) {
            return response()->json([
                'success' => false,
                'message' => "Support introuvable à l'index {$index}.",
            ], 404);
        }

        // Supprimer le fichier physique via le service
        $filePath = $supports[$index]['file'] ?? null;
        if ($filePath) {
            $this->storage->delete($filePath);
        }

        // Retirer du tableau et ré-indexer
        array_splice($supports, $index, 1);

        \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->update([
                'course_support_file' => json_encode(array_values($supports)),
                'updated_by'          => 'professor',
                'updated_at'          => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Support supprimé avec succès.',
        ]);
    }

    // ─── MONOGRAPHIE d'un programme ───────────────────────────────────────────

    /**
     * PUT /api/rh/contrats/{contratId}/programs/{programId}/monographie
     *
     * Met à jour number_monographie et amount_monographie sur la ligne
     * correspondante de la table contrat_programs.
     */
    public function updateProgramMonographie(Request $request, $contratId, $programId)
    {
        if (is_numeric($contratId)) {
            $this->assertCanManageMonographie($request, Contrat::find($contratId));
        } else {
            $this->assertCanManageMonographie($request);
        }

        try {
            $validated = $request->validate([
                'number_monographie' => 'required|integer|min:0',
                'amount_monographie' => 'required|numeric|min:0',
            ]);

            if (!is_numeric($contratId) || !is_numeric($programId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Identifiants invalides.',
                ], 400);
            }

            \DB::beginTransaction();

            $pivot = \DB::table('contrat_programs')
                ->where('contrat_id', $contratId)
                ->where('course_element_professor_id', $programId)
                ->first();

            if (!$pivot) {
                \DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Programme introuvable pour ce contrat.',
                ], 404);
            }

            \DB::table('contrat_programs')
                ->where('contrat_id', $contratId)
                ->where('course_element_professor_id', $programId)
                ->update([
                    'number_monographie' => (int)   $validated['number_monographie'],
                    'amount_monographie' => (float) $validated['amount_monographie'],
                    'updated_at'         => now(),
                ]);

            $updated = \DB::table('contrat_programs')
                ->where('contrat_id', $contratId)
                ->where('course_element_professor_id', $programId)
                ->first();

            \DB::commit();

            return response()->json([
                'success'            => true,
                'message'            => 'Monographie mise à jour avec succès.',
                'number_monographie' => $updated->number_monographie,
                'amount_monographie' => $updated->amount_monographie,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\QueryException $e) {
            \DB::rollBack();
            Log::error('Erreur SQL updateProgramMonographie', [
                'message'  => $e->getMessage(),
                'sql'      => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur SQL : ' . $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Erreur updateProgramMonographie', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne : ' . $e->getMessage(),
            ], 500);
        }
    }

    public function uploadFacturesNormalisees(Request $request, $id)
    {
        // Accessible au professeur et à l'admin — pas d'assertAdmin.

        $request->validate([
            'factures_normalisees'   => 'required|array|min:1',
            'factures_normalisees.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'replace'                => 'nullable|string',
        ]);

        $contrat  = Contrat::findOrFail($id);
        $existing = $contrat->factures_normalisees ?? [];

        // Rétrocompatibilité : normaliser les anciennes entrées string
        $existing = array_map(function ($item) {
            if (is_string($item)) {
                return ['name' => $item, 'path' => 'factures_normalisees/' . $item, 'type' => 'facture'];
            }
            return $item;
        }, $existing);

        // Accepte "1", "true", "yes", true
        $replace = filter_var($request->input('replace', false), FILTER_VALIDATE_BOOLEAN);

        // Vérifier si une facture normalisée existe déjà
        $existingFacture = collect($existing)->first(
            fn($f) => isset($f['type']) && $f['type'] === 'facture'
        );

        // Si une facture existe et que le remplacement n'est pas confirmé → 422
        if ($existingFacture && !$replace) {
            return response()->json([
                'success'       => false,
                'has_existing'  => true,
                'existing_name' => $existingFacture['name'] ?? 'fichier existant',
                'message'       => 'Une facture existe déjà pour ce contrat.',
            ], 422);
        }

        // Si remplacement confirmé : supprimer les anciens fichiers du disque
        if ($replace) {
            foreach ($existing as $item) {
                if (!empty($item['path'])) {
                    $this->storage->delete($item['path']);
                }
            }
            $existing = [];
        }

        // Uploader les nouveaux fichiers via le service
        $defaultTypes = ['facture', 'rib'];
        $newEntries   = [];

        foreach ($request->file('factures_normalisees') as $index => $file) {
            $original = $file->getClientOriginalName();
            $fileType = $request->input("type.{$index}") ?? ($defaultTypes[$index] ?? 'autre');

            $path = $this->storage->storeFacture(
                $contrat->contrat_number,
                $file,
                $fileType
            );

            $newEntries[] = [
                'name' => $original,
                'path' => $path,
                'type' => $fileType,
                'url'  => $this->storage->url($path),
            ];
        }

        $contrat->factures_normalisees = array_merge($existing, $newEntries);
        $contrat->save();

        return response()->json([
            'success' => true,
            'message' => $replace ? 'Facture remplacée avec succès.' : 'Factures uploadées avec succès.',
            'data'    => $contrat->factures_normalisees,
        ]);
    }

}
