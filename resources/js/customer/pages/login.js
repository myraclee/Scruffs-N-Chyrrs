import Toast from "/resources/js/utils/toast.js";

document.addEventListener("DOMContentLoaded", () => {
    // Redirect Warning Toast
    const pendingMessage = sessionStorage.getItem("auth_toast_message");
    if (pendingMessage) {
        setTimeout(() => {
            Toast.warning(pendingMessage);
        }, 300);
        sessionStorage.removeItem("auth_toast_message");
    }

    // 🚀 NEW: Clean SVG Icons
    const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    const toggleLogin = document.getElementById("toggle_login_password");
    const loginInput = document.getElementById("password");

    if (toggleLogin && loginInput) {
        // Set default icon
        toggleLogin.innerHTML = eyeOpen;

        toggleLogin.addEventListener("click", function () {
            const isPassword = loginInput.getAttribute("type") === "password";
            loginInput.setAttribute("type", isPassword ? "text" : "password");
            // Swap SVG: Show slashed eye if text is visible
            this.innerHTML = isPassword ? eyeClosed : eyeOpen;
        });
    }
});
