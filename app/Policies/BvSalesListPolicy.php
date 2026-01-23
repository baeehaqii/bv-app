<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvSalesList;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvSalesListPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvSalesList');
    }

    public function view(AuthUser $authUser, BvSalesList $bvSalesList): bool
    {
        return $authUser->can('View:BvSalesList');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvSalesList');
    }

    public function update(AuthUser $authUser, BvSalesList $bvSalesList): bool
    {
        return $authUser->can('Update:BvSalesList');
    }

    public function delete(AuthUser $authUser, BvSalesList $bvSalesList): bool
    {
        return $authUser->can('Delete:BvSalesList');
    }

    public function restore(AuthUser $authUser, BvSalesList $bvSalesList): bool
    {
        return $authUser->can('Restore:BvSalesList');
    }

    public function forceDelete(AuthUser $authUser, BvSalesList $bvSalesList): bool
    {
        return $authUser->can('ForceDelete:BvSalesList');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvSalesList');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvSalesList');
    }

    public function replicate(AuthUser $authUser, BvSalesList $bvSalesList): bool
    {
        return $authUser->can('Replicate:BvSalesList');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvSalesList');
    }

}