<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use OwenIt\Auditing\Models\Audit;

class AuditPolicy
{
    use HandlesAuthorization;

    /**
     * Managers can always access audit logs.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('manager')) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any audits.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-audits');
    }

    /**
     * Determine whether the user can view the audit.
     */
    public function view(User $user, Audit $audit): bool
    {
        return $user->can('view-audits');
    }
}
