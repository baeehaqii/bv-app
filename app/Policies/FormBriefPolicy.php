<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\FormBrief;
use Illuminate\Auth\Access\HandlesAuthorization;

class FormBriefPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FormBrief');
    }

    public function view(AuthUser $authUser, FormBrief $formBrief): bool
    {
        return $authUser->can('View:FormBrief');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FormBrief');
    }

    public function update(AuthUser $authUser, FormBrief $formBrief): bool
    {
        return $authUser->can('Update:FormBrief');
    }

    public function delete(AuthUser $authUser, FormBrief $formBrief): bool
    {
        return $authUser->can('Delete:FormBrief');
    }

    public function restore(AuthUser $authUser, FormBrief $formBrief): bool
    {
        return $authUser->can('Restore:FormBrief');
    }

    public function forceDelete(AuthUser $authUser, FormBrief $formBrief): bool
    {
        return $authUser->can('ForceDelete:FormBrief');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FormBrief');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FormBrief');
    }

    public function replicate(AuthUser $authUser, FormBrief $formBrief): bool
    {
        return $authUser->can('Replicate:FormBrief');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FormBrief');
    }

}