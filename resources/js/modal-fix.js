document.addEventListener('DOMContentLoaded', function() {
    // Ensure modals work on first click
    setTimeout(() => {
        if (window.Alpine) {
            window.Alpine.start();
        }
    }, 100);
});

// Re-initialize on Livewire navigation
document.addEventListener('livewire:navigated', function() {
    setTimeout(() => {
        if (window.Alpine) {
            window.Alpine.start();
        }
    }, 50);
});