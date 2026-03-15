<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Matter;
use Illuminate\Auth\Access\HandlesAuthorization;

class MatterPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Matter');
    }

    public function view(AuthUser $authUser, Matter $matter): bool
    {
        if ($matter->trashed() && !$authUser->can('ViewTrashed:Matter')) return false;

        if ($authUser->can('ViewAny:Matter')) return true;

        if ($authUser->can('ViewOwn:Matter') && $authUser->party) {
            // Use loaded relation — no extra query
            if ($matter->relationLoaded('matterParties')) {
                return $matter->matterParties
                    ->contains('party_id', $authUser->party->id);
            }

            // Fallback if relation not loaded (e.g. direct URL access)
            return $matter->matterParties()
                ->where('party_id', $authUser->party->id)
                ->exists();
        }

        return $authUser->can('View:Matter');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Matter');
    }

    public function update(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('Update:Matter');
    }

    public function delete(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('Delete:Matter');
    }

    public function restore(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('Restore:Matter');
    }

    public function forceDelete(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('ForceDelete:Matter');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Matter');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Matter');
    }

    public function replicate(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('Replicate:Matter');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Matter');
    }

    public function export(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('Export:Matter');
    }

    public function import(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('Import:Matter');
    }

    public function initialReport(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('InitialReport:Matter');
    }

    public function finalReport(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('FinalReport:Matter');
    }

    public function createNote(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('CreateNote:Matter');
    }

    public function updateNote(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('UpdateNote:Matter');
    }

    public function deleteNote(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('DeleteNote:Matter');
    }

    public function createRequest(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('CreateRequest:Matter');
    }

    public function approveRequest(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('ApproveRequest:Matter');
    }

    public function rejectRequest(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('RejectRequest:Matter');
    }

    public function createFee(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('CreateFee:Matter');
    }

    public function updateFee(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('UpdateFee:Matter');
    }

    public function deleteFee(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('DeleteFee:Matter');
    }

    public function collectFee(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('CollectFee:Matter');
    }

    public function updateAllocation(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('UpdateAllocation:Matter');
    }

    public function deleteAllocation(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('DeleteAllocation:Matter');
    }

    public function createAttachment(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('CreateAttachment:Matter');
    }

    public function deleteAttachment(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('DeleteAttachment:Matter');
    }

    public function viewOwn(AuthUser $authUser, Matter $matter): bool
    {
        return $authUser->can('ViewOwn:Matter');
    }

    public function viewTrashed(AuthUser $authUser): bool{
        return $authUser->can('ViewTrashed:Matter');
    }

}
