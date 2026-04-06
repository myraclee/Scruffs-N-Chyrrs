document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".change_password_form");
    const passwordInput = document.getElementById("current_password");
    const confirmInput = document.getElementById("password_confirmation");
    const matchMsg = document.getElementById("match_message");

    if (!form) return;

    // --- Eye Icon SVGs ---
    const eyeOpen = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const eyeClosed = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    function setupEye(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        if (!toggle || !input) return;

        toggle.innerHTML = eyeOpen;
        toggle.addEventListener("click", () => {
            const isPassword = input.type === "password";
            input.type = isPassword ? "text" : "password";
            toggle.innerHTML = isPassword ? eyeClosed : eyeOpen;
        });
    }

    setupEye("toggle_current_password", "current_password");
    setupEye("toggle_confirm_password", "password_confirmation");

    // --- Live Match Logic ---
    function checkMatch() {
        if (!matchMsg) return;
        const val = passwordInput.value;
        const conf = confirmInput.value;

        if (conf === "") {
            matchMsg.textContent = "";
            return;
        }

        if (val === conf) {
            matchMsg.textContent = "✓ Passwords match!";
            matchMsg.style.color = "#4caf50";
        } else {
            matchMsg.textContent = "✗ Passwords do not match.";
            matchMsg.style.color = "#d32f2f";
        }
    }

    passwordInput.addEventListener("input", checkMatch);
    confirmInput.addEventListener("input", checkMatch);

    // --- Error Handling Helpers ---
    function showFieldError(input, message) {
        const group = input.closest(".form_group");
        let el = group.querySelector(".js_error_message");
        if (!el) {
            el = document.createElement("span");
            el.className = "error_message js_error_message";
            const wrapper = group.querySelector(".password_wrapper");
            wrapper.insertAdjacentElement("afterend", el);
        }
        el.textContent = message;
        input.classList.add("input_error");
    }

    function clearFieldError(input) {
        const group = input.closest(".form_group");
        const el = group.querySelector(".js_error_message");
        if (el) el.textContent = "";
        input.classList.remove("input_error");
    }

    form.querySelectorAll(".form_input").forEach((input) => {
        input.addEventListener("input", () => clearFieldError(input));
    });

    // --- Final Submission Validation ---
    form.addEventListener("submit", (e) => {
        let isValid = true;
        const pass = passwordInput.value.trim();
        const conf = confirmInput.value.trim();

        if (!pass) {
            showFieldError(passwordInput, "Password is required.");
            isValid = false;
        }

        if (!conf) {
            showFieldError(confirmInput, "Confirmation is required.");
            isValid = false;
        } else if (pass !== conf) {
            showFieldError(confirmInput, "Passwords do not match.");
            isValid = false;
        }

        if (!isValid) e.preventDefault();
    });
});
