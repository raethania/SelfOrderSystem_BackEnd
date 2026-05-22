<?php

namespace App\Policies;

use App\Models\Orders;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrdersPolicy
{
    /**
     * Determine whether the user can view any models.
     * Access: Admin, Cashier, Kitchen, Customer (filtered in controller)
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'cashier', 'kitchen', 'customer']);
    }

    /**
     * Determine whether the user can view the model.
     * Access: Admin, Cashier, Kitchen (any order), Customer (own order only)
     */
    public function view(User $user, Orders $order): bool
    {
        if (in_array($user->role, ['admin', 'cashier', 'kitchen'])) {
            return true;
        }

        // Customer can only view their own orders
        return $user->role === 'customer' && $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     * Access: Admin, Cashier, Customer
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'cashier', 'customer']);
    }

    /**
     * Determine whether the user can update the order status.
     * Access: Admin, Cashier, Kitchen
     */
    public function updateStatus(User $user, Orders $order): bool
    {
        return in_array($user->role, ['admin', 'cashier', 'kitchen']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Orders $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Orders $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Orders $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Orders $order): bool
    {
        return false;
    }
}
