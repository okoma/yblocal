@php
    $hours = is_array($businessHours) ? $businessHours : [];
    
    $days = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];
@endphp

@if (empty($hours))
    <div class="text-sm text-gray-500 dark:text-gray-400">
        No business hours set.
    </div>
@else
    <div class="space-y-2">
        @foreach ($days as $key => $dayName)
            @if (isset($hours[$key]))
                @php
                    $dayHours = $hours[$key];
                    $isClosed = isset($dayHours['closed']) && $dayHours['closed'];
                @endphp
                
                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-gray-900 dark:text-white">{{ $dayName }}</span>
                    
                    @if ($isClosed)
                        <span class="text-sm text-gray-500 dark:text-gray-400">Closed</span>
                    @elseif (isset($dayHours['open']) && isset($dayHours['close']))
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ date('g:i A', strtotime($dayHours['open'])) }} - {{ date('g:i A', strtotime($dayHours['close'])) }}
                        </span>
                    @else
                        <span class="text-sm text-gray-500 dark:text-gray-400">Not set</span>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endif
