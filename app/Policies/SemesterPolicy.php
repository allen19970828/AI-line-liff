<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Semester;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SemesterPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_semester');
    }

    public function view(AuthUser $authUser, Semester $semester): bool
    {
        return $authUser->can('view_semester');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_semester');
    }

    public function update(AuthUser $authUser, Semester $semester): bool
    {
        return $authUser->can('update_semester');
    }

    public function delete(AuthUser $authUser, Semester $semester): bool
    {
        return $authUser->can('delete_semester');
    }

    public function restore(AuthUser $authUser, Semester $semester): bool
    {
        return $authUser->can('restore_semester');
    }
}
