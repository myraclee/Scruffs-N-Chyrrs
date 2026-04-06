document.addEventListener("DOMContentLoaded", () => {
    const trigger = document.querySelector(".account_trigger");
    const popup = document.getElementById("accountPopup");
    const logoutButton = document.getElementById("logoutButton");
    const logoutForm = document.getElementById("logoutForm");
    const viewAccountButton = document.getElementById("viewAccountButton");
    const viewDashboardButton = document.getElementById("viewDashboardButton");

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

    if (!trigger || !popup) return;

    // Toggle popup when clicking account trigger
    trigger.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        popup.classList.toggle("show");
    });

    // Close popup when clicking elsewhere
    document.addEventListener("click", () => {
        popup.classList.remove("show");
    });

    // Handle view dashboard button click
    if (viewDashboardButton) {
        viewDashboardButton.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = "/owner/pages/dashboard";
        });
    }

    // Handle view account button click
    if (viewAccountButton) {
        viewAccountButton.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = "/account";
        });
    }

    // Handle logout button click
    if (logoutButton && logoutForm) {
        logoutButton.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            clearAppClientAuthArtifacts();
            logoutForm.submit();
        });

        logoutForm.addEventListener("submit", () => {
            clearAppClientAuthArtifacts();
        });
    }
});
