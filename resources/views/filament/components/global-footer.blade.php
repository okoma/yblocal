{{-- Global footer for all Filament panels (Admin, Business, Customer). --}}
<footer class="bg-white border-t border-gray-200 py-4 px-8 flex flex-col md:flex-row items-center justify-between text-sm text-gray-500">
  <!-- Left Side -->
  <div class="flex items-center gap-2 mb-2 md:mb-0">
    <span>&copy; <?php echo date('Y'); ?> YellowBooks.</span>
    <span class="hidden md:inline">All rights reserved.</span>
  </div>
  
  <!-- Right Side -->
  <div class="flex items-center gap-4">
    <a href="https://yellowbooks.ng/terms" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-600 transition-colors">
      Terms
    </a>
    <a href="https://yellowbooks.ng/privacy" target="_blank" rel="noopener noreferrer" class="hover:text-indigo-600 transition-colors">
      Privacy
    </a>
    <a href="mailto:support@yellowbooks.ng" class="hover:text-indigo-600 flex items-center gap-1 transition-colors">
      <i data-lucide="mail" class="w-4 h-4"></i> Support
    </a>
  </div>
</footer>
