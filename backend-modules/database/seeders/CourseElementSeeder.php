<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Cours\Models\CourseElement;
use App\Modules\Cours\Models\TeachingUnit;
use App\Modules\RH\Models\Professor;
use Illuminate\Support\Str;

class CourseElementSeeder extends Seeder
{
    public function run(): void
    {
        $teachingUnits = TeachingUnit::limit(15)->get();
        
        if ($teachingUnits->isEmpty()) {
            $this->command->warn('⚠️  Aucune UE trouvée. Exécutez TeachingUnitSeeder d\'abord.');
            return;
        }

        $professors = Professor::where('status', 'active')->get();
        $courseTypes = ['CM', 'TD', 'TP', 'Projet'];
        $created = 0;

        foreach ($teachingUnits as $ue) {
            $numElements = rand(2, 3);
            
            for ($i = 0; $i < $numElements; $i++) {
                $type = $courseTypes[$i % count($courseTypes)];
                
                $courseElement = CourseElement::updateOrCreate(
                    [
                        'code' => $ue->code . '-' . $type,
                        'teaching_unit_id' => $ue->id,
                    ],
                    [
                        'uuid' => Str::uuid()->toString(),
                        'name' => "{$ue->name} - {$type}",
                        'type' => $type,
                        'hours' => $this->getHours($type),
                        'coefficient' => $this->getCoef($type),
                        'description' => "Élément {$type} pour {$ue->name}",
                    ]
                );

                if ($professors->isNotEmpty() && rand(0, 1) == 1) {
                    $courseElement->professors()->syncWithoutDetaching([$professors->random()->id]);
                }

                $created++;
            }
        }

        $this->command->info('✅ Éléments de cours créés avec succès!');
        $this->command->info("📚 Total: {$created} éléments créés");
    }

    protected function getHours($type) {
        return match($type) {
            'CM' => 20,
            'TD' => 15,
            'TP' => 12,
            'Projet' => 30,
            default => 15,
        };
    }

    protected function getCoef($type) {
        return match($type) {
            'CM' => 3,
            'TD' => 2,
            'TP' => 2,
            'Projet' => 4,
            default => 2,
        };
    }
}
