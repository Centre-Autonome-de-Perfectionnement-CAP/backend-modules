<?php

namespace App\Modules\RH\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Contrat;
use App\Modules\Cours\Models\CourseElementProfessor;
use Illuminate\Support\Facades\Validator;

class ContratController extends Controller
{
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
            'data'    => $contrats,
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

            $contrat = Contrat::create(array_merge($data, ['status' => 'pending']));
            $contrat->contrat_number = str_pad($contrat->id, 3, '0', STR_PAD_LEFT);
            $contrat->save();

            if (!empty($programIds)) {
                $contrat->courseElementProfessors()->sync($programIds);
            }

            return response()->json([
                'success' => true,
                'message' => 'Contrat créé avec succès',
                'data'    => $contrat->load([
                    'professor',
                    'academicYear',
                    'cycle',
                    'courseElementProfessors.courseElement.teachingUnit',
                    'courseElementProfessors.classGroup',
                ]),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du contrat',
                'error'   => $e->getMessage(),
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
            'data'    => $contrat,
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
                'data'    => $contrat->load([
                    'professor',
                    'academicYear',
                    'cycle',
                    'courseElementProfessors.courseElement.teachingUnit',
                    'courseElementProfessors.classGroup',
                ]),
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