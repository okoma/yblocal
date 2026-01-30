<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Policies\BusinessPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessPolicy $policy;
    protected User $admin;
    protected User $businessOwner;
    protected User $manager;
    protected User $customer;
    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new BusinessPolicy();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->businessOwner = User::factory()->create(['role' => 'business_owner']);
        $this->manager = User::factory()->create(['role' => 'branch_manager']);
        $this->customer = User::factory()->create(['role' => 'customer']);
        
        $this->business = Business::factory()->create([
            'user_id' => $this->businessOwner->id,
        ]);
    }

    /** @test */
    public function admin_can_view_all_businesses()
    {
        $this->assertTrue($this->policy->viewAny($this->admin));
        $this->assertTrue($this->policy->view($this->admin, $this->business));
    }

    /** @test */
    public function business_owner_can_view_own_business()
    {
        $this->assertTrue($this->policy->view($this->businessOwner, $this->business));
    }

    /** @test */
    public function business_owner_cannot_view_others_business()
    {
        $otherOwner = User::factory()->create(['role' => 'business_owner']);
        
        $this->assertFalse($this->policy->view($otherOwner, $this->business));
    }

    /** @test */
    public function customer_cannot_view_business_in_admin_context()
    {
        $this->assertFalse($this->policy->viewAny($this->customer));
    }

    /** @test */
    public function business_owner_can_update_own_business()
    {
        $this->assertTrue($this->policy->update($this->businessOwner, $this->business));
    }

    /** @test */
    public function manager_can_update_business_with_permission()
    {
        // Add manager to business with edit permission
        $this->business->managers()->create([
            'user_id' => $this->manager->id,
            'business_id' => $this->business->id,
            'status' => 'active',
            'permissions' => ['edit_business' => true],
        ]);

        $this->assertTrue($this->policy->update($this->manager, $this->business));
    }

    /** @test */
    public function manager_cannot_update_business_without_permission()
    {
        // Add manager without edit permission
        $this->business->managers()->create([
            'user_id' => $this->manager->id,
            'business_id' => $this->business->id,
            'status' => 'active',
            'permissions' => ['edit_business' => false],
        ]);

        $this->assertFalse($this->policy->update($this->manager, $this->business));
    }

    /** @test */
    public function only_owner_and_admin_can_delete_business()
    {
        $this->assertTrue($this->policy->delete($this->admin, $this->business));
        $this->assertTrue($this->policy->delete($this->businessOwner, $this->business));
        $this->assertFalse($this->policy->delete($this->manager, $this->business));
        $this->assertFalse($this->policy->delete($this->customer, $this->business));
    }

    /** @test */
    public function only_admin_can_restore_deleted_business()
    {
        $this->assertTrue($this->policy->restore($this->admin, $this->business));
        $this->assertFalse($this->policy->restore($this->businessOwner, $this->business));
    }

    /** @test */
    public function manager_can_view_analytics_with_permission()
    {
        $this->business->managers()->create([
            'user_id' => $this->manager->id,
            'business_id' => $this->business->id,
            'status' => 'active',
            'permissions' => ['view_analytics' => true],
        ]);

        $this->assertTrue($this->policy->viewAnalytics($this->manager, $this->business));
    }

    /** @test */
    public function manager_cannot_manage_subscription()
    {
        $this->business->managers()->create([
            'user_id' => $this->manager->id,
            'business_id' => $this->business->id,
            'status' => 'active',
            'permissions' => [],
        ]);

        $this->assertFalse($this->policy->manageSubscription($this->manager, $this->business));
    }

    /** @test */
    public function only_owner_and_admin_can_invite_managers()
    {
        $this->assertTrue($this->policy->inviteManagers($this->admin, $this->business));
        $this->assertTrue($this->policy->inviteManagers($this->businessOwner, $this->business));
        $this->assertFalse($this->policy->inviteManagers($this->manager, $this->business));
    }

    /** @test */
    public function banned_user_cannot_create_business()
    {
        $bannedOwner = User::factory()->create([
            'role' => 'business_owner',
            'is_banned' => true,
        ]);

        $this->assertFalse($this->policy->create($bannedOwner));
    }

    /** @test */
    public function inactive_user_cannot_create_business()
    {
        $inactiveOwner = User::factory()->create([
            'role' => 'business_owner',
            'is_active' => false,
        ]);

        $this->assertFalse($this->policy->create($inactiveOwner));
    }
}
