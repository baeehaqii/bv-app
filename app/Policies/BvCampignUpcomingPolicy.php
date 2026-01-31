<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvCampignUpcoming;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvCampignUpcomingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvCampignUpcoming');
    }

    public function view(AuthUser $authUser, BvCampignUpcoming $bvCampignUpcoming): bool
    {
        return $authUser->can('View:BvCampignUpcoming');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvCampignUpcoming');
    }

    public function update(AuthUser $authUser, BvCampignUpcoming $bvCampignUpcoming): bool
    {
        return $authUser->can('Update:BvCampignUpcoming');
    }

    public function delete(AuthUser $authUser, BvCampignUpcoming $bvCampignUpcoming): bool
    {
        return $authUser->can('Delete:BvCampignUpcoming');
    }

    public function restore(AuthUser $authUser, BvCampignUpcoming $bvCampignUpcoming): bool
    {
        return $authUser->can('Restore:BvCampignUpcoming');
    }

    public function forceDelete(AuthUser $authUser, BvCampignUpcoming $bvCampignUpcoming): bool
    {
        return $authUser->can('ForceDelete:BvCampignUpcoming');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvCampignUpcoming');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvCampignUpcoming');
    }

    public function replicate(AuthUser $authUser, BvCampignUpcoming $bvCampignUpcoming): bool
    {
        return $authUser->can('Replicate:BvCampignUpcoming');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvCampignUpcoming');
    }

}