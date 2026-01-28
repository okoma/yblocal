{{-- Global search will be moved here via JavaScript --}}
<div id="global-search-topbar-container" class="fi-topbar-global-search-wrapper"></div>

<script>
(function() {
    function moveGlobalSearch() {
        const sidebarSearch = document.querySelector('.fi-sidebar .fi-global-search');
        const topbarContainer = document.getElementById('global-search-topbar-container');
        
        if (sidebarSearch && topbarContainer && !topbarContainer.querySelector('.fi-global-search')) {
            topbarContainer.appendChild(sidebarSearch);
            sidebarSearch.style.display = 'flex';
            sidebarSearch.style.width = '100%';
        }
    }
    
    // Try immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', moveGlobalSearch);
    } else {
        moveGlobalSearch();
    }
    
    // Also listen for Livewire updates
    document.addEventListener('livewire:load', moveGlobalSearch);
    document.addEventListener('livewire:update', moveGlobalSearch);
})();
</script>
