<?php

namespace App\Policies;

use App\Models\ChamaExpense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChamaExpensePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the expense.
     */
    public function update(User $user, ChamaExpense $expense)
    {
        return $user->id === $expense->created_by || $expense->chama->isAdmin($user->id);
    }

    /**
     * Determine whether the user can delete the expense.
     */
    public function delete(User $user, ChamaExpense $expense)
    {
        return $user->id === $expense->created_by || $expense->chama->isAdmin($user->id);
    }
}
