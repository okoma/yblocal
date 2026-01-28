<div class="space-y-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
    <div>
        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $request->title }}</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($request->description, 200) }}</p>
    </div>
    
    <div class="grid grid-cols-2 gap-3 text-sm">
        <div>
            <span class="text-gray-500 dark:text-gray-400">Category:</span>
            <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $request->category->name }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Location:</span>
            <span class="ml-2 font-medium text-gray-900 dark:text-white">
                {{ $request->stateLocation->name }}{{ $request->cityLocation ? ' - ' . $request->cityLocation->name : '' }}
            </span>
        </div>
        @if($request->budget_min || $request->budget_max)
        <div class="col-span-2">
            <span class="text-gray-500 dark:text-gray-400">Budget:</span>
            <span class="ml-2 font-medium text-gray-900 dark:text-white">
                @if($request->budget_min && $request->budget_max)
                    ₦{{ number_format($request->budget_min, 0) }} - ₦{{ number_format($request->budget_max, 0) }}
                @elseif($request->budget_min)
                    From ₦{{ number_format($request->budget_min, 0) }}
                @elseif($request->budget_max)
                    Up to ₦{{ number_format($request->budget_max, 0) }}
                @endif
            </span>
        </div>
        @endif
    </div>
</div>
