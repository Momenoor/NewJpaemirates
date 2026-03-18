<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MatterTypeIncentiveConfig;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class MatterTypeIncentiveConfigPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MatterTypeIncentiveConfig');
    }

    public function view(AuthUser $authUser, MatterTypeIncentiveConfig $config): bool
    {
        return $authUser->can('View:MatterTypeIncentiveConfig') || $authUser->can('ViewAny:MatterTypeIncentiveConfig');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MatterTypeIncentiveConfig');
    }

    public function update(AuthUser $authUser, MatterTypeIncentiveConfig $config): bool
    {
        return $authUser->can('Update:MatterTypeIncentiveConfig');
    }

    public function delete(AuthUser $authUser, MatterTypeIncentiveConfig $config): bool
    {
        return $authUser->can('Delete:MatterTypeIncentiveConfig');
    }

    public function restore(AuthUser $authUser, MatterTypeIncentiveConfig $config): bool
    {
        return $authUser->can('Restore:MatterTypeIncentiveConfig');
    }

    public function forceDelete(AuthUser $authUser, MatterTypeIncentiveConfig $config): bool
    {
        return $authUser->can('ForceDelete:MatterTypeIncentiveConfig');
    }
}
