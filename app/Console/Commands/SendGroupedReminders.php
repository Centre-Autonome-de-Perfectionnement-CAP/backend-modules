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
        $ignoredStatuses = ['pending', 'ready', 'delivered', 'rejected'];

        // On récupère toutes les demandes non traitées depuis > 36h
        $demandes = DB::table('document_requests as dr')
            ->join('student_pending_student as sps', 'dr.student_pending_student_id', '=', 'sps.id')
            ->join('pending_students as ps', 'sps.pending_student_id', '=', 'ps.id')
            ->join('personal_information as pi', 'ps.personal_information_id', '=', 'pi.id')
            ->whereNotIn('dr.status', $ignoredStatuses)
            ->where('dr.updated_at', '<', $seuil)
            ->select(
                'dr.id',
                'dr.reference',
                'dr.type',
                'dr.status',
                'dr.updated_at',
                'dr.chef_division_type',
                'pi.first_names',
                'pi.last_name'
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

            $users = $this->findUsersWithRole($targetRoleSlug, $demande->chef_division_type);

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

            // 1. Email
            try {
                Mail::send('core::emails.grouped-reminders', [
                    'destinataireNom' => $user->name,
                    'count'           => $count,
                    'demandes'        => $listeDemandes,
                    'urlEspace'       => config('app.url') . '/dashboard',
                    'etablissement'   => config('app.name', 'CAP-EPAC'),
                ], function ($m) use ($user) {
                    $m->to($user->email, $user->name)
                      ->subject("Relance : Vous avez des dossiers en retard");
                });
            } catch (\Exception $e) {
                Log::error('[Reminders] Erreur email', [
                    'error' => $e->getMessage(),
                    'user'  => $user->email,
                ]);
            }

            // 2. WhatsApp
            if (!empty($user->phone)) {
                $appName = config('app.name', 'CAP-EPAC');
                $label = $count > 1 ? 'dossiers en attente de traitement' : 'dossier en attente de traitement';
                
                $message = "*{$appName} — Relance*\n\nBonjour *{$user->name}*,\n\nVous avez *{$count} {$label}* depuis plus de 36h.\n\nMerci de vous connecter sur la plateforme pour les traiter dans les plus brefs délais.\n\n_Ceci est un message automatique._";

                $whatsApp->send($user->phone, $message, "reminders:{$user->id}");
            }
            
            $this->info("Relance envoyée à {$user->name} ({$count} dossiers).");
        }

        $this->info("Fin de la relance.");
    }

    private function findUsersWithRole(string $roleSlug, ?string $chefDivisionType): \Illuminate\Support\Collection
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

        if ($roleSlug === 'chef-division' && $chefDivisionType) {
            $query->where('u.chef_division_type', $chefDivisionType);
        }

        return $query->get();
    }
}
