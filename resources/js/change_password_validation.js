document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".change_password_form");
    const newPasswordInput = document.getElementById("new_password");
    const confirmPasswordInput = document.getElementById(
        "new_password_confirmation",
    );
    const reqBox = document.getElementById("password_requirements");
    const matchMsg = document.getElementById("match_message");

    if (!form) return;

    const reqs = {
        length: {
            regex: /.{8,}/,
            element: document.getElementById("req_length"),
        },
        upper: {
            regex: /[A-Z]/,
            element: document.getElementById("req_upper"),
        },
        lower: {
            regex: /[a-z]/,
            element: document.getElementById("req_lower"),
        },
        number: {
            regex: /[0-9]/,
            element: document.getElementById("req_number"),
        },
        symbol: {
            regex: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/,
            element: document.getElementById("req_symbol"),
        },
    };

    // ── Eye icons ──────────────────────────────────────────────
    const eyeOpen = `<svg xmlns="[w3.org](http://www.w3.org/2000/svg)" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const eyeClosed = `<svg xmlns="[w3.org](http://www.w3.org/2000/svg)" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

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
    setupEye("toggle_new_password", "new_password");
    setupEye("toggle_confirm_password", "new_password_confirmation");

    // ── Password requirement hints ─────────────────────────────
    function updateHintsUI(val) {
        for (const key in reqs) {
            const { regex, element } = reqs[key];
            if (!element) continue;
            const met = regex.test(val);
            const text = element.textContent.substring(2); // strip leading ✓/✗ + space
            element.textContent = (met ? "✓ " : "✗ ") + text;
            element.style.color = met ? "#4caf50" : "#d32f2f";
        }
    }

    if (newPasswordInput && reqBox) {
        newPasswordInput.addEventListener("focus", () => {
            reqBox.style.display = "block";
            updateHintsUI(newPasswordInput.value);
        });
        newPasswordInput.addEventListener("input", (e) => {
            updateHintsUI(e.target.value);
            checkMatch();
        });
    }

    // ── Live match indicator (the #match_message span) ─────────
    function checkMatch() {
        if (!matchMsg) return;
        const newVal = newPasswordInput?.value ?? "";
        const confirmVal = confirmPasswordInput?.value ?? "";

        if (confirmVal === "") {
            matchMsg.textContent = "";
            return;
        }
        if (newVal === confirmVal) {
            matchMsg.textContent = "✓ Passwords match!";
            matchMsg.style.color = "#4caf50";
        } else {
            matchMsg.textContent = "✗ Passwords do not match.";
            matchMsg.style.color = "#d32f2f";
        }
    }

    confirmPasswordInput?.addEventListener("input", checkMatch);

    // ── Error helpers ──────────────────────────────────────────
    // Errors live in .form_group, never inside .password_wrapper
    function showFieldError(input, message) {
        const group = input.closest(".form_group");
        if (!group) return;

        // Reuse existing JS-created error element or create one
        let el = group.querySelector(".js_error_message");
        if (!el) {
            el = document.createElement("span");
            el.className = "error_message js_error_message";
            // Insert right after the .password_wrapper div
            const wrapper = group.querySelector(".password_wrapper");
            wrapper.insertAdjacentElement("afterend", el);
        }
        el.textContent = message;
        input.classList.add("input_error");
    }

    function clearFieldError(input) {
        const group = input.closest(".form_group");
        if (!group) return;
        const el = group.querySelector(".js_error_message");
        if (el) el.textContent = "";
        input.classList.remove("input_error");
    }

    // Clear JS errors as the user types
    form.querySelectorAll(".form_input").forEach((input) => {
        input.addEventListener("input", () => clearFieldError(input));
    });

    // ── Submit validation ──────────────────────────────────────
    form.addEventListener("submit", (e) => {
        const currentPassword = document
            .getElementById("current_password")
            .value.trim();
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        // Clear all previous JS errors first
        clearFieldError(document.getElementById("current_password"));
        clearFieldError(newPasswordInput);
        clearFieldError(confirmPasswordInput);
        // Also clear the live match message so it doesn't duplicate
        if (matchMsg) matchMsg.textContent = "";

        let isValid = true;

        if (!currentPassword) {
            showFieldError(
                document.getElementById("current_password"),
                "Current password is required.",
            );
            isValid = false;
        }

        const newPasswordErrors = validatePassword(newPassword);
        if (!newPassword) {
            showFieldError(newPasswordInput, "New password is required.");
            isValid = false;
        } else if (newPasswordErrors.length > 0) {
            showFieldError(
                newPasswordInput,
                "Password must meet requirements.",
            );
            isValid = false;
        }

        if (!confirmPassword) {
            showFieldError(
                confirmPasswordInput,
                "Please confirm your new password.",
            );
            isValid = false;
        } else if (newPassword !== confirmPassword) {
            showFieldError(confirmPasswordInput, "Passwords do not match.");
            isValid = false;
        }

        if (!isValid) e.preventDefault();
    });

    function validatePassword(password) {
        const errors = [];
        if (password.length < 8) errors.push("At least 8 characters");
        if (!/[A-Z]/.test(password)) errors.push("At least 1 uppercase letter");
        if (!/[a-z]/.test(password)) errors.push("At least 1 lowercase letter");
        if (!/[0-9]/.test(password)) errors.push("At least 1 number");
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password))
            errors.push("At least 1 symbol");
        return errors;
    }
});
