{{-- Global footer for all Filament panels (Admin, Business, Customer). --}}
<footer class="py-4 px-5 flex flex-col md:flex-row items-center justify-between text-sm text-gray-500 dark:text-gray-400">
  <!-- Left Side -->
  <div class="flex items-center gap-2 mb-2 md:mb-0">
    <span>&copy; <?php echo date('Y'); ?> YBLocal.</span>
    <span class="hidden md:inline">All rights reserved.</span>
  </div>
  
  <!-- Right Side -->
  <div class="flex items-center gap-4">
    <a href="https://yellowbooks.ng/terms" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1">
      <x-filament::icon icon="heroicon-o-document-text" class="w-4 h-4" />
      Terms
    </a>
    <a href="https://yellowbooks.ng/privacy" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1">
      <x-filament::icon icon="heroicon-o-shield-check" class="w-4 h-4" />
      Privacy
    </a>
    <a href="mailto:support@yellowbooks.ng" class="hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-1 transition-colors">
      <x-filament::icon icon="heroicon-o-envelope" class="w-4 h-4" />
      Support
    </a>
  </div>
</footer>