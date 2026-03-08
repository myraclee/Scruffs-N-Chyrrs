document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidenav = document.querySelector('.sidenav');
    const userToggle = document.getElementById('userToggle');
    const section = document.getElementById('userSection');
    const logoutButton = document.getElementById('sidenavLogoutButton');
    const logoutForm = document.getElementById('ownerLogoutForm');

    // Mobile Menu Toggle
    if (menuToggle && sidenav) {
        menuToggle.addEventListener('click', () => {
            sidenav.classList.toggle('open');
        });
    }

    // Smooth User Profile Toggle
    if (userToggle && section) {
        userToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            section.classList.toggle('active'); // CSS handles all the animation!
        });
    }

    // Handle Logout
    if (logoutButton && logoutForm) {
        logoutButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            logoutForm.submit();
        });
    }
});