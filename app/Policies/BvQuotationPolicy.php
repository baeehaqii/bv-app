<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvQuotation;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvQuotationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvQuotation');
    }

    public function view(AuthUser $authUser, BvQuotation $bvQuotation): bool
    {
        return $authUser->can('View:BvQuotation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvQuotation');
    }

    public function update(AuthUser $authUser, BvQuotation $bvQuotation): bool
    {
        return $authUser->can('Update:BvQuotation');
    }

    public function delete(AuthUser $authUser, BvQuotation $bvQuotation): bool
    {
        return $authUser->can('Delete:BvQuotation');
    }

    public function restore(AuthUser $authUser, BvQuotation $bvQuotation): bool
    {
        return $authUser->can('Restore:BvQuotation');
    }

    public function forceDelete(AuthUser $authUser, BvQuotation $bvQuotation): bool
    {
        return $authUser->can('ForceDelete:BvQuotation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvQuotation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvQuotation');
    }

    public function replicate(AuthUser $authUser, BvQuotation $bvQuotation): bool
    {
        return $authUser->can('Replicate:BvQuotation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvQuotation');
    }

}