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

    const updateHintsUI = (val) => {
        for (const key in reqs) {
            if (reqs[key].element) {
                if (reqs[key].regex.test(val)) {
                    reqs[key].element.innerHTML =
                        "✓ " + reqs[key].element.innerText.substring(2);
                    reqs[key].element.style.color = "#4caf50";
                } else {
                    reqs[key].element.innerHTML =
                        "✗ " + reqs[key].element.innerText.substring(2);
                    reqs[key].element.style.color = "#d32f2f";
                }
            }
        }
    };

    if (newPasswordInput && reqBox) {
        newPasswordInput.addEventListener("focus", () => {
            reqBox.style.display = "block";
            updateHintsUI(newPasswordInput.value); // 🚀 Triggers RED instantly on click
        });
        newPasswordInput.addEventListener("input", (e) => {
            updateHintsUI(e.target.value);
            checkMatch();
        });
    }

    function checkMatch() {
        if (!newPasswordInput || !confirmPasswordInput || !matchMsg) return;
        if (confirmPasswordInput.value === "") {
            matchMsg.textContent = "";
        } else if (newPasswordInput.value === confirmPasswordInput.value) {
            matchMsg.textContent = "✓ Passwords match!";
            matchMsg.style.color = "#4caf50";
        } else {
            matchMsg.textContent = "✗ Passwords do not match.";
            matchMsg.style.color = "#d32f2f";
        }
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener("input", checkMatch);
    }

    // 🚀 NEW: Clean SVG Icons
    const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    function setupEye(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        if (toggle && input) {
            toggle.innerHTML = eyeOpen; // Set default
            toggle.addEventListener("click", function () {
                const isPassword = input.getAttribute("type") === "password";
                input.setAttribute("type", isPassword ? "text" : "password");
                this.innerHTML = isPassword ? eyeClosed : eyeOpen;
            });
        }
    }

    setupEye("toggle_current_password", "current_password");
    setupEye("toggle_new_password", "new_password");
    setupEye("toggle_confirm_password", "new_password_confirmation");

    form.addEventListener("submit", (e) => {
        const currentPassword = form.querySelector("#current_password").value;
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        let isValid = true;
        const newPasswordErrors = validatePassword(newPassword);

        if (!currentPassword) {
            isValid = false;
            showFieldError(
                form.querySelector("#current_password"),
                "Current password is required",
            );
        }
        if (newPasswordErrors.length > 0) {
            isValid = false;
            showFieldError(
                newPasswordInput,
                "Password must meet requirements.",
            );
        }
        if (newPassword !== confirmPassword) {
            isValid = false;
            showFieldError(confirmPasswordInput, "Passwords do not match");
        }
        if (!isValid) e.preventDefault();
    });

    function showFieldError(field, message) {
        let errorElement = field.parentElement.querySelector(".error_message");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.className = "error_message";
            field.parentElement.appendChild(errorElement);
        }
        errorElement.textContent = message;
        field.classList.add("input_error");
    }
});
