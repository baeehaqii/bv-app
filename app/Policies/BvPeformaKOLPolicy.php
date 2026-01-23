<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvPeformaKOL;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvPeformaKOLPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvPeformaKOL');
    }

    public function view(AuthUser $authUser, BvPeformaKOL $bvPeformaKOL): bool
    {
        return $authUser->can('View:BvPeformaKOL');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvPeformaKOL');
    }

    public function update(AuthUser $authUser, BvPeformaKOL $bvPeformaKOL): bool
    {
        return $authUser->can('Update:BvPeformaKOL');
    }

    public function delete(AuthUser $authUser, BvPeformaKOL $bvPeformaKOL): bool
    {
        return $authUser->can('Delete:BvPeformaKOL');
    }

    public function restore(AuthUser $authUser, BvPeformaKOL $bvPeformaKOL): bool
    {
        return $authUser->can('Restore:BvPeformaKOL');
    }

    public function forceDelete(AuthUser $authUser, BvPeformaKOL $bvPeformaKOL): bool
    {
        return $authUser->can('ForceDelete:BvPeformaKOL');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvPeformaKOL');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvPeformaKOL');
    }

    public function replicate(AuthUser $authUser, BvPeformaKOL $bvPeformaKOL): bool
    {
        return $authUser->can('Replicate:BvPeformaKOL');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvPeformaKOL');
    }

}