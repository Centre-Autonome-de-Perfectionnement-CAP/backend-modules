<?php

namespace App\Modules\Inscription\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CahierTexte\Models\TextbookEntry;
use App\Modules\Cours\Models\Program;
use App\Modules\Cours\Models\CourseElementProfessor;
use App\Modules\EmploiDuTemps\Models\EmploiDuTemps;
use App\Modules\Inscription\Models\ClassGroup;
use App\Modules\Inscription\Models\StudentGroup;
use App\Modules\Inscription\Models\PersonalInformation;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\RH\Models\Contrat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class TextbookController extends Controller
{
    
    private function assertResponsableCanAccessClass(int $classGroupId): PersonalInformation{
        /** @var PersonalInformation $user */
        $user = Auth::user();

        if (!$user instanceof PersonalInformation || $user->role_id != 9) {
            abort(403, 'Non autorisé');
        }

        // Vérifier que la classe appartient bien au responsable (via la chaîne student)
        $pendingStudentIds = PendingStudent::where('personal_information_id', $user->id)
            ->pluck('id');

        if ($pendingStudentIds->isEmpty()) {
            abort(403, 'Aucune classe associée');
        }

        $studentIds = DB::table('student_pending_student')
            ->whereIn('pending_student_id', $pendingStudentIds)
            ->pluck('student_id')
            ->unique();

        $authorizedClassIds = StudentGroup::whereIn('student_id', $studentIds)
            ->pluck('class_group_id')
            ->unique();

        if (!$authorizedClassIds->contains($classGroupId)) {
            abort(403, 'Accès interdit à cette classe');
        }

        return $user;
    }

  
    public function getClassPrograms(Request $request, int $classGroupId) {
        $this->assertResponsableCanAccessClass($classGroupId);

        $programs = Program::where('class_group_id', $classGroupId)
            ->with([
                'courseElementProfessor.courseElement.teachingUnit',
                'courseElementProfessor.professor',
                'academicYear',
            ])
            ->get();

        $today = Carbon::today();

        $result = $programs->map(function (Program $program) use ($today) {
            $cep       = $program->courseElementProfessor;
            $element   = $cep?->courseElement;
            $professor = $cep?->professor;

            // ── Recherche du contrat le plus récent pour ce professeur + année ──
            $contractInfo = $this->resolveContractInfo(
                $professor?->id,
                $program->academic_year_id,
                $today
            );

            return [
                'id'                    => $program->id,
                'semester'              => $program->semester,
                'academic_year_id'      => $program->academic_year_id,
                'academic_year_name'    => optional($program->academicYear)->academic_year
                                          ?? optional($program->academicYear)->name
                                          ?? 'N/A',

                // ECUE
                'course_element_id'     => $element?->id,
                'course_element_name'   => $element?->name,
                'course_element_code'   => $element?->code,
                'teaching_unit_name'    => optional($element?->teachingUnit)->name,

                // Professeur
                'professor_id'          => $professor?->id,
                'professor_name'        => trim(
                    ($professor?->first_name ?? '') . ' ' . ($professor?->last_name ?? '')
                ),

                // Contrat
                'contract_status'       => $contractInfo['status'],
                'can_add_textbook'      => $contractInfo['can_add'],

                // Nombre d'entrées dans le cahier de texte
                'textbook_entries_count' => TextbookEntry::where('program_id', $program->id)->count(),
            ];
        });

        return response()->json(['programs' => $result]);
    }

   
    private function resolveContractInfo(?int $professorId, ?int $academicYearId, Carbon $today): array {
        if (!$professorId || !$academicYearId) {
            return ['status' => null, 'can_add' => false];
        }

        $contrat = Contrat::where('professor_id', $professorId)
            ->where('academic_year_id', $academicYearId)
            ->orderByDesc('created_at')
            ->first();

        if (!$contrat) {
            return ['status' => null, 'can_add' => false];
        }

        // Contrat expiré (date de fin dépassée)
        if ($contrat->end_date && $contrat->end_date->lt($today)) {
            return ['status' => 'expired', 'can_add' => false];
        }

        // Contrat en attente (non signé ou statut pending)
        if ($contrat->status === 'pending' || !$contrat->is_validated || !$contrat->is_authorized) {
            return ['status' => 'pending', 'can_add' => false];
        }

        // Contrat non valide pour ajout (ex: terminé)
        if ($contrat->status === 'completed') {
            return ['status' => 'validated', 'can_add' => true];
        }

        // Contrat valide (signed ou ongoing)
        if (in_array($contrat->status, ['signed', 'ongoing'])) {
            return ['status' => 'validated', 'can_add' => true];
        }

        return ['status' => null, 'can_add' => false];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Liste des entrées du cahier de texte d'un programme
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /responsable/programs/{programId}/textbook
     */
    public function index(Request $request, int $programId)
    {
        $program = Program::findOrFail($programId);
        $this->assertResponsableCanAccessClass($program->class_group_id);

        $entries = TextbookEntry::where('program_id', $programId)
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->get();

        return response()->json(['entries' => $entries]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Créer une entrée (brouillon)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /responsable/programs/{programId}/textbook
     *
     * Règles :
     * 1. Contrat du prof doit être validé + autorisé + non expiré
     * 2. La saisie n'est autorisée que pendant le créneau horaire de l'emploi du temps
     *    OU dans l'heure qui suit la fin du créneau
     */
    public function store(Request $request, int $programId)
    {
        $program = Program::with([
            'courseElementProfessor.professor',
            'classGroup',
        ])->findOrFail($programId);

        $this->assertResponsableCanAccessClass($program->class_group_id);

        // ── 1. Vérification du contrat ─────────────────────────────────────
        $today        = Carbon::today();
        $contractInfo = $this->resolveContractInfo(
            $program->courseElementProfessor?->professor?->id,
            $program->academic_year_id,
            $today
        );

        if (!$contractInfo['can_add']) {
            $messages = [
                null        => "Aucun contrat trouvé pour cet enseignant.",
                'pending'   => "Le contrat de l'enseignant est en attente de validation.",
                'rejected'  => "Le contrat de l'enseignant a été rejeté/annulé.",
                'expired'   => "Le contrat de l'enseignant a expiré.",
            ];
            return response()->json([
                'error'          => 'Saisie impossible',
                'contract_status' => $contractInfo['status'],
                'message'        => $messages[$contractInfo['status']] ?? 'Contrat invalide.',
            ], 422);
        }

        // ── 2. Vérification de l'emploi du temps ──────────────────────────
        $now        = Carbon::now();
        $dayOfWeek  = strtolower($now->englishDayOfWeek); // monday, tuesday…

        $scheduleSlot = EmploiDuTemps::where('class_group_id', $program->class_group_id)
            ->where('program_id', $programId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('is_cancelled', false)
            ->first();

        if (!$scheduleSlot) {
            return response()->json([
                'error'   => 'Saisie impossible',
                'message' => "Aucun cours prévu aujourd'hui ({$now->isoFormat('dddd')}) pour ce programme.",
            ], 422);
        }

        // Fenêtre autorisée : [start_time  →  end_time + 1 heure]
        $slotStart  = Carbon::parse($scheduleSlot->start_time)->setDateFrom($now);
        $slotEnd    = Carbon::parse($scheduleSlot->end_time)->setDateFrom($now);
        $deadline   = $slotEnd->copy()->addHour();

        if ($now->lt($slotStart) || $now->gt($deadline)) {
            return response()->json([
                'error'   => 'Saisie impossible',
                'message' => sprintf(
                    "La saisie est autorisée uniquement entre %s et %s (1 heure de retard incluse). Il est actuellement %s.",
                    $slotStart->format('H:i'),
                    $deadline->format('H:i'),
                    $now->format('H:i')
                ),
                'slot_start' => $slotStart->format('H:i'),
                'slot_end'   => $slotEnd->format('H:i'),
                'deadline'   => $deadline->format('H:i'),
                'now'        => $now->format('H:i'),
            ], 422);
        }

        // ── 3. Validation des données ──────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'session_title'    => 'required|string|max:255',
            'content_covered'  => 'required|string',
            'objectives'       => 'nullable|string',
            'teaching_methods' => 'nullable|string',
            'homework'         => 'nullable|string',
            'homework_due_date'=> 'nullable|date',
            'students_present' => 'nullable|integer|min:0',
            'students_absent'  => 'nullable|integer|min:0',
            'observations'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ── 4. Création de l'entrée en brouillon ───────────────────────────
        $entry = TextbookEntry::create([
            'program_id'       => $programId,
            'session_date'     => $now->toDateString(),
            'start_time'       => $scheduleSlot->start_time,
            'end_time'         => $scheduleSlot->end_time,
            'hours_taught'     => round($slotStart->diffInMinutes($slotEnd) / 60, 2),
            'session_title'    => $request->input('session_title'),
            'content_covered'  => $request->input('content_covered'),
            'objectives'       => $request->input('objectives'),
            'teaching_methods' => $request->input('teaching_methods'),
            'homework'         => $request->input('homework'),
            'homework_due_date'=> $request->input('homework_due_date'),
            'students_present' => $request->input('students_present'),
            'students_absent'  => $request->input('students_absent'),
            'observations'     => $request->input('observations'),
            'status'           => 'draft', // toujours brouillon à la création
        ]);

        return response()->json([
            'message' => 'Entrée créée avec succès (brouillon).',
            'entry'   => $entry,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mettre à jour une entrée (brouillon uniquement)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * PUT /responsable/textbook/{entryId}
     */
    public function update(Request $request, int $entryId)
    {
        $entry = TextbookEntry::findOrFail($entryId);
        $program = Program::findOrFail($entry->program_id);

        $this->assertResponsableCanAccessClass($program->class_group_id);

        if ($entry->status !== 'draft') {
            return response()->json([
                'error'   => 'Modification impossible',
                'message' => 'Seules les entrées au statut « brouillon » peuvent être modifiées.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'session_title'    => 'required|string|max:255',
            'content_covered'  => 'required|string',
            'objectives'       => 'nullable|string',
            'teaching_methods' => 'nullable|string',
            'homework'         => 'nullable|string',
            'homework_due_date'=> 'nullable|date',
            'students_present' => 'nullable|integer|min:0',
            'students_absent'  => 'nullable|integer|min:0',
            'observations'     => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $entry->update($request->only([
            'session_title', 'content_covered', 'objectives',
            'teaching_methods', 'homework', 'homework_due_date',
            'students_present', 'students_absent', 'observations',
        ]));

        return response()->json([
            'message' => 'Entrée mise à jour avec succès.',
            'entry'   => $entry->fresh(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Supprimer une entrée (brouillon uniquement)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * DELETE /responsable/textbook/{entryId}
     */
    public function destroy(int $entryId)
    {
        $entry   = TextbookEntry::findOrFail($entryId);
        $program = Program::findOrFail($entry->program_id);

        $this->assertResponsableCanAccessClass($program->class_group_id);

        if ($entry->status !== 'draft') {
            return response()->json([
                'error'   => 'Suppression impossible',
                'message' => 'Seules les entrées au statut « brouillon » peuvent être supprimées.',
            ], 422);
        }

        $entry->delete();

        return response()->json(['message' => 'Entrée supprimée avec succès.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vérification anticipée de la fenêtre de saisie (pour affichage UI)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /responsable/programs/{programId}/textbook/can-add
     *
     * Indique si la saisie est actuellement possible (contrat + emploi du temps).
     * Utilisé côté front pour afficher/griser le bouton « Ajouter ».
     */
    public function canAdd(int $programId)
    {
        $program = Program::with([
            'courseElementProfessor.professor',
        ])->findOrFail($programId);

        $this->assertResponsableCanAccessClass($program->class_group_id);

        $today        = Carbon::today();
        $now          = Carbon::now();
        $contractInfo = $this->resolveContractInfo(
            $program->courseElementProfessor?->professor?->id,
            $program->academic_year_id,
            $today
        );

        if (!$contractInfo['can_add']) {
            return response()->json([
                'can_add'         => false,
                'reason'          => 'contract',
                'contract_status' => $contractInfo['status'],
            ]);
        }

        $dayOfWeek   = strtolower($now->englishDayOfWeek);
        $scheduleSlot = EmploiDuTemps::where('class_group_id', $program->class_group_id)
            ->where('program_id', $programId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('is_cancelled', false)
            ->first();

        if (!$scheduleSlot) {
            return response()->json([
                'can_add' => false,
                'reason'  => 'no_schedule_today',
            ]);
        }

        $slotStart = Carbon::parse($scheduleSlot->start_time)->setDateFrom($now);
        $slotEnd   = Carbon::parse($scheduleSlot->end_time)->setDateFrom($now);
        $deadline  = $slotEnd->copy()->addHour();

        if ($now->lt($slotStart) || $now->gt($deadline)) {
            return response()->json([
                'can_add'    => false,
                'reason'     => 'outside_window',
                'slot_start' => $slotStart->format('H:i'),
                'slot_end'   => $slotEnd->format('H:i'),
                'deadline'   => $deadline->format('H:i'),
                'now'        => $now->format('H:i'),
            ]);
        }

        return response()->json([
            'can_add'    => true,
            'slot_start' => $slotStart->format('H:i'),
            'slot_end'   => $slotEnd->format('H:i'),
            'deadline'   => $deadline->format('H:i'),
        ]);
    }
}