<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BusinessReported;

class BusinessReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_report_and_notifications_sent()
    {
        Notification::fake();

        $user = User::factory()->create();
        $business = Business::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post("/{$business->business_type_id}/{$business->slug}/report", [
            'reason' => 'spam',
            'details' => 'This listing is spam',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('business_reports', ['business_id' => $business->id, 'reason' => 'spam']);

        Notification::assertSentTo($user, BusinessReported::class);
    }
}
