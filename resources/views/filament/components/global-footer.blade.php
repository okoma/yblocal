{{-- Global footer for all Filament panels (Admin, Business, Customer). --}}
<footer class="tw-bg-white tw-border-t tw-border-gray-200 tw-py-4 tw-px-8 tw-flex tw-flex-col md:tw-flex-row tw-items-center tw-justify-between tw-text-sm tw-text-gray-500">
  <!-- Left Side -->
  <div class="tw-flex tw-items-center tw-gap-2 tw-mb-2 md:tw-mb-0">
    <span>&copy; <?php echo date('Y'); ?> YellowBooks.</span>
    <span class="tw-hidden md:tw-inline">All rights reserved.</span>
  </div>
  
  <!-- Right Side -->
  <div class="tw-flex tw-items-center tw-gap-4">
    <a href="https://yellowbooks.ng/terms" target="_blank" rel="noopener noreferrer" class="hover:tw-text-indigo-600 tw-transition-colors">
      Terms
    </a>
    <a href="https://yellowbooks.ng/privacy" target="_blank" rel="noopener noreferrer" class="hover:tw-text-indigo-600 tw-transition-colors">
      Privacy
    </a>
    <a href="mailto:support@yellowbooks.ng" class="hover:tw-text-indigo-600 tw-flex tw-items-center tw-gap-1 tw-transition-colors">
      <i data-lucide="mail" class="tw-w-4 tw-h-4"></i> Support
    </a>
  </div>
</footer>
