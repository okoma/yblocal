<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reviews</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @else
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3/dist/tailwind.min.css" rel="stylesheet">
    @endif
</head>
<body class="bg-transparent">
    <div class="space-y-6">
        @forelse($reviews as $review)
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <!-- Review Header -->
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <!-- Reviewer Info -->
                        <div class="flex items-center gap-3 mb-2">
                            @if($review->user)
                                @if($review->user->avatar)
                                    <img 
                                        src="{{ Storage::url($review->user->avatar) }}" 
                                        alt="{{ $review->user->name }}"
                                        class="w-10 h-10 rounded-full object-cover"
                                    >
                                @else
                                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                        {{ substr($review->user->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $review->user->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $review->created_at->diffForHumans() }}</div>
                                </div>
                            @elseif($review->reviewer_name)
                                <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white font-semibold">
                                    {{ substr($review->reviewer_name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $review->reviewer_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $review->created_at->diffForHumans() }}</div>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-gray-400 flex items-center justify-center text-white font-semibold">
                                    A
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">Anonymous</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $review->created_at->diffForHumans() }}</div>
                                </div>
                            @endif
                        </div>

                        <!-- Star Rating -->
                        <div class="flex items-center gap-2">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                            <span class="ml-1 text-sm font-medium text-gray-900 dark:text-white">{{ $review->rating }}/5</span>
                        </div>
                    </div>

                    <!-- Verified Purchase Badge -->
                    @if(isset($review->is_verified_purchase) && $review->is_verified_purchase)
                        <span class="inline-flex items-center px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-md text-xs font-medium">
                            ✓ Verified
                        </span>
                    @endif
                </div>

                <!-- Review Content -->
                <div class="mb-4">
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                        {{ $review->comment }}
                    </p>
                </div>

                <!-- Review Photos -->
                @if($review->photos && count($review->photos) > 0)
                    <div class="flex gap-2 mb-4 overflow-x-auto">
                        @foreach($review->photos as $photo)
                            <img 
                                src="{{ Storage::url($photo) }}" 
                                alt="Review photo"
                                class="h-24 w-24 object-cover rounded-lg cursor-pointer hover:opacity-80"
                                onclick="openImageModal('{{ Storage::url($photo) }}')"
                            >
                        @endforeach
                    </div>
                @endif

                <!-- Review Actions -->
                <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button 
                        onclick="voteReview({{ $review->id }}, 'up')"
                        class="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400 hover:text-green-600 dark:hover:text-green-400"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                        </svg>
                        <span>Helpful</span>
                        @if(isset($review->helpful_count) && $review->helpful_count > 0)
                            <span class="font-medium">({{ $review->helpful_count }})</span>
                        @endif
                    </button>
                    
                    <button 
                        onclick="voteReview({{ $review->id }}, 'down')"
                        class="flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018c.163 0 .326.02.485.06L17 4m-7 10v2a2 2 0 002 2h.095c.5 0 .905-.405.905-.905 0-.714.211-1.412.608-2.006L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"></path>
                        </svg>
                        <span>Not helpful</span>
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No reviews yet</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Be the first to review this business!</p>
            </div>
        @endforelse

        <!-- Load More / Pagination -->
        @if($reviews->hasMorePages())
            <div class="text-center">
                <a 
                    href="{{ $reviews->nextPageUrl() }}"
                    class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                >
                    Load More Reviews
                </a>
            </div>
        @endif
    </div>

    <script>
        async function voteReview(reviewId, voteType) {
            try {
                const response = await fetch(`/reviews/${reviewId}/vote`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ vote: voteType })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Optionally update the helpful count in UI
                    console.log('Vote recorded');
                }
            } catch (error) {
                console.error('Error voting:', error);
            }
        }

        function openImageModal(imageUrl) {
            // Simple lightbox - can be enhanced with a library
            window.open(imageUrl, '_blank');
        }
    </script>
</body>
</html>
