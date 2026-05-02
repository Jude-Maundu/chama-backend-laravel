<?php

namespace App\Policies;

use App\Models\PettyCashAccount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PettyCashPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can submit a claim.
     */
    public function submitClaim(User $user, PettyCashAccount $account)
    {
        return $account->chama->isMember($user->id);
    }

    /**
     * Determine whether the user can approve a claim.
     */
    public function approveClaim(User $user, PettyCashAccount $account)
    {
        return $user->id === $account->custodian_id || $account->chama->isAdmin($user->id);
    }
}
