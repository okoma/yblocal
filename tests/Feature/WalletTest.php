<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Policies\WalletPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Business $business;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'business_owner']);
        $this->business = Business::factory()->create(['user_id' => $this->user->id]);
        $this->wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'balance' => 1000,
        ]);
    }

    /** @test */
    public function it_can_deposit_funds()
    {
        $initialBalance = $this->wallet->balance;
        
        $this->wallet->deposit(500, 'Test deposit');
        
        $this->wallet->refresh();
        $this->assertEquals($initialBalance + 500, $this->wallet->balance);
        
        // Check transaction was logged
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'deposit',
            'amount' => 500,
            'description' => 'Test deposit',
        ]);
    }

    /** @test */
    public function it_can_withdraw_funds()
    {
        $initialBalance = $this->wallet->balance;
        
        $this->wallet->withdraw(300, 'Test withdrawal');
        
        $this->wallet->refresh();
        $this->assertEquals($initialBalance - 300, $this->wallet->balance);
        
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'withdrawal',
            'amount' => 300,
        ]);
    }

    /** @test */
    public function it_prevents_withdrawal_exceeding_balance()
    {
        $this->wallet->update(['balance' => 100]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');
        
        $this->wallet->withdraw(200, 'Over-withdrawal attempt');
    }

    /** @test */
    public function it_tracks_balance_before_and_after_transaction()
    {
        $initialBalance = $this->wallet->balance;
        
        $this->wallet->deposit(250, 'Balance tracking test');
        
        $transaction = WalletTransaction::latest()->first();
        
        $this->assertEquals($initialBalance, $transaction->balance_before);
        $this->assertEquals($initialBalance + 250, $transaction->balance_after);
    }

    /** @test */
    public function wallet_policy_allows_owner_to_view_wallet()
    {
        $policy = new WalletPolicy();
        
        $this->assertTrue($policy->view($this->user, $this->wallet));
    }

    /** @test */
    public function wallet_policy_prevents_other_users_from_viewing_wallet()
    {
        $otherUser = User::factory()->create(['role' => 'business_owner']);
        $policy = new WalletPolicy();
        
        $this->assertFalse($policy->view($otherUser, $this->wallet));
    }

    /** @test */
    public function wallet_policy_allows_withdrawal_only_with_sufficient_balance()
    {
        $this->wallet->update(['balance' => 0]);
        $policy = new WalletPolicy();
        
        $this->assertFalse($policy->withdraw($this->user, $this->wallet));
        
        $this->wallet->update(['balance' => 100]);
        $this->assertTrue($policy->withdraw($this->user, $this->wallet));
    }

    /** @test */
    public function it_calculates_has_balance_correctly()
    {
        $this->wallet->update(['balance' => 500]);
        
        $this->assertTrue($this->wallet->hasBalance(400));
        $this->assertTrue($this->wallet->hasBalance(500));
        $this->assertFalse($this->wallet->hasBalance(600));
    }

    /** @test */
    public function it_prevents_negative_deposits()
    {
        $this->expectException(\Exception::class);
        
        $this->wallet->deposit(-100, 'Negative deposit');
    }

    /** @test */
    public function it_prevents_negative_withdrawals()
    {
        $this->expectException(\Exception::class);
        
        $this->wallet->withdraw(-50, 'Negative withdrawal');
    }

    /** @test */
    public function admin_can_access_any_wallet()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $policy = new WalletPolicy();
        
        $this->assertTrue($policy->view($admin, $this->wallet));
        $this->assertTrue($policy->update($admin, $this->wallet));
    }
}
