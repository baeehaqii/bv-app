<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Okr;
use Illuminate\Auth\Access\HandlesAuthorization;

class OkrPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Okr');
    }

    public function view(AuthUser $authUser, Okr $okr): bool
    {
        return $authUser->can('View:Okr');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Okr');
    }

    public function update(AuthUser $authUser, Okr $okr): bool
    {
        return $authUser->can('Update:Okr');
    }

    public function delete(AuthUser $authUser, Okr $okr): bool
    {
        return $authUser->can('Delete:Okr');
    }

    public function restore(AuthUser $authUser, Okr $okr): bool
    {
        return $authUser->can('Restore:Okr');
    }

    public function forceDelete(AuthUser $authUser, Okr $okr): bool
    {
        return $authUser->can('ForceDelete:Okr');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Okr');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Okr');
    }

    public function replicate(AuthUser $authUser, Okr $okr): bool
    {
        return $authUser->can('Replicate:Okr');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Okr');
    }

}