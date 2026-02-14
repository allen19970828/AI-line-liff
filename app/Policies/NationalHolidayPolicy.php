<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NationalHoliday;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class NationalHolidayPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_national_holiday');
    }

    public function view(AuthUser $authUser, NationalHoliday $nationalHoliday): bool
    {
        return $authUser->can('view_national_holiday');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_national_holiday');
    }

    public function update(AuthUser $authUser, NationalHoliday $nationalHoliday): bool
    {
        return $authUser->can('update_national_holiday');
    }

    public function delete(AuthUser $authUser, NationalHoliday $nationalHoliday): bool
    {
        return $authUser->can('delete_national_holiday');
    }

    public function restore(AuthUser $authUser, NationalHoliday $nationalHoliday): bool
    {
        return $authUser->can('restore_national_holiday');
    }
}
