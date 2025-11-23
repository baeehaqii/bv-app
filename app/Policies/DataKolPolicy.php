<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DataKol;
use Illuminate\Auth\Access\HandlesAuthorization;

class DataKolPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DataKol');
    }

    public function view(AuthUser $authUser, DataKol $dataKol): bool
    {
        return $authUser->can('View:DataKol');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DataKol');
    }

    public function update(AuthUser $authUser, DataKol $dataKol): bool
    {
        return $authUser->can('Update:DataKol');
    }

    public function delete(AuthUser $authUser, DataKol $dataKol): bool
    {
        return $authUser->can('Delete:DataKol');
    }

    public function restore(AuthUser $authUser, DataKol $dataKol): bool
    {
        return $authUser->can('Restore:DataKol');
    }

    public function forceDelete(AuthUser $authUser, DataKol $dataKol): bool
    {
        return $authUser->can('ForceDelete:DataKol');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DataKol');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DataKol');
    }

    public function replicate(AuthUser $authUser, DataKol $dataKol): bool
    {
        return $authUser->can('Replicate:DataKol');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DataKol');
    }

}