<?php

namespace App\Modules\Notes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CahierTexte\Models\TextbookEntry;
use App\Modules\Cours\Models\Program;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfessorTextbookController extends Controller{
    use ApiResponse;

 
    public function getStats(Request $request): JsonResponse  {
        $professorId = $request->user()->id;

        // Récupérer tous les programs de ce professeur
        $programIds = $this->getProfessorProgramIds($professorId);

        if ($programIds->isEmpty()) {
            return $this->successResponse([
                'total_hours_published' => 0,
                'total_hours_draft'     => 0,
                'count_published'       => 0,
                'count_draft'           => 0,
                'programs_count'        => 0,
            ], 'Statistiques récupérées');
        }

        $base = TextbookEntry::whereIn('program_id', $programIds);

        $published = (clone $base)->where('status', 'published');
        $draft     = (clone $base)->where('status', 'draft');

        return $this->successResponse([
            'total_hours_published' => round((float) $published->sum('hours_taught'), 2),
            'total_hours_draft'     => round((float) $draft->sum('hours_taught'), 2),
            'count_published'       => $published->count(),
            'count_draft'           => $draft->count(),
            'programs_count'        => $programIds->count(),
        ], 'Statistiques récupérées');
    }

 


public function getPrograms(Request $request): JsonResponse
{
    try {
        $professorId = $request->user()->id;
        $academicYearId = $request->input('academic_year_id')
            ? (int) $request->input('academic_year_id')
            : null;

        $query = Program::with([
            'classGroup.department',
            'classGroup.academicYear',
            'courseElementProfessor.courseElement',
            'courseElementProfessor.professor',
            'academicYear',
        ])
        ->whereHas('courseElementProfessor', function ($q) use ($professorId) {
            $q->where('professor_id', $professorId);
        });

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        $programs = $query->get()->map(function (Program $p) {

            $cep        = $p->courseElementProfessor;
            $element    = $cep?->courseElement;
            $classGroup = $p->classGroup;
            $dept       = $classGroup?->department;
            $year       = $p->academicYear;

            // 🔴 LOG des relations manquantes
            if (!$cep) {
                Log::warning('CEP manquant', ['program_id' => $p->id]);
            }

            if (!$element) {
                Log::warning('CourseElement manquant', [
                    'program_id' => $p->id,
                    'cep_id' => $cep?->id
                ]);
            }

            if (!$classGroup) {
                Log::warning('ClassGroup manquant', ['program_id' => $p->id]);
            }

            if (!$dept) {
                Log::warning('Department manquant', [
                    'program_id' => $p->id,
                    'class_group_id' => $classGroup?->id
                ]);
            }

            if (!$year) {
                Log::warning('AcademicYear manquant', [
                    'program_id' => $p->id
                ]);
            }

            // Optimisation des compteurs (évite 3 requêtes)
            $counts = TextbookEntry::where('program_id', $p->id)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(status = 'published') as published,
                    SUM(status = 'draft') as draft
                ")
                ->first();

            return [
                'id'                => $p->id,
                'uuid'              => $p->uuid,
                'course_name'       => $element?->name ?? 'N/A',
                'course_code'       => $element?->code ?? 'N/A',
                'class_name'        => $classGroup?->group_name ?? 'N/A',
                'department_name'   => $dept?->name ?? 'N/A',
                'academic_year'     => $year?->academic_year ?? 'N/A',
                'semester'          => $p->semester,
                'entries_published' => (int) ($counts->published ?? 0),
                'entries_draft'     => (int) ($counts->draft ?? 0),
                'entries_total'     => (int) ($counts->total ?? 0),
            ];
        });

        return $this->successResponse($programs, 'Programmes récupérés');

    } catch (\Throwable $e) {
        Log::error('Erreur récupération programmes', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
            'user_id' => $request->user()?->id
        ]);

        return response()->json([
            'message' => 'Une erreur est survenue'
        ], 500);
    }
}

    public function getEntries(Request $request, int $programId): JsonResponse  {
        $professorId = $request->user()->id;

        $program = Program::whereHas('courseElementProfessor', function ($q) use ($professorId) {
            $q->where('professor_id', $professorId);
        })->findOrFail($programId);

        $entries = TextbookEntry::where('program_id', $program->id)
            ->orderByDesc('session_date')
            ->orderByDesc('start_time')
            ->get()
            ->map(fn ($e) => $this->formatEntry($e));

        // Stats rapides pour ce programme
        $allEntries = TextbookEntry::where('program_id', $program->id);
        $stats = [
            'total_hours'     => round((float) (clone $allEntries)->sum('hours_taught'), 2),
            'hours_published' => round((float) (clone $allEntries)->where('status', 'published')->sum('hours_taught'), 2),
            'hours_draft'     => round((float) (clone $allEntries)->where('status', 'draft')->sum('hours_taught'), 2),
            'count_published' => (clone $allEntries)->where('status', 'published')->count(),
            'count_draft'     => (clone $allEntries)->where('status', 'draft')->count(),
        ];

        return $this->successResponse([
            'entries' => $entries,
            'stats'   => $stats,
        ], 'Entrées récupérées');
    }

    public function publishEntry(Request $request, int $entryId): JsonResponse {
        $professorId = $request->user()->id;

        $entry = TextbookEntry::findOrFail($entryId);

        // Vérifier que l'entrée appartient bien à un programme de ce professeur
        $program = Program::whereHas('courseElementProfessor', function ($q) use ($professorId) {
            $q->where('professor_id', $professorId);
        })->find($entry->program_id);

        if (!$program) {
            return $this->forbiddenResponse('Vous n\'avez pas accès à cette entrée');
        }

        if ($entry->status === 'published' || $entry->status === 'validated') {
            return $this->errorResponse('Cette entrée est déjà publiée ou validée', 422);
        }

        $entry->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        return $this->successResponse($this->formatEntry($entry->fresh()), 'Entrée publiée avec succès');
    }

    public function unpublishEntry(Request $request, int $entryId): JsonResponse
    {
        $professorId = $request->user()->id;

        $entry = TextbookEntry::findOrFail($entryId);

        $program = Program::whereHas('courseElementProfessor', function ($q) use ($professorId) {
            $q->where('professor_id', $professorId);
        })->find($entry->program_id);

        if (!$program) {
            return $this->forbiddenResponse('Vous n\'avez pas accès à cette entrée');
        }

        if ($entry->status === 'validated') {
            return $this->errorResponse('Une entrée validée ne peut plus être modifiée', 422);
        }

        $entry->update([
            'status'       => 'draft',
            'published_at' => null,
        ]);

        return $this->successResponse($this->formatEntry($entry->fresh()), 'Entrée repassée en brouillon');
    }

    private function getProfessorProgramIds(int $professorId): \Illuminate\Support\Collection {
        return Program::whereHas('courseElementProfessor', function ($q) use ($professorId) {
            $q->where('professor_id', $professorId);
        })->pluck('id');
    }

    private function formatEntry(TextbookEntry $e): array {
        return [
            'id'               => $e->id,
            'uuid'             => $e->uuid ?? null,
            'program_id'       => $e->program_id,
            'session_date'     => $e->session_date?->format('Y-m-d'),
            'start_time'       => $e->start_time,
            'end_time'         => $e->end_time,
            'hours_taught'     => $e->hours_taught,
            'session_title'    => $e->session_title,
            'content_covered'  => $e->content_covered,
            'objectives'       => $e->objectives,
            'teaching_methods' => $e->teaching_methods,
            'homework'         => $e->homework,
            'homework_due_date'=> $e->homework_due_date?->format('Y-m-d'),
            'students_present' => $e->students_present,
            'students_absent'  => $e->students_absent,
            'observations'     => $e->observations,
            'status'           => $e->status,
            'published_at'     => $e->published_at?->format('Y-m-d H:i'),
            'validated_at'     => $e->validated_at?->format('Y-m-d H:i'),
            'created_at'       => $e->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function forbiddenResponse(string $message): JsonResponse   {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    private function errorResponse(string $message, int $code = 400): JsonResponse  {
        return response()->json(['success' => false, 'message' => $message], $code);
    }
}