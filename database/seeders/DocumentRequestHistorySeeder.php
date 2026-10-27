<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DocumentRequestHistorySeeder extends Seeder
{
    public function run(): void
    {
        $requests = DB::table('document_requests')->get();

        $actorsByRole = [
            'secretaire'   => ['Marie Cotonou',    'secretaire'],
            'comptable'    => ['Jean Bohicon',      'comptable'],
            'chef_division'=> ['Paul Gbégamey',    'chef_division'],
            'chef_cap'     => ['Arsène Calavi',     'chef_cap'],
            'sec_da'       => ['Inès Sèmè',         'sec_da'],
            'da'           => ['Dr. Ahounou',       'da'],
            'sec_dir'      => ['Rosine Godomey',    'sec_dir'],
            'directeur'    => ['Prof. Kpossou',     'directeur'],
        ];

        $workflow = [
            'secretaire',
            'comptable',
            'chef_division',
            'chef_cap',
            'sec_da',
            'da',
            'sec_dir',
            'directeur',
        ];

        foreach ($requests as $request) {
            $at = Carbon::parse($request->created_at);

            // Nombre d'étapes à simuler selon le statut actuel
            $currentStatusIndex = array_search(
                str_replace('_review', '', $request->status),
                $workflow
            );
            $steps = $currentStatusIndex === false ? 1 : $currentStatusIndex + 1;

            for ($s = 0; $s < $steps; $s++) {
                $role = $workflow[$s];
                [$actorName, $actorRole] = $actorsByRole[$role];

                $isLastStep = ($s === $steps - 1);
                $actionType = $isLastStep && $request->status === 'rejected'
                    ? 'rejet_definitif'
                    : ($isLastStep && $request->status === 'ready'
                        ? 'livraison'
                        : 'transmission');

                $at = $at->copy()->addHours(rand(1, 8));

                DB::table('document_request_histories')->insert([
                    'document_request_id' => $request->id,
                    'actor_id'            => null, // pas de vrais users en seed
                    'actor_name'          => $actorName,
                    'actor_role'          => $actorRole,
                    'action_type'         => $actionType,
                    'action_label'        => ucfirst($actionType) . ' par ' . $actorName,
                    'status_before'       => $s === 0 ? 'pending' : $workflow[$s - 1] . '_review',
                    'status_after'        => $role . '_review',
                    'comment'             => $actionType === 'rejet_definitif' ? 'Dossier incomplet.' : null,
                    'created_at'          => $at,
                ]);
            }
        }
    }
}