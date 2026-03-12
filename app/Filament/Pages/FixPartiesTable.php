<?php

namespace App\Filament\Pages;

use App\Models\Expert;
use App\Models\Matter;
use App\Models\MatterExpert;
use App\Models\MatterParty;
use App\Models\Party;
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
    protected string $view = 'filament.pages.fix-parties-table';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Fix Parties Data';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canAccess(): bool
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
                ->color('danger') // Red button to show it's a structural change
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation() // Security: Ask before running loop
                ->modalHeading('Fix Parties Table')
                ->modalDescription('This will migrate "office" types to "representative" in parties table, and add all experts to parties table and update JSON roles. Are you sure?')
                ->action(fn() => $this->runFix()),

            Action::make('removeNullRoles')
                ->label('Remove Null Roles')
                ->color('warning')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->action(fn() => $this->runRemoveNullRoles()),

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
                $newRoles = collect($currentRoles)->reject(fn($role) => is_array($role) && array_key_exists('role', $role) && $role['role'] === null)->values()->toArray();

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
        \Illuminate\Support\Facades\DB::transaction(function () {
            $this->runRemoveNullRoles(false);
            $this->runFixParties();
            $this->runFixExperts();
        });

        Notification::make()
            ->title('Global Migration Complete')
            ->body('Parties, Experts, and Matter links have been synchronized.')
            ->success()
            ->send();
    }

    public function runFixParties(): void
    {
        $parties = Party::all();
        $blackList = 0;
        foreach ($parties as $party) {

            $roleMapping = [
                'party' => 'party',
                'partie' => 'party',
                'office' => 'representative',
                'advisor' => 'advisor',
                'advocate' => 'advisor',
                'defendant' => 'party',
                'plaintiff' => 'party',
            ];
            $newRoleName = $roleMapping[$party->type] ?? null;

            // Initialize as empty array if null
            $currentRoles = is_array($party->role) ? $party->role : [];

            // Remove role:null if found
            $currentRoles = collect($currentRoles)->reject(fn($role) => is_array($role) && array_key_exists('role', $role) && $role['role'] === null)->values()->toArray();

            // Check if this specific role name already exists in the array
            $exists = collect($currentRoles)->contains(function ($role) use ($newRoleName) {
                return isset($role['role']) && $role['role'] === $newRoleName;
            });

            if ($newRoleName && !$exists) {
                $currentRoles[] = ['role' => $newRoleName];
            }
            $blackList += $party->black_list == 2 ? 0 : 1;
            $party->update([
                'role' => $currentRoles,
                'old_id' => $party->id,
                'black_list' => $blackList,
            ]);
        }

        $matterParties = DB::table('matter_party')->get();
        foreach ($matterParties as $matterParty) {
            $mapping = match ($matterParty->type) {
                'defendant' => ['role' => 'party', 'type' => 'defendant'],
                'plaintiff' => ['role' => 'party', 'type' => 'plaintiff'],
                'implicat-litigant' => ['role' => 'party', 'type' => 'implicate-litigant'],
                'plaintiff_party', 'plaintiff_advocate' => ['role' => 'representative', 'type' => 'plaintiff'],
                'defendant_party', 'defendant_advocate' => ['role' => 'representative', 'type' => 'defendant'],
                'implicat-litigant_party', 'implicat-litigant_advocate' => ['role' => 'representative', 'type' => 'implicate-litigant'],
                default => null,
            };
            if ($mapping) {
                DB::table('matter_party')
                    ->where('matter_id', $matterParty->matter_id)
                    ->where('party_id', $matterParty->party_id)
                    ->update([
                        'role' => $mapping['role'],
                        'type' => $mapping['type'],
                        'updated_at' => now(),
                    ]);
            }


        }

    }

    private function cleanupDuplicates(): void
    {
        // This SQL identifies duplicate names and deletes the newer ones
        $sql = Str::of('DELETE p1 FROM parties p1 INNER JOIN parties p2 WHERE p1.id > p2.id AND TRIM(p1.name) = TRIM(p2.name)');
        \Illuminate\Support\Facades\DB::statement($sql);
    }

    private function runFixExperts(): void
    {
        $experts = Expert::with('account')->get();

        foreach ($experts as $expert) {
            $cleanName = trim($expert->name);
            $expertTypes = [
                'main' => ['role' => 'expert', 'type' => 'certified'],
                'certified' => ['role' => 'expert', 'type' => 'assistant'],
                'assistant' => ['role' => 'expert', 'type' => 'assistant'],
                'external' => ['role' => 'expert', 'type' => 'external'],
                'external-assistant' => ['role' => 'expert', 'type' => 'external-assistant'],
            ];
            $expertRole = $expertTypes[$expert->category] ?? ['role' => 'expert', 'type' => 'other'];
            $expertRole['field'] = $expert->field;

            $party = Party::firstOrCreate(
                ['name' => $cleanName],
                [
                    'type' => $expert->category,
                    'old_id' => $expert->id,
                    'phone' => $expert->account?->phone,
                    'email' => $expert->account?->email,
                    'role' => [] // Initialize empty if new
                ]
            );

            $currentRoles = is_array($party->role) ? $party->role : [];

            // Remove role:null if found
            $currentRoles = collect($currentRoles)->reject(fn($role) => is_array($role) && array_key_exists('role', $role) && $role['role'] === null)->values()->toArray();

            // IMPROVED CHECK: Check for the specific role AND type combination
            $alreadyHasThisExpertRole = collect($currentRoles)->contains(function ($value) use ($expertRole) {
                return isset($value['role']) && $value['role'] === 'expert' && ($value['type'] ?? '') === ($expertRole['type'] ?? '');
            });

            if (!$alreadyHasThisExpertRole) {
                $currentRoles[] = $expertRole;
            }

            // If main or certified expert, add another role as assistant with the same field
            if (in_array($expert->category, ['main', 'certified'])) {
                $assistantRole = ['role' => 'expert', 'type' => 'assistant', 'field' => $expert->field];
                $alreadyHasAssistantRole = collect($currentRoles)->contains(function ($value) use ($assistantRole) {
                    return isset($value['role']) && $value['role'] === 'expert' && ($value['type'] ?? '') === 'assistant';
                });
                if (!$alreadyHasAssistantRole) {
                    $currentRoles[] = $assistantRole;
                }
            }

            $party->update([
                'old_id' => $expert->id,
                'role' => $currentRoles
            ]);
        }

         $matters = Matter::whereNotNull('expert_id')->get();
         foreach ($matters as $matter) {
             $expertAsParty = Party::where('old_id', $matter->expert_id)
                 ->whereJsonContains('role', ['role' => 'expert'])
                 ->first();

             if ($expertAsParty) {
                 $roleData = collect($expertAsParty->role)->firstWhere('role', 'expert');
                 $existing = MatterParty::where('matter_id', $matter->id)
                     ->where('party_id', $expertAsParty->id)
                     ->first();
                 if (!$existing) {

                     MatterParty::create([
                         'matter_id' => $matter->id,
                         'party_id' => $expertAsParty->id,
                         'role' => $roleData['role'] ?? 'expert',
                         'type' => $roleData['type'] ?? 'main'
                     ]);
                 }
             }
         }

        // 2. Migrate Additional Experts (from MatterExpert pivot)
        // Assuming your MatterExpert model has matter_id and expert_id
        $matterExperts = MatterExpert::all();

        foreach ($matterExperts as $matterExpert) {
            // Find the party that has an 'expert' role in its JSON array
            $expertAsParty = Party::where('old_id', $matterExpert->expert_id)
                ->whereJsonContains('role', ['role' => 'expert'])
                ->first();

            if ($expertAsParty) {
                // Find the specific expert object inside the roles array
                $expertData = collect($expertAsParty->role)->firstWhere('role', 'expert');

                $pivotData = [
                    'matter_id' => $matterExpert->matter_id,
                    'party_id' => $expertAsParty->id,
                ];

                $exists = DB::table('matter_party')
                    ->where('matter_id', $pivotData['matter_id'])
                    ->where('party_id', $pivotData['party_id'])
                    ->exists();

                $expertData['type'] = $expertData['type'] == 'certified' ? 'assistant' : $expertData['type'];

                if ($exists) {
                    DB::table('matter_party')
                        ->where('matter_id', $pivotData['matter_id'])
                        ->where('party_id', $pivotData['party_id'])
                        ->update([
                            'role' => $expertData['role'] ?? 'expert',
                            'type' => $expertData['type'] ?? 'assistant',
                            'updated_at' => now(),
                        ]);
                } else {
                    DB::table('matter_party')->insert(
                        array_merge($pivotData, [
                            'role' => $expertData['role'] ?? 'expert',
                            'type' => $expertData['type'] ?? 'assistant',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                    );
                }
            }
        }
    }

    protected function runFixParentRelation(bool $notify = true): void
    {
        $countParties = 0;
        $countMatterParties = 0;

        // 1. Fix parties.parent_id
        Party::whereNotNull('parent_id')->chunk(100, function ($parties) use (&$countParties) {
            foreach ($parties as $party) {
                $parent = Party::where('old_id', $party->parent_id)->first();
                if ($parent && $party->parent_id != $parent->id) {
                    $party->update(['parent_id' => $parent->id]);
                    $countParties++;
                }
            }
        });

        // 2. Fix matter_party.parent_id
        DB::table('matter_party')
            ->where('parent_id', '>', 0)
            ->chunkById(100, function ($matterParties) use (&$countMatterParties) {
                foreach ($matterParties as $matterParty) {
                    $parent = MatterParty::where('party_id', $matterParty->parent_id)->where('matter_id',$matterParty->matter_id)->first();
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
