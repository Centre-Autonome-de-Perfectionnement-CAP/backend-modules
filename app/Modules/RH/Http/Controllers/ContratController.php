<?php

namespace App\Modules\RH\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Contrat;
use App\Modules\Cours\Models\CourseElementProfessor;
use App\Modules\RH\Http\Resources\ProfessorResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class ContratController extends Controller{

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

    // ─── LISTE ────────────────────────────────────────────────────────────────
    public function index()  {
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

    // ─── PROGRAMMES D'UN PROFESSEUR ──────────────────────────────────────────
    public function professorPrograms($professorId) {
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
                        'id'           => $cep->courseElement?->id,
                        'name'         => $cep->courseElement?->name,
                        'code'         => $cep->courseElement?->code,
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
    public function store(Request $request) {
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

            $contrat = Contrat::create(array_merge($data, ['status' => 'pending', 'contrat_number'  =>  1]));
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

    public function sendTransferEmail($id) {
        $contrat = Contrat::with('professor')->find($id);

        if (!$contrat) {
            return response()->json([
                'success' => false,
                'message' => 'Contrat introuvable',
            ], 404);
        }

        if ($contrat->status !== 'transfered') {
            return response()->json([
                'success' => false,
                'message' => 'Le contrat doit être en statut transféré pour envoyer l\'email',
            ], 400);
        }

        try {
            // Envoyer l'email à l'enseignant
            $professor = $contrat->professor;
            $details = [
                'title' => 'Votre contrat a été transféré',
                'body' => "Bonjour {$professor->full_name},

    Votre contrat n°{$contrat->contrat_number} a été transféré et est maintenant disponible pour signature.

    Cordialement,
    L'équipe du CAP"
            ];

            Mail::to($professor->email)->send(new \App\Mail\ContratTransferred($details));

            return response()->json([
                'success' => true,
                'message' => 'Email de notification envoyé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // ─── DÉTAIL ───────────────────────────────────────────────────────────────
    public function show($id) {
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
            // CORRIGÉ : 'transfered' ajouté dans la liste des statuts autorisés
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
