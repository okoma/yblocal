<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Business;
use App\Models\User;

class SavedBusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_and_unsave_business()
    {
        $user = User::factory()->create();
        $business = Business::factory()->create();

        $this->actingAs($user)->post("/{$business->business_type_id}/{$business->slug}/save")->assertRedirect();
        $this->assertDatabaseHas('saved_businesses', ['user_id' => $user->id, 'business_id' => $business->id]);

        $this->actingAs($user)->delete("/{$business->business_type_id}/{$business->slug}/save")->assertRedirect();
        $this->assertDatabaseMissing('saved_businesses', ['user_id' => $user->id, 'business_id' => $business->id]);
    }
}
