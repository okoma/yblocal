<?php

namespace Tests\Feature;

use App\Livewire\BusinessFilters;
use App\Models\Business;
use App\Models\Category;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test locations
        Location::factory()->create(['name' => 'Lagos', 'type' => 'state']);
        $lagosCity = Location::factory()->create(['name' => 'Ikeja', 'type' => 'city']);
        
        Location::factory()->create(['name' => 'Abuja', 'type' => 'state']);
        $abujaCity = Location::factory()->create(['name' => 'Wuse', 'type' => 'city']);
        
        // Create test categories
        $hotelCategory = Category::factory()->create(['name' => 'Hotels', 'slug' => 'hotels']);
        $restaurantCategory = Category::factory()->create(['name' => 'Restaurants', 'slug' => 'restaurants']);
        
        // Create test businesses
        $owner = User::factory()->create(['role' => 'business_owner']);
        
        // Lagos hotel - verified, high rating
        $lagosHotel = Business::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Lagos Grand Hotel',
            'city_location_id' => $lagosCity->id,
            'is_verified' => true,
            'is_active' => true,
            'average_rating' => 4.5,
        ]);
        $lagosHotel->categories()->attach($hotelCategory->id);
        
        // Abuja restaurant - not verified, medium rating
        $abujaRestaurant = Business::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Abuja Delights',
            'city_location_id' => $abujaCity->id,
            'is_verified' => false,
            'is_active' => true,
            'average_rating' => 3.2,
        ]);
        $abujaRestaurant->categories()->attach($restaurantCategory->id);
        
        // Lagos restaurant - verified, low rating
        $lagosRestaurant = Business::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Lagos Eats',
            'city_location_id' => $lagosCity->id,
            'is_verified' => true,
            'is_active' => true,
            'average_rating' => 2.8,
        ]);
        $lagosRestaurant->categories()->attach($restaurantCategory->id);
    }

    /** @test */
    public function it_renders_successfully()
    {
        Livewire::test(BusinessFilters::class)
            ->assertStatus(200);
    }

    /** @test */
    public function it_filters_businesses_by_state()
    {
        Livewire::test(BusinessFilters::class)
            ->set('state', 'Lagos')
            ->assertSee('Lagos Grand Hotel')
            ->assertSee('Lagos Eats')
            ->assertDontSee('Abuja Delights');
    }

    /** @test */
    public function it_filters_businesses_by_city()
    {
        Livewire::test(BusinessFilters::class)
            ->set('city', 'Ikeja')
            ->assertSee('Lagos Grand Hotel')
            ->assertSee('Lagos Eats')
            ->assertDontSee('Abuja Delights');
    }

    /** @test */
    public function it_filters_businesses_by_category()
    {
        Livewire::test(BusinessFilters::class)
            ->set('businessType', 'hotels')
            ->assertSee('Lagos Grand Hotel')
            ->assertDontSee('Lagos Eats')
            ->assertDontSee('Abuja Delights');
    }

    /** @test */
    public function it_filters_businesses_by_verification_status()
    {
        Livewire::test(BusinessFilters::class)
            ->set('verified', true)
            ->assertSee('Lagos Grand Hotel')
            ->assertSee('Lagos Eats')
            ->assertDontSee('Abuja Delights');
    }

    /** @test */
    public function it_filters_businesses_by_minimum_rating()
    {
        Livewire::test(BusinessFilters::class)
            ->set('minRating', 4.0)
            ->assertSee('Lagos Grand Hotel')
            ->assertDontSee('Lagos Eats')
            ->assertDontSee('Abuja Delights');
    }

    /** @test */
    public function it_syncs_filters_to_url_parameters()
    {
        Livewire::test(BusinessFilters::class)
            ->set('state', 'Lagos')
            ->set('businessType', 'hotels')
            ->assertSet('state', 'Lagos')
            ->assertSet('businessType', 'hotels');
    }

    /** @test */
    public function it_resets_city_when_state_changes()
    {
        Livewire::test(BusinessFilters::class)
            ->set('state', 'Lagos')
            ->set('city', 'Ikeja')
            ->set('state', 'Abuja')
            ->assertSet('city', '');
    }

    /** @test */
    public function it_applies_multiple_filters_simultaneously()
    {
        Livewire::test(BusinessFilters::class)
            ->set('state', 'Lagos')
            ->set('businessType', 'hotels')
            ->set('verified', true)
            ->set('minRating', 4.0)
            ->assertSee('Lagos Grand Hotel')
            ->assertDontSee('Lagos Eats')
            ->assertDontSee('Abuja Delights');
    }

    /** @test */
    public function it_clears_all_filters()
    {
        Livewire::test(BusinessFilters::class)
            ->set('state', 'Lagos')
            ->set('businessType', 'hotels')
            ->set('verified', true)
            ->call('clearFilters')
            ->assertSet('state', '')
            ->assertSet('businessType', '')
            ->assertSet('verified', null);
    }

    /** @test */
    public function it_only_shows_active_businesses()
    {
        // Create inactive business
        $owner = User::factory()->create(['role' => 'business_owner']);
        $inactiveBusiness = Business::factory()->create([
            'user_id' => $owner->id,
            'name' => 'Inactive Business',
            'is_active' => false,
        ]);

        Livewire::test(BusinessFilters::class)
            ->assertDontSee('Inactive Business');
    }

    /** @test */
    public function it_loads_cities_for_selected_state()
    {
        $component = Livewire::test(BusinessFilters::class)
            ->set('state', 'Lagos');

        // Access the computed property
        $cities = $component->get('availableCities');
        
        $this->assertNotEmpty($cities);
        $this->assertTrue($cities->contains('name', 'Ikeja'));
    }
}
