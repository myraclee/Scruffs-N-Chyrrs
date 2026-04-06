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

    function clearAppClientAuthArtifacts() {
        try {
            sessionStorage.removeItem("auth_toast_message");
        } catch (error) {
            console.warn("Failed to clear session storage auth artifact", error);
        }

        try {
            const keysToRemove = [];
            for (let index = 0; index < localStorage.length; index += 1) {
                const key = localStorage.key(index);
                if (key && key.startsWith("form_state_")) {
                    keysToRemove.push(key);
                }
            }

            keysToRemove.forEach((key) => localStorage.removeItem(key));
        } catch (error) {
            console.warn("Failed to clear local storage auth artifacts", error);
        }
    }

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
            clearAppClientAuthArtifacts();
            logoutForm.submit();
        });
    }

    if (logoutForm) {
        logoutForm.addEventListener("submit", () => {
            clearAppClientAuthArtifacts();
        });
    }
});
