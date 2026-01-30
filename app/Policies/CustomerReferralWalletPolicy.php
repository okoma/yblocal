<?php

namespace App\Policies;

use App\Models\CustomerReferralWallet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerReferralWalletPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any customer referral wallets.
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view all customer referral wallets
        return $user->isAdmin();
    }

    /**
     * Determine if user can view a specific customer referral wallet.
     */
    public function view(User $user, CustomerReferralWallet $wallet): bool
    {
        // Admins can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Users can only view their own referral wallet
        return $wallet->user_id === $user->id;
    }

    /**
     * Determine if user can create customer referral wallets.
     */
    public function create(User $user): bool
    {
        // Wallets are auto-created, no manual creation allowed
        return false;
    }

    /**
     * Determine if user can update a customer referral wallet.
     */
    public function update(User $user, CustomerReferralWallet $wallet): bool
    {
        // Only admins can adjust balances
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete a customer referral wallet.
     */
    public function delete(User $user, CustomerReferralWallet $wallet): bool
    {
        // Wallets should never be deleted
        return false;
    }

    /**
     * Determine if user can request withdrawal from referral wallet.
     */
    public function withdraw(User $user, CustomerReferralWallet $wallet): bool
    {
        // Users can withdraw from their own wallet if balance > 0
        return $wallet->user_id === $user->id 
            && $wallet->balance > 0
            && !$user->is_banned
            && $user->is_active;
    }

    /**
     * Determine if user can view transaction history.
     */
    public function viewTransactions(User $user, CustomerReferralWallet $wallet): bool
    {
        return $this->view($user, $wallet);
    }

    /**
     * Determine if user can view withdrawal requests.
     */
    public function viewWithdrawals(User $user, CustomerReferralWallet $wallet): bool
    {
        return $this->view($user, $wallet);
    }
}
