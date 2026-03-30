<?php

namespace App\Modules\Soutenance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Soutenance\Http\Requests\CreateJuryMemberRequest;
use App\Modules\Soutenance\Http\Requests\UpdateJuryMemberRequest;
use App\Modules\Soutenance\Services\DefenseJuryService;
use App\Modules\Soutenance\Services\DefenseSubmissionService;
use App\Modules\Soutenance\Models\DefenseJuryMember;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DefenseJuryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected DefenseJuryService $defenseJuryService,
        protected DefenseSubmissionService $defenseSubmissionService
    ) {}

    public function index(int $submissionId): JsonResponse
    {
        $submission = $this->defenseSubmissionService->getById($submissionId);

        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $juryMembers = $this->defenseJuryService->getJuryMembers($submission);

        return $this->successResponse($juryMembers, 'Membres du jury récupérés avec succès');
    }

    public function getData(Request $request)
    {
        $query = \App\Modules\Soutenance\Models\DefenseSubmission::with(['professor', 'juryMembers.professor', 'period'])
            ->where('status', 'accepted')
            ->when($request->academic_year, function ($q) use ($request) {
                $q->whereHas('period', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year);
                });
            })
            ->when($request->defense_type, function ($q) use ($request) {
                $q->where('defense_type', $request->defense_type);
            })
            ->when($request->jury_status === 'complete', function ($q) {
                $q->whereHas('juryMembers', function ($q) {
                    $q->where('role', 'president');
                })->whereHas('juryMembers', function ($q) {
                    $q->where('role', 'directeur');
                });
            })
            ->when($request->jury_status === 'incomplete', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereDoesntHave('juryMembers', function ($q) {
                        $q->where('role', 'president');
                    })->orWhereDoesntHave('juryMembers', function ($q) {
                        $q->where('role', 'directeur');
                    });
                });
            })
            ->when($request->scheduled === 'scheduled', function ($q) {
                $q->whereNotNull('defense_date')->whereNotNull('room_id');
            })
            ->when($request->scheduled === 'unscheduled', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('defense_date')->orWhereNull('room_id');
                });
            });

        return datatables()->eloquent($query)
            ->addIndexColumn()
            ->addColumn('student_name', fn($row) => $row->first_names . ' ' . $row->last_name ?? 'N/A')
            ->addColumn('professor_name', fn($row) => $row->professor->first_name . ' ' . $row->professor->last_name ?? 'N/A')
            ->addColumn('jury_status', function ($row) {
                $hasPresident = $row->juryMembers->contains('role', 'president');
                $hasDirecteur = $row->juryMembers->contains('role', 'directeur');
                if ($hasPresident && $hasDirecteur) {
                    return '<span class="badge badge-jury-complete text-white p-2">Complet</span>';
                }
                return '<span class="badge badge-jury-incomplete text-white p-2">Incomplet</span>';
            })
            ->addColumn('defense_date', fn($row) => $row->defense_date ? $row->defense_date->format('d/m/Y H:i') : 'Non planifié')
            ->addColumn('room', fn($row) => $row->room ? ($row->room->name ?? $row->room) : '-')
            ->addColumn('actions', fn($row) => '<button class="btn btn-sm btn-primary btn-configure-jury" data-id="' . $row->id . '">
                <i class="fa fa-users"></i> Constituer
            </button>')
            ->rawColumns(['jury_status', 'actions'])
            ->toJson();
    }

    public function getJury(Request $request)
    {
        $submission = $this->defenseSubmissionService->getById($request->submission_id);
        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        return $this->successResponse([
            'room' => $submission->room,
            'defense_date' => $submission->defense_date ? $submission->defense_date->format('Y-m-d\TH:i') : null,
            'members' => $submission->juryMembers->map(function ($member) {
                return [
                    'professor_id' => $member->professor_id,
                    'role' => $member->role
                ];
            })
        ], 'Jury récupéré avec succès');
    }

    public function getSuggestions(Request $request)
    {
        $submission = $this->defenseSubmissionService->getById($request->submission_id);
        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $suggestions = $this->defenseJuryService->getScheduleSuggestions($submission);
        return $this->successResponse($suggestions, 'Suggestions récupérées avec succès');
    }

    public function checkstatus(Request $request)
    {
        $submissions = \App\Modules\Soutenance\Models\DefenseSubmission::with('juryMembers')
            ->whereHas('period', function ($q) use ($request) {
                $q->where('academic_year_id', $request->academic_year);
            })
            ->where('defense_type', $request->defense_type)
            ->where('status', 'accepted')
            ->get();

        $required = match ($request->defense_type) {
            'licence' => 3,
            'master' => 4,
            default => 3,
        };

        $complete = $submissions->filter(function ($submission) use ($required) {
            return $submission->juryMembers->count() >= $required;
        })->count();

        $total = $submissions->count();

        return $this->successResponse([
            'all_complete' => $total > 0 && $complete === $total,
            'complete_count' => $complete,
            'total_count' => $total
        ], 'status des jurys récupéré avec succès');
    }

    public function store(Request $request, int $submissionId): JsonResponse
    {
        $submission = $this->defenseSubmissionService->getById($submissionId);

        if (!$submission) {
            return $this->errorResponse('Soumission non trouvée', 404);
        }

        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'defense_date' => 'required|date',
            'jury_members' => 'required|array|min:2',
        ]);

        foreach ($request->jury_members as $i => $member) {
            if (($member['professor_id'] ?? null) === 'external') {
                if (empty($member['external_name'])) {
                    return $this->validationErrorResponse("Le nom de l'intervenant externe est requis.");
                }
            } else {
                $request->validate([
                    "jury_members.$i.professor_id" => 'required|exists:professors,id',
                    "jury_members.$i.role" => 'required|string|in:president,directeur,rapporteur,examinateur'
                ]);
            }
        }

        $roles = collect($request->jury_members)->pluck('role');
        if (!$roles->contains('president')) {
            return $this->validationErrorResponse('Un président du jury est requis');
        }
        if (!$roles->contains('directeur')) {
            return $this->validationErrorResponse('Un directeur de mémoire est requis');
        }

        $submission->update([
            'room_id' => $request->room_id,
            'defense_date' => $request->defense_date
        ]);

        $this->defenseJuryService->syncJuryMembers($submission, $request->jury_members);

        return $this->successResponse(null, 'Jury enregistré avec succès');
    }

    public function update(UpdateJuryMemberRequest $request, int $submissionId, int $juryMemberId): JsonResponse
    {
        $juryMember = DefenseJuryMember::find($juryMemberId);

        if (!$juryMember || $juryMember->defense_submission_id != $submissionId) {
            return $this->errorResponse('Membre du jury non trouvé', 404);
        }

        $juryMember = $this->defenseJuryService->updateJuryMember($juryMember, $request->validated());

        return $this->successResponse($juryMember, 'Membre du jury mis à jour avec succès');
    }

    public function destroy(int $submissionId, int $juryMemberId): JsonResponse
    {
        $juryMember = DefenseJuryMember::find($juryMemberId);

        if (!$juryMember || $juryMember->defense_submission_id != $submissionId) {
            return $this->errorResponse('Membre du jury non trouvé', 404);
        }

        $this->defenseJuryService->deleteJuryMember($juryMember);

        return $this->successResponse(null, 'Membre du jury supprimé avec succès');
    }
}
