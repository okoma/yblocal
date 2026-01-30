<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\PaymentGateway;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentService $paymentService;
    protected User $user;
    protected Business $business;
    protected PaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->paymentService = app(PaymentService::class);
        
        // Create test user
        $this->user = User::factory()->create([
            'role' => 'business_owner',
            'is_active' => true,
            'is_banned' => false,
        ]);

        // Create test business
        $this->business = Business::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        // Create payment gateway
        $this->gateway = PaymentGateway::create([
            'name' => 'Paystack',
            'slug' => 'paystack',
            'public_key' => 'pk_test_xxxxx',
            'secret_key' => 'sk_test_xxxxx',
            'is_enabled' => true,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_validates_minimum_payment_amount()
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
        ]);

        $result = $this->paymentService->initializePayment(
            user: $this->user,
            amount: 50, // Below minimum
            gatewayId: $this->gateway->id,
            payable: $wallet
        );

        $this->assertFalse($result->success);
        $this->assertStringContainsString('100', $result->message);
    }

    /** @test */
    public function it_rejects_payment_for_banned_user()
    {
        $this->user->update(['is_banned' => true]);
        
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
        ]);

        $result = $this->paymentService->initializePayment(
            user: $this->user,
            amount: 1000,
            gatewayId: $this->gateway->id,
            payable: $wallet
        );

        $this->assertFalse($result->success);
    }

    /** @test */
    public function it_creates_transaction_record_on_payment_initialization()
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
        ]);

        $this->paymentService->initializePayment(
            user: $this->user,
            amount: 5000,
            gatewayId: $this->gateway->id,
            payable: $wallet
        );

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
            'amount' => 5000,
            'status' => 'pending',
            'transactionable_type' => Wallet::class,
            'transactionable_id' => $wallet->id,
        ]);
    }

    /** @test */
    public function it_validates_gateway_is_active()
    {
        $this->gateway->update(['is_active' => false]);
        
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
        ]);

        $result = $this->paymentService->initializePayment(
            user: $this->user,
            amount: 1000,
            gatewayId: $this->gateway->id,
            payable: $wallet
        );

        $this->assertFalse($result->success);
    }

    /** @test */
    public function it_generates_unique_transaction_references()
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
        ]);

        $this->paymentService->initializePayment(
            user: $this->user,
            amount: 1000,
            gatewayId: $this->gateway->id,
            payable: $wallet
        );

        $this->paymentService->initializePayment(
            user: $this->user,
            amount: 2000,
            gatewayId: $this->gateway->id,
            payable: $wallet
        );

        $transactions = Transaction::all();
        $this->assertCount(2, $transactions);
        $this->assertNotEquals(
            $transactions[0]->transaction_ref,
            $transactions[1]->transaction_ref
        );
    }

    /** @test */
    public function it_validates_payable_entity_has_business_id()
    {
        $subscription = Subscription::factory()->create([
            'business_id' => $this->business->id,
            'status' => 'active',
        ]);

        $result = $this->paymentService->initializePayment(
            user: $this->user,
            amount: 5000,
            gatewayId: $this->gateway->id,
            payable: $subscription
        );

        $transaction = Transaction::first();
        $this->assertNotNull($transaction);
        $this->assertEquals($this->business->id, $transaction->business_id);
    }

    /** @test */
    public function it_handles_invalid_gateway_gracefully()
    {
        $wallet = Wallet::factory()->create([
            'user_id' => $this->user->id,
            'business_id' => $this->business->id,
        ]);

        $result = $this->paymentService->initializePayment(
            user: $this->user,
            amount: 1000,
            gatewayId: 99999, // Non-existent gateway
            payable: $wallet
        );

        $this->assertFalse($result->success);
    }
}
