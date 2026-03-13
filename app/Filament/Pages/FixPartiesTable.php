<?php

namespace App\Filament\Pages;

use App\Models\Expert;
use App\Models\Matter;
use App\Models\MatterExpert;
use App\Models\MatterParty;
use App\Models\Party;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixPartiesTable extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.fix-parties-table';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Fix Parties Data';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }


    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Statistics')->schema([
                TextEntry::make('parties')->default(Party::all()->count()),
                TextEntry::make('experts')->default(Expert::all()->count()),
                TextEntry::make('matters_parties')->default(MatterParty::all()->count()),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fixParties')
                ->label('Start Data Migration')
                ->color('danger')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Fix Parties Table')
                ->modalDescription('This will migrate "office" types to "representative" in parties table, and add all experts to parties table and update JSON roles. Are you sure?')
                ->action(fn() => $this->runFix()),

            Action::make('removeNullRoles')
                ->label('Remove Null Roles')
                ->color('warning')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(fn() => $this->runRemoveNullRoles()),

            Action::make('mattersExperts')
                ->label('Fix Matter Experts')
                ->color('warning')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(fn() => $this->fixMatterExperts()),

            Action::make('fixParentId')
                ->label('Fix Parent IDs')
                ->color('info')
                ->icon('heroicon-o-link')
                ->requiresConfirmation()
                ->modalHeading('Fix Parent IDs')
                ->modalDescription('This will update parent_id to refer to the new PK id instead of old_id. Continue?')
                ->action(fn() => $this->runFixParentRelation()),
        ];
    }

    protected function runRemoveNullRoles(bool $notify = true): void
    {
        $count = 0;
        if (Party::where('role', 'like', '%"role":null%')->count() === 0) {
            if ($notify) {
                Notification::make()->title('No Null Roles Found')->info()->send();
            }
            return;
        }

        Party::where('role', 'like', '%"role":null%')->chunk(100, function ($parties) use (&$count) {
            foreach ($parties as $party) {
                $currentRoles = is_array($party->role) ? $party->role : [];
                $newRoles = collect($currentRoles)
                    ->reject(fn($role) => is_array($role) && array_key_exists('role', $role) && $role['role'] === null)
                    ->values()
                    ->toArray();

                if (count($currentRoles) !== count($newRoles)) {
                    $party->update(['role' => $newRoles]);
                    $count++;
                }
            }
        });

        if ($notify) {
            Notification::make()
                ->title('Null Roles Removed')
                ->body("Removed null roles from $count parties.")
                ->success()
                ->send();
        }
    }

    protected function runFix(): void
    {
        // No wrapping transaction — each step commits immediately to avoid
        // lock wait timeouts on large datasets
        DB::statement('SET innodb_lock_wait_timeout = 120;');
        try {
            $this->runRemoveNullRoles(false);
            $this->runFixParties();
            $this->runFixExperts();

            Notification::make()
                ->title('Global Migration Complete')
                ->body('Parties, Experts, and Matter links have been synchronized.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Migration Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function runFixParties(): void
    {
        $parties = Party::all();
        foreach ($parties as $party) {
            $roleMapping = [
                'party'     => 'party',
                'partie'    => 'party',
                'office'    => 'representative',
                'advisor'   => 'advisor',
                'advocate'  => 'advisor',
                'defendant' => 'party',
                'plaintiff' => 'party',
            ];
            $newRoleName = $roleMapping[$party->type ?? ''] ?? null;

            $currentRoles = is_array($party->role) ? $party->role : [];
            $currentRoles = collect($currentRoles)
                ->reject(fn($role) => is_array($role) && array_key_exists('role', $role) && $role['role'] === null)
                ->values()
                ->toArray();

            $exists = collect($currentRoles)->contains(
                fn($role) => isset($role['role']) && $role['role'] === $newRoleName
            );

            if ($newRoleName && !$exists) {
                $currentRoles[] = ['role' => $newRoleName];
            }

            $party->update([
                'role'       => $currentRoles,
                'old_id'     => $party->id,
                'black_list' => $party->black_list == 2 ? 0 : (int) $party->black_list,
            ]);
        }

        $matterParties = DB::table('matter_party')->get();
        foreach ($matterParties as $matterParty) {
            $mapping = match ($matterParty->type ?? '') {
                'defendant'                              => ['role' => 'party',          'type' => 'defendant'],
                'plaintiff'                              => ['role' => 'party',          'type' => 'plaintiff'],
                'implicat-litigant'                      => ['role' => 'party',          'type' => 'implicate-litigant'],
                'plaintiff_party', 'plaintiff_advocate'  => ['role' => 'representative', 'type' => 'plaintiff'],
                'defendant_party', 'defendant_advocate'  => ['role' => 'representative', 'type' => 'defendant'],
                'implicat-litigant_party',
                'implicat-litigant_advocate'             => ['role' => 'representative', 'type' => 'implicate-litigant'],
                default                                  => null,
            };

            if ($mapping) {
                DB::table('matter_party')
                    ->where('matter_id', $matterParty->matter_id)
                    ->where('party_id', $matterParty->party_id)
                    ->update([
                        'role'       => $mapping['role'],
                        'type'       => $mapping['type'],
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    private function cleanupDuplicates(): void
    {
        $sql = Str::of('DELETE p1 FROM parties p1 INNER JOIN parties p2 WHERE p1.id > p2.id AND TRIM(p1.name) = TRIM(p2.name)');
        DB::statement($sql);
    }

    public function fixMatterExperts(){
        $matters = Matter::whereNotNull('expert_id')->get();
        foreach ($matters as $matter) {
            $expertAsParty = Party::where('old_id', $matter->expert_id)
                ->whereJsonContains('role', ['role' => 'expert'])
                ->first();
            dd($expertAsParty);
            if (!$expertAsParty) continue;

            $roles = is_array($expertAsParty->role) ? $expertAsParty->role : (json_decode($expertAsParty->role, true) ?? []);
            $roleData = collect($roles)->firstWhere('role', 'expert');

            // Guard: skip if no expert role entry found in the JSON
            if (!$roleData) continue;

            $exists = MatterParty::where('matter_id', $matter->id)
                ->where('party_id', $expertAsParty->id)
                ->exists();

            if (!$exists) {
                MatterParty::create([
                    'matter_id' => $matter->id,
                    'party_id'  => $expertAsParty->id,
                    'role'      => $roleData['role'] ?? 'expert',
                    'type'      => $roleData['type'] ?? 'certified',
                ]);
            }
        }

        // Migrate MatterExpert pivot → matter_party
        $matterExperts = MatterExpert::all();
        foreach ($matterExperts as $matterExpert) {
            $expertAsParty = Party::where('old_id', $matterExpert->expert_id)
                ->whereJsonContains('role', ['role' => 'expert'])
                ->first();

            if (!$expertAsParty) continue;

            $roles = is_array($expertAsParty->role) ? $expertAsParty->role : (json_decode($expertAsParty->role, true) ?? []);
            $expertData = collect($roles)->firstWhere('role', 'expert');

            // Guard: skip if no expert role entry found in the JSON
            if (!$expertData) continue;

            $expertType = ($expertData['type'] ?? '') === 'certified'
                ? 'assistant'
                : ($expertData['type'] ?? 'assistant');

            $pivotData = [
                'matter_id' => $matterExpert->matter_id,
                'party_id'  => $expertAsParty->id,
            ];

            $exists = DB::table('matter_party')
                ->where('matter_id', $pivotData['matter_id'])
                ->where('party_id', $pivotData['party_id'])
                ->exists();

            if ($exists) {
                DB::table('matter_party')
                    ->where('matter_id', $pivotData['matter_id'])
                    ->where('party_id', $pivotData['party_id'])
                    ->update([
                        'role'       => $expertData['role'] ?? 'expert',
                        'type'       => $expertType,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('matter_party')->insert(array_merge($pivotData, [
                    'role'       => $expertData['role'] ?? 'expert',
                    'type'       => $expertType,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function runFixExperts(): void
    {
        $expertTypes = [
            'main'               => ['role' => 'expert', 'type' => 'certified'],
            'certified'          => ['role' => 'expert', 'type' => 'assistant'],
            'assistant'          => ['role' => 'expert', 'type' => 'assistant'],
            'external'           => ['role' => 'expert', 'type' => 'external'],
            'external-assistant' => ['role' => 'expert', 'type' => 'external-assistant'],
        ];

        $experts = Expert::with('account')->get();

        foreach ($experts as $expert) {
            $cleanName   = trim($expert->name);
            $expertRole  = $expertTypes[$expert->category] ?? ['role' => 'expert', 'type' => 'other'];
            $expertRole['field'] = $expert->field;

            $party = Party::firstOrCreate(
                ['name' => $cleanName],
                [
                    'old_id' => $expert->id,
                    'phone'  => $expert->account?->phone,
                    'email'  => $expert->account?->email,
                    'role'   => [],
                ]
            );

            $currentRoles = is_array($party->role) ? $party->role : [];
            $currentRoles = collect($currentRoles)
                ->reject(fn($role) => is_array($role) && array_key_exists('role', $role) && $role['role'] === null)
                ->values()
                ->toArray();

            $alreadyHasThisExpertRole = collect($currentRoles)->contains(
                fn($v) => isset($v['role']) && $v['role'] === 'expert' && ($v['type'] ?? '') === ($expertRole['type'] ?? '')
            );

            if (!$alreadyHasThisExpertRole) {
                $currentRoles[] = $expertRole;
            }

            if (in_array($expert->category, ['main', 'certified'])) {
                $hasAssistant = collect($currentRoles)->contains(
                    fn($v) => isset($v['role']) && $v['role'] === 'expert' && ($v['type'] ?? '') === 'assistant'
                );
                if (!$hasAssistant) {
                    $currentRoles[] = ['role' => 'expert', 'type' => 'assistant', 'field' => $expert->field];
                }
            }

            $party->update(['old_id' => $expert->id, 'role' => $currentRoles]);
        }

        // Migrate matters.expert_id → matter_party
        $matters = Matter::whereNotNull('expert_id')->get();
        foreach ($matters as $matter) {
            $expertAsParty = Party::where('old_id', $matter->expert_id)
                ->whereJsonContains('role', ['role' => 'expert'])
                ->first();

            if (!$expertAsParty) continue;

            $roles = is_array($expertAsParty->role) ? $expertAsParty->role : (json_decode($expertAsParty->role, true) ?? []);
            $roleData = collect($roles)->firstWhere('role', 'expert');

            // Guard: skip if no expert role entry found in the JSON
            if (!$roleData) continue;

            $exists = MatterParty::where('matter_id', $matter->id)
                ->where('party_id', $expertAsParty->id)
                ->exists();

            if (!$exists) {
                MatterParty::create([
                    'matter_id' => $matter->id,
                    'party_id'  => $expertAsParty->id,
                    'role'      => $roleData['role'] ?? 'expert',
                    'type'      => $roleData['type'] ?? 'certified',
                ]);
            }
        }

        // Migrate MatterExpert pivot → matter_party
        $matterExperts = MatterExpert::all();
        foreach ($matterExperts as $matterExpert) {
            $expertAsParty = Party::where('old_id', $matterExpert->expert_id)
                ->whereJsonContains('role', ['role' => 'expert'])
                ->first();

            if (!$expertAsParty) continue;

            $roles = is_array($expertAsParty->role) ? $expertAsParty->role : (json_decode($expertAsParty->role, true) ?? []);
            $expertData = collect($roles)->firstWhere('role', 'expert');

            // Guard: skip if no expert role entry found in the JSON
            if (!$expertData) continue;

            $expertType = ($expertData['type'] ?? '') === 'certified'
                ? 'assistant'
                : ($expertData['type'] ?? 'assistant');

            $pivotData = [
                'matter_id' => $matterExpert->matter_id,
                'party_id'  => $expertAsParty->id,
            ];

            $exists = DB::table('matter_party')
                ->where('matter_id', $pivotData['matter_id'])
                ->where('party_id', $pivotData['party_id'])
                ->exists();

            if ($exists) {
                DB::table('matter_party')
                    ->where('matter_id', $pivotData['matter_id'])
                    ->where('party_id', $pivotData['party_id'])
                    ->update([
                        'role'       => $expertData['role'] ?? 'expert',
                        'type'       => $expertType,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('matter_party')->insert(array_merge($pivotData, [
                    'role'       => $expertData['role'] ?? 'expert',
                    'type'       => $expertType,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    protected function runFixParentRelation(bool $notify = true): void
    {
        $countParties       = 0;
        $countMatterParties = 0;

        Party::whereNotNull('parent_id')->chunk(100, function ($parties) use (&$countParties) {
            foreach ($parties as $party) {
                $parent = Party::where('old_id', $party->parent_id)->first();
                if ($parent && $party->parent_id != $parent->id) {
                    $party->update(['parent_id' => $parent->id]);
                    $countParties++;
                }
            }
        });

        DB::table('matter_party')
            ->where('parent_id', '>', 0)
            ->chunkById(100, function ($matterParties) use (&$countMatterParties) {
                foreach ($matterParties as $matterParty) {
                    $parent = MatterParty::where('party_id', $matterParty->parent_id)
                        ->where('matter_id', $matterParty->matter_id)
                        ->first();

                    if ($parent && $matterParty->parent_id != $parent->id) {
                        DB::table('matter_party')
                            ->where('id', $matterParty->id)
                            ->update(['parent_id' => $parent->id]);
                        $countMatterParties++;
                    }
                }
            });

        if ($notify) {
            Notification::make()
                ->title('Parent Relations Fixed')
                ->body("Updated parent_id for $countParties parties and $countMatterParties matter-party links.")
                ->success()
                ->send();
        }
    }
}
