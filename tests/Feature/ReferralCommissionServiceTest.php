<?php

namespace Tests\Feature;

use App\Jobs\ProcessReferralCommissionJob;
use App\Models\Business;
use App\Models\CustomerReferral;
use App\Models\CustomerReferralWallet;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReferralCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReferralCommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReferralCommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = app(ReferralCommissionService::class);
    }

    /** @test */
    public function it_calculates_10_percent_commission_correctly()
    {
        // Create referrer (customer)
        $referrer = User::factory()->create(['role' => 'customer']);
        
        // Create business owner
        $businessOwner = User::factory()->create(['role' => 'business_owner']);
        
        // Create referred business
        $business = Business::factory()->create(['user_id' => $businessOwner->id]);
        
        // Create referral record
        CustomerReferral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $businessOwner->id,
            'referred_business_id' => $business->id,
            'referral_code' => $referrer->referral_code,
            'status' => 'pending',
        ]);

        // Create wallet for referrer
        $wallet = CustomerReferralWallet::create([
            'user_id' => $referrer->id,
            'balance' => 0,
            'currency' => 'NGN',
        ]);

        // Create transaction (business payment)
        $transaction = Transaction::factory()->create([
            'user_id' => $businessOwner->id,
            'business_id' => $business->id,
            'amount' => 10000,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Process commission
        $this->commissionService->processCustomerCommission($transaction);

        // Assert commission was credited
        $wallet->refresh();
        $this->assertEquals(1000, $wallet->balance); // 10% of 10,000
        
        // Assert transaction was created
        $this->assertDatabaseHas('customer_referral_transactions', [
            'customer_referral_wallet_id' => $wallet->id,
            'type' => 'commission',
            'amount' => 1000,
            'transaction_id' => $transaction->id,
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_commission_processing()
    {
        $referrer = User::factory()->create(['role' => 'customer']);
        $businessOwner = User::factory()->create(['role' => 'business_owner']);
        $business = Business::factory()->create(['user_id' => $businessOwner->id]);
        
        CustomerReferral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $businessOwner->id,
            'referred_business_id' => $business->id,
            'referral_code' => $referrer->referral_code,
            'status' => 'pending',
        ]);

        $wallet = CustomerReferralWallet::create([
            'user_id' => $referrer->id,
            'balance' => 0,
            'currency' => 'NGN',
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $businessOwner->id,
            'business_id' => $business->id,
            'amount' => 5000,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // Process twice
        $this->commissionService->processCustomerCommission($transaction);
        $this->commissionService->processCustomerCommission($transaction);

        // Should only receive commission once
        $wallet->refresh();
        $this->assertEquals(500, $wallet->balance); // Not 1000
        
        $this->assertEquals(
            1,
            \App\Models\CustomerReferralTransaction::where('transaction_id', $transaction->id)->count()
        );
    }

    /** @test */
    public function it_skips_commission_for_non_existent_referral()
    {
        $businessOwner = User::factory()->create(['role' => 'business_owner']);
        $business = Business::factory()->create(['user_id' => $businessOwner->id]);

        $transaction = Transaction::factory()->create([
            'user_id' => $businessOwner->id,
            'business_id' => $business->id,
            'amount' => 10000,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        // No referral exists
        $this->commissionService->processCustomerCommission($transaction);

        // No commission should be created
        $this->assertEquals(0, \App\Models\CustomerReferralTransaction::count());
    }

    /** @test */
    public function it_updates_referral_status_to_qualified_on_first_commission()
    {
        $referrer = User::factory()->create(['role' => 'customer']);
        $businessOwner = User::factory()->create(['role' => 'business_owner']);
        $business = Business::factory()->create(['user_id' => $businessOwner->id]);
        
        $referral = CustomerReferral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $businessOwner->id,
            'referred_business_id' => $business->id,
            'referral_code' => $referrer->referral_code,
            'status' => 'pending',
        ]);

        CustomerReferralWallet::create([
            'user_id' => $referrer->id,
            'balance' => 0,
            'currency' => 'NGN',
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $businessOwner->id,
            'business_id' => $business->id,
            'amount' => 5000,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $this->commissionService->processCustomerCommission($transaction);

        $referral->refresh();
        $this->assertEquals('qualified', $referral->status);
    }

    /** @test */
    public function it_handles_zero_amount_transactions_gracefully()
    {
        $referrer = User::factory()->create(['role' => 'customer']);
        $businessOwner = User::factory()->create(['role' => 'business_owner']);
        $business = Business::factory()->create(['user_id' => $businessOwner->id]);
        
        CustomerReferral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $businessOwner->id,
            'referred_business_id' => $business->id,
            'referral_code' => $referrer->referral_code,
            'status' => 'pending',
        ]);

        $wallet = CustomerReferralWallet::create([
            'user_id' => $referrer->id,
            'balance' => 0,
            'currency' => 'NGN',
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $businessOwner->id,
            'business_id' => $business->id,
            'amount' => 0,
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $this->commissionService->processCustomerCommission($transaction);

        // No commission should be created for zero amount
        $wallet->refresh();
        $this->assertEquals(0, $wallet->balance);
    }
}
