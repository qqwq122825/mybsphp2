<?php

namespace App\Policies;

use App\Auth\AdminPermission;
use App\Models\Administrator;

final class AdministratorPolicy
{
    public function viewAny(Administrator $actor): bool
    {
        return $this->allows($actor, AdminPermission::AdministratorsView);
    }

    public function view(Administrator $actor, Administrator $administrator): bool
    {
        return $this->allows($actor, AdminPermission::AdministratorsView);
    }

    public function create(Administrator $actor): bool
    {
        return $this->allows($actor, AdminPermission::AdministratorsCreate);
    }

    public function update(Administrator $actor, Administrator $administrator): bool
    {
        return $this->allows($actor, AdminPermission::AdministratorsUpdate);
    }

    public function delete(Administrator $actor, Administrator $administrator): bool
    {
        return $actor->isNot($administrator)
            && $this->allows($actor, AdminPermission::AdministratorsDelete);
    }

    private function allows(Administrator $actor, AdminPermission $permission): bool
    {
        return $actor->status === 'active'
            && $actor->hasPermission($permission->value);
    }
}
