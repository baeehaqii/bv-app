<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DataVendor;
use Illuminate\Auth\Access\HandlesAuthorization;

class DataVendorPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DataVendor');
    }

    public function view(AuthUser $authUser, DataVendor $dataVendor): bool
    {
        return $authUser->can('View:DataVendor');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DataVendor');
    }

    public function update(AuthUser $authUser, DataVendor $dataVendor): bool
    {
        return $authUser->can('Update:DataVendor');
    }

    public function delete(AuthUser $authUser, DataVendor $dataVendor): bool
    {
        return $authUser->can('Delete:DataVendor');
    }

    public function restore(AuthUser $authUser, DataVendor $dataVendor): bool
    {
        return $authUser->can('Restore:DataVendor');
    }

    public function forceDelete(AuthUser $authUser, DataVendor $dataVendor): bool
    {
        return $authUser->can('ForceDelete:DataVendor');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DataVendor');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DataVendor');
    }

    public function replicate(AuthUser $authUser, DataVendor $dataVendor): bool
    {
        return $authUser->can('Replicate:DataVendor');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DataVendor');
    }

}