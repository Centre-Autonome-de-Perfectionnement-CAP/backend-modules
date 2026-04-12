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

    // ─── LISTE DES CONTRATS DU PROFESSEUR CONNECTÉ ───────────────────────────
    public function myContrats(Request $request)
    {
        $user = $request->user();

        // Sanctum peut authentifier plusieurs modèles (User, Professor, PersonalInformation).
        // On s'assure que c'est bien un Professor qui appelle cette route.
        if (!($user instanceof \App\Modules\RH\Models\Professor)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé aux professeurs.',
            ], 403);
        }

        $contrats = Contrat::with([
            'academicYear',
            'cycle',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])
            ->where('professor_id', $user->id)
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

    // ─── ACCÈS PAR TOKEN (lien email — PUBLIC) ────────────────────────────────
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

        if (!in_array($contrat->status, ['transfered', 'pending', 'signed'])) {
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

        if (!in_array($contrat->status, ['transfered', 'pending', 'signed'])) {
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
            'message' => 'Contrat rejeté. Votre motif a été transmis au service RH du CAP.',
        ]);
    }

    // ─── TÉLÉCHARGEMENT PDF PAR TOKEN ─────────────────────────────────────────
    public function downloadByToken(string $token)
    {
        $contrat = Contrat::with([
            'professor', 'academicYear', 'cycle',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])->where('uuid', $token)->first();

        if (!$contrat) {
            return response()->json(['success' => false, 'message' => 'Lien invalide ou expiré.'], 404);
        }

        if (!$contrat->is_validated || !$contrat->is_authorized) {
            return response()->json([
                'success' => false,
                'message' => 'Le contrat doit être validé et autorisé avant de pouvoir être téléchargé.',
            ], 403);
        }

        // Générer ou retourner le PDF du contrat
        // Adaptez selon votre système de génération PDF (DomPDF, Snappy, fichier stocké, etc.)
        try {
            // Option 1 : Fichier PDF déjà généré et stocké
            if ($contrat->pdf_path && \Storage::exists($contrat->pdf_path)) {
                return \Storage::download(
                    $contrat->pdf_path,
                    "contrat-{$contrat->contrat_number}.pdf",
                    ['Content-Type' => 'application/pdf']
                );
            }

            // Option 2 : Génération à la volée avec DomPDF (si installé)
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.contrat', ['contrat' => $contrat]);
                return $pdf->download("contrat-{$contrat->contrat_number}.pdf");
            }

            return response()->json([
                'success' => false,
                'message' => 'Génération PDF non configurée. Contactez le service RH.',
            ], 501);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ─── AUTORISER UN CONTRAT (admin) ─────────────────────────────────────────
    public function authorizeContrat($id)
    {
        $contrat = Contrat::find($id);

        if (!$contrat) {
            return response()->json(['success' => false, 'message' => 'Contrat introuvable.'], 404);
        }

        if (!$contrat->is_validated || $contrat->status !== 'signed') {
            return response()->json([
                'success' => false,
                'message' => 'Le contrat doit être validé par le professeur avant de pouvoir être autorisé.',
            ], 400);
        }

        if ($contrat->is_authorized) {
            return response()->json(['success' => false, 'message' => 'Ce contrat est déjà autorisé.'], 400);
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
    /**
     * Le lien envoyé par email pointe vers le FRONTEND React :
     *   http://localhost:3000/services/notes/professor/contrats/{uuid}
     *
     * L'app React a basename="/services", donc la route complète dans React est :
     *   /notes/professor/contrats/{uuid}  (montée dans NoteRoutes)
     *
     * Quand le professeur clique :
     *   1. Si connecté → ProfessorContratDetail charge directement le contrat
     *   2. Si non connecté → NoteRoutes redirige vers /login?redirectTo=<url encodée>
     *   3. Après login → la page Login lit redirectTo et renvoie vers le contrat
     */
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

        if (empty($contrat->uuid)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat ne possède pas de token UUID. Veuillez le régénérer.',
            ], 400);
        }

        try {
            $professor = $contrat->professor;

            // basename React = /services
            // Route React = /notes/professor/contrats/:token  (montée sous NoteRoutes)
            // → Lien complet = http://localhost:3000/services/notes/professor/contrats/{uuid}
            // Si non connecté : NoteRoutes redirige vers /login?redirectTo=<url>
            // Après login : la page Login lit redirectTo et renvoie vers la bonne URL
            $frontendBase = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');
            $contratUrl   = "{$frontendBase}/services/notes/professor/contrats/{$contrat->uuid}";

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
                // Lien unique vers la page du contrat (avec redirection login si non connecté)
                'contrat_url'       => $contratUrl,
                'link_expiry_hours' => 72,
            ];

            Mail::to($professor->email)->send(new \App\Mail\ContratTransferred($details));

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
            return response()->json(['success' => false, 'message' => 'Contrat introuvable'], 404);
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
            return response()->json(['success' => false, 'message' => 'Contrat introuvable'], 404);
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
            return response()->json(['success' => false, 'message' => 'Contrat introuvable'], 404);
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
