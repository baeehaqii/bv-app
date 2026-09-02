<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BvInvoice;
use Illuminate\Auth\Access\HandlesAuthorization;

class BvInvoicePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BvInvoice');
    }

    public function view(AuthUser $authUser, BvInvoice $bvInvoice): bool
    {
        return $authUser->can('View:BvInvoice');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BvInvoice');
    }

    public function update(AuthUser $authUser, BvInvoice $bvInvoice): bool
    {
        return $authUser->can('Update:BvInvoice');
    }

    public function delete(AuthUser $authUser, BvInvoice $bvInvoice): bool
    {
        return $authUser->can('Delete:BvInvoice');
    }

    public function restore(AuthUser $authUser, BvInvoice $bvInvoice): bool
    {
        return $authUser->can('Restore:BvInvoice');
    }

    public function forceDelete(AuthUser $authUser, BvInvoice $bvInvoice): bool
    {
        return $authUser->can('ForceDelete:BvInvoice');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BvInvoice');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BvInvoice');
    }

    public function replicate(AuthUser $authUser, BvInvoice $bvInvoice): bool
    {
        return $authUser->can('Replicate:BvInvoice');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BvInvoice');
    }

}