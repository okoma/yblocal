<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Location;
use App\Models\QuoteRequest;
use App\Models\QuoteResponse;
use App\Models\User;
use App\Policies\QuoteRequestPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $businessOwner;
    protected Business $business;
    protected Category $category;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->businessOwner = User::factory()->create(['role' => 'business_owner']);
        
        $this->category = Category::factory()->create();
        $this->location = Location::factory()->create();
        
        $this->business = Business::factory()->create([
            'user_id' => $this->businessOwner->id,
            'is_active' => true,
            'is_claimed' => true,
            'city_location_id' => $this->location->id,
        ]);
        
        $this->business->categories()->attach($this->category->id);
    }

    /** @test */
    public function customer_can_create_quote_request()
    {
        $quoteRequest = QuoteRequest::create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'title' => 'Need web development services',
            'description' => 'Looking for a professional web developer to build an e-commerce website',
            'budget_min' => 50000,
            'budget_max' => 100000,
            'status' => 'open',
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertDatabaseHas('quote_requests', [
            'id' => $quoteRequest->id,
            'user_id' => $this->customer->id,
            'status' => 'open',
        ]);
    }

    /** @test */
    public function business_can_submit_quote_response()
    {
        $quoteRequest = QuoteRequest::factory()->create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'status' => 'open',
            'expires_at' => now()->addDays(7),
        ]);

        $response = QuoteResponse::create([
            'quote_request_id' => $quoteRequest->id,
            'business_id' => $this->business->id,
            'price' => 75000,
            'delivery_time' => '4-6 weeks',
            'message' => 'We can deliver a professional e-commerce solution',
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('quote_responses', [
            'id' => $response->id,
            'quote_request_id' => $quoteRequest->id,
            'business_id' => $this->business->id,
            'status' => 'submitted',
        ]);
    }

    /** @test */
    public function quote_request_policy_allows_owner_to_view()
    {
        $quoteRequest = QuoteRequest::factory()->create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $policy = new QuoteRequestPolicy();
        
        $this->assertTrue($policy->view($this->customer, $quoteRequest));
    }

    /** @test */
    public function quote_request_policy_allows_matching_business_to_view()
    {
        $quoteRequest = QuoteRequest::factory()->create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'status' => 'open',
        ]);

        $policy = new QuoteRequestPolicy();
        
        // Business in same category/location can view
        $this->assertTrue($policy->view($this->businessOwner, $quoteRequest));
    }

    /** @test */
    public function customer_cannot_delete_quote_request_with_responses()
    {
        $quoteRequest = QuoteRequest::factory()->create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        QuoteResponse::factory()->create([
            'quote_request_id' => $quoteRequest->id,
            'business_id' => $this->business->id,
        ]);

        $policy = new QuoteRequestPolicy();
        
        $this->assertFalse($policy->delete($this->customer, $quoteRequest));
    }

    /** @test */
    public function customer_can_delete_quote_request_without_responses()
    {
        $quoteRequest = QuoteRequest::factory()->create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $policy = new QuoteRequestPolicy();
        
        $this->assertTrue($policy->delete($this->customer, $quoteRequest));
    }

    /** @test */
    public function business_cannot_respond_to_expired_quote_request()
    {
        $quoteRequest = QuoteRequest::factory()->create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'status' => 'open',
            'expires_at' => now()->subDay(), // Expired
        ]);

        $policy = new QuoteRequestPolicy();
        
        $this->assertFalse($policy->respond($this->businessOwner, $quoteRequest));
    }

    /** @test */
    public function business_cannot_respond_to_closed_quote_request()
    {
        $quoteRequest = QuoteRequest::factory()->create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'status' => 'closed',
            'expires_at' => now()->addDays(7),
        ]);

        $policy = new QuoteRequestPolicy();
        
        $this->assertFalse($policy->respond($this->businessOwner, $quoteRequest));
    }

    /** @test */
    public function only_quote_owner_can_shortlist_responses()
    {
        $quoteRequest = QuoteRequest::factory()->create([
            'user_id' => $this->customer->id,
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
        ]);

        $otherUser = User::factory()->create(['role' => 'customer']);
        $policy = new QuoteRequestPolicy();
        
        $this->assertTrue($policy->shortlist($this->customer, $quoteRequest));
        $this->assertFalse($policy->shortlist($otherUser, $quoteRequest));
    }
}
