<?php

namespace Tests\Feature;

use App\Models\Matter;
use App\Models\MatterParty;
use App\Models\Party;
use App\Models\Court;
use App\Models\Type;
use App\Enums\MatterLevel;
use App\Enums\MatterDifficulty;
use App\Enums\MatterStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterPartyObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_matter_party_observer_sets_matter_id_from_parent()
    {
        $court = Court::create(['name' => 'Test Court']);
        $type = Type::create(['name' => 'Test Type', 'active' => true]);

        $matter = Matter::create([
            'year' => '2026',
            'number' => 'TEST-0001',
            'court_id' => $court->id,
            'type_id' => $type->id,
            'level' => MatterLevel::FIRST_INSTANCE,
            'difficulty' => MatterDifficulty::EASY,
            'status' => MatterStatus::CURRENT,
            'commissioning' => false,
        ]);

        $party = Party::create(['name' => 'Main Party', 'role' => [['role' => 'party']], 'type' => 'party']);
        $rep = Party::create(['name' => 'Rep Party', 'role' => [['role' => 'representative']], 'type' => 'party']);

        // Create main party record
        $mainMatterParty = MatterParty::create([
            'matter_id' => $matter->id,
            'party_id' => $party->id,
            'role' => 'party',
            'type' => 'plaintiff',
        ]);

        // Simulating the issue: create representative WITHOUT matter_id, but WITH parent_id
        // This is what happens in Filament nested repeater when creating new matter
        $repMatterParty = MatterParty::create([
            'party_id' => $rep->id,
            'parent_id' => $mainMatterParty->id,
            'role' => 'representative',
            'type' => 'plaintiff',
            // matter_id is MISSING
        ]);

        $this->assertNotNull($repMatterParty->matter_id);
        $this->assertEquals($matter->id, $repMatterParty->matter_id);
        $this->assertEquals($mainMatterParty->id, $repMatterParty->parent_id);
    }
}
