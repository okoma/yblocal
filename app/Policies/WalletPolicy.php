<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any wallets.
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view all wallets
        return $user->isAdmin();
    }

    /**
     * Determine if user can view a specific wallet.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        // Admins can view all wallets
        if ($user->isAdmin()) {
            return true;
        }

        // Users can view their own wallet
        if ($wallet->user_id === $user->id) {
            return true;
        }

        // Business owners can view wallets of their businesses
        if ($user->isBusinessOwner() && $wallet->business_id) {
            return $wallet->business->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can create wallets.
     */
    public function create(User $user): bool
    {
        // Wallets are auto-created, no manual creation
        return false;
    }

    /**
     * Determine if user can update a wallet.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        // Only admins can directly update wallet balances
        // Normal users use deposit/withdrawal methods
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete a wallet.
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        // Wallets should never be deleted (use soft deletes if needed)
        return false;
    }

    /**
     * Determine if user can make deposits.
     */
    public function deposit(User $user, Wallet $wallet): bool
    {
        // Admins can deposit to any wallet
        if ($user->isAdmin()) {
            return true;
        }

        // Users can deposit to their own wallet
        if ($wallet->user_id === $user->id) {
            return true;
        }

        // Business owners can deposit to their business wallet
        if ($user->isBusinessOwner() && $wallet->business_id) {
            return $wallet->business->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can make withdrawals.
     */
    public function withdraw(User $user, Wallet $wallet): bool
    {
        // Admins can withdraw from any wallet
        if ($user->isAdmin()) {
            return true;
        }

        // Users can withdraw from their own wallet if they have sufficient balance
        if ($wallet->user_id === $user->id && $wallet->balance > 0) {
            return true;
        }

        // Business owners can withdraw from their business wallet
        if ($user->isBusinessOwner() && $wallet->business_id) {
            return $wallet->business->user_id === $user->id && $wallet->balance > 0;
        }

        return false;
    }

    /**
     * Determine if user can view wallet transactions.
     */
    public function viewTransactions(User $user, Wallet $wallet): bool
    {
        return $this->view($user, $wallet);
    }
}
