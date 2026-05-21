<?php

namespace App\Modules\RH\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\RH\Models\Contrat;
use App\Modules\Cours\Models\CourseElementProfessor;
use App\Modules\RH\Http\Resources\ProfessorResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Modules\RH\Models\Professor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

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

    private function processAndStoreSignature(Request $request, Contrat $contrat): string
    {
        // ── Extension : toujours PNG pour conserver la transparence ──────────
        $folder   = 'signatures';
        $filename = $folder . '/' . Str::uuid() . '-contrat-' . $contrat->id . '.png';

        // ── Vider l'ancienne signature si elle existe ─────────────────────────
        if ($contrat->professor_signature_path) {
            Storage::disk('public')->delete($contrat->professor_signature_path);
        }

        // ── Source 1 : image base64 (canvas dessiné à la main) ──────────────
        if ($request->filled('signature_data')) {
            $base64Data = $request->input('signature_data');

            // Retirer le préfixe data:image/png;base64, s'il est présent
            if (str_contains($base64Data, ',')) {
                $base64Data = explode(',', $base64Data, 2)[1];
            }

            $imageData = base64_decode($base64Data);

            if ($imageData === false) {
                throw new \Exception('Données base64 invalides.');
            }

            // Supprimer l'arrière-plan blanc (GD)
            $imageData = $this->removeWhiteBackground($imageData);

            Storage::disk('public')->put($filename, $imageData);
            return $filename;
        }

        // ── Source 2 : fichier uploadé ────────────────────────────────────────
        if ($request->hasFile('signature_file')) {
            $file      = $request->file('signature_file');
            $imageData = file_get_contents($file->getRealPath());

            // Supprimer l'arrière-plan blanc/clair
            $imageData = $this->removeWhiteBackground($imageData, $file->getMimeType());

            Storage::disk('public')->put($filename, $imageData);
            return $filename;
        }

        throw new \Exception('Aucune source de signature valide.');
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

        if (empty($contrat->uuid)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat ne possède pas de token UUID. Veuillez le régénérer.',
            ], 400);
        }

        try {
            $professor    = $contrat->professor;
            $frontendBase = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');
            $contratUrl   = "{$frontendBase}/services/notes/professor/contrats/{$contrat->uuid}";

            $details = [
                'title'             => "Contrat N°{$contrat->contrat_number} — Signature requise",
                'professor_name'    => $professor->full_name,
                'contrat_number'    => $contrat->contrat_number,
                'academic_year'     => $contrat->academicYear?->academic_year ?? '—',
                'amount'            => number_format($contrat->amount, 0, ',', ' '),
                'start_date'        => \Carbon\Carbon::parse($contrat->start_date)->format('d/m/Y'),
                'division'          => $contrat->division ?? '—',
                'cycle'             => $contrat->cycle?->name ?? '—',
                'regroupement'      => $contrat->regroupement === '1' ? 'I' : ($contrat->regroupement === '2' ? 'II' : '—'),
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
                'message' => "Erreur lors de l'envoi de l'email " . $e->getMessage(),
                'error'   => $e->getMessage(),
            ], 500);
        }
        $contrat->update(['transferred_at' => now()]);
    }


    // ─── helpers ──────────────────────────────────────────────────────────────

    private function formatContrat(Contrat $c): array
    {
        $c->load([
            'professor',
            'cycle',
            'academicYear',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ]);

        return array_merge($c->toArray(), [
            'academic_year'             => $c->academicYear,
            'course_element_professors' => $c->courseElementProfessors->map(fn($p) => [
                'id'             => $p->id,
                'is_primary'     => $p->is_primary ?? false,
                'label'          => $p->label ?? ($p->courseElement->name ?? ''),
                'hours'          => $p->pivot->hours ?? 0,
                'course_element' => $p->courseElement ? [
                    'id'           => $p->courseElement->id,
                    'name'         => $p->courseElement->name,
                    'code'         => $p->courseElement->code,
                    'hours'        => $p->courseElement->hours ?? 0,
                    'teaching_unit' => $p->courseElement->teachingUnit ? [
                        'id'   => $p->courseElement->teachingUnit->id,
                        'name' => $p->courseElement->teachingUnit->name,
                        'code' => $p->courseElement->teachingUnit->code ?? '',
                    ] : null,
                ] : null,
                'class_group' => $p->classGroup ? [
                    'id'   => $p->classGroup->id,
                    'name' => $p->classGroup->name,
                ] : null,
            ])->values()->all(),
        ]);
    }

    // ─── INDEX ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $contrats = Contrat::with([
            'professor',
            'cycle',
            'academicYear',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $contrats->map(fn($c) => $this->formatContrat($c)),
        ]);
    }

    // ─── STORE ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'division'                       => 'nullable|string',
            'professor_id'                   => 'required|integer|exists:professors,id',
            'academic_year_id'               => 'required|integer',
            'cycle_id'                       => 'nullable|integer',
            'regroupement'                   => 'nullable|string',
            'start_date'                     => 'required|date',
            'end_date'                       => 'nullable|date|after_or_equal:start_date',
            'amount'                         => 'required|numeric|min:100',
            'notes'                          => 'nullable|string',
            'course_element_professor_ids'   => 'nullable|array',
            'course_element_professor_ids.*' => 'integer',
        ]);

        // Génération du numéro de contrat
        $lastContrat = Contrat::latest('id')->first();

        if ($lastContrat) {
            $nextNumber = $lastContrat->id + 1;
        } else {
            $nextNumber = 1;
        }

        // Formatage sur 5 caractères avec des zéros devant
        $validated['contrat_number'] = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        $validated['status'] = 'pending';

        $contrat = Contrat::create($validated);

        // Attachement des programmes
        if (!empty($validated['course_element_professor_ids'])) {
            $contrat->courseElementProfessors()->sync($validated['course_element_professor_ids']);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatContrat($contrat->fresh()),
        ], 201);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────

    public function show($id)
    {
        $contrat = Contrat::findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => $this->formatContrat($contrat),
        ]);
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $contrat = Contrat::findOrFail($id);

        // ── Verrouillage : un contrat validé ou autorisé ne peut plus être modifié ─
        if ($contrat->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat est verrouillé car il a déjà été validé ou autorisé. Aucune modification n\'est possible.',
            ], 403);
        }

        $validated = $request->validate([
            'division'                       => 'nullable|string',
            'professor_id'                   => 'required|integer|exists:professors,id',
            'academic_year_id'               => 'required|integer',
            'cycle_id'                       => 'nullable|integer',
            'regroupement'                   => 'nullable|string',
            'start_date'                     => 'required|date',
            'end_date'                       => 'nullable|date|after_or_equal:start_date',
            'amount'                         => 'required|numeric|min:100',
            'notes'                          => 'nullable|string',
            'status'                         => 'sometimes|string|in:pending,transfered,signed,ongoing,completed,cancelled',
            'course_element_professor_ids'   => 'nullable|array',
            'course_element_professor_ids.*' => 'integer',
        ]);

        $contrat->update($validated);

        // ── Si l'admin remet le contrat en "pending" (relance après rejet),
        //    on efface le motif de rejet pour repartir proprement ──────────────
        if (($validated['status'] ?? null) === 'pending') {
            $contrat->update(['rejection_reason' => null]);
        }

        if (array_key_exists('course_element_professor_ids', $validated)) {
            $contrat->courseElementProfessors()->sync($validated['course_element_professor_ids'] ?? []);
        }

        return response()->json([
            'success' => true,
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── DESTROY ──────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        $contrat = Contrat::findOrFail($id);

        // ── Verrouillage ────────────────────────────────────────────────────
        if ($contrat->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat est verrouillé (validé ou autorisé) et ne peut pas être supprimé.',
            ], 403);
        }

        $contrat->delete();

        return response()->json(['success' => true, 'message' => 'Contrat supprimé.']);
    }

    // ─── SHOW BY TOKEN ────────────────────────────────────────────────────────

    public function showByToken($token)
    {
        $contrat = Contrat::where('uuid', $token)->firstOrFail();
        $this->expireIfOverdue($contrat);
        return response()->json([
            'success' => true,
            'data'    => $this->formatContrat($contrat),
        ]);
    }

    // ─── VALIDATE BY TOKEN (signature électronique du professeur) ─────────────

    public function validateByToken(Request $request, $token)
    {
        $contrat = Contrat::where('uuid', $token)->firstOrFail();

        if ($contrat->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat a déjà été validé.',
            ], 422);
        }
        $this->expireIfOverdue($contrat->fresh());
$contrat->refresh();

if ($contrat->status === 'resiliated') {
    return response()->json([
        'success' => false,
        'message' => 'Ce lien a expiré (72h dépassées). Veuillez contacter le service RH du CAP.',
    ], 422);
}

        $request->validate([
            'signature_type' => 'required|in:drawn,uploaded,manual',
            'signature_data' => 'nullable|string',   // base64 pour 'drawn'
            'signature_file' => 'nullable|file|image|max:2048', // fichier pour 'uploaded'
        ]);

        $signaturePath = null;
        $signatureType = $request->input('signature_type');

        if ($signatureType === 'drawn' && $request->filled('signature_data')) {
            $dataUrl = $request->input('signature_data');
            $base64  = preg_replace('/^data:image\/\w+;base64,/', '', $dataUrl);
            $binary  = base64_decode($base64);

            $filename      = 'signatures/sig_' . $contrat->id . '_' . time() . '.png';
            Storage::disk('public')->put($filename, $binary);
            $signaturePath = $filename;

        } elseif ($signatureType === 'uploaded' && $request->hasFile('signature_file')) {
            $file          = $request->file('signature_file');
            $filename      = 'signatures/sig_' . $contrat->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('signatures', basename($filename), 'public');
            $signaturePath = $filename;
        }
        // Pour 'manual' (signer après impression) : pas de signature numérique

        $contrat->update([
            'is_validated'             => true,
            'validation_date'          => now(),
            'status'                   => 'signed',
            'professor_signature_path' => $signaturePath,
            'professor_signature_type' => $signatureType !== 'manual' ? $signatureType : null,
            'professor_signed_at'      => now(),
        ]);

        // Générer et stocker le PDF du contrat après validation
        $this->generateAndStorePdf($contrat);

        return response()->json([
            'success' => true,
            'message' => 'Contrat validé avec succès.',
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── REJECT BY TOKEN ──────────────────────────────────────────────────────

    public function rejectByToken(Request $request, $token)
    {
        $contrat = Contrat::where('uuid', $token)->firstOrFail();

        if ($contrat->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat a déjà été validé et ne peut plus être rejeté.',
            ], 422);
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:10|max:1000',
        ]);

        $contrat->update([
            'status'           => 'cancelled',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        // ── Notifier l'admin par email du rejet ───────────────────────────────
        try {
            $professor = $contrat->professor;
            $adminEmail = config('mail.admin_email', config('mail.from.address'));

        } catch (\Exception $e) {
            // Ne bloque pas la réponse si l'email échoue
            Log::warning("Email de notification de rejet non envoyé pour contrat #{$contrat->id} : " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Contrat rejeté. Le motif a été transmis au CAP.',
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── DOWNLOAD BY TOKEN ────────────────────────────────────────────────────

    public function downloadByToken($token)
    {
        $contrat = Contrat::where('uuid', $token)->firstOrFail();

        // Si un PDF stocké existe, le retourner directement
        if ($contrat->pdf_path && Storage::disk('public')->exists($contrat->pdf_path)) {
            return Storage::disk('public')->download(
                $contrat->pdf_path,
                'Contrat_' . $contrat->contrat_number . '.pdf'
            );
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucun PDF disponible pour ce contrat.',
        ], 404);
    }

    // ─── AUTHORIZE (admin) ────────────────────────────────────────────────────

    public function authorizeContrat(Request $request, $id)
    {
        $contrat = Contrat::findOrFail($id);

        if (!$contrat->is_validated) {
            return response()->json([
                'success' => false,
                'message' => 'Le contrat doit d\'abord être validé (signé) par le professeur avant d\'être autorisé.',
            ], 422);
        }

        $contrat->update([
            'is_authorized'      => true,
            'authorization_date' => now(),
            'status'             => 'ongoing',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contrat autorisé avec succès.',
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── UPLOAD PDF FINAL (admin remplace le PDF par un fichier uploadé) ──────

    public function uploadPdf(Request $request, $id)
    {
        $contrat = Contrat::findOrFail($id);

        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:10240', // max 10 Mo
        ]);

        // Supprimer l'ancien PDF s'il existe
        if ($contrat->pdf_path && Storage::disk('public')->exists($contrat->pdf_path)) {
            Storage::disk('public')->delete($contrat->pdf_path);
        }

        $file     = $request->file('pdf_file');
        $basename = 'pdf_' . $contrat->id . '_' . time() . '.pdf';
        $file->storeAs('contrats', $basename, 'public');
        $filename = 'contrats/' . $basename;

        $contrat->update([
            'pdf_path'        => $filename,
            'pdf_uploaded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'PDF mis à jour avec succès.',
            'data'    => $this->formatContrat($contrat->fresh()),
        ]);
    }

    // ─── PROFESSOR PROGRAMS ───────────────────────────────────────────────────

    public function professorPrograms($professorId)
    {
        $professor = Professor::findOrFail($professorId);

        $programs = \App\Modules\Cours\Models\CourseElementProfessor::with([
            'courseElement.teachingUnit',
            'classGroup',
        ])->where('professor_id', $professorId)->get();

        return response()->json([
            'success' => true,
            'data'    => $programs->map(fn($p) => [
                'id'             => $p->id,
                'is_primary'     => $p->is_primary ?? false,
                'label'          => $p->courseElement->name ?? '',
                'course_element' => $p->courseElement ? [
                    'id'           => $p->courseElement->id,
                    'name'         => $p->courseElement->name,
                    'code'         => $p->courseElement->code,
                    'teaching_unit' => $p->courseElement->teachingUnit ? [
                        'id'   => $p->courseElement->teachingUnit->id,
                        'name' => $p->courseElement->teachingUnit->name,
                        'code' => $p->courseElement->teachingUnit->code ?? '',
                    ] : null,
                ] : null,
                'class_group' => $p->classGroup ? [
                    'id'   => $p->classGroup->id,
                    'name' => $p->classGroup->name,
                ] : null,
            ])->values(),
        ]);
    }

    // ─── MY CONTRATS (professeur connecté) ────────────────────────────────────

    public function myContrats(Request $request)
    {
        $user = $request->user();

        // Rechercher le professeur lié à l'utilisateur connecté
        $professor = Professor::where('email', $user->email)->first();

        if (!$professor) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $contrats = Contrat::where('status', '!=', 'pending')->with([
            'professor',
            'cycle',
            'academicYear',
            'courseElementProfessors.courseElement.teachingUnit',
            'courseElementProfessors.classGroup',
        ])->where('professor_id', $professor->id)->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $contrats->map(fn($c) => $this->formatContrat($c)),
        ]);
    }

    // ─── GENERATE AND STORE PDF ───────────────────────────────────────────────

    private function generateAndStorePdf(Contrat $contrat): void
    {
        try {
            // Charger les relations nécessaires
            $contrat->load([
                'professor',
                'cycle',
                'academicYear',
                'courseElementProfessors.courseElement.teachingUnit',
                'courseElementProfessors.classGroup',
            ]);

            if (!$contrat) {
                throw new \Exception('Contrat invalide');
            }

            // Générer le HTML via Blade
            $html = view('pdf.contrat', ['contrat' => $contrat])->render();

            if (empty($html)) {
                throw new \Exception('Le rendu HTML est vide');
            }

            $filename = 'contrats/contrat_' . $contrat->id . '_' . time() . '.pdf';

            // ───────────── DomPDF ─────────────
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                try {
                    $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                    Storage::disk('public')->put($filename, $pdf->output());
                    $contrat->update([
                        'pdf_path'        => $filename,
                        'pdf_uploaded_at' => now(),
                    ]);
                    Log::info("PDF généré avec DomPDF pour contrat #{$contrat->id}");
                    return;
                } catch (\Exception $e) {
                    Log::error("Erreur DomPDF contrat #{$contrat->id} : " . $e->getMessage());
                }
            }

            // ───────────── Snappy ─────────────
            if (app()->bound('snappy.pdf')) {
                try {
                    $snappy = app('snappy.pdf');
                    $output = $snappy->getOutputFromHtml($html);
                    Storage::disk('public')->put($filename, $output);
                    $contrat->update([
                        'pdf_path'        => $filename,
                        'pdf_uploaded_at' => now(),
                    ]);
                    Log::info("PDF généré avec Snappy pour contrat #{$contrat->id}");
                    return;
                } catch (\Exception $e) {
                    Log::error("Erreur Snappy contrat #{$contrat->id} : " . $e->getMessage());
                }
            }

            Log::warning("Aucune librairie PDF disponible pour contrat #{$contrat->id}");

        } catch (\Throwable $e) {
            Log::error("Erreur globale génération PDF contrat #{$contrat->id} : " . $e->getMessage());
        }
    }

private function expireIfOverdue(Contrat $contrat): void
{
    if (
        $contrat->status === 'transfered'
        && !$contrat->is_validated
        && $contrat->transferred_at
        && Carbon::parse($contrat->transferred_at)->addHours(72)->isPast()
    ) {
        $contrat->update(['status' => 'resiliated']);
    }
}

    public function listProgramSupports(Request $request, $contratId, $programId)
    {
        $contrat = \App\Modules\RH\Models\Contrat::findOrFail($contratId);

        // Récupérer la ligne pivot dans contrat_programs
        $pivot = \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->first();

        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Programme introuvable pour ce contrat.',
            ], 404);
        }

        $supports = json_decode($pivot->course_support_file ?? '[]', true) ?? [];

        // Reconstruire les URLs publiques
        $supports = array_map(function ($s) {
            if (!empty($s['file']) && Storage::disk('public')->exists($s['file'])) {
                $s['url'] = Storage::disk('public')->url($s['file']);
            }
            return $s;
        }, $supports);

        return response()->json([
            'success' => true,
            'data'    => array_values($supports),
        ]);
    }

    // ─── AJOUT d'un support ───────────────────────────────────────────────────

    /**
     * POST /api/rh/contrats/{contratId}/programs/{programId}/supports
     *
     * Body (multipart/form-data) :
     *   - title    : string (obligatoire)
     *   - pdf_file : file PDF (obligatoire)
     */
    public function addProgramSupport(Request $request, $contratId, $programId)
    {
        $contrat = \App\Modules\RH\Models\Contrat::findOrFail($contratId);

        $request->validate([
            'title'    => 'required|string|max:255',
            'pdf_file' => 'required|file|mimes:pdf|max:20480', // max 20 Mo
        ]);

        // Récupérer la ligne pivot
        $pivot = \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->first();

        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Programme introuvable pour ce contrat.',
            ], 404);
        }

        // Charger les supports existants
        $supports = json_decode($pivot->course_support_file ?? '[]', true) ?? [];

        // Stocker le fichier PDF
        $file     = $request->file('pdf_file');
        $basename = \Illuminate\Support\Str::uuid() . '-support-' . $contratId . '-' . $programId . '.pdf';
        $path     = 'supports/' . $basename;
        $file->storeAs('supports', $basename, 'public');

        // Ajouter l'entrée au tableau
        $supports[] = [
            'title' => $request->input('title'),
            'file'  => $path,
        ];

        // Mettre à jour la colonne JSON + updated_by
        \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->update([
                'course_support_file' => json_encode(array_values($supports)),
                'updated_by'          => 'professor',
                'updated_at'          => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Support de cours ajouté avec succès.',
            'data'    => [
                'title' => $request->input('title'),
                'file'  => $path,
                'url'   => Storage::disk('public')->url($path),
            ],
        ], 201);
    }

    // ─── SUPPRESSION d'un support ─────────────────────────────────────────────

    /**
     * DELETE /api/rh/contrats/{contratId}/programs/{programId}/supports/{index}
     *
     * Supprime l'entrée à l'index {index} du tableau JSON et efface le fichier.
     */
    public function deleteProgramSupport(Request $request, $contratId, $programId, $index)
    {
        $pivot = \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->first();

        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Programme introuvable pour ce contrat.',
            ], 404);
        }

        $supports = json_decode($pivot->course_support_file ?? '[]', true) ?? [];
        $index    = (int) $index;

        if (!isset($supports[$index])) {
            return response()->json([
                'success' => false,
                'message' => "Support introuvable à l'index {$index}.",
            ], 404);
        }

        // Supprimer le fichier physique
        $filePath = $supports[$index]['file'] ?? null;
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        // Retirer du tableau et ré-indexer
        array_splice($supports, $index, 1);

        \DB::table('contrat_programs')
            ->where('contrat_id', $contratId)
            ->where('course_element_professor_id', $programId)
            ->update([
                'course_support_file' => json_encode(array_values($supports)),
                'updated_by'          => 'professor',
                'updated_at'          => now(),
            ]);

Contrat::where('status', 'transfered')
    ->where('is_validated', false)
    ->whereNotNull('transferred_at')
    ->where('transferred_at', '<', Carbon::now()->subHours(72))
    ->update(['status' => 'resiliated']);
    
        return response()->json([
            'success' => true,
            'message' => 'Support supprimé avec succès.',
        ]);
    }
}
