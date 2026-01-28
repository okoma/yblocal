{{-- Global search will be moved here via JavaScript --}}
<div id="global-search-topbar-container" class="fi-topbar-global-search-wrapper"></div>

<script>
(function() {
    let moved = false;
    
    function moveGlobalSearch() {
        if (moved) return;
        
        const sidebarSearch = document.querySelector('.fi-sidebar .fi-global-search');
        const topbarContainer = document.getElementById('global-search-topbar-container');
        
        if (sidebarSearch && topbarContainer && !topbarContainer.querySelector('.fi-global-search')) {
            topbarContainer.appendChild(sidebarSearch);
            sidebarSearch.style.display = 'flex';
            sidebarSearch.style.width = '100%';
            sidebarSearch.style.flex = '1';
            sidebarSearch.style.maxWidth = '32rem';
            moved = true;
        }
    }
    
    // Try multiple times with delays to catch Livewire rendering
    function tryMove() {
        moveGlobalSearch();
        if (!moved) {
            setTimeout(tryMove, 100);
        }
    }
    
    // Try immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            tryMove();
            // Keep trying for up to 3 seconds
            setTimeout(() => { if (!moved) tryMove(); }, 500);
            setTimeout(() => { if (!moved) tryMove(); }, 1000);
            setTimeout(() => { if (!moved) tryMove(); }, 2000);
        });
    } else {
        tryMove();
        setTimeout(() => { if (!moved) tryMove(); }, 500);
        setTimeout(() => { if (!moved) tryMove(); }, 1000);
    }
    
    // Also listen for Livewire updates
    if (window.Livewire) {
        Livewire.hook('morph.updated', () => {
            moved = false;
            tryMove();
        });
    }
    
    document.addEventListener('livewire:load', function() {
        moved = false;
        tryMove();
    });
    
    document.addEventListener('livewire:update', function() {
        moved = false;
        setTimeout(tryMove, 100);
    });
})();
</script>
