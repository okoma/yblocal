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
    
    // Get today's day name in lowercase
    $today = strtolower(now()->format('l')); // e.g., 'monday', 'tuesday'
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
                    $isToday = ($key === $today);
                @endphp
                
                <div class="flex items-center justify-between p-4 rounded-lg border-2 transition-all
                    @if ($isToday)
                        @if ($isClosed)
                            border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/30 shadow-md
                        @else
                            border-green-500 dark:border-green-500 bg-green-100 dark:bg-green-900/30 shadow-md
                        @endif
                    @else
                        @if ($isClosed)
                            border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50
                        @else
                            border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800/50
                        @endif
                    @endif
                ">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-lg {{ $isToday ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $dayName }}
                        </span>
                        
                        @if ($isToday)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-500 text-white animate-pulse">
                                TODAY
                            </span>
                        @endif
                        
                        @if ($isClosed)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                {{ $isToday ? 'bg-red-200 dark:bg-red-800 text-red-800 dark:text-red-200' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                                Closed
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold 
                                {{ $isToday ? 'bg-green-600 text-white' : 'bg-green-200 dark:bg-green-800 text-green-800 dark:text-green-200' }}">
                                Open
                            </span>
                        @endif
                    </div>
                    
                    <div class="text-right">
                        @if (!$isClosed && isset($dayHours['open']) && isset($dayHours['close']))
                            <div class="text-base font-medium {{ $isToday ? 'text-green-700 dark:text-green-300 font-bold' : 'text-gray-900 dark:text-white' }}">
                                {{ date('g:i A', strtotime($dayHours['open'])) }}
                            </div>
                            <div class="text-sm {{ $isToday ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                to {{ date('g:i A', strtotime($dayHours['close'])) }}
                            </div>
                        @elseif (!$isClosed)
                            <span class="text-sm text-gray-500 dark:text-gray-400">Not set</span>
                        @else
                            @if ($isToday)
                                <span class="text-sm font-semibold text-red-600 dark:text-red-400">Closed Today</span>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
@endif