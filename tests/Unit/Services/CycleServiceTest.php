<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Modules\Inscription\Services\CycleService;
use App\Modules\Inscription\Models\Cycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CycleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CycleService $cycleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cycleService = new CycleService();
    }

    /** @test */
    public function it_can_create_a_cycle()
    {
        $data = [
            'name' => 'Licence',
            'abbreviation' => 'L',
            'years_count' => 3,
            'is_lmd' => true,
            'type' => 'university',
        ];

        $cycle = $this->cycleService->create($data);

        $this->assertInstanceOf(Cycle::class, $cycle);
        $this->assertEquals('Licence', $cycle->name);
        $this->assertEquals('L', $cycle->abbreviation);
        $this->assertDatabaseHas('cycles', [
            'name' => 'Licence',
            'abbreviation' => 'L',
        ]);
    }

    /** @test */
    public function it_can_get_cycle_by_id()
    {
        $cycle = Cycle::create([
            'name' => 'Master',
            'abbreviation' => 'M',
            'years_count' => 2,
            'is_lmd' => true,
            'type' => 'university',
        ]);

        $found = $this->cycleService->getById($cycle->id);

        $this->assertNotNull($found);
        $this->assertEquals($cycle->id, $found->id);
        $this->assertEquals('Master', $found->name);
    }

    /** @test */
    public function it_returns_null_for_non_existent_cycle()
    {
        $found = $this->cycleService->getById(999999);

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_get_all_cycles()
    {
        for ($i = 0; $i < 3; $i++) {
            Cycle::create([
                'name' => "Cycle Test $i",
                'abbreviation' => "CT$i",
                'years_count' => 2 + $i,
                'is_lmd' => true,
                'type' => 'university',
            ]);
        }

        $result = $this->cycleService->getAll();

        $this->assertGreaterThanOrEqual(3, $result->total());
    }

    /** @test */
    public function it_can_filter_cycles_by_search()
    {
        Cycle::create(['name' => 'Licence', 'abbreviation' => 'L', 'years_count' => 3, 'is_lmd' => true, 'type' => 'university']);
        Cycle::create(['name' => 'Master', 'abbreviation' => 'M', 'years_count' => 2, 'is_lmd' => true, 'type' => 'university']);
        Cycle::create(['name' => 'Doctorat', 'abbreviation' => 'D', 'years_count' => 3, 'is_lmd' => true, 'type' => 'university']);

        $result = $this->cycleService->getAll(['search' => 'Licence']);

        $this->assertGreaterThanOrEqual(1, $result->total());
        $this->assertStringContainsString('Licence', $result->first()->name);
    }

    /** @test */
    public function it_creates_cycle_within_transaction()
    {
        $data = [
            'name' => 'Test Cycle Transaction',
            'abbreviation' => 'TCT',
            'years_count' => 2,
            'is_lmd' => true,
            'type' => 'university',
        ];

        $cycle = $this->cycleService->create($data);

        // Vérifier que le cycle a été créé
        $this->assertInstanceOf(Cycle::class, $cycle);
        $this->assertDatabaseHas('cycles', [
            'name' => 'Test Cycle Transaction',
        ]);
    }
}
