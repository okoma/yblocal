<?php

namespace App\Livewire;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;

class BusinessFilters extends Component
{
    use WithPagination;

    // URL-synced properties for SEO
    #[Url(as: 'business_type', history: true)]
    public $businessType = '';

    #[Url(as: 'category', history: true)]
    public $category = '';

    #[Url(as: 'state', history: true)]
    public $state = '';

    #[Url(as: 'city', history: true)]
    public $city = '';

    #[Url(as: 'rating', history: true)]
    public $rating = '';

    #[Url(as: 'verified', history: true)]
    public $verified = false;

    #[Url(as: 'premium', history: true)]
    public $premium = false;

    #[Url(as: 'open_now', history: true)]
    public $openNow = false;

    #[Url(as: 'sort', history: true)]
    public $sort = 'relevance';

    #[Url(as: 'q', history: true)]
    public $search = '';

    // Context properties (not in URL)
    public $contextLocation = null;
    public $contextCategory = null;
    public $contextBusinessType = null;

    // UI state
    public $showFilters = false;

    public function mount($location = null, $category = null, $businessType = null, $search = '')
    {
        $this->contextLocation = $location;
        $this->contextCategory = $category;
        $this->contextBusinessType = $businessType;
        $this->search = $search;

        // Pre-select filters based on context
        if ($location) {
            if ($location->type === 'state') {
                $this->state = $location->slug;
            } elseif ($location->type === 'city') {
                $this->city = $location->slug;
                $this->state = $location->parent ? $location->parent->slug : '';
            }
        }

        if ($category) {
            $this->category = $category->slug;
        }

        if ($businessType) {
            $this->businessType = $businessType->slug;
        }
    }

    public function updatedState($value)
    {
        // Reset city when state changes
        if (!$value) {
            $this->city = '';
        }
        $this->resetPage();
    }

    public function updatedBusinessType()
    {
        $this->resetPage();
    }

    public function updatedCategory()
    {
        $this->resetPage();
    }

    public function updatedCity()
    {
        $this->resetPage();
    }

    public function updatedRating()
    {
        $this->resetPage();
    }

    public function updatedSort()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset([
            'businessType',
            'category',
            'state',
            'city',
            'rating',
            'verified',
            'premium',
            'openNow',
            'sort',
        ]);
        $this->resetPage();
    }

    public function clearFilter($filter)
    {
        $this->$filter = '';
        if ($filter === 'state') {
            $this->city = '';
        }
        $this->resetPage();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    #[Computed]
    public function businesses()
    {
        $query = Business::with(['businessType', 'categories', 'location.parent'])
            ->where('status', 'active');

        // Apply context filters (from route)
        if ($this->contextLocation) {
            if ($this->contextLocation->type === 'state') {
                $query->where('state_id', $this->contextLocation->id);
            } elseif ($this->contextLocation->type === 'city') {
                $query->where('city_id', $this->contextLocation->id);
            }
        }

        if ($this->contextCategory) {
            $query->whereHas('categories', function ($q) {
                $q->where('categories.id', $this->contextCategory->id);
            });
        }

        if ($this->contextBusinessType) {
            $query->where('business_type_id', $this->contextBusinessType->id);
        }

        // Apply user-selected filters
        if ($this->businessType) {
            $query->whereHas('businessType', function ($q) {
                $q->where('slug', $this->businessType);
            });
        }

        if ($this->category) {
            $query->whereHas('categories', function ($q) {
                $q->where('slug', $this->category);
            });
        }

        if ($this->state) {
            $stateLocation = Location::where('slug', $this->state)
                ->where('type', 'state')
                ->first();
            if ($stateLocation) {
                $query->where('state_id', $stateLocation->id);
            }
        }

        if ($this->city) {
            $cityLocation = Location::where('slug', $this->city)
                ->where('type', 'city')
                ->first();
            if ($cityLocation) {
                $query->where('city_id', $cityLocation->id);
            }
        }

        if ($this->rating) {
            $query->where('avg_rating', '>=', $this->rating);
        }

        if ($this->verified) {
            $query->where('is_verified', true);
        }

        if ($this->premium) {
            $query->where('is_premium', true);
        }

        if ($this->openNow) {
            $query->where('is_open_now', true);
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('business_name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('tagline', 'like', '%' . $this->search . '%');
            });
        }

        // Sorting
        switch ($this->sort) {
            case 'rating':
                $query->orderBy('avg_rating', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'relevance':
            default:
                $query->orderBy('is_premium', 'desc')
                    ->orderBy('is_verified', 'desc')
                    ->orderBy('avg_rating', 'desc');
                break;
        }

        return $query->paginate(12);
    }

    #[Computed]
    public function businessTypes()
    {
        return BusinessType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'icon']);
    }

    #[Computed]
    public function categories()
    {
        $query = Category::where('is_active', true);
        
        if ($this->businessType) {
            $query->whereHas('businessType', function ($q) {
                $q->where('slug', $this->businessType);
            });
        }
        
        return $query->orderBy('name')->get(['id', 'name', 'slug', 'icon', 'color']);
    }

    #[Computed]
    public function states()
    {
        return Location::where('type', 'state')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    #[Computed]
    public function cities()
    {
        if (!$this->state) {
            return collect();
        }

        $stateLocation = Location::where('slug', $this->state)
            ->where('type', 'state')
            ->first();

        if (!$stateLocation) {
            return collect();
        }

        return Location::where('parent_id', $stateLocation->id)
            ->where('type', 'city')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    #[Computed]
    public function activeFiltersCount()
    {
        $count = 0;
        if ($this->businessType) $count++;
        if ($this->category) $count++;
        if ($this->state) $count++;
        if ($this->city) $count++;
        if ($this->rating) $count++;
        if ($this->verified) $count++;
        if ($this->premium) $count++;
        if ($this->openNow) $count++;
        return $count;
    }

    public function render()
    {
        return view('livewire.business-filters', [
            'businesses' => $this->businesses,
            'businessTypes' => $this->businessTypes,
            'categories' => $this->categories,
            'states' => $this->states,
            'cities' => $this->cities,
            'activeFiltersCount' => $this->activeFiltersCount,
        ]);
    }
}
