<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DataClient;
use Illuminate\Auth\Access\HandlesAuthorization;

class DataClientPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DataClient');
    }

    public function view(AuthUser $authUser, DataClient $dataClient): bool
    {
        return $authUser->can('View:DataClient');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DataClient');
    }

    public function update(AuthUser $authUser, DataClient $dataClient): bool
    {
        return $authUser->can('Update:DataClient');
    }

    public function delete(AuthUser $authUser, DataClient $dataClient): bool
    {
        return $authUser->can('Delete:DataClient');
    }

    public function restore(AuthUser $authUser, DataClient $dataClient): bool
    {
        return $authUser->can('Restore:DataClient');
    }

    public function forceDelete(AuthUser $authUser, DataClient $dataClient): bool
    {
        return $authUser->can('ForceDelete:DataClient');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DataClient');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DataClient');
    }

    public function replicate(AuthUser $authUser, DataClient $dataClient): bool
    {
        return $authUser->can('Replicate:DataClient');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DataClient');
    }

}