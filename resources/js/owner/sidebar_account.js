document.addEventListener("DOMContentLoaded", () => {
    // Sidenav Elements
    const menuToggle = document.getElementById("menuToggle");
    const closeSidenav = document.getElementById("closeSidenav");
    const body = document.body;

    // User Popup Elements
    const userToggle = document.getElementById("userToggle");
    const section = document.getElementById("userSection");
    const logoutButton = document.getElementById("sidenavLogoutButton");
    const logoutForm = document.getElementById("ownerLogoutForm");

    // --- 1. Sidenav Open/Close Logic ---
    function openNav() {
        body.classList.remove("sidenav-closed");
    }

    function closeNav() {
        body.classList.add("sidenav-closed");
    }

    // Automatically close the sidebar on load if the screen is small!
    if (window.innerWidth <= 768) {
        closeNav();
    }

    // Attach click events
    if (menuToggle) menuToggle.addEventListener("click", openNav);
    if (closeSidenav) closeSidenav.addEventListener("click", closeNav);

    // --- 2. User Profile Popup Logic ---
    if (userToggle && section) {
        userToggle.addEventListener("click", (e) => {
            e.stopPropagation();
            section.classList.toggle("active");
        });
    }

    // --- 3. Logout Logic ---
    if (logoutButton && logoutForm) {
        logoutButton.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            logoutForm.submit();
        });
    }
});
