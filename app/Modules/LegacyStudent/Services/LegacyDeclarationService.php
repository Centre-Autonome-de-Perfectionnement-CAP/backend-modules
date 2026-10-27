<?php

namespace App\Modules\LegacyStudent\Services;

use App\Modules\Core\Services\Notifier;
use App\Modules\Demandes\Services\DocumentRequestHistoryService;
use App\Modules\Demandes\WorkflowConstants;
use App\Modules\LegacyStudent\Models\LegacyStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cycle de vie de la déclaration d'un ancien étudiant (avant 2023) et son
 * intégration au circuit officiel des demandes (décisions validées) :
 *
 *  - D1 : une demande peut être déposée pendant que la déclaration est en
 *         attente ; la secrétaire (ou l'admin) valide / rejette la
 *         déclaration depuis le dossier. "Valider → Comptable" est bloqué
 *         tant que la déclaration n'est pas validée. Un rejet de la
 *         déclaration rejette en cascade les demandes ouvertes, avec
 *         notification ;
 *  - D2 : tous les types (inscription, passage, définitive) + bulletins,
 *         vérifiés manuellement par la secrétaire ;
 *  - D3 : l'ancien étudiant choisit l'année de son bulletin ;
 *  - D4 : chaque nouvelle déclaration notifie la secrétaire et le
 *         responsable de division du cycle concerné (email + WhatsApp),
 *         et l'étudiant reçoit un accusé de réception.
 */
class LegacyDeclarationService
{
    /** Statuts de demande considérés comme clos. */
    private const CLOSED_REQUEST_STATUSES = ['rejected', 'picked_up'];

    public function __construct(
        private readonly Notifier $notifier,
        private readonly DocumentRequestHistoryService $history,
    ) {}

    // ── Recherche ────────────────────────────────────────────────────────────

    /** Déclaration exploitable (en attente ou validée) pour un matricule. */
    public function findActiveByMatricule(string $matricule): ?LegacyStudent
    {
        // Comparaison insensible à la casse (matricules saisis librement).
        return LegacyStudent::with('department.cycle')
            ->whereRaw('UPPER(TRIM(matricule)) = ?', [strtoupper(trim($matricule))])
            ->where('status', '!=', 'rejected')
            ->latest('id')
            ->first();
    }

    /**
     * Division compétente - même règle que pour les étudiants inscrits
     * (TransitionService::resolveResponsableDivisionType) : Licence
     * Professionnelle → formation à distance, sinon formation continue.
     */
    public function responsableDivisionType(LegacyStudent $student): string
    {
        $cycleName = $student->department?->cycle?->name ?? (string) $student->cycle;

        return (mb_strtolower(trim($cycleName)) === mb_strtolower('Licence Professionnelle'))
            ? 'formation_distance'
            : 'formation_continue';
    }

    // ── Nouvelle déclaration (D4) ────────────────────────────────────────────

    public function notifyNewDeclaration(LegacyStudent $student): void
    {
        try {
            $student->loadMissing('department.cycle');
            $nom       = trim("{$student->last_name} {$student->first_name}");
            $filiere   = $student->department?->name ?? '—';
            $divType   = $this->responsableDivisionType($student);
            $urlEspace = rtrim((string) config('app.url'), '/') . '/services';

            // Accusé de réception à l'étudiant
            $this->notifier->email(
                $student->email,
                'Déclaration reçue - CAP-EPAC',
                'core::emails.notification',
                [
                    'title' => 'Votre déclaration a bien été reçue',
                    'body'  => "Bonjour {$student->first_name},\n\n"
                        . "Votre déclaration d'ancien étudiant (matricule {$student->matricule}, {$filiere}) a bien été enregistrée. "
                        . "Elle sera vérifiée par le secrétariat. Vous pouvez dès maintenant déposer vos demandes "
                        . "d'attestation ou de bulletin : elles seront traitées après cette vérification.",
                ],
                toName: $nom,
            );
            $this->notifier->whatsapp(
                $student->phone,
                \App\Modules\Core\Support\WhatsAppText::compose('Déclaration reçue', [
                    "Bonjour {$student->first_name},",
                    "Votre déclaration d'ancien étudiant a bien été enregistrée.",
                    \App\Modules\Core\Support\WhatsAppText::fields(['Matricule' => $student->matricule]),
                    'Elle sera vérifiée par le secrétariat. Vos demandes seront traitées après cette vérification.',
                ]),
                "legacy-declaration:{$student->id}",
                'LegacyStudent',
            );

            // Secrétaire(s) + responsable de division du cycle concerné
            $body = "Nouvelle déclaration d'ancien étudiant à vérifier :\n"
                . "- Nom : {$nom}\n- Matricule : {$student->matricule}\n- Filière : {$filiere}\n"
                . "- Année d'inscription : {$student->enrollment_year}";
            $wa = \App\Modules\Core\Support\WhatsAppText::compose("Nouvelle déclaration d'ancien étudiant", [
                'Une déclaration est à vérifier :',
                \App\Modules\Core\Support\WhatsAppText::fields([
                    'Nom'                 => $nom,
                    'Matricule'           => $student->matricule,
                    'Filière'             => $filiere,
                    "Année d'inscription" => $student->enrollment_year,
                ]),
                "_Vérifier sur la plateforme :_\n{$urlEspace}",
            ]);

            foreach ($this->staffRecipients($divType) as $user) {
                $this->notifier->email(
                    $user->email,
                    "Nouvelle déclaration d'ancien étudiant - {$student->matricule}",
                    'core::emails.notification',
                    [
                        'title'      => "Nouvelle déclaration d'ancien étudiant",
                        'body'       => $body,
                        'actionUrl'  => $urlEspace,
                        'actionText' => 'Ouvrir la plateforme',
                    ],
                    toName: $user->name,
                );
                $this->notifier->whatsapp($user->phone, $wa, "legacy-declaration-staff:{$student->id}:{$user->id}", 'LegacyStudent');
            }
        } catch (\Throwable $e) {
            Log::error('[LegacyDeclaration] Notification nouvelle déclaration impossible', [
                'legacy_student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ── Validation ───────────────────────────────────────────────────────────

    public function validate(LegacyStudent $student, ?object $user, bool $bulk = false): LegacyStudent
    {
        $alreadyValidated = $student->status === 'validated';

        $student->update([
            'status'           => 'validated',
            'validated_by'     => $this->actorName($user),
            'validated_at'     => now(),
            'rejection_reason' => null,
        ]);

        if (!$alreadyValidated) {
            $this->notifyStudent(
                $student,
                'Déclaration validée - CAP-EPAC',
                'Votre déclaration a été validée',
                "Bonjour {$student->first_name},\n\nVotre déclaration d'ancien étudiant (matricule {$student->matricule}) a été validée par le secrétariat. "
                    . "Vos demandes en cours poursuivent leur traitement.",
                \App\Modules\Core\Support\WhatsAppText::compose('Déclaration validée', [
                    "Bonjour {$student->first_name},",
                    "Votre déclaration d'ancien étudiant a été *validée*.",
                    \App\Modules\Core\Support\WhatsAppText::fields(['Matricule' => $student->matricule]),
                    'Vos demandes en cours poursuivent leur traitement.',
                ]),
                "legacy-validated:{$student->id}",
                $bulk,
            );
        }

        return $student->fresh();
    }

    // ── Rejet + cascade (D1) ─────────────────────────────────────────────────

    /**
     * @return array{student: LegacyStudent, rejected_references: string[]}
     */
    public function reject(LegacyStudent $student, string $reason, ?object $user, bool $bulk = false): array
    {
        $reason = trim(strip_tags($reason));
        $motif  = "Déclaration d'ancien étudiant rejetée : {$reason}";

        $references = DB::transaction(function () use ($student, $reason, $motif, $user) {
            $student->update([
                'status'           => 'rejected',
                'rejection_reason' => $reason,
            ]);

            $open = DB::table('document_requests')
                ->where('legacy_student_id', $student->id)
                ->whereNotIn('status', self::CLOSED_REQUEST_STATUSES)
                ->lockForUpdate()
                ->get(['id', 'reference', 'status']);

            foreach ($open as $demande) {
                DB::table('document_requests')->where('id', $demande->id)->update([
                    'status'                   => 'rejected',
                    'rejected_reason'          => $motif,
                    'rejected_by'              => 'Secrétaire',
                    'is_in_correction_circuit' => false,
                    'correction_origin_role'   => null,
                    'correction_origin_status' => null,
                    'updated_at'               => now(),
                ]);

                if ($user) {
                    $this->history->record(
                        documentRequestId: $demande->id,
                        action:            'secretaire_reject_final',
                        statusBefore:      $demande->status,
                        statusAfter:       'rejected',
                        comment:           $motif,
                    );
                }
            }

            return $open->pluck('reference')->all();
        });

        $refsText = $references ? "\nDemande(s) clôturée(s) : " . implode(', ', $references) . '.' : '';

        $this->notifyStudent(
            $student,
            'Déclaration non validée - CAP-EPAC',
            "Votre déclaration n'a pas été validée",
            "Bonjour {$student->first_name},\n\nVotre déclaration d'ancien étudiant (matricule {$student->matricule}) n'a pas été validée.\n"
                . "Motif : {$reason}{$refsText}\n\nVous pouvez corriger votre déclaration puis déposer de nouvelles demandes.",
            \App\Modules\Core\Support\WhatsAppText::compose('Déclaration non validée', [
                "Bonjour {$student->first_name},",
                "Votre déclaration d'ancien étudiant n'a pas été validée.",
                \App\Modules\Core\Support\WhatsAppText::fields([
                    'Matricule'             => $student->matricule,
                    'Motif'                 => $reason,
                    'Demande(s) clôturée(s)' => $references ? implode(', ', $references) : '',
                ]),
                'Vous pouvez corriger votre déclaration puis déposer de nouvelles demandes.',
            ]),
            "legacy-rejected:{$student->id}",
            $bulk,
        );

        return ['student' => $student->fresh(), 'rejected_references' => $references];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function notifyStudent(LegacyStudent $student, string $subject, string $title, string $body, string $wa, string $context, bool $bulk = false): void
    {
        try {
            $this->notifier->email($student->email, $subject, 'core::emails.notification', [
                'title' => $title,
                'body'  => $body,
            ], toName: trim("{$student->last_name} {$student->first_name}"), bulk: $bulk);
            $this->notifier->whatsapp($student->phone, $wa, $context, 'LegacyStudent', bulk: $bulk);
        } catch (\Throwable $e) {
            Log::error('[LegacyDeclaration] Notification étudiant impossible', [
                'legacy_student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Secrétaires + responsables de la division compétente. */
    private function staffRecipients(string $divisionType): Collection
    {
        $base = fn (array $slugs) => DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->whereIn('r.slug', $slugs)
            ->select('u.id', DB::raw("CONCAT(u.first_name, ' ', u.last_name) as name"), 'u.email', 'u.phone');

        $secretaires = $base(WorkflowConstants::roleSlugVariants('secretaire'))->get();
        $division    = $base(WorkflowConstants::roleSlugVariants('responsable-division'))
            ->where('u.chef_division_type', $divisionType)
            ->get();

        return $secretaires->concat($division)->unique('id')->values();
    }

    private function actorName(?object $user): string
    {
        $name = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
        return $name !== '' ? $name : 'Secrétariat Scolarité';
    }
}
