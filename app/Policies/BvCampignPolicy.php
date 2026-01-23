<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvCampign;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvCampignPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvCampign');
    }

    public function view(AuthUser $authUser, BvCampign $bvCampign): bool
    {
        return $authUser->can('View:BvCampign');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvCampign');
    }

    public function update(AuthUser $authUser, BvCampign $bvCampign): bool
    {
        return $authUser->can('Update:BvCampign');
    }

    public function delete(AuthUser $authUser, BvCampign $bvCampign): bool
    {
        return $authUser->can('Delete:BvCampign');
    }

    public function restore(AuthUser $authUser, BvCampign $bvCampign): bool
    {
        return $authUser->can('Restore:BvCampign');
    }

    public function forceDelete(AuthUser $authUser, BvCampign $bvCampign): bool
    {
        return $authUser->can('ForceDelete:BvCampign');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvCampign');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvCampign');
    }

    public function replicate(AuthUser $authUser, BvCampign $bvCampign): bool
    {
        return $authUser->can('Replicate:BvCampign');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvCampign');
    }

}