document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("custom_signup_form");
    const emailInput = document.getElementById("email");
    const contactInput = document.getElementById("contact_number");
    const passwordInput = document.getElementById("password");
    const firstNameInput = document.getElementById("first_name");
    const lastNameInput = document.getElementById("last_name");
    const confirmPasswordInput = document.getElementById(
        "password_confirmation",
    );
    const reqBox = document.getElementById("password_requirements");
    const matchMsg = document.getElementById("match_message");

    let isFormSubmitted = false;

    if (!form) return;

    // --- HELPER FUNCTIONS FOR ERROR UI ---
    function showFieldError(field, message) {
        const container = field.closest(".input_group");
        if (!container) return;
        let errorElement = container.querySelector(".client_error");
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = "block";
            errorElement.classList.add("show_error");
        }
        field.style.setProperty("border-color", "#d93025", "important");
        field.style.boxShadow = "0 0 5px rgba(217, 48, 37, 0.3)";
    }

    function clearFieldError(field) {
        const container = field.closest(".input_group");
        if (!container) return;
        const errorElement = container.querySelector(".client_error");
        if (errorElement) {
            errorElement.style.display = "none";
            errorElement.textContent = "";
            errorElement.classList.remove("show_error");
        }
        field.style.setProperty("border-color", "#682c7a", "important");
        field.style.boxShadow = "none";
    }

    // --- 1. NAME VALIDATION ---
    function sanitizeNameInput(value) {
        return value.replace(/[^A-Za-z\s]/g, "").replace(/^\s+/, "");
    }

    function validateNameField(input) {
        const value = input.value.trim();
        const fieldName =
            input.id === "first_name" ? "first name" : "last name";
        if (value === "") {
            showFieldError(input, `The ${fieldName} field is required.`);
            return false;
        } else if (!/^[A-Za-z\s]+$/.test(value)) {
            showFieldError(input, "Only letters and spaces are allowed.");
            return false;
        } else {
            clearFieldError(input);
            return true;
        }
    }

    function setupNameValidation(input) {
        if (!input) return;
        input.addEventListener("input", () => {
            const cleaned = sanitizeNameInput(input.value);
            if (input.value !== cleaned) input.value = cleaned;
            if (isFormSubmitted || input.value.length > 0) {
                validateNameField(input);
            }
        });
        input.addEventListener("keypress", (e) => {
            if (!/[A-Za-z\s]/.test(String.fromCharCode(e.which)))
                e.preventDefault();
        });
    }
    setupNameValidation(firstNameInput);
    setupNameValidation(lastNameInput);

    // --- 2. EMAIL VALIDATION ---
    function sanitizeEmail(value) {
        return value.replace(/[^a-z0-9.@]/gi, "").toLowerCase();
    }

    function validateEmailPolicy(email) {
        if (email === "") return "The email field is required.";
        if (!email.includes("@")) return "The email field format is invalid.";
        const parts = email.split("@");
        if (parts.length !== 2) return "The email field format is invalid.";
        const [prefix, domain] = parts;
        if (!prefix || !domain) return "The email field format is invalid.";
        if (domain !== "gmail.com" && domain !== "ust.edu.ph") {
            return "Only @gmail.com and @ust.edu.ph email domains are allowed.";
        }
        if (!/^[a-z0-9.]+$/.test(prefix)) {
            return "Email prefix may only contain lowercase letters, numbers, and periods.";
        }
        if (
            prefix.startsWith(".") ||
            prefix.endsWith(".") ||
            prefix.includes("..")
        ) {
            return "Email prefix cannot start/end with a period or contain consecutive periods.";
        }
        return null;
    }

    function validateEmailField() {
        const email = emailInput.value.trim();
        const validationError = validateEmailPolicy(email);
        if (validationError) {
            showFieldError(emailInput, validationError);
            return false;
        }
        clearFieldError(emailInput);
        return true;
    }

    if (emailInput) {
        emailInput.addEventListener("input", (e) => {
            const cleaned = sanitizeEmail(e.target.value);
            if (e.target.value !== cleaned) e.target.value = cleaned;
            if (isFormSubmitted || emailInput.value.length > 0) {
                validateEmailField();
            }
        });
    }

    // --- 3. CONTACT NUMBER VALIDATION ---
    function validateContactField() {
        const phone = contactInput.value.trim();
        if (phone === "") {
            showFieldError(
                contactInput,
                "The contact number field is required.",
            );
            return false;
        } else if (!/^9[0-9]{9}$/.test(phone)) {
            showFieldError(
                contactInput,
                "The contact number field format is invalid.",
            );
            return false;
        } else {
            clearFieldError(contactInput);
            return true;
        }
    }

    if (contactInput) {
        contactInput.addEventListener("input", () => {
            contactInput.value = contactInput.value.replace(/[^0-9]/g, "");
            if (isFormSubmitted || contactInput.value.length > 0) {
                validateContactField();
            }
        });
    }

    // --- 4. PASSWORD VALIDATION (LIVE ERROR CLEARING) ---
    function isPasswordValid(password) {
        return (
            password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /[0-9]/.test(password) &&
            /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        );
    }

    function validatePasswordComplexity() {
        const password = passwordInput.value;
        if (password === "") {
            if (isFormSubmitted)
                showFieldError(
                    passwordInput,
                    "The password field is required.",
                );
            return false;
        }
        if (!isPasswordValid(password)) {
            if (isFormSubmitted || password.length > 0) {
                showFieldError(
                    passwordInput,
                    "Password must meet all requirements below.",
                );
            }
            return false;
        }
        clearFieldError(passwordInput);
        return true;
    }

    // Password hints UI
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

    function updateHintsUI(val) {
        for (const key in reqs) {
            if (reqs[key].element) {
                const originalText = reqs[key].element.innerText.replace(
                    /^[✓✗]\s/,
                    "",
                );
                if (reqs[key].regex.test(val)) {
                    reqs[key].element.innerHTML = "✓ " + originalText;
                    reqs[key].element.style.color = "#4caf50";
                } else {
                    reqs[key].element.innerHTML = "✗ " + originalText;
                    reqs[key].element.style.color = "#d32f2f";
                }
            }
        }
    }

    // Live password validation - THIS MAKES ERROR DISAPPEAR WHEN REQUIREMENTS ARE MET
    if (passwordInput && reqBox) {
        passwordInput.addEventListener("focus", () => {
            reqBox.style.display = "block";
            updateHintsUI(passwordInput.value);
        });
        passwordInput.addEventListener("input", (e) => {
            updateHintsUI(e.target.value);
            validatePasswordComplexity(); // Clears error immediately when valid
            checkMatch();
        });
    }

    // Confirm password match with live clearing
    function checkMatch() {
        if (!passwordInput || !confirmPasswordInput || !matchMsg) return;
        const password = passwordInput.value;
        const confirm = confirmPasswordInput.value;
        if (confirm === "") {
            matchMsg.textContent = "";
            if (isFormSubmitted)
                showFieldError(
                    confirmPasswordInput,
                    "Please confirm your password.",
                );
            else clearFieldError(confirmPasswordInput);
            return false;
        }
        if (password === confirm) {
            matchMsg.textContent = "✓ Passwords match!";
            matchMsg.style.color = "#4caf50";
            clearFieldError(confirmPasswordInput);
            return true;
        } else {
            matchMsg.style.color = "#d32f2f";
            if (isFormSubmitted || confirm.length > 0) {
                showFieldError(confirmPasswordInput, "Passwords do not match.");
            }
            return false;
        }
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener("input", () => {
            checkMatch();
        });
    }

    // --- 5. PASSWORD VISIBILITY TOGGLE ---
    const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#682c7a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#682c7a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    function setupEye(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        if (toggle && input) {
            toggle.innerHTML = eyeOpen;
            toggle.addEventListener("click", function () {
                const isPassword = input.getAttribute("type") === "password";
                input.setAttribute("type", isPassword ? "text" : "password");
                this.innerHTML = isPassword ? eyeClosed : eyeOpen;
            });
        }
    }
    setupEye("toggle_signup_password", "password");
    setupEye("toggle_signup_confirm", "password_confirmation");

    // --- 6. FORM SUBMISSION ---
    form.addEventListener("submit", (e) => {
        isFormSubmitted = true;
        let isValid = true;

        if (!validateNameField(firstNameInput)) isValid = false;
        if (!validateNameField(lastNameInput)) isValid = false;
        if (!validateEmailField()) isValid = false;
        if (!validateContactField()) isValid = false;

        // Password validation
        if (passwordInput.value === "") {
            showFieldError(passwordInput, "The password field is required.");
            isValid = false;
        } else if (!isPasswordValid(passwordInput.value)) {
            showFieldError(
                passwordInput,
                "Password must meet all requirements below.",
            );
            isValid = false;
        } else {
            clearFieldError(passwordInput);
        }

        // Confirm password validation
        if (confirmPasswordInput.value === "") {
            showFieldError(
                confirmPasswordInput,
                "Please confirm your password.",
            );
            isValid = false;
        } else if (passwordInput.value !== confirmPasswordInput.value) {
            showFieldError(confirmPasswordInput, "Passwords do not match.");
            isValid = false;
        } else {
            clearFieldError(confirmPasswordInput);
        }

        if (!isValid) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });
});
