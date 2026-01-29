<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Referral link + copy & share --}}
        <x-filament::section>
            <x-slot name="heading">Your referral link</x-slot>
            <x-slot name="description">Share this link with businesses. When they sign up and pay, you earn 10% commission.</x-slot>
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

        {{-- Summary --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::section class="text-center">
                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">₦{{ number_format($this->getWallet()?->balance ?? 0, 2) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Available balance</div>
            </x-filament::section>
            <x-filament::section class="text-center">
                <div class="text-2xl font-bold">{{ $this->getReferredBusinesses()->count() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Businesses referred</div>
            </x-filament::section>
            <x-filament::section class="text-center">
                <div class="text-2xl font-bold text-success-600 dark:text-success-400">₦{{ number_format($this->getTotalCommissionEarned(), 2) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total commission earned</div>
            </x-filament::section>
            <x-filament::section class="text-center">
                <div class="text-2xl font-bold">{{ $this->getWithdrawals()->where('status', 'pending')->count() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pending withdrawals</div>
            </x-filament::section>
        </div>

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
                                <th class="text-left py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($referrals as $ref)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2">{{ $ref->referredBusiness?->business_name ?? '—' }}</td>
                                    <td class="py-2">{{ $ref->created_at->format('M d, Y') }}</td>
                                    <td class="py-2">
                                        <x-filament::badge :color="$ref->status === 'qualified' ? 'success' : 'warning'">
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

        {{-- Earnings trend --}}
        @php $byMonth = $this->getCommissionEarningsByMonth(); @endphp
        @if(!empty($byMonth))
            <x-filament::section>
                <x-slot name="heading">Earnings by month</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 font-medium">Month</th>
                                <th class="text-right py-2 font-medium">Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($byMonth as $month => $total)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2">{{ $month }}</td>
                                    <td class="py-2 text-right">₦{{ number_format($total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Withdrawals --}}
        <x-filament::section>
            <x-slot name="heading">Withdrawal requests</x-slot>
            @php $withdrawals = $this->getWithdrawals(); @endphp
            @if($withdrawals->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">No withdrawal requests yet. Use "Request Withdrawal" above when you have balance.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 font-medium">Amount</th>
                                <th class="text-left py-2 font-medium">Bank</th>
                                <th class="text-left py-2 font-medium">Date</th>
                                <th class="text-left py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($withdrawals as $w)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2">₦{{ number_format($w->amount, 2) }}</td>
                                    <td class="py-2">{{ $w->bank_name }} · {{ $w->account_name }}</td>
                                    <td class="py-2">{{ $w->created_at->format('M d, Y') }}</td>
                                    <td class="py-2">
                                        <x-filament::badge :color="match($w->status) { 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', default => 'gray' }">
                                            {{ ucfirst($w->status) }}
                                        </x-filament::badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
