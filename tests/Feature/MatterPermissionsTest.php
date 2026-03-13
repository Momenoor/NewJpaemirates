<?php

namespace Tests\Feature;

use App\Models\Matter;
use App\Models\User;
use App\Policies\MatterPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterPermissionsTest extends TestCase
{
    public function test_all_matter_permissions_are_correctly_handled_by_policy()
    {
        $policy = new MatterPolicy();
        $permissions = [
            'ViewAny:Matter' => 'viewAny',
            'View:Matter' => 'view',
            'Create:Matter' => 'create',
            'Update:Matter' => 'update',
            'Delete:Matter' => 'delete',
            'ForceDelete:Matter' => 'forceDelete',
            'ForceDeleteAny:Matter' => 'forceDeleteAny',
            'Restore:Matter' => 'restore',
            'RestoreAny:Matter' => 'restoreAny',
            'Replicate:Matter' => 'replicate',
            'Export:Matter' => 'export',
            'Import:Matter' => 'import',
            'InitialReport:Matter' => 'initialReport',
            'FinalReport:Matter' => 'finalReport',
            'CreateNote:Matter' => 'createNote',
            'UpdateNote:Matter' => 'updateNote',
            'DeleteNote:Matter' => 'deleteNote',
            'CreateRequest:Matter' => 'createRequest',
            'ApproveRequest:Matter' => 'approveRequest',
            'RejectRequest:Matter' => 'rejectRequest',
            'CreateFee:Matter' => 'createFee',
            'UpdateFee:Matter' => 'updateFee',
            'DeleteFee:Matter' => 'deleteFee',
            'CollectFee:Matter' => 'collectFee',
            'UpdateAllocation:Matter' => 'updateAllocation',
            'DeleteAllocation:Matter' => 'deleteAllocation',
            'CreateAttachment:Matter' => 'createAttachment',
            'DeleteAttachment:Matter' => 'deleteAttachment',
            'ViewOwn:Matter' => 'viewOwn',
        ];

        foreach ($permissions as $permissionName => $policyMethod) {
            $user = \Mockery::mock(AuthUser::class);
            $matter = \Mockery::mock(Matter::class);

            // Assert FALSE when user DOES NOT have the permission
            $user->shouldReceive('can')->with($permissionName)->once()->andReturn(false);
            $this->assertFalse($this->invokePolicyMethod($policy, $policyMethod, $user, $matter));

            // Assert TRUE when user DOES have the permission
            $user->shouldReceive('can')->with($permissionName)->once()->andReturn(true);
            $this->assertTrue($this->invokePolicyMethod($policy, $policyMethod, $user, $matter));
        }
    }

    public function test_all_user_permissions_are_correctly_handled_by_policy()
    {
        $policy = new UserPolicy();
        $permissions = [
            'ViewAny:User' => 'viewAny',
            'View:User' => 'view',
            'Create:User' => 'create',
            'Update:User' => 'update',
            'Delete:User' => 'delete',
            'Restore:User' => 'restore',
            'ForceDelete:User' => 'forceDelete',
            'ForceDeleteAny:User' => 'forceDeleteAny',
            'RestoreAny:User' => 'restoreAny',
            'Replicate:User' => 'replicate',
            'Reorder:User' => 'reorder',
        ];

        foreach ($permissions as $permissionName => $policyMethod) {
            $user = \Mockery::mock(AuthUser::class);

            // Assert FALSE when user DOES NOT have the permission
            $user->shouldReceive('can')->with($permissionName)->once()->andReturn(false);
            $this->assertFalse($this->invokePolicyMethod($policy, $policyMethod, $user));

            // Assert TRUE when user DOES have the permission
            $user->shouldReceive('can')->with($permissionName)->once()->andReturn(true);
            $this->assertTrue($this->invokePolicyMethod($policy, $policyMethod, $user));
        }
    }

    private function invokePolicyMethod($policy, string $method, $user, $matter = null): bool
    {
        $reflection = new \ReflectionMethod($policy, $method);
        $parameters = $reflection->getParameters();

        if (count($parameters) === 1) {
            return $policy->$method($user);
        }

        return $policy->$method($user, $matter);
    }
}
