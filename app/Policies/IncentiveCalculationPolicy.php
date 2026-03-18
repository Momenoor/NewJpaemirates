<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IncentiveCalculation;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class IncentiveCalculationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:IncentiveCalculation');
    }

    public function view(AuthUser $authUser, IncentiveCalculation $calculation): bool
    {
        return $authUser->can('View:IncentiveCalculation') || $authUser->can('ViewAny:IncentiveCalculation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:IncentiveCalculation');
    }

    public function update(AuthUser $authUser, IncentiveCalculation $calculation): bool
    {
        return $authUser->can('Update:IncentiveCalculation');
    }

    public function delete(AuthUser $authUser, IncentiveCalculation $calculation): bool
    {
        return $authUser->can('Delete:IncentiveCalculation');
    }

    public function restore(AuthUser $authUser, IncentiveCalculation $calculation): bool
    {
        return $authUser->can('Restore:IncentiveCalculation');
    }

    public function forceDelete(AuthUser $authUser, IncentiveCalculation $calculation): bool
    {
        return $authUser->can('ForceDelete:IncentiveCalculation');
    }

    public function runCalculation(AuthUser $authUser, IncentiveCalculation $calculation): bool
    {
        return $authUser->can('RunCalculation:IncentiveCalculation');
    }

    public function finalize(AuthUser $authUser, IncentiveCalculation $calculation): bool
    {
        return $authUser->can('Finalize:IncentiveCalculation');
    }

    public function print(AuthUser $authUser, IncentiveCalculation $calculation): bool
    {
        return $authUser->can('Print:IncentiveCalculation') || $authUser->can('View:IncentiveCalculation');
    }
}
