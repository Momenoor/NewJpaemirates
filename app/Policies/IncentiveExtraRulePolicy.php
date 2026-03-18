<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IncentiveExtraRule;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncentiveExtraRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IncentiveExtraRule');
    }

    public function view(AuthUser $authUser, IncentiveExtraRule $rule): bool
    {
        return $authUser->can('View:IncentiveExtraRule') || $authUser->can('ViewAny:IncentiveExtraRule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IncentiveExtraRule');
    }

    public function update(AuthUser $authUser, IncentiveExtraRule $rule): bool
    {
        return $authUser->can('Update:IncentiveExtraRule');
    }

    public function delete(AuthUser $authUser, IncentiveExtraRule $rule): bool
    {
        return $authUser->can('Delete:IncentiveExtraRule');
    }

    public function restore(AuthUser $authUser, IncentiveExtraRule $rule): bool
    {
        return $authUser->can('Restore:IncentiveExtraRule');
    }

    public function forceDelete(AuthUser $authUser, IncentiveExtraRule $rule): bool
    {
        return $authUser->can('ForceDelete:IncentiveExtraRule');
    }
}
