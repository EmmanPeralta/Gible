// Wait for the DOM to fully load before running script
document.addEventListener('DOMContentLoaded', () => {
    // Get the settings modal element
    const settingsModal = document.getElementById('settings-modal');

    // Function to open the modal with animation
    function openModal() {
        if (settingsModal) {
            settingsModal.style.display = 'flex';
            settingsModal.style.animation = 'scaleIn 0.1s ease-out forwards';
        }
    }

    // Function to close the modal with animation
    function closeModal() {
        if (settingsModal) {
            settingsModal.style.animation = 'scaleOut 0.1s ease-out forwards';
            setTimeout(() => {
                settingsModal.style.display = 'none';
                settingsModal.style.animation = '';
            }, 100);
        }
    }

    // Expose openModal and closeModal functions globally
    window.openModal = openModal;
    window.closeModal = closeModal;
});