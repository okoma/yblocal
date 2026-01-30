<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any transactions.
     */
    public function viewAny(User $user): bool
    {
        // Admins and moderators can view all transactions
        return $user->isAdmin() || $user->isModerator();
    }

    /**
     * Determine if user can view a specific transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        // Admins can view all transactions
        if ($user->isAdmin() || $user->isModerator()) {
            return true;
        }

        // Users can view their own transactions
        if ($transaction->user_id === $user->id) {
            return true;
        }

        // Business owners can view transactions for their businesses
        if ($user->isBusinessOwner() && $transaction->business_id) {
            return $transaction->business->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine if user can create transactions.
     */
    public function create(User $user): bool
    {
        // Transactions are created through payment flows, not manually
        // Only admins can manually create (for adjustments)
        return $user->isAdmin();
    }

    /**
     * Determine if user can update a transaction.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // Only admins can update transactions (for corrections)
        return $user->isAdmin();
    }

    /**
     * Determine if user can delete a transaction.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // Transactions should never be deleted (audit trail)
        // Only soft delete allowed for admins
        return false;
    }

    /**
     * Determine if user can refund a transaction.
     */
    public function refund(User $user, Transaction $transaction): bool
    {
        // Only admins can process refunds
        if (!$user->isAdmin()) {
            return false;
        }

        // Transaction must be completed and not already refunded
        return $transaction->status === 'completed' 
            && !$transaction->is_refunded
            && $transaction->paid_at !== null;
    }

    /**
     * Determine if user can view transaction receipt.
     */
    public function viewReceipt(User $user, Transaction $transaction): bool
    {
        return $this->view($user, $transaction) && $transaction->status === 'completed';
    }

    /**
     * Determine if user can download transaction invoice.
     */
    public function downloadInvoice(User $user, Transaction $transaction): bool
    {
        return $this->view($user, $transaction) && $transaction->invoice()->exists();
    }
}
