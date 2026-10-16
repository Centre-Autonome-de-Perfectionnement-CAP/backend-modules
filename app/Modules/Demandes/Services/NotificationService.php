<?php

namespace App\Modules\Demandes\Services;

use App\Modules\Demandes\Jobs\SendNotificationJob;
use App\Modules\Demandes\WorkflowConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * NotificationService — version queue asynchrone.
 *
 * SEUL CHANGEMENT vs l'original :
 *   Mail::send() direct       → SendNotificationJob::dispatch() canal 'email'
 *   $this->whatsApp->send()   → SendNotificationJob::dispatch() canal 'whatsapp'
 *
 * La logique métier est IDENTIQUE à l'original :
 *   - Mêmes méthodes publiques, mêmes signatures
 *   - Mêmes événements déclenchés aux mêmes moments
 *   - Mêmes destinataires (findUsersWithRole inchangé)
 *   - Mêmes templates (toujours dans WhatsAppService)
 *
 * Résultat : le cycle HTTP ne attend plus SMTP ni bridge.
 *   → Boutons répondent en < 100ms au lieu de 500ms–2s+
 *   → Bridge down ne cause plus de 500
 *   → 1000 soumissions simultanées → 1000 jobs en queue, traités en ordre
 *
 * Événements couverts (tous conservés) :
 *   sendSoumission()                         → étudiant + secrétaire
 *   sendComplementSecretaire()               → étudiant + secrétaire
 *   sendRejected()                           → étudiant
 *   sendSousReserve()                        → étudiant
 *   sendReady()                              → étudiant
 *   sendDelivered()                          → étudiant
 *   notifyNextActor()                        → prochain acteur du circuit
 *   notifySecretaireAfterDirecteurSign()     → secrétaire
 *   notifySecretaryOfDirectionTransmission() → secrétaire
 */
class NotificationService
{
    /**
     * CORRECTIF (16/08/2026) : cette propriété était utilisée 12 fois dans
     * ce fichier ($this->whatsApp->templateXxx()) sans jamais être déclarée
     * ni injectée — erreur fatale "Undefined property" garantie à chaque
     * notification. Ajouté ici.
     */
    public function __construct(
        private WhatsAppService $whatsApp,
    ) {}

    // ── Mails étudiant ────────────────────────────────────────────────────────

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS DISPATCH — remplacent Mail::send() et whatsApp->send() directs
    // ══════════════════════════════════════════════════════════════════════════

    private function email(
        string  $to,
        string  $toName,
        string  $subject,
        string  $view,
        array   $vars,
        string  $context = '',
        array   $attachments = [],
    ): void {
        if (empty($to)) return;

        SendNotificationJob::send('email', [
            'to'          => $to,
            'to_name'     => $toName,
            'subject'     => $subject,
            'view'        => $view,
            'vars'        => $vars,
            'attachments' => $attachments,
        ]);
    }

    private function wa(string $phone, string $message, string $context = ''): void
    {
        if (empty($phone)) return;

        SendNotificationJob::send('whatsapp', [
            'phone'   => $phone,
            'message' => $message,
            'context' => $context,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // NOTIFICATIONS ÉTUDIANT
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Soumission d'une demande.
     * Notifie : étudiant (email + WhatsApp) + secrétaire(s) (email + WhatsApp).
     */
    public function sendSoumission(object $demande): void
    {
        $typeLabel    = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);
        $matricule    = $etudiantInfo->matricule ?? '';

        // ── Email étudiant ────────────────────────────────────────────────────
        $this->email(
            to:      $demande->email ?? '',
            toName:  $etudiantNom,
            subject: "Demande reçue — Réf : {$demande->reference}",
            view:    'core::emails.demande-soumission',
            vars:    [
                'reference'   => $demande->reference,
                'typeLabel'   => $typeLabel,
                'submittedAt' => now()->format('d/m/Y à H:i'),
                'email'       => $demande->email ?? '',
            ],
            context: "soumission:{$demande->reference}",
        );

        // ── WhatsApp étudiant ─────────────────────────────────────────────────
        if (!empty($demande->demandeur_whatsapp)) {
            $this->wa(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateSoumission(
                    $demande->reference,
                    $typeLabel,
                    $etudiantNom
                ),
                "soumission:{$demande->reference}",
            );
        }

        // ── Secrétaires : email + WhatsApp ────────────────────────────────────
        $secretaires = $this->findUsersWithRole('secretaire', null);

        foreach ($secretaires as $sec) {
            $this->email(
                to:      $sec->email,
                toName:  $sec->name,
                subject: "Nouvelle demande — Réf : {$demande->reference}",
                view:    'core::emails.dossier-transmis',
                vars:    [
                    'destinataireNom'   => $sec->name,
                    'destinataireRole'  => 'Secrétaire',
                    'expediteurNom'     => 'Portail étudiant',
                    'expediteurRole'    => '',
                    'reference'         => $demande->reference,
                    'typeDocument'      => $typeLabel,
                    'etudiantNom'       => $etudiantNom,
                    'etudiantMatricule' => $matricule,
                    'dateTransmission'  => now()->format('d/m/Y à H:i'),
                    'commentaire'       => null,
                    'urlEspace'         => $this->urlEspace('secretaire'),
                    'etablissement'     => config('app.name', 'CAP-EPAC'),
                ],
                context: "soumission-secretaire:{$demande->reference}",
            );

            if (!empty($sec->phone)) {
                $this->wa(
                    $sec->phone,
                    $this->whatsApp->templateNouvelleDemandeSecretaire(
                        destinataireNom: $sec->name,
                        reference:       $demande->reference,
                        typeDocument:    $typeLabel,
                        nomEtudiant:     $etudiantNom,
                        matricule:       $matricule,
                    ),
                    "soumission-secretaire:{$demande->reference}",
                );
            }
        }
    }

    /**
     * Rejet définitif.
     * Notifie : étudiant (email + WhatsApp).
     */
    public function sendRejected(object $demande, string $motif): void
    {
        $typeLabel    = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);

        $this->email(
            to:      $demande->email ?? '',
            toName:  '',
            subject: "Votre demande a été rejetée — Réf : {$demande->reference}",
            view:    'core::emails.demande-rejected',
            vars:    [
                'reference' => $demande->reference,
                'type'      => WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null),
                'motif'     => $motif,
            ],
            context: "rejet:{$demande->reference}",
        );

        if (!empty($demande->demandeur_whatsapp)) {
            $this->wa(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateRejete($demande->reference, $typeLabel, $motif, $etudiantNom),
                "rejet:{$demande->reference}",
            );
        }
    }

    /**
     * Dossier sous réserve.
     * Notifie : étudiant (email + WhatsApp).
     */
    public function sendSousReserve(object $demande, string $motif): void
    {
        $typeLabel    = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);

        $this->email(
            to:      $demande->email ?? '',
            toName:  '',
            subject: "Action requise : Votre dossier est sous réserve — Réf : {$demande->reference}",
            view:    'core::emails.demande-sous-reserve',
            vars:    [
                'reference' => $demande->reference,
                'type'      => $typeLabel,
                'motif'     => $motif,
            ],
            context: "sous-reserve:{$demande->reference}",
        );

        if (!empty($demande->demandeur_whatsapp)) {
            $this->wa(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateSousReserve($demande->reference, $typeLabel, $motif, $etudiantNom),
                "sous-reserve:{$demande->reference}",
            );
        }
    }

    /**
     * Document prêt à retirer.
     * Notifie : étudiant (email + WhatsApp).
     */
    public function sendReady(object $demande): void
    {
        $typeLabel    = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);

        $this->email(
            to:      $demande->email ?? '',
            toName:  '',
            subject: "Votre document est prêt — Réf : {$demande->reference}",
            view:    'core::emails.demande-ready',
            vars:    [
                'reference' => $demande->reference,
                'type'      => WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null),
            ],
            context: "pret:{$demande->reference}",
        );

        if (!empty($demande->demandeur_whatsapp)) {
            $this->wa(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templatePret($demande->reference, $typeLabel, $etudiantNom),
                "pret:{$demande->reference}",
            );
        }
    }

    /**
     * Document remis physiquement.
     * Notifie : étudiant (email + WhatsApp).
     */
    public function sendDelivered(object $demande): void
    {
        $typeLabel    = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);

        $this->email(
            to:      $demande->email ?? '',
            toName:  '',
            subject: "Votre document vous a été remis — Réf : {$demande->reference}",
            view:    'core::emails.demande-delivered',
            vars:    [
                'reference' => $demande->reference,
                'type'      => WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null),
            ],
            context: "remis:{$demande->reference}",
        );

        if (!empty($demande->demandeur_whatsapp)) {
            $this->wa(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateRemis($demande->reference, $typeLabel, $etudiantNom),
                "remis:{$demande->reference}",
            );
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // NOTIFICATION COMPLÉMENT DE DOSSIER
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Complément de dossier déposé.
     * Notifie : étudiant (email + WhatsApp) + secrétaire(s) (email + WhatsApp).
     */
    public function sendComplementSecretaire(
        string  $etudiantEmail,
        array   $vars,
        ?string $whatsappEtudiant = null,
    ): void {
        $reference  = $vars['reference']  ?? '—';
        $nomComplet = $vars['nomComplet'] ?? "L'étudiant(e)";
        $piecesList = $vars['piecesList'] ?? [];

        // ── Email étudiant ────────────────────────────────────────────────────
        $this->email(
            to:      $etudiantEmail,
            toName:  $nomComplet,
            subject: "Complément de dossier reçu — Réf : {$reference}",
            view:    'core::emails.complement-confirmation',
            vars:    $vars,
            context: "complement-etudiant:{$reference}",
        );

        // ── WhatsApp étudiant ─────────────────────────────────────────────────
        if (!empty($whatsappEtudiant)) {
            $this->wa(
                $whatsappEtudiant,
                $this->whatsApp->templateComplementEtudiant($reference, $piecesList, $nomComplet),
                "complement-etudiant:{$reference}",
            );
        }

        // ── Secrétaires : email + WhatsApp ────────────────────────────────────
        $secretaires = $this->findUsersWithRole('secretaire', null);

        foreach ($secretaires as $sec) {
            $this->email(
                to:      $sec->email,
                toName:  $sec->name,
                subject: "Nouveau complément — Réf : {$reference}",
                view:    'core::emails.complement-notification-secretariat',
                vars:    array_merge($vars, ['email' => $etudiantEmail, 'urlEspace' => $this->urlEspace('secretaire')]),
                context: "complement-secretaire:{$reference}",
            );

            if (!empty($sec->phone)) {
                $this->wa(
                    $sec->phone,
                    $this->whatsApp->templateComplementSecretaire(
                        destinataireNom: $sec->name,
                        reference:       $reference,
                        nomEtudiant:     $nomComplet,
                        nbPieces:        count($piecesList),
                    ),
                    "complement-secretaire:{$reference}",
                );
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // NOTIFICATIONS ACTEURS INTERNES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Notifie le prochain acteur qu'un dossier lui a été transmis.
     * Fonctionne dans les deux sens (transmission normale ou retour correction).
     */
    public function notifyNextActor(
        object  $demande,
        string  $newStatus,
        object  $expediteurUser,
        ?string $expediteurRole,
        ?string $responsableDivisionType = null,
        ?string $commentaire = null,
    ): void {
        $targetRoleSlug = WorkflowConstants::STATUS_TO_ROLE[$newStatus] ?? null;
        if (!$targetRoleSlug) return;

        $destinataires = $this->findUsersWithRole($targetRoleSlug, $responsableDivisionType);

        // Filet de sécurité : si aucun Responsable Division n'est rattaché au
        // type du dossier (chef_division_type non renseigné sur l'utilisateur),
        // on prévient tous les Responsables Division plutôt que personne —
        // un dossier ne doit jamais arriver chez un acteur sans notification.
        if ($destinataires->isEmpty() && $targetRoleSlug === 'responsable-division' && $responsableDivisionType) {
            Log::warning('notifyNextActor: aucun Responsable Division pour ce type — notification de tous les Responsables Division', [
                'type' => $responsableDivisionType,
                'ref'  => $demande->reference,
            ]);
            $destinataires = $this->findUsersWithRole($targetRoleSlug, null);
        }

        if ($destinataires->isEmpty()) {
            Log::warning('notifyNextActor: aucun utilisateur pour le rôle', [
                'role'   => $targetRoleSlug,
                'status' => $newStatus,
                'ref'    => $demande->reference,
            ]);
            return;
        }

        $etudiantInfo      = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom       = trim(($etudiantInfo->first_names ?? '') . ' ' . ($etudiantInfo->last_name ?? '')) ?: 'Étudiant(e)';
        $matricule         = $etudiantInfo->matricule ?? '';
        $expediteurNomRole = (WorkflowConstants::ROLE_LABELS[$expediteurRole] ?? $expediteurRole ?? 'Acteur');
        $typeDocument      = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $destinataireRole  = WorkflowConstants::ROLE_LABELS[$targetRoleSlug] ?? $targetRoleSlug;
        $isCorrection      = !empty($demande->is_in_correction_circuit);

        foreach ($destinataires as $dest) {
            // ── Email ──────────────────────────────────────────────────────────
            $this->email(
                to:      $dest->email,
                toName:  $dest->name,
                subject: "Dossier à traiter — Réf : {$demande->reference}",
                view:    'core::emails.dossier-transmis',
                vars:    [
                    'destinataireNom'   => $dest->name,
                    'destinataireRole'  => $destinataireRole,
                    'expediteurNom'     => $expediteurUser->name ?? 'Acteur',
                    'expediteurRole'    => $expediteurNomRole,
                    'reference'         => $demande->reference,
                    'typeDocument'      => $typeDocument,
                    'etudiantNom'       => $etudiantNom,
                    'etudiantMatricule' => $matricule,
                    'dateTransmission'  => now()->format('d/m/Y à H:i'),
                    'commentaire'       => $commentaire,
                    'urlEspace'         => $this->urlEspace($targetRoleSlug),
                    'etablissement'     => config('app.name', 'CAP-EPAC'),
                ],
                context: "acteur:{$targetRoleSlug}:{$demande->reference}",
            );

            // ── WhatsApp ───────────────────────────────────────────────────────
            if (!empty($dest->phone)) {
                if ($targetRoleSlug === 'secretaire' && $isCorrection) {
                    $message = $this->whatsApp->templateCorrectionCircuit(
                        destinataireNom: $dest->name,
                        expediteurRole:  $expediteurNomRole,
                        reference:       $demande->reference,
                        typeDocument:    $typeDocument,
                        etudiantNom:     $etudiantNom,
                        matricule:       $matricule,
                        commentaire:     $commentaire,
                    );
                } else {
                    $message = $this->whatsApp->templateActeurDossier(
                        destinataireNom: $dest->name,
                        expediteurRole:  $expediteurNomRole,
                        reference:       $demande->reference,
                        typeDocument:    $typeDocument,
                        etudiantNom:     $etudiantNom,
                        matricule:       $matricule,
                        commentaire:     $commentaire,
                    );
                }

                $this->wa(
                    $dest->phone,
                    $message,
                    "acteur:{$targetRoleSlug}:{$demande->reference}",
                );
            }
        }
    }

    /**
     * Notifie la secrétaire que le Directeur a signé.
     * Notifie : secrétaire(s) (email + WhatsApp).
     */
    public function notifySecretaireAfterDirecteurSign(object $demande): void
    {
        $secretaires  = $this->findUsersWithRole('secretaire', null);
        $typeLabel    = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);
        $matricule    = $etudiantInfo->matricule ?? '';

        foreach ($secretaires as $sec) {
            $this->email(
                to:      $sec->email,
                toName:  $sec->name,
                subject: "✅ Dossier signé par le Directeur — À finaliser — Réf : {$demande->reference}",
                view:    'core::emails.dossier-transmis',
                vars:    [
                    'destinataireNom'   => $sec->name,
                    'destinataireRole'  => 'Secrétaire',
                    'expediteurNom'     => 'Directeur',
                    'expediteurRole'    => 'Directeur',
                    'reference'         => $demande->reference,
                    'typeDocument'      => $typeLabel,
                    'etudiantNom'       => $etudiantNom,
                    'etudiantMatricule' => $matricule,
                    'dateTransmission'  => now()->format('d/m/Y à H:i'),
                    'commentaire'       => 'Le Directeur a signé ce dossier. Veuillez préparer le document et le marquer comme prêt à retirer.',
                    'urlEspace'         => $this->urlEspace('secretaire'),
                    'etablissement'     => config('app.name', 'CAP-EPAC'),
                ],
                context: "directeur-signe:{$demande->reference}",
            );

            if (!empty($sec->phone)) {
                $this->wa(
                    $sec->phone,
                    $this->whatsApp->templateDirecteurSigne(
                        destinataireNom: $sec->name,
                        nomEtudiant:     $etudiantNom,
                        reference:       $demande->reference,
                        typeDocument:    $typeLabel,
                        matricule:       $matricule,
                    ),
                    "directeur-signe:{$demande->reference}",
                );
            }
        }
    }

    /**
     * Notifie la secrétaire qu'un dossier entre en Direction.
     * Notifie : secrétaire(s) (email + WhatsApp).
     */
    public function notifySecretaryOfDirectionTransmission(object $demande): void
    {
        $secretaires  = $this->findUsersWithRole('secretaire', null);
        $typeLabel    = WorkflowConstants::typeLabel($demande->type, $demande->academic_year ?? null);
        $etudiantInfo = $this->fetchEtudiantInfo($demande->id);
        $etudiantNom  = $this->buildNom($etudiantInfo);
        $matricule    = $etudiantInfo->matricule ?? '';

        foreach ($secretaires as $sec) {
            $this->email(
                to:      $sec->email,
                toName:  $sec->name,
                subject: "Dossier à transmettre physiquement (Direction) — Réf : {$demande->reference}",
                view:    'core::emails.dossier-transmis',
                vars:    [
                    'destinataireNom'   => $sec->name,
                    'destinataireRole'  => 'Secrétaire',
                    'expediteurNom'     => 'Chef CAP',
                    'expediteurRole'    => 'Chef CAP',
                    'reference'         => $demande->reference,
                    'typeDocument'      => $typeLabel,
                    'etudiantNom'       => $etudiantNom,
                    'etudiantMatricule' => $matricule,
                    'dateTransmission'  => now()->format('d/m/Y à H:i'),
                    'commentaire'       => 'Ce dossier est maintenant en cours de signature (Direction). Veuillez préparer et transmettre les documents physiques correspondants.',
                    'urlEspace'         => $this->urlEspace('secretaire'),
                    'etablissement'     => config('app.name', 'CAP-EPAC'),
                ],
                context: "direction:{$demande->reference}",
            );

            if (!empty($sec->phone)) {
                $this->wa(
                    $sec->phone,
                    $this->whatsApp->templateDossierDirection(
                        destinataireNom: $sec->name,
                        nomEtudiant:     $etudiantNom,
                        reference:       $demande->reference,
                    ),
                    "direction:{$demande->reference}",
                );
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // MESSAGE LIBRE SECRÉTAIRE → DEMANDEUR (bouton « Contacter le demandeur »)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Met en file d'envoi le message libre de la secrétaire, sur les deux
     * canaux, indépendamment l'un de l'autre :
     *   - WhatsApp : 1 job pour le texte + 1 job par pièce jointe ;
     *   - Email    : 1 job (pièces jointes incluses dans le même email).
     *
     * @param array<int, array{path: string, name: string}> $attachments  (disque 'public')
     * @return array{whatsapp: bool, email: bool}  canaux effectivement mis en file
     */
    public function sendMessageDemandeur(
        object $demande,
        string $nomDemandeur,
        string $typeLabel,
        string $message,
        string $secretaireName,
        array  $attachments = [],
    ): array {
        $context = "message-secretariat:{$demande->reference}";
        $queued  = ['whatsapp' => false, 'email' => false];

        if (!empty($demande->demandeur_whatsapp)) {
            $this->wa(
                $demande->demandeur_whatsapp,
                $this->whatsApp->templateMessageSecretariat($nomDemandeur, $message, $secretaireName),
                $context,
            );

            foreach ($attachments as $att) {
                SendNotificationJob::send('whatsapp_file', [
                    'phone'     => $demande->demandeur_whatsapp,
                    'disk'      => 'public',
                    'path'      => $att['path'],
                    'file_name' => $att['name'],
                    'caption'   => "Pièce jointe — dossier {$demande->reference}",
                    'context'   => $context,
                ]);
            }
            $queued['whatsapp'] = true;
        }

        if (!empty($demande->email)) {
            $this->email(
                to:          $demande->email,
                toName:      $nomDemandeur,
                subject:     "CAP-EPAC — Message concernant votre dossier {$demande->reference}",
                view:        'core::emails.secretaire-message',
                // NB : la clé 'message' est réservée par Laravel dans les vues mail
                // (instance Illuminate\Mail\Message) — d'où 'messageBody'.
                vars:        [
                    'reference'      => $demande->reference,
                    'typeLabel'      => $typeLabel,
                    'nomDemandeur'   => $nomDemandeur,
                    'messageBody'    => $message,
                    'secretaireName' => $secretaireName,
                ],
                context:     $context,
                attachments: array_map(
                    fn (array $a) => ['disk' => 'public', 'path' => $a['path'], 'name' => $a['name']],
                    $attachments,
                ),
            );
            $queued['email'] = true;
        }

        return $queued;
    }

    /**
     * URL de l'espace de traitement du progiciel pour un rôle donné :
     *   - rôles direction  → /services/demandes/cap (espace dédié)
     *   - autres rôles     → /services/demandes
     */
    private function urlEspace(?string $roleSlug): string
    {
        $base = rtrim((string) config('app.url'), '/') . '/services';

        return in_array($roleSlug, WorkflowConstants::DIRECTION_ROLES, true)
            ? $base . '/demandes/cap'
            : $base . '/demandes';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS (INCHANGÉS vs original)
    // ══════════════════════════════════════════════════════════════════════════

    private function findUsersWithRole(string $roleSlug, ?string $responsableDivisionType): \Illuminate\Support\Collection
    {
        $slugVariants = WorkflowConstants::roleSlugVariants($roleSlug);

        $query = DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->whereIn('r.slug', $slugVariants)
            ->whereNotNull('u.email')
            ->select(
                'u.id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as name"),
                'u.email',
                'u.phone',
                'u.chef_division_type',
            );

        if ($roleSlug === 'responsable-division' && $responsableDivisionType) {
            $query->where('u.chef_division_type', $responsableDivisionType);
        }

        return $query->get();
    }

    private function fetchEtudiantInfo(int $demandeId): ?object
    {
        // Inscrit OU ancien étudiant déclaré (jointures gauches, cf. Requester).
        $query = DB::table('document_requests as dr');
        \App\Modules\Demandes\Support\Requester::join($query, 'dr', false);

        return $query->where('dr.id', $demandeId)
            ->select(\App\Modules\Demandes\Support\Requester::identityColumns())
            ->first();
    }

    private function buildNom(?object $etudiantInfo): string
    {
        return trim(($etudiantInfo->first_names ?? '') . ' ' . ($etudiantInfo->last_name ?? '')) ?: 'Étudiant(e)';
    }
}
