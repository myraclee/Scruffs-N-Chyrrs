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

    // Track if form has been submitted
    let isFormSubmitted = false;

    if (!form) return;

    // --- 1. NAME VALIDATION & SANITIZATION ---
    function sanitizeNameInput(value) {
        return value.replace(/[^A-Za-z\s]/g, "").replace(/^\s+/, "");
    }

    function setupNameValidation(input) {
        if (!input) return;

        input.addEventListener("input", () => {
            const cleaned = sanitizeNameInput(input.value);
            if (input.value !== cleaned) {
                input.value = cleaned;
            }

            // Only clear errors if form has been submitted
            if (isFormSubmitted) {
                validateNameField(input);
            }
        });

        input.addEventListener("keypress", (e) => {
            const char = String.fromCharCode(e.which);
            if (!/[A-Za-z\s]/.test(char)) {
                e.preventDefault();
            }
        });
    }

    setupNameValidation(firstNameInput);
    setupNameValidation(lastNameInput);

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

    // --- 2. EMAIL VALIDATION & SANITIZATION ---
    function sanitizeEmail(value) {
        // Only allow lowercase letters, numbers, periods, and @.
        return value.replace(/[^a-z0-9.@]/gi, "").toLowerCase();
    }

    function validateEmailPolicy(email) {
        if (email === "") {
            return "The email field is required.";
        }

        if (!email.includes("@")) {
            return "The email field format is invalid.";
        }

        const parts = email.split("@");
        if (parts.length !== 2) {
            return "The email field format is invalid.";
        }

        const [prefix, domain] = parts;

        if (!prefix || !domain) {
            return "The email field format is invalid.";
        }

        if (domain !== "gmail.com" && domain !== "ust.edu.ph") {
            return "Only @gmail.com and @ust.edu.ph email domains are allowed.";
        }

        if (!/^[a-z0-9.]+$/.test(prefix)) {
            return "The email prefix may only contain lowercase letters (a-z), numbers (0-9), and periods (.).";
        }

        if (prefix.startsWith(".") || prefix.endsWith(".") || prefix.includes("..")) {
            return "The email prefix cannot start or end with a period and cannot contain consecutive periods.";
        }

        return null;
    }

    if (emailInput) {
        emailInput.addEventListener("input", (e) => {
            // Force lowercase and restrict characters
            const cleaned = sanitizeEmail(e.target.value);
            if (e.target.value !== cleaned) {
                e.target.value = cleaned;
            }

            // Only validate if form has been submitted
            if (isFormSubmitted) {
                validateEmailField();
            }
        });

        emailInput.addEventListener("keypress", (e) => {
            const char = String.fromCharCode(e.which);
            // Only allow letters, numbers, periods, and @.
            if (!/[a-zA-Z0-9@.]/.test(char)) {
                e.preventDefault();
            }
        });
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

    // --- 3. CONTACT NUMBER VALIDATION ---
    if (contactInput) {
        contactInput.addEventListener("input", () => {
            // Only allow numbers
            contactInput.value = contactInput.value.replace(/[^0-9]/g, "");

            // Only validate if form has been submitted
            if (isFormSubmitted) {
                validateContactField();
            }
        });
    }

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

    // --- 4. PASSWORD VALIDATION (Real-time hints) ---
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

    const updateHintsUI = (val) => {
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
    };

    if (passwordInput && reqBox) {
        passwordInput.addEventListener("focus", () => {
            reqBox.style.display = "block";
            updateHintsUI(passwordInput.value);
        });

        passwordInput.addEventListener("input", (e) => {
            updateHintsUI(e.target.value);
            checkMatch();
        });
    }

    function checkMatch() {
        if (!passwordInput || !confirmPasswordInput || !matchMsg) return;

        if (confirmPasswordInput.value === "") {
            matchMsg.textContent = "";
        } else if (passwordInput.value === confirmPasswordInput.value) {
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

    // --- 6. FORM SUBMISSION VALIDATION ---
    form.addEventListener("submit", (e) => {
        isFormSubmitted = true;
        let isValid = true;

        // Validate First Name
        if (!validateNameField(firstNameInput)) {
            isValid = false;
        }

        // Validate Last Name
        if (!validateNameField(lastNameInput)) {
            isValid = false;
        }

        // Validate Email
        if (!validateEmailField()) {
            isValid = false;
        }

        // Validate Contact Number
        if (!validateContactField()) {
            isValid = false;
        }

        // Validate Password
        const password = passwordInput.value;
        if (password === "") {
            isValid = false;
            showFieldError(passwordInput, "The password field is required.");
        } else {
            const passwordErrors = validatePassword(password);
            if (passwordErrors.length > 0) {
                isValid = false;
                showFieldError(
                    passwordInput,
                    "Password must have: " + passwordErrors.join(", "),
                );
            } else {
                clearFieldError(passwordInput);
            }
        }

        // Validate Confirm Password
        const confirmPassword = confirmPasswordInput.value;
        if (confirmPassword === "") {
            isValid = false;
            showFieldError(
                confirmPasswordInput,
                "Please confirm your password.",
            );
        } else if (password !== confirmPassword) {
            isValid = false;
            showFieldError(confirmPasswordInput, "Passwords do not match.");
        } else {
            clearFieldError(confirmPasswordInput);
        }

        if (!isValid) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // --- 7. ERROR UI HELPERS ---
    function showFieldError(field, message) {
        const container = field.closest(".input_group");
        if (!container) return;

        let errorElement = container.querySelector(".client_error");

        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = "block";
            errorElement.classList.add("show_error");
        }

        // Apply red border to the input
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

        // Reset to purple border
        field.style.setProperty("border-color", "#682c7a", "important");
        field.style.boxShadow = "none";
    }
});
