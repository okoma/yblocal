<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BusinessClaimSubmitted;

class BusinessClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_claim_and_notifications_sent()
    {
        Notification::fake();

        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/{$business->business_type_id}/{$business->slug}/claim", [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'message' => 'I own this business',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('business_claims', ['business_id' => $business->id, 'email' => 'alice@example.com']);

        Notification::assertSentTo($user, BusinessClaimSubmitted::class);
    }
}
