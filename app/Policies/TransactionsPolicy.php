<?php

namespace App\Policies;

use App\Models\Transactions;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TransactionsPolicy
{
    /**
     * Determine whether the user can view any models.
     * Access: Admin, Cashier
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'cashier']);
    }

    /**
     * Determine whether the user can create models.
     * Access: Admin, Cashier
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'cashier']);
    }

    // Default implementations restricting other actions
    public function view(User $user, Transactions $transactions): bool { return false; }
    public function update(User $user, Transactions $transactions): bool { return false; }
    public function delete(User $user, Transactions $transactions): bool { return false; }
    public function restore(User $user, Transactions $transactions): bool { return false; }
    public function forceDelete(User $user, Transactions $transactions): bool { return false; }
}
