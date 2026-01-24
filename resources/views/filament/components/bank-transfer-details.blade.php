<div class="space-y-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
    <div class="space-y-1">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Number</div>
        <div class="text-2xl font-bold font-mono tracking-wider text-gray-900 dark:text-gray-100">
            {{ $accountNumber }}
        </div>
    </div>
    
    <div class="space-y-1">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Name</div>
        <div class="text-base font-semibold text-gray-900 dark:text-gray-100">
            {{ $accountName }}
        </div>
    </div>
    
    <div class="space-y-1">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Bank</div>
        <div class="text-base font-semibold text-gray-900 dark:text-gray-100">
            {{ $bankName }}
        </div>
    </div>
</div>