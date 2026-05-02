<?php

namespace App\Policies;

use App\Models\Chama;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChamaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the chama.
     */
    public function view(User $user, Chama $chama)
    {
        return $chama->isMember($user->id) || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can manage the chama.
     */
    public function manageChama(User $user, Chama $chama)
    {
        return $chama->isAdmin($user->id) || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can create an expense.
     */
    public function createExpense(User $user, Chama $chama)
    {
        return $chama->isMember($user->id) || $user->hasRole('super-admin');
    }

    /**
     * Determine whether the user can approve an expense.
     */
    public function approveExpense(User $user, Chama $chama)
    {
        return $chama->isAdmin($user->id) || $chama->isTreasurer($user->id) || $user->hasRole('super-admin');
    }
}
