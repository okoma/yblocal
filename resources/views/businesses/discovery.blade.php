
//resources/views/businesses/discovery.blade.php
@extends('layouts.app')

@section('title', $pageTitle ?? 'Discover Local Businesses')

@section('meta-description', $metaDescription ?? 'Find and connect with verified local businesses in your area.')

@section('content')
    @livewire('business-filters', [
        'location' => $state ?? null,
        'category' => isset($categories) && $categories->isNotEmpty() ? $categories->first() : null,
        'businessType' => $businessType ?? null,
        'search' => $searchQuery ?? ''
    ])
@endsection

@push('scripts')
<script>
    // SEO: Update page title dynamically (optional)
    document.addEventListener('livewire:load', function () {
        Livewire.on('filtersUpdated', (data) => {
            // Update browser history with new filter state
            // This is handled automatically by Livewire's URL binding
        });
    });
</script>
@endpush
