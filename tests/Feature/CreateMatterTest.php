<?php

namespace Tests\Feature;

use App\Models\Court;
use App\Models\Matter;
use App\Models\MatterParty;
use App\Models\Party;
use App\Models\Type;
use App\Enums\MatterLevel;
use App\Enums\MatterDifficulty;
use App\Enums\MatterStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateMatterTest extends TestCase
{
    // Do not use RefreshDatabase as we want to see the real data
    // use RefreshDatabase;

    public function test_create_matter_with_all_party_types()
    {
        echo "\nStarting Matter Creation Test...\n";

        // Get or create basic requirements
        $court = Court::first() ?: Court::create(['name' => 'Test Court']);
        $type = Type::first() ?: Type::create(['name' => 'Test Type', 'active' => true]);

        // Create the Matter
        $matter = Matter::create([
            'year' => '2026',
            'number' => 'TEST-0001-' . time(),
            'court_id' => $court->id,
            'type_id' => $type->id,
            'level' => MatterLevel::FIRST_INSTANCE,
            'difficulty' => MatterDifficulty::EASY,
            'status' => MatterStatus::CURRENT,
            'commissioning' => false,
        ]);

        echo "Matter created with ID: {$matter->id}\n";

        // Define and fetch or create parties for each type
        $partyConfigs = [
            'plaintiff' => ['name' => 'Plaintiff Party', 'role' => [['role' => 'party']]],
            'defendant' => ['name' => 'Defendant Party', 'role' => [['role' => 'party']]],
            'implicate-litigant' => ['name' => 'Implicate Litigant Party', 'role' => [['role' => 'party']]],
            'certified' => ['name' => 'Certified Expert Party', 'role' => [['role' => 'expert', 'type' => 'certified']]],
            'assistant' => ['name' => 'Assistant Expert Party', 'role' => [['role' => 'expert', 'type' => 'assistant']]],
            'representative' => ['name' => 'Representative Party', 'role' => [['role' => 'representative']]],
        ];

        $parties = [];
        foreach ($partyConfigs as $key => $config) {
            $parties[$key] = Party::firstOrCreate(
                ['name' => $config['name']],
                ['role' => $config['role'], 'type' => 'party']
            );
        }

        // Add main parties to the matter
        $mainPartyTypes = [
            'plaintiff' => ['role' => 'party', 'type' => 'plaintiff'],
            'defendant' => ['role' => 'party', 'type' => 'defendant'],
            'implicate-litigant' => ['role' => 'party', 'type' => 'implicate-litigant'],
            'certified' => ['role' => 'expert', 'type' => 'certified'],
            'assistant' => ['role' => 'expert', 'type' => 'assistant'],
        ];

        foreach ($mainPartyTypes as $key => $mapping) {
            MatterParty::create([
                'matter_id' => $matter->id,
                'party_id' => $parties[$key]->id,
                'role' => $mapping['role'],
                'type' => $mapping['type'],
            ]);
            echo "Linked Party '{$key}' (ID: {$parties[$key]->id}) to Matter as role: {$mapping['role']}, type: {$mapping['type']}\n";
        }

        // Add a representative for the Plaintiff
        MatterParty::create([
            'matter_id' => $matter->id,
            'party_id' => $parties['representative']->id,
            'parent_id' => $parties['plaintiff']->id,
            'role' => 'representative',
            'type' => 'plaintiff', // Using type 'plaintiff' for representatives of plaintiff
        ]);
        echo "Linked Representative (ID: {$parties['representative']->id}) for Plaintiff (ID: {$parties['plaintiff']->id}) to Matter.\n";

        // Fetch and display results
        $results = MatterParty::where('matter_id', $matter->id)->get();
        echo "\nDatabase Records in matter_party for Matter ID {$matter->id}:\n";
        echo str_repeat("-", 100) . "\n";
        echo sprintf("%-10s | %-10s | %-10s | %-20s | %-15s | %-10s\n", "Matter ID", "Party ID", "Parent ID", "Party Name", "Role", "Type");
        echo str_repeat("-", 100) . "\n";
        foreach ($results as $row) {
            $partyName = $row->party->name ?? 'N/A';
            echo sprintf("%-10s | %-10s | %-10s | %-20s | %-15s | %-10s\n",
                $row->matter_id,
                $row->party_id,
                $row->parent_id ?: 'NULL',
                $partyName,
                $row->role,
                $row->type
            );
        }
        echo str_repeat("-", 100) . "\n";

        $this->assertTrue(true);
    }
}
