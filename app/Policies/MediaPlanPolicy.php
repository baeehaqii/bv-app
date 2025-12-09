<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\MediaPlan;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPlanPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MediaPlan');
    }

    public function view(AuthUser $authUser, MediaPlan $mediaPlan): bool
    {
        return $authUser->can('View:MediaPlan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MediaPlan');
    }

    public function update(AuthUser $authUser, MediaPlan $mediaPlan): bool
    {
        return $authUser->can('Update:MediaPlan');
    }

    public function delete(AuthUser $authUser, MediaPlan $mediaPlan): bool
    {
        return $authUser->can('Delete:MediaPlan');
    }

    public function restore(AuthUser $authUser, MediaPlan $mediaPlan): bool
    {
        return $authUser->can('Restore:MediaPlan');
    }

    public function forceDelete(AuthUser $authUser, MediaPlan $mediaPlan): bool
    {
        return $authUser->can('ForceDelete:MediaPlan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MediaPlan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MediaPlan');
    }

    public function replicate(AuthUser $authUser, MediaPlan $mediaPlan): bool
    {
        return $authUser->can('Replicate:MediaPlan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MediaPlan');
    }

}