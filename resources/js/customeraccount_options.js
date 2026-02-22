document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.querySelector('.account_trigger');
    const popup = document.getElementById('accountPopup');
    const logoutButton = document.getElementById('logoutButton');
    const logoutForm = document.getElementById('logoutForm');
    const viewAccountButton = document.getElementById('viewAccountButton');

    if (!trigger || !popup) return;

    // Toggle popup when clicking account trigger
    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        popup.classList.toggle('show');
    });

    // Close popup when clicking elsewhere
    document.addEventListener('click', () => {
        popup.classList.remove('show');
    });

    // Handle view account button click
    if (viewAccountButton) {
        viewAccountButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = '/account';
        });
    }

    // Handle logout button click
    if (logoutButton && logoutForm) {
        logoutButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            logoutForm.submit();
        });
    }
});

