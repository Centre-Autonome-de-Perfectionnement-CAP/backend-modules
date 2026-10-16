<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Modules\Demandes\Services\WhatsAppService;
use App\Modules\Demandes\WorkflowConstants;
use Carbon\Carbon;

class SendGroupedReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demandes:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie un récapitulatif des dossiers en retard (>36h) à chaque acteur concerné.';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $whatsApp)
    {
        $this->info("Début de la relance des dossiers en retard...");

        // Ne s'exécute pas le week-end par sécurité (bien que géré par le scheduler)
        if (now()->isWeekend()) {
            $this->info("Week-end ignoré.");
            return;
        }

        $seuil = now()->subHours(36);

        // Statuts ignorés car ne nécessitant pas d'action (ou clos)
        $ignoredStatuses = ['submitted', 'ready_for_pickup', 'picked_up', 'rejected'];

        // On récupère toutes les demandes non traitées depuis > 36h
        // Inscrits ET anciens étudiants (jointures gauches, cf. Requester).
        $demandesQuery = DB::table('document_requests as dr');
        \App\Modules\Demandes\Support\Requester::join($demandesQuery, 'dr', false);
        $demandes = $demandesQuery
            ->whereNotIn('dr.status', $ignoredStatuses)
            ->where('dr.updated_at', '<', $seuil)
            ->select(
                'dr.id',
                'dr.reference',
                'dr.type',
                'dr.status',
                'dr.updated_at',
                'dr.responsable_division_type',
                DB::raw(\App\Modules\Demandes\Support\Requester::FIRST_NAMES . ' as first_names'),
                DB::raw(\App\Modules\Demandes\Support\Requester::LAST_NAME . ' as last_name'),
            )
            ->get();

        if ($demandes->isEmpty()) {
            $this->info("Aucun dossier en retard (>36h).");
            return;
        }

        // On va grouper ces demandes par acteur (user_id)
        $actorDemandes = [];

        foreach ($demandes as $demande) {
            $targetRoleSlug = WorkflowConstants::STATUS_TO_ROLE[$demande->status] ?? null;
            if (!$targetRoleSlug) {
                continue;
            }

            $users = $this->findUsersWithRole($targetRoleSlug, $demande->responsable_division_type);

            foreach ($users as $user) {
                if (!isset($actorDemandes[$user->id])) {
                    $actorDemandes[$user->id] = [
                        'user' => $user,
                        'demandes' => []
                    ];
                }
                $actorDemandes[$user->id]['demandes'][] = $demande;
            }
        }

        // Envoi des emails/WhatsApp groupés
        foreach ($actorDemandes as $data) {
            $user = $data['user'];
            $listeDemandes = $data['demandes'];
            $count = count($listeDemandes);

            // Email + WhatsApp en file (retry automatique, canaux indépendants).
            $notifier = app(\App\Modules\Core\Services\Notifier::class);
            $notifier->email(
                $user->email,
                'Relance : Vous avez des dossiers en retard',
                'core::emails.grouped-reminders',
                [
                    'destinataireNom' => $user->name,
                    'count'           => $count,
                    'demandes'        => $listeDemandes, // stdClass : sérialisables
                    // CORRECTIF : /dashboard n'existe pas — le progiciel est servi sous /services.
                    'urlEspace'       => rtrim((string) config('app.url'), '/') . '/services/demandes',
                    'etablissement'   => config('app.name', 'CAP-EPAC'),
                ],
                toName: $user->name,
            );

            if (!empty($user->phone)) {
                $appName = config('app.name', 'CAP-EPAC');
                $label = $count > 1 ? 'dossiers en attente de traitement' : 'dossier en attente de traitement';

                $message = "*{$appName} — Relance*\n\nBonjour *{$user->name}*,\n\nVous avez *{$count} {$label}* depuis plus de 36h.\n\nMerci de vous connecter sur la plateforme pour les traiter dans les plus brefs délais.\n\n_Ceci est un message automatique._";

                $notifier->whatsapp($user->phone, $message, "reminders:{$user->id}", 'Demandes');
            }

            $this->info("Relance envoyée à {$user->name} ({$count} dossiers).");
        }

        $this->info("Fin de la relance.");
    }

    private function findUsersWithRole(string $roleSlug, ?string $responsableDivisionType): \Illuminate\Support\Collection
    {
        $query = DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('r.slug', $roleSlug)
            ->whereNotNull('u.email')
            ->whereNull('u.deleted_at')
            ->select(
                'u.id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as name"),
                'u.email',
                'u.phone'
            );

        if ($roleSlug === 'chef-division' && $responsableDivisionType) {
            $query->where('u.responsable_division_type', $responsableDivisionType);
        }

        return $query->get();
    }
}
