<?php

namespace App\Modules\Demandes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

/**
 * DocumentRequestController — avec notifications inter-acteurs
 *
 * NOUVEAU : à chaque transition validate/transmit/sign,
 * un mail automatique est envoyé au prochain acteur du workflow
 * (son email est dans la table `users`).
 *
 * Le mail lui dit : "X vous a transmis un dossier, allez consulter."
 *
 * FLUX :
 *   pending
 *     → comptable_review              (Secrétaire valide)
 *     → chef_division_review          (Comptable valide)
 *     → chef_cap_review               (Chef Division valide)
 *     → sec_dir_adjointe_review       (Chef CAP paraphe)
 *     → directrice_adjointe_review    (Sec. DA transmet)
 *     → sec_directeur_review          (Directrice Adjointe signe)
 *     → directeur_review              (Sec. Dir. transmet)
 *     → ready                         (Directeur signe)
 *     → delivered                     (Secrétaire remet)
 *
 *   Rejet intermédiaire → secretaire_correction  (notifie la Secrétaire)
 *   Rejet définitif     → rejected               (notifie l'étudiant)
 */
class DocumentRequestController extends Controller
{
    use ApiResponse;

    private const ROLE_LABELS = [
        'chef-division'       => 'Chef Division',
        'comptable'           => 'Comptable',
        'chef-cap'            => 'Chef CAP',
        'sec-da'              => 'Secrétaire Directrice Adjointe',
        'directrice-adjointe' => 'Directrice Adjointe',
        'sec-dir'             => 'Secrétaire Directeur',
        'directeur'           => 'Directeur',
        'secretaire'          => 'Secrétaire',
    ];

    private const TYPE_LABELS = [
        'attestation_passage'     => 'Attestation de Passage',
        'attestation_definitive'  => 'Attestation Définitive',
        'attestation_inscription' => "Attestation d'Inscription",
        'bulletin_notes'          => 'Bulletin de Notes',
    ];

    /**
     * Mapping : nouveau statut → slug du rôle destinataire
     * Utilisé pour retrouver l'email du prochain acteur dans `users`.
     */
    private const STATUS_TO_ROLE = [
        'comptable_review'            => 'comptable',
        'chef_division_review'        => 'chef-division',
        'chef_cap_review'             => 'chef-cap',
        'sec_dir_adjointe_review'     => 'sec-da',
        'directrice_adjointe_review'  => 'directrice-adjointe',
        'sec_directeur_review'        => 'sec-dir',
        'directeur_review'            => 'directeur',
        'secretaire_correction'       => 'secretaire',   // retour vers secrétaire
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $role = $user->roles->first()?->slug ?? null;

        $query = DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->leftJoin('departments as dept', 'ps.department_id', '=', 'dept.id')
            ->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id')
            ->select([
                'dr.id',
                'dr.reference',
                'dr.type',
                'dr.status',
                'dr.email',
                'dr.files',
                'dr.submitted_at',
                'dr.updated_at',
                'dr.rejected_reason',
                'dr.rejected_by',
                'dr.chef_division_comment',
                'dr.secretaire_comment',
                'dr.comptable_comment',
                'dr.signature_type',
                'dr.department_name',
                'dr.chef_division_type',
                'dr.chef_division_reviewed_at',
                'dr.comptable_reviewed_at',
                'dr.chef_cap_reviewed_at',
                'dr.sec_da_reviewed_at',
                'dr.directrice_adjointe_reviewed_at',
                'dr.sec_directeur_reviewed_at',
                'dr.delivered_at',
                'dr.student_pending_student_id',
                'pi.last_name',
                'pi.first_names',
                DB::raw("(SELECT student_id_number FROM students s
                          JOIN student_pending_student sps2 ON sps2.student_id = s.id
                          WHERE sps2.id = dr.student_pending_student_id LIMIT 1) as matricule"),
                'dept.name as department',
                'ay.academic_year',
            ]);

        $visibleStatuses = $this->getVisibleStatusesForRole($role);
        if (!empty($visibleStatuses)) {
            $query->whereIn('dr.status', $visibleStatuses);
        }

        if ($role === 'chef-division' && $user->chef_division_type) {
            $query->where('dr.chef_division_type', $user->chef_division_type);
        }

        if ($request->filled('status')) {
            $query->where('dr.status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('dr.type', $request->type);
        }
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('pi.last_name', 'like', $s)
                  ->orWhere('pi.first_names', 'like', $s)
                  ->orWhere('dr.reference', 'like', $s);
            });
        }

        $demandes = $query->orderBy('dr.updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $demandes,
            'role'    => $role,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHOW
    // ─────────────────────────────────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $demande = DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->leftJoin('departments as dept', 'ps.department_id', '=', 'dept.id')
            ->leftJoin('academic_years as ay', 'dr.academic_year_id', '=', 'ay.id')
            ->where('dr.id', $id)
            ->select([
                'dr.*',
                'pi.last_name',
                'pi.first_names',
                'pi.birth_date',
                'dept.name as department',
                'ay.academic_year',
                DB::raw("(SELECT student_id_number FROM students s
                          JOIN student_pending_student sps2 ON sps2.student_id = s.id
                          WHERE sps2.id = dr.student_pending_student_id LIMIT 1) as matricule"),
                DB::raw("ps.level as study_level"),
            ])
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        return response()->json(['success' => true, 'data' => $demande]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSITION
    // ─────────────────────────────────────────────────────────────────────────

    public function transition(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'action'             => 'required|string',
            'motif'              => 'nullable|string|max:1000',
            'signature_type'     => 'nullable|in:paraphe,signature',
            'chef_division_type' => 'nullable|in:formation_distance,formation_continue',
            'resend_to'          => 'nullable|string',
        ]);

        $user   = Auth::user();
        $role   = $user->roles->first()?->slug ?? null;
        $action = $request->action;
        $motif  = $request->motif;

        $demande = DB::table('document_requests')->where('id', $id)->first();
        if (!$demande) {
            return response()->json(['message' => 'Demande introuvable.'], 404);
        }

        if (!$this->isActionAllowed($role, $action, $demande->status)) {
            return response()->json([
                'message' => "Action « {$action} » non autorisée pour le rôle « {$role} » depuis le statut « {$demande->status} ».",
            ], 403);
        }

        $update   = ['updated_at' => now()];
        $mailData = null;       // mail vers l'étudiant
        $newStatus = null;      // pour déduire le destinataire acteur

        switch ($action) {

            // ── SECRÉTAIRE ────────────────────────────────────────────────────

            case 'secretaire_validate':
                $newStatus                            = 'comptable_review';
                $update['status']                     = $newStatus;
                $update['processed_by_secretaire_id'] = $user->id;
                break;

            case 'secretaire_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire pour rejeter.'], 422);
                }
                $update['status']             = 'rejected';
                $update['rejected_reason']    = $motif;
                $update['secretaire_comment'] = $motif;
                $update['rejected_by']        = 'Secrétaire';
                $mailData = $this->rejectedMail($demande, $motif);
                break;

            case 'secretaire_reject_final':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $update['status']             = 'rejected';
                $update['rejected_reason']    = $motif;
                $update['secretaire_comment'] = $motif;
                $update['rejected_by']        = 'Secrétaire';
                $mailData = $this->rejectedMail($demande, $motif);
                break;

            case 'secretaire_resend':
                $statusMap = [
                    'comptable'           => 'comptable_review',
                    'chef_division'       => 'chef_division_review',
                    'chef_cap'            => 'chef_cap_review',
                    'sec_da'              => 'sec_dir_adjointe_review',
                    'directrice_adjointe' => 'directrice_adjointe_review',
                    'sec_directeur'       => 'sec_directeur_review',
                    'directeur'           => 'directeur_review',
                ];
                $newStatus = $statusMap[$request->resend_to] ?? null;
                if (!$newStatus) {
                    return response()->json(['message' => 'Destination invalide.'], 422);
                }
                $update['status']          = $newStatus;
                $update['rejected_by']     = null;
                $update['rejected_reason'] = null;

                if ($request->resend_to === 'chef_division' && $request->filled('chef_division_type')) {
                    $update['chef_division_type'] = $request->chef_division_type;
                }
                break;

            case 'secretaire_deliver':
                $update['status']       = 'delivered';
                $update['delivered_at'] = now();
                $mailData = $this->deliveredMail($demande);
                break;

            // ── COMPTABLE ─────────────────────────────────────────────────────

            case 'comptable_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire.'], 422);
                }
                $newStatus                           = 'secretaire_correction';
                $update['status']                    = $newStatus;
                $update['comptable_comment']         = $motif;
                $update['rejected_reason']           = $motif;
                $update['rejected_by']               = self::ROLE_LABELS[$role] ?? $role;
                $update['comptable_reviewed_at']     = now();
                $update['processed_by_comptable_id'] = $user->id;
                break;

            case 'comptable_validate':
                if (!$request->filled('chef_division_type')) {
                    return response()->json(['message' => 'Vous devez sélectionner le Responsable Division.'], 422);
                }
                $newStatus                           = 'chef_division_review';
                $update['status']                    = $newStatus;
                $update['chef_division_type']        = $request->chef_division_type;
                $update['comptable_reviewed_at']     = now();
                $update['processed_by_comptable_id'] = $user->id;
                break;

            // ── CHEF DIVISION ─────────────────────────────────────────────────

            case 'chef_division_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un commentaire est obligatoire.'], 422);
                }
                $newStatus                               = 'secretaire_correction';
                $update['status']                        = $newStatus;
                $update['chef_division_comment']         = $motif;
                $update['rejected_reason']               = $motif;
                $update['rejected_by']                   = self::ROLE_LABELS[$role] ?? $role;
                $update['chef_division_reviewed_at']     = now();
                $update['processed_by_chef_division_id'] = $user->id;
                break;

            case 'chef_division_validate':
                $newStatus                               = 'chef_cap_review';
                $update['status']                        = $newStatus;
                $update['chef_division_reviewed_at']     = now();
                $update['processed_by_chef_division_id'] = $user->id;
                break;

            // ── CHEF CAP ──────────────────────────────────────────────────────

            case 'chef_cap_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                          = 'secretaire_correction';
                $update['status']                   = $newStatus;
                $update['rejected_reason']          = $motif;
                $update['rejected_by']              = self::ROLE_LABELS[$role] ?? $role;
                $update['chef_cap_reviewed_at']     = now();
                $update['processed_by_chef_cap_id'] = $user->id;
                break;

            case 'chef_cap_sign':
                $sigType                            = $request->signature_type ?? 'signature';
                $update['signature_type']           = $sigType;
                $update['chef_cap_reviewed_at']     = now();
                $update['processed_by_chef_cap_id'] = $user->id;
                if ($sigType === 'paraphe') {
                    $newStatus        = 'sec_dir_adjointe_review';
                    $update['status'] = $newStatus;
                } else {
                    $update['status'] = 'ready';
                    $mailData = $this->readyMail($demande);
                }
                break;

            // ── SEC. DIRECTRICE ADJOINTE ──────────────────────────────────────

            case 'sec_da_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                    = 'secretaire_correction';
                $update['status']             = $newStatus;
                $update['rejected_reason']    = $motif;
                $update['rejected_by']        = self::ROLE_LABELS[$role] ?? $role;
                $update['sec_da_reviewed_at'] = now();
                break;

            case 'sec_da_transmit':
                $newStatus                    = 'directrice_adjointe_review';
                $update['status']             = $newStatus;
                $update['sec_da_reviewed_at'] = now();
                break;

            // ── DIRECTRICE ADJOINTE ───────────────────────────────────────────

            case 'directrice_adjointe_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                                 = 'secretaire_correction';
                $update['status']                          = $newStatus;
                $update['rejected_reason']                 = $motif;
                $update['rejected_by']                     = self::ROLE_LABELS[$role] ?? $role;
                $update['directrice_adjointe_reviewed_at'] = now();
                break;

            case 'directrice_adjointe_sign':
                $newStatus                                 = 'sec_directeur_review';
                $update['status']                          = $newStatus;
                $update['directrice_adjointe_reviewed_at'] = now();
                break;

            // ── SEC. DIRECTEUR ────────────────────────────────────────────────

            case 'sec_directeur_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                           = 'secretaire_correction';
                $update['status']                    = $newStatus;
                $update['rejected_reason']           = $motif;
                $update['rejected_by']               = self::ROLE_LABELS[$role] ?? $role;
                $update['sec_directeur_reviewed_at'] = now();
                break;

            case 'sec_directeur_transmit':
                $newStatus                           = 'directeur_review';
                $update['status']                    = $newStatus;
                $update['sec_directeur_reviewed_at'] = now();
                break;

            // ── DIRECTEUR ─────────────────────────────────────────────────────

            case 'directeur_reject':
                if (empty($motif)) {
                    return response()->json(['message' => 'Un motif est obligatoire.'], 422);
                }
                $newStatus                 = 'secretaire_correction';
                $update['status']          = $newStatus;
                $update['rejected_reason'] = $motif;
                $update['rejected_by']     = self::ROLE_LABELS[$role] ?? $role;
                break;

            case 'directeur_sign':
                $sigType                         = $request->signature_type ?? 'signature';
                $update['signature_type']        = $sigType;
                $update['directeur_reviewed_at'] = now();
                $update['status']                = 'ready';
                $mailData = $this->readyMail($demande);
                break;

            default:
                return response()->json(['message' => "Action inconnue : {$action}"], 422);
        }

        // ── MAIL ÉTUDIANT (rejet, prêt, remis) ───────────────────────────────
        if ($mailData && $demande->email) {
            try {
                Mail::send(
                    $mailData['view'],
                    $mailData['vars'],
                    fn($m) => $m->to($demande->email)->subject($mailData['subject'])
                );
            } catch (\Exception $e) {
                Log::error('Erreur envoi mail étudiant', [
                    'error' => $e->getMessage(),
                    'ref'   => $demande->reference,
                ]);
            }
        }

        // ── MAIL ACTEUR INTERNE (notification inter-acteurs) ──────────────────
        // On notifie le prochain maillon dès qu'un nouveau statut est défini
        if ($newStatus) {
            $this->notifyNextActor(
                demande:          $demande,
                newStatus:        $newStatus,
                expediteurUser:   $user,
                expediteurRole:   $role,
                chefDivisionType: $update['chef_division_type'] ?? ($demande->chef_division_type ?? null),
                commentaire:      $motif ?? null,
            );
        }

        DB::table('document_requests')->where('id', $id)->update($update);

        $updated = DB::table('document_requests')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour avec succès.',
            'data'    => $updated,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NOTIFICATION INTER-ACTEURS  ← NOUVEAU
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envoie un mail d'alerte à l'acteur suivant dans le workflow.
     *
     * La logique :
     *  1. On déduit le slug du rôle destinataire depuis le nouveau statut (STATUS_TO_ROLE).
     *  2. On cherche dans `users` (via la table pivot rôles) l'email du ou des utilisateurs
     *     qui ont ce rôle. Pour chef-division, on filtre en plus par chef_division_type.
     *  3. On envoie le mail "dossier-transmis" à chaque destinataire trouvé.
     *
     * @param object      $demande          La demande courante
     * @param string      $newStatus        Le nouveau statut après transition
     * @param object      $expediteurUser   L'utilisateur Auth connecté
     * @param string|null $expediteurRole   Son slug de rôle
     * @param string|null $chefDivisionType Filtre pour chef-division si applicable
     * @param string|null $commentaire      Motif / commentaire optionnel
     */
    private function notifyNextActor(
        object  $demande,
        string  $newStatus,
        object  $expediteurUser,
        ?string $expediteurRole,
        ?string $chefDivisionType = null,
        ?string $commentaire      = null,
    ): void {
        // Quel rôle doit recevoir la notification ?
        $targetRoleSlug = self::STATUS_TO_ROLE[$newStatus] ?? null;
        if (!$targetRoleSlug) {
            return; // statut sans destinataire interne connu (ex: ready, delivered, rejected)
        }

        // Récupérer le(s) utilisateur(s) avec ce rôle
        // On suppose que les rôles sont dans une table `roles` reliée à `users`
        // via une table pivot `role_user` (ou `model_has_roles` si Spatie).
        // Adapter la jointure selon votre implémentation.
        $query = DB::table('users as u')
    ->join('role_user as mhr', function ($join) {
        $join->on('mhr.user_id', '=', 'u.id');
    })
    ->join('roles as r', 'mhr.role_id', '=', 'r.id')
    ->where('r.slug', $targetRoleSlug)
    ->whereNotNull('u.email')
    ->select(
        'u.id',
        DB::raw("CONCAT(u.first_name, ' ', u.last_name) as name"),
        'u.email',
        'u.chef_division_type'
    );
        // Pour chef-division, filtrer par le bon type
        if ($targetRoleSlug === 'chef-division' && $chefDivisionType) {
            $query->where('u.chef_division_type', $chefDivisionType);
        }

        $destinataires = $query->get();

        if ($destinataires->isEmpty()) {
            Log::warning("notifyNextActor : aucun utilisateur trouvé pour le rôle [{$targetRoleSlug}] / statut [{$newStatus}]", [
                'demande_ref' => $demande->reference,
            ]);
            return;
        }

        // Infos sur l'expéditeur (acteur connecté)
        $expediteurNom  = $expediteurUser->name ?? 'Un acteur';
        $expediteurRole = self::ROLE_LABELS[$expediteurRole] ?? ($expediteurRole ?? 'Acteur');

        // Infos sur le dossier / l'étudiant
        $etudiantInfo = DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->where('dr.id', $demande->id)
            ->select(
                'pi.last_name',
                'pi.first_names',
                DB::raw("(SELECT student_id_number FROM students s
                          JOIN student_pending_student sps2 ON sps2.student_id = s.id
                          WHERE sps2.id = dr.student_pending_student_id LIMIT 1) as matricule")
            )
            ->first();

        $etudiantNom      = trim(($etudiantInfo->first_names ?? '') . ' ' . ($etudiantInfo->last_name ?? '')) ?: 'Étudiant(e)';
        $etudiantMatricule = $etudiantInfo->matricule ?? null;

        foreach ($destinataires as $destinataire) {
            try {
                Mail::send(
                    'core::emails.dossier-transmis',
                    [
                        'destinataireNom'  => $destinataire->name,
                        'destinataireRole' => self::ROLE_LABELS[$targetRoleSlug] ?? $targetRoleSlug,
                        'expediteurNom'    => $expediteurNom,
                        'expediteurRole'   => $expediteurRole,
                        'reference'        => $demande->reference,
                        'typeDocument'     => self::TYPE_LABELS[$demande->type] ?? $demande->type,
                        'etudiantNom'      => $etudiantNom,
                        'etudiantMatricule'=> $etudiantMatricule,
                        'dateTransmission' => now()->format('d/m/Y à H:i'),
                        'commentaire'      => $commentaire,
                        'urlEspace'        => config('app.url') . '/dashboard',
                        'etablissement'    => config('app.name', 'CAP-EPAC'),
                    ],
                    fn($m) => $m
                        ->to($destinataire->email, $destinataire->name)
                        ->subject("📂 Dossier à traiter — Réf : {$demande->reference}")
                );

                Log::info("Notification inter-acteurs envoyée", [
                    'destinataire' => $destinataire->email,
                    'role'         => $targetRoleSlug,
                    'ref'          => $demande->reference,
                ]);
            } catch (\Exception $e) {
                Log::error('Erreur envoi mail acteur interne', [
                    'error'        => $e->getMessage(),
                    'destinataire' => $destinataire->email,
                    'ref'          => $demande->reference,
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS PRIVÉS (inchangés)
    // ─────────────────────────────────────────────────────────────────────────

    private function getVisibleStatusesForRole(?string $role): array
    {
        return match ($role) {
            'secretaire' => [
                'pending', 'secretaire_correction',
                'comptable_review', 'chef_division_review', 'chef_cap_review',
                'sec_dir_adjointe_review', 'directrice_adjointe_review',
                'sec_directeur_review', 'directeur_review',
                'ready', 'delivered', 'rejected',
            ],
            'comptable'           => ['comptable_review'],
            'chef-division'       => ['chef_division_review'],
            'chef-cap'            => ['chef_cap_review'],
            'sec-da'              => ['sec_dir_adjointe_review'],
            'directrice-adjointe' => ['directrice_adjointe_review'],
            'sec-dir'             => ['sec_directeur_review'],
            'directeur'           => ['directeur_review'],
            'admin'               => [],
            default               => ['pending'],
        };
    }

    private function isActionAllowed(?string $role, string $action, string $currentStatus): bool
    {
        if ($role === 'admin') return true;

        $matrix = [
            'secretaire' => [
                'secretaire_validate'     => ['pending'],
                'secretaire_reject'       => ['pending'],
                'secretaire_resend'       => ['secretaire_correction'],
                'secretaire_reject_final' => ['secretaire_correction'],
                'secretaire_deliver'      => ['ready'],
            ],
            'comptable' => [
                'comptable_reject'   => ['comptable_review'],
                'comptable_validate' => ['comptable_review'],
            ],
            'chef-division' => [
                'chef_division_reject'   => ['chef_division_review'],
                'chef_division_validate' => ['chef_division_review'],
            ],
            'chef-cap' => [
                'chef_cap_reject' => ['chef_cap_review'],
                'chef_cap_sign'   => ['chef_cap_review'],
            ],
            'sec-da' => [
                'sec_da_reject'   => ['sec_dir_adjointe_review'],
                'sec_da_transmit' => ['sec_dir_adjointe_review'],
            ],
            'directrice-adjointe' => [
                'directrice_adjointe_reject' => ['directrice_adjointe_review'],
                'directrice_adjointe_sign'   => ['directrice_adjointe_review'],
            ],
            'sec-dir' => [
                'sec_directeur_reject'   => ['sec_directeur_review'],
                'sec_directeur_transmit' => ['sec_directeur_review'],
            ],
            'directeur' => [
                'directeur_reject' => ['directeur_review'],
                'directeur_sign'   => ['directeur_review'],
            ],
        ];

        $allowedStatuses = $matrix[$role][$action] ?? null;
        return $allowedStatuses !== null && in_array($currentStatus, $allowedStatuses);
    }

    private function rejectedMail(object $demande, string $motif): array
    {
        return [
            'view'    => 'core::emails.demande-rejected',
            'subject' => "Votre demande a été rejetée — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => self::TYPE_LABELS[$demande->type] ?? $demande->type,
                'motif'     => $motif,
            ],
        ];
    }

    private function readyMail(object $demande): array
    {
        return [
            'view'    => 'core::emails.demande-ready',
            'subject' => "Votre document est prêt — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => self::TYPE_LABELS[$demande->type] ?? $demande->type,
            ],
        ];
    }

    private function deliveredMail(object $demande): array
    {
        return [
            'view'    => 'core::emails.demande-delivered',
            'subject' => "Votre document vous a été remis — Réf : {$demande->reference}",
            'vars'    => [
                'reference' => $demande->reference,
                'type'      => self::TYPE_LABELS[$demande->type] ?? $demande->type,
            ],
        ];
    }
}
