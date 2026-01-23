<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvCashflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvCashflowPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvCashflow');
    }

    public function view(AuthUser $authUser, BvCashflow $bvCashflow): bool
    {
        return $authUser->can('View:BvCashflow');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvCashflow');
    }

    public function update(AuthUser $authUser, BvCashflow $bvCashflow): bool
    {
        return $authUser->can('Update:BvCashflow');
    }

    public function delete(AuthUser $authUser, BvCashflow $bvCashflow): bool
    {
        return $authUser->can('Delete:BvCashflow');
    }

    public function restore(AuthUser $authUser, BvCashflow $bvCashflow): bool
    {
        return $authUser->can('Restore:BvCashflow');
    }

    public function forceDelete(AuthUser $authUser, BvCashflow $bvCashflow): bool
    {
        return $authUser->can('ForceDelete:BvCashflow');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvCashflow');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvCashflow');
    }

    public function replicate(AuthUser $authUser, BvCashflow $bvCashflow): bool
    {
        return $authUser->can('Replicate:BvCashflow');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvCashflow');
    }

}