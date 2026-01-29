<x-filament-panels::page>
    <div class="space-y-6">
        @php $business = $this->getActiveBusiness(); @endphp
        @if(!$business)
            <x-filament::section>
                <p class="text-sm text-gray-500 dark:text-gray-400">Please select an active business to view referrals.</p>
            </x-filament::section>
        @else
            {{-- Referral link + copy & share --}}
            <x-filament::section>
                <x-slot name="heading">Your referral link</x-slot>
                <x-slot name="description">Share this link with other businesses. When they sign up, you earn referral credits (convert to ad credits, quote credits, or 1-month subscription).</x-slot>
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" readonly
                               value="{{ $this->getReferralLink() }}"
                               class="flex-1 min-w-0 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm"/>
                        <x-filament::button
                            color="gray"
                            x-data="{ link: @js($this->getReferralLink()) }"
                            x-on:click="navigator.clipboard && navigator.clipboard.writeText(link); $dispatch('notify', { message: 'Link copied!' })"
                        >
                            Copy link
                        </x-filament::button>
                    </div>
                    @if($this->getReferralLink())
                        <div class="flex flex-wrap gap-2">
                            @php $link = $this->getReferralLink(); $encoded = urlencode($link); @endphp
                            <a href="https://wa.me/?text={{ $encoded }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm bg-[#25D366] text-white hover:opacity-90">
                                WhatsApp
                            </a>
                            <a href="https://twitter.com/intent/tweet?text={{ $encoded }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm bg-[#1DA1F2] text-white hover:opacity-90">
                                Twitter
                            </a>
                        </div>
                    @endif
                </div>
            </x-filament::section>

            {{-- Referral credit balance --}}
            <x-filament::section class="text-center">
                <x-slot name="heading">Referral credit balance</x-slot>
                <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">{{ $business->referral_credits ?? 0 }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">credits (convert to ad credits, quote credits, or 1-month subscription above)</div>
            </x-filament::section>

            {{-- Referred businesses --}}
            <x-filament::section>
                <x-slot name="heading">Referred businesses</x-slot>
                @php $referrals = $this->getReferredBusinesses(); @endphp
                @if($referrals->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No businesses referred yet. Share your link to get started.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-2 font-medium">Business</th>
                                    <th class="text-left py-2 font-medium">Signed up</th>
                                    <th class="text-left py-2 font-medium">Credits awarded</th>
                                    <th class="text-left py-2 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($referrals as $ref)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2">{{ $ref->referredBusiness?->business_name ?? '—' }}</td>
                                        <td class="py-2">{{ $ref->created_at->format('M d, Y') }}</td>
                                        <td class="py-2">{{ $ref->referral_credits_awarded }}</td>
                                        <td class="py-2">
                                            <x-filament::badge :color="$ref->status === 'credited' ? 'success' : 'warning'">
                                                {{ ucfirst($ref->status) }}
                                            </x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            {{-- Credits earned by month --}}
            @php $byMonth = $this->getCreditsEarnedByMonth(); @endphp
            @if(!empty($byMonth))
                <x-filament::section>
                    <x-slot name="heading">Credits earned by month</x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-2 font-medium">Month</th>
                                    <th class="text-right py-2 font-medium">Credits</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byMonth as $month => $total)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2">{{ $month }}</td>
                                        <td class="py-2 text-right">{{ number_format($total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            {{-- Conversion history --}}
            <x-filament::section>
                <x-slot name="heading">Conversion history</x-slot>
                @php $history = $this->getConversionHistory(); @endphp
                @if($history->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No conversions yet. Use the buttons above to convert referral credits.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-2 font-medium">Date</th>
                                    <th class="text-left py-2 font-medium">Type</th>
                                    <th class="text-right py-2 font-medium">Amount</th>
                                    <th class="text-right py-2 font-medium">Balance after</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $tx)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="py-2">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                        <td class="py-2">{{ str_replace('_', ' ', ucfirst($tx->type)) }}</td>
                                        <td class="py-2 text-right {{ $tx->amount >= 0 ? 'text-success-600 dark:text-success-400' : 'text-gray-600 dark:text-gray-400' }}">
                                            {{ $tx->amount >= 0 ? '+' : '' }}{{ $tx->amount }}
                                        </td>
                                        <td class="py-2 text-right">{{ $tx->balance_after }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
