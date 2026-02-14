<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SchoolPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_school');
    }

    public function view(AuthUser $authUser, School $school): bool
    {
        return $authUser->can('view_school');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_school');
    }

    public function update(AuthUser $authUser, School $school): bool
    {
        return $authUser->can('update_school');
    }

    public function delete(AuthUser $authUser, School $school): bool
    {
        return $authUser->can('delete_school');
    }

    public function restore(AuthUser $authUser, School $school): bool
    {
        return $authUser->can('restore_school');
    }
}
