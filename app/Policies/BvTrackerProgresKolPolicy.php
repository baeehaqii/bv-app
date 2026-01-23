<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvTrackerProgresKol;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvTrackerProgresKolPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvTrackerProgresKol');
    }

    public function view(AuthUser $authUser, BvTrackerProgresKol $bvTrackerProgresKol): bool
    {
        return $authUser->can('View:BvTrackerProgresKol');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvTrackerProgresKol');
    }

    public function update(AuthUser $authUser, BvTrackerProgresKol $bvTrackerProgresKol): bool
    {
        return $authUser->can('Update:BvTrackerProgresKol');
    }

    public function delete(AuthUser $authUser, BvTrackerProgresKol $bvTrackerProgresKol): bool
    {
        return $authUser->can('Delete:BvTrackerProgresKol');
    }

    public function restore(AuthUser $authUser, BvTrackerProgresKol $bvTrackerProgresKol): bool
    {
        return $authUser->can('Restore:BvTrackerProgresKol');
    }

    public function forceDelete(AuthUser $authUser, BvTrackerProgresKol $bvTrackerProgresKol): bool
    {
        return $authUser->can('ForceDelete:BvTrackerProgresKol');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvTrackerProgresKol');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvTrackerProgresKol');
    }

    public function replicate(AuthUser $authUser, BvTrackerProgresKol $bvTrackerProgresKol): bool
    {
        return $authUser->can('Replicate:BvTrackerProgresKol');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvTrackerProgresKol');
    }

}