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
    <div class="space-y-3">
        @foreach ($days as $key => $dayName)
            @if (isset($hours[$key]))
                @php
                    $dayHours = $hours[$key];
                    $isClosed = isset($dayHours['closed']) && $dayHours['closed'];
                @endphp
                
                <div class="flex items-center justify-between p-4 rounded-lg border-2 {{ $isClosed ? 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50' : 'border-success-300 dark:border-success-600 bg-success-50 dark:bg-success-900/20' }}">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-lg text-gray-900 dark:text-white">{{ $dayName }}</span>
                        
                        @if ($isClosed)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                Closed
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-success-200 dark:bg-success-800 text-success-800 dark:text-success-200">
                                Open
                            </span>
                        @endif
                    </div>
                    
                    <div class="text-right">
                        @if (!$isClosed && isset($dayHours['open']) && isset($dayHours['close']))
                            <div class="text-base font-medium text-gray-900 dark:text-white">
                                {{ date('g:i A', strtotime($dayHours['open'])) }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                to {{ date('g:i A', strtotime($dayHours['close'])) }}
                            </div>
                        @elseif (!$isClosed)
                            <span class="text-sm text-gray-500 dark:text-gray-400">Not set</span>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif
