/**
 * Login Page Script
 * Handles incoming session messages (like redirect warnings)
 */

import Toast from "/resources/js/utils/toast.js";

document.addEventListener("DOMContentLoaded", () => {
    // Check if we have a message waiting for us from a redirect
    const pendingMessage = sessionStorage.getItem("auth_toast_message");

    if (pendingMessage) {
        // Wait a tiny fraction of a second for the page to look pretty before popping the toast
        setTimeout(() => {
            Toast.warning(pendingMessage);
        }, 300);

        // Delete the message so it doesn't pop up again if they refresh the page!
        sessionStorage.removeItem("auth_toast_message");
    }
});
