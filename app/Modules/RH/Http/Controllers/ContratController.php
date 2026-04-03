<?php

namespace App\Modules\RH\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Contrat;
use App\Modules\Cours\Models\CourseElementProfessor;
use App\Modules\RH\Http\Resources\ProfessorResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class ContratController extends Controller
{
    /**
     * Sérialise un contrat en ajoutant le professor via ProfessorResource
     * pour garantir que tous les champs (nationality, city, rib_number…) sont présents.
     */
    private function serializeContrat(Contrat $contrat): array
    {
        $data = $contrat->toArray();
        if ($contrat->relationLoaded('professor') && $contrat->professor) {
            $data['professor'] = (new ProfessorResource($contrat->professor))->toArray(request());
        }
        return $data;
    }

    // ─── LISTE (admin) ────────────────────────────────────────────────────────
    public function index()
    {
        $contrats = Contrat::with([
            'professor',
            'academicYear',
            'cycle',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $contrats->map(fn($c) => $this->serializeContrat($c)),
        ]);
    }

    // ─── LISTE DES CONTRATS D'UN PROFESSEUR (authentifié) ────────────────────
    public function myContrats(Request $request)
    {
        $professor = $request->user();

        $contrats = Contrat::with([
            'academicYear',
            'cycle',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])
            ->where('professor_id', $professor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $contrats->toArray(),
        ]);
    }

    // ─── PROGRAMMES D'UN PROFESSEUR ───────────────────────────────────────────
    public function professorPrograms($professorId)
    {
        $assignments = CourseElementProfessor::with([
            'courseElement.teachingUnit',
            'classGroup',
        ])
            ->where('professor_id', $professorId)
            ->get()
            ->map(function ($cep) {
                return [
                    'id'             => $cep->id,
                    'is_primary'     => $cep->is_primary,
                    'course_element' => [
                        'id'            => $cep->courseElement?->id,
                        'name'          => $cep->courseElement?->name,
                        'code'          => $cep->courseElement?->code,
                        'teaching_unit' => [
                            'id'   => $cep->courseElement?->teachingUnit?->id,
                            'name' => $cep->courseElement?->teachingUnit?->name,
                            'code' => $cep->courseElement?->teachingUnit?->code,
                        ],
                    ],
                    'class_group' => $cep->classGroup
                        ? ['id' => $cep->classGroup->id, 'name' => $cep->classGroup->name]
                        : null,
                    'label' => implode(' — ', array_filter([
                        $cep->courseElement?->code,
                        $cep->courseElement?->name,
                        $cep->classGroup?->name,
                    ])),
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $assignments,
        ]);
    }

    // ─── CRÉATION ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'division'                       => 'nullable|string|in:RD-FAD,RD-FC',
            'professor_id'                   => 'required|integer|exists:professors,id',
            'academic_year_id'               => 'required|integer|exists:academic_years,id',
            'cycle_id'                       => 'nullable|integer|exists:cycles,id',
            'regroupement'                   => 'nullable|string|in:1,2',
            'start_date'                     => 'required|date',
            'end_date'                       => 'nullable|date|after_or_equal:start_date',
            'amount'                         => 'required|numeric|min:100',
            'notes'                          => 'nullable|string|max:1000',
            'course_element_professor_ids'   => 'nullable|array',
            'course_element_professor_ids.*' => 'integer|exists:course_element_professor,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data       = $validator->validated();
            $programIds = $data['course_element_professor_ids'] ?? [];
            unset($data['course_element_professor_ids']);

            $contrat = Contrat::create(array_merge($data, [
                'status'         => 'pending',
                'contrat_number' => 1,
            ]));
            $contrat->contrat_number = str_pad($contrat->id, 3, '0', STR_PAD_LEFT);
            $contrat->save();

            if (!empty($programIds)) {
                $contrat->courseElementProfessors()->sync($programIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contrat créé avec succès',
                'data'    => $this->serializeContrat($contrat->load([
                    'professor',
                    'academicYear',
                    'cycle',
                    'courseElementProfessors.courseElement.teachingUnit',
                    'courseElementProfessors.classGroup',
                ])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du contrat',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── ACCÈS PAR TOKEN (lien email) ─────────────────────────────────────────
    public function showByToken(string $token)
    {
        $contrat = Contrat::with([
            'professor', 'academicYear', 'cycle',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])->where('uuid', $token)->first();

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Lien invalide ou expiré.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->serializeContrat($contrat),
        ]);
    }

    // ─── VALIDER PAR TOKEN ────────────────────────────────────────────────────
    public function validateByToken(string $token)
    {
        $contrat = Contrat::where('uuid', $token)->first();

        if (!$contrat) {
            return response()->json(['success' => false, 'message' => 'Lien invalide.'], 404);
        }

        if (!in_array($contrat->status, ['transfered', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat ne peut plus être modifié.',
            ], 400);
        }

        $contrat->update([
            'status'          => 'signed',
            'is_validated'    => true,
            'validation_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contrat validé avec succès.',
        ]);
    }

    // ─── REJETER PAR TOKEN (avec motif) ──────────────────────────────────────
    public function rejectByToken(Request $request, string $token)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:10|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Le motif de rejet est requis (minimum 10 caractères).',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $contrat = Contrat::where('uuid', $token)->first();

        if (!$contrat) {
            return response()->json(['success' => false, 'message' => 'Lien invalide.'], 404);
        }

        if (!in_array($contrat->status, ['transfered', 'pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat ne peut plus être modifié.',
            ], 400);
        }

        $contrat->update([
            'status'           => 'cancelled',
            'rejection_reason' => $validator->validated()['rejection_reason'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contrat rejeté. Votre motif a été transmis au CAP.',
        ]);
    }

    // ─── AUTORISER UN CONTRAT (admin, après validation professeur) ────────────
    public function authorizee($id)
    {
        $contrat = Contrat::find($id);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable.',
            ], 404);
        }

        if (!$contrat->is_validated || $contrat->status !== 'signed') {
            return response()->json([
                'success' => false,
                'message' => 'Le contrat doit être validé par le professeur avant de pouvoir être autorisé.',
            ], 400);
        }

        if ($contrat->is_authorized) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat est déjà autorisé.',
            ], 400);
        }

        $contrat->update([
            'is_authorized'      => true,
            'authorization_date' => now(),
            'status'             => 'ongoing',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contrat autorisé avec succès.',
            'data'    => $this->serializeContrat($contrat->load([
                'professor', 'academicYear', 'cycle',
                'courseElementProfessors.courseElement.teachingUnit',
                'courseElementProfessors.classGroup',
            ])),
        ]);
    }

    // ─── EMAIL DE TRANSFERT ───────────────────────────────────────────────────
    public function sendTransferEmail($id)
    {
        $contrat = Contrat::with(['professor', 'academicYear', 'cycle'])->find($id);

        if (!$contrat) {
            return response()->json(['success' => false, 'message' => 'Contrat introuvable'], 404);
        }

        if ($contrat->status !== 'transfered') {
            return response()->json([
                'success' => false,
                'message' => "Le contrat doit être en statut transféré pour envoyer l'email",
            ], 400);
        }

        try {
            $professor = $contrat->professor;
            $baseUrl   = rtrim(config('app.frontend_url', config('app.url')), '/');
            $token     = $contrat->uuid;

            $details = [
                'title'             => "Contrat N°{$contrat->contrat_number} — Action requise",
                'professor_name'    => $professor->full_name,
                'contrat_number'    => $contrat->contrat_number,
                'academic_year'     => $contrat->academicYear?->academic_year ?? '—',
                'amount'            => number_format($contrat->amount, 0, ',', ' '),
                'start_date'        => \Carbon\Carbon::parse($contrat->start_date)->format('d/m/Y'),
                'division'          => $contrat->division ?? '—',
                'cycle'             => $contrat->cycle?->name ?? '—',
                'regroupement'      => $contrat->regroupement === '1' ? 'I' : ($contrat->regroupement === '2' ? 'II' : '—'),
                'contrat_url'       => "{$baseUrl}/professor/contrats/{$token}",
                'my_contrats_url'   => "{$baseUrl}/professor/contrats",
                'dashboard_url'     => "{$baseUrl}/professor/dashboard",
                'link_expiry_hours' => 72,
            ];

            Mail::to($professor->email)->send(new \App\Mail\ContratTransferred($details));

            // Mise à jour du statut si envoi réussi
            $contrat->update(['status' => 'transfered']);

            return response()->json([
                'success' => true,
                'message' => 'Email envoyé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de l'envoi de l'email",
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── DÉTAIL ───────────────────────────────────────────────────────────────
    public function show($id)
    {
        $contrat = Contrat::with([
            'professor',
            'academicYear',
            'cycle',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])->find($id);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->serializeContrat($contrat),
        ]);
    }

    // ─── MISE À JOUR ──────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $contrat = Contrat::find($id);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'division'                       => 'nullable|string|in:RD-FAD,RD-FC',
            'professor_id'                   => 'required|integer|exists:professors,id',
            'academic_year_id'               => 'required|integer|exists:academic_years,id',
            'cycle_id'                       => 'nullable|integer|exists:cycles,id',
            'regroupement'                   => 'nullable|string|in:1,2',
            'start_date'                     => 'required|date',
            'end_date'                       => 'nullable|date|after_or_equal:start_date',
            'amount'                         => 'required|numeric|min:100',
            'status'                         => 'required|string|in:pending,signed,ongoing,completed,cancelled,transfered',
            'notes'                          => 'nullable|string|max:1000',
            'course_element_professor_ids'   => 'nullable|array',
            'course_element_professor_ids.*' => 'integer|exists:course_element_professor,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $data       = $validator->validated();
            $programIds = $data['course_element_professor_ids'] ?? null;
            unset($data['course_element_professor_ids']);

            $contrat->update($data);

            if ($programIds !== null) {
                $contrat->courseElementProfessors()->sync($programIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contrat modifié avec succès',
                'data'    => $this->serializeContrat($contrat->load([
                    'professor',
                    'academicYear',
                    'cycle',
                    'courseElementProfessors.courseElement.teachingUnit',
                    'courseElementProfessors.classGroup',
                ])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du contrat',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── SUPPRESSION ──────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $contrat = Contrat::find($id);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable',
            ], 404);
        }

        try {
            $contrat->courseElementProfessors()->detach();
            $contrat->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contrat supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
