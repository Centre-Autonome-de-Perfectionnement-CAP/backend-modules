<?php

namespace App\Modules\Soutenance\Services;

use App\Modules\Soutenance\Models\DefenseJuryMember;
use App\Modules\Soutenance\Models\DefenseSubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DefenseJuryService
{
    public function addJuryMember(DefenseSubmission $submission, array $data): DefenseJuryMember
    {
        $juryMember = $submission->juryMembers()->create($data);

        Log::info('Membre du jury ajouté', [
            'submission_id' => $submission->id,
            'jury_member_id' => $juryMember->id,
        ]);

        return $juryMember->load(['professor', 'grade']);
    }

    public function syncJuryMembers(DefenseSubmission $submission, array $membersData)
    {
        $submission->juryMembers()->delete();

        foreach ($membersData as $member) {
            if (
                (isset($member['professor_id']) && $member['professor_id'] === 'external')
                || empty($member['professor_id'])
            ) {
                DefenseJuryMember::create([
                    'defense_submission_id' => $submission->id,
                    'professor_id' => null,
                    'grade_id' => null,
                    'role' => $member['role'],
                    'name' => $member['external_name'] ?? 'Intervenant externe',
                ]);
            } else {
                $professor = \App\Modules\RH\Models\Professor::find($member['professor_id']);
                DefenseJuryMember::create([
                    'defense_submission_id' => $submission->id,
                    'professor_id' => $professor->id,
                    'grade_id' => $professor->grade_id ?? null,
                    'role' => $member['role'],
                    'name' => $professor->first_name . ' ' . $professor->last_name,
                ]);
            }
        }
    }

    public function getScheduleSuggestions(DefenseSubmission $submission)
    {
        $startDate = now()->addDays(1)->setHour(9)->setMinute(0);
        $endDate = now()->addDays(15);

        $defenseRooms = \App\Modules\EmploiDuTemps\Models\Room::where('room_type', 'conference')->get();

        foreach ($defenseRooms as $room) {
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $endTime = $currentDate->copy()->addHours(2);

                $conflictingDefenses = DefenseSubmission::where('room_id', $room->id)
                    ->where(function ($query) use ($currentDate, $endTime) {
                        $query->whereBetween('defense_date', [$currentDate, $endTime])
                            ->orWhere(function ($q) use ($currentDate, $endTime) {
                                $q->where('defense_date', '<', $currentDate)
                                    ->where('defense_date', '>', $currentDate->copy()->subHours(2));
                            });
                    })
                    ->exists();

                if (!$conflictingDefenses && $currentDate->isWeekday()) {
                    return [
                        'suggested_room_id' => $room->id,
                        'suggested_date' => $currentDate->format('Y-m-d\TH:i')
                    ];
                }

                $currentDate->addHour();
            }
        }

        return [
            'suggested_room_id' => $defenseRooms->first()->id ?? null,
            'suggested_date' => $startDate->format('Y-m-d\TH:i')
        ];
    }

    public function updateJuryMember(DefenseJuryMember $juryMember, array $data): DefenseJuryMember
    {
        $juryMember->update($data);

        Log::info('Membre du jury mis à jour', [
            'jury_member_id' => $juryMember->id,
        ]);

        return $juryMember->fresh(['professor', 'grade']);
    }

    public function deleteJuryMember(DefenseJuryMember $juryMember): bool
    {
        $juryMember->delete();

        Log::info('Membre du jury supprimé', [
            'jury_member_id' => $juryMember->id,
        ]);

        return true;
    }

    public function getJuryMembers(DefenseSubmission $submission)
    {
        return $submission->juryMembers()->with(['professor', 'grade'])->get();
    }
}
