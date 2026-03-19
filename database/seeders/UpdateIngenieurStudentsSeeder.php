<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Modules\Inscription\Models\AcademicPath;
use App\Modules\Inscription\Models\PendingStudent;
use App\Modules\Inscription\Models\StudentPendingStudent;
use App\Modules\Inscription\Models\Department;
use App\Modules\Inscription\Models\Student;
use App\Modules\Inscription\Models\Cycle;

/**
 * Restructure les parcours Ingénieur pour le nouveau système 4 ans
 * - Tous les étudiants Ingénieur doivent avoir une année Prépa
 * - Les années sont décalées : study_level N → Prépa(1) + Spécialité(1..N-1)
 */
class UpdateIngenieurStudentsSeeder extends Seeder
{
    private $stats = [
        'total' => 0,
        'niveau_0' => 0,
        'niveau_1' => 0,
        'niveau_2' => 0,
        'niveau_3' => 0,
        'niveau_4' => 0,
        'niveau_plus' => 0,
        'errors' => 0,
    ];

    private $deptMapping = [];

    public function run(): void
    {
        $this->command->info('🚀 Restructuration des parcours Ingénieur (5 ans → 4 ans)...');
        $this->command->newLine();

        // Charger les mappings de départements
        $this->loadDepartmentMappings();

        // Récupérer le cycle Ingénieur
        $ingenieurCycle = Cycle::where('name', 'LIKE', '%Ing%')->first();
        if (!$ingenieurCycle) {
            $this->command->error('❌ Cycle Ingénieur introuvable');
            return;
        }

        // Récupérer tous les départements spécialité Ingénieur (GC, GT, GE, GME)
        $specialiteDepts = Department::where('cycle_id', $ingenieurCycle->id)
            ->whereIn('abbreviation', ['GC', 'GT', 'GE', 'GME'])
            ->pluck('id')
            ->toArray();

        if (empty($specialiteDepts)) {
            $this->command->error('❌ Aucun département spécialité trouvé');
            return;
        }

        // Récupérer tous les pending_students concernés
        $pendingStudents = PendingStudent::whereIn('department_id', $specialiteDepts)
            ->with(['studentPendingStudents.student'])
            ->get();

        $this->command->info("📊 {$pendingStudents->count()} étudiants Ingénieur trouvés");
        $this->command->newLine();

        $bar = $this->command->getOutput()->createProgressBar($pendingStudents->count());
        $bar->start();

        foreach ($pendingStudents as $pendingStudent) {
            try {
                $this->restructureStudent($pendingStudent);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->command->newLine();
                $this->command->error("❌ Erreur pour {$pendingStudent->tracking_code}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        $this->displayStats();
    }

    private function loadDepartmentMappings(): void
    {
        $mapping = [
            'GC' => 'P-GC',
            'GT' => 'P-GT',
            'GE' => 'P-GE',
            'GME' => 'P-GME',
        ];

        foreach ($mapping as $specialite => $prepa) {
            $specDept = Department::where('abbreviation', $specialite)
                ->whereHas('cycle', fn($q) => $q->where('name', 'LIKE', '%Ing%'))
                ->first();

            $prepaDept = Department::where('abbreviation', $prepa)
                ->whereHas('cycle', fn($q) => $q->where('name', 'LIKE', '%Ing%'))
                ->first();

            if ($specDept && $prepaDept) {
                $this->deptMapping[$specDept->id] = [
                    'prepa_id' => $prepaDept->id,
                    'spec_abbr' => $specialite,
                    'prepa_abbr' => $prepa,
                ];
            }
        }

        $this->command->info('✅ Mappings départements chargés: ' . count($this->deptMapping));
    }

    private function restructureStudent(PendingStudent $oldPendingStudent): void
    {
        $this->stats['total']++;

        // Récupérer le lien vers l'étudiant
        $link = $oldPendingStudent->studentPendingStudents()->first();
        if (!$link) {
            throw new \Exception("Aucun lien student_pending_student trouvé");
        }

        // Récupérer les academic_paths existants pour déterminer le niveau réel
        $oldAcademicPaths = AcademicPath::where('student_pending_student_id', $link->id)->get();

        // Le niveau actuel = MAX(study_level) des academic_paths
        $currentLevel = $oldAcademicPaths->max('study_level') ?? 1;

        // Incrémenter les stats
        if ($currentLevel <= 0) {
            $this->stats['niveau_0']++;
        } elseif ($currentLevel >= 5) {
            $this->stats['niveau_plus']++;
        } else {
            $levelKey = 'niveau_' . $currentLevel;
            $this->stats[$levelKey]++;
        }

        // Si niveau 0 ou invalide, on ignore
        if ($currentLevel <= 0) {
            return;
        }

        $student = $link->student;
        if (!$student) {
            throw new \Exception("Student introuvable");
        }

        // Si aucun academic_path (normalement impossible car on calcule currentLevel à partir d'eux)
        if ($oldAcademicPaths->isEmpty()) {
            $this->command->warn("⚠ Aucun academic_path pour {$oldPendingStudent->tracking_code}, création niveau 1 par défaut");
            $currentLevel = 1;
        }

        DB::beginTransaction();

        try {
            // 1. CRÉER PRÉPA (toujours niveau 1)
            $firstPath = $oldAcademicPaths->first();
            $this->createPrepaPath($student, $oldPendingStudent, $firstPath);

            // 2. SI NIVEAU >= 2: CRÉER SPÉCIALITÉ
            if ($currentLevel >= 2) {
                $this->createSpecialitePaths($student, $oldPendingStudent, $currentLevel, $oldAcademicPaths);
            }

            // 3. SUPPRIMER LES ANCIENS
            foreach ($oldAcademicPaths as $oldPath) {
                $oldPath->delete();
            }
            $link->delete();
            $oldPendingStudent->delete();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function createPrepaPath(Student $student, PendingStudent $oldPending, ?AcademicPath $oldPath): void
    {
        $deptInfo = $this->deptMapping[$oldPending->department_id] ?? null;
        if (!$deptInfo) {
            throw new \Exception("Mapping département introuvable pour dept_id {$oldPending->department_id}");
        }

        // Créer pending_student pour PRÉPA
        $prepaPending = PendingStudent::create([
            'personal_information_id' => $oldPending->personal_information_id,
            'tracking_code' => $oldPending->tracking_code . '-PREP',
            'cuca_opinion' => $oldPending->cuca_opinion,
            'cuca_comment' => $oldPending->cuca_comment,
            'department_id' => $deptInfo['prepa_id'],
            'academic_year_id' => $oldPending->academic_year_id,
            'level' => '1',
            'documents' => $oldPending->documents,
            'entry_diploma_id' => $oldPending->entry_diploma_id,
            'status' => $oldPending->status,
            'sponsorise' => $oldPending->sponsorise,
            'photo' => $oldPending->photo,
            'cuo_opinion' => $oldPending->cuo_opinion ?? 'pending',
            'rejection_reason' => $oldPending->rejection_reason,
        ]);

        // Créer lien pivot
        $prepaLink = StudentPendingStudent::create([
            'student_id' => $student->id,
            'pending_student_id' => $prepaPending->id,
        ]);

        // Créer academic_path pour PRÉPA
        AcademicPath::create([
            'student_pending_student_id' => $prepaLink->id,
            'academic_year_id' => $oldPath ? $oldPath->academic_year_id : $oldPending->academic_year_id,
            'study_level' => '1',
            'year_decision' => $oldPath ? $oldPath->year_decision : null,
            'financial_status' => $oldPath ? $oldPath->financial_status : 'Non exonéré',
            'cohort' => $oldPath ? $oldPath->cohort : date('Y'),
        ]);
    }

    private function createSpecialitePaths(Student $student, PendingStudent $oldPending, int $currentLevel, $oldAcademicPaths): void
    {
        $deptInfo = $this->deptMapping[$oldPending->department_id] ?? null;
        if (!$deptInfo) {
            throw new \Exception("Mapping département introuvable");
        }

        // Nombre d'années en spécialité = currentLevel - 1
        $specYears = $currentLevel - 1;

        // Créer pending_student pour SPÉCIALITÉ
        $specPending = PendingStudent::create([
            'personal_information_id' => $oldPending->personal_information_id,
            'tracking_code' => $oldPending->tracking_code . '-SPEC',
            'cuca_opinion' => $oldPending->cuca_opinion,
            'cuca_comment' => $oldPending->cuca_comment,
            'department_id' => $oldPending->department_id, // Garde le département spécialité (GC, GT, etc.)
            'academic_year_id' => $oldPending->academic_year_id,
            'level' => (string) $specYears, // Niveau = nombre d'années en spécialité
            'documents' => $oldPending->documents,
            'entry_diploma_id' => $oldPending->entry_diploma_id,
            'status' => $oldPending->status,
            'sponsorise' => $oldPending->sponsorise,
            'photo' => $oldPending->photo,
            'cuo_opinion' => $oldPending->cuo_opinion ?? 'pending',
            'rejection_reason' => $oldPending->rejection_reason,
        ]);

        // Créer lien pivot
        $specLink = StudentPendingStudent::create([
            'student_id' => $student->id,
            'pending_student_id' => $specPending->id,
        ]);

        // Créer academic_paths pour chaque année en spécialité
        $baseAcademicPath = $oldAcademicPaths->first();

        for ($i = 1; $i <= $specYears; $i++) {
            AcademicPath::create([
                'student_pending_student_id' => $specLink->id,
                'academic_year_id' => $baseAcademicPath->academic_year_id,
                'study_level' => (string) $i,
                'year_decision' => $baseAcademicPath->year_decision,
                'financial_status' => $baseAcademicPath->financial_status,
                'cohort' => $baseAcademicPath->cohort,
            ]);
        }
    }

    private function displayStats(): void
    {
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║           STATISTIQUES DE RESTRUCTURATION                     ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();

        $this->command->info("📊 Total traité: {$this->stats['total']}");

        if ($this->stats['niveau_0'] > 0) {
            $this->command->warn("   ⚠  Niveau 0 (ignorés): {$this->stats['niveau_0']}");
        }

        $this->command->info("   • Niveau 1 (Prépa uniquement): {$this->stats['niveau_1']}");
        $this->command->info("   • Niveau 2 (Prépa + Ing 1): {$this->stats['niveau_2']}");
        $this->command->info("   • Niveau 3 (Prépa + Ing 1-2): {$this->stats['niveau_3']}");
        $this->command->info("   • Niveau 4 (Prépa + Ing 1-2-3): {$this->stats['niveau_4']}");

        if ($this->stats['niveau_plus'] > 0) {
            $this->command->warn("   ⚠  Niveau 5+ (rares): {$this->stats['niveau_plus']}");
        }

        if ($this->stats['errors'] > 0) {
            $this->command->error("   ❌ Erreurs: {$this->stats['errors']}");
        } else {
            $this->command->info("   ✅ Aucune erreur");
        }
    }
}
