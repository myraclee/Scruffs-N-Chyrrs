document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("custom_signup_form");
    const emailInput = document.getElementById("email");
    const contactInput = document.getElementById("contact_number");
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById(
        "password_confirmation",
    );
    const reqBox = document.getElementById("password_requirements");
    const matchMsg = document.getElementById("match_message");

    if (!form) return;

    function validateEmail(email) {
        return /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/.test(email);
    }
    function validatePhone(phone) {
        return /^9[0-9]{9}$/.test(phone);
    }

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

    if (passwordInput && reqBox) {
        passwordInput.addEventListener("focus", () => {
            reqBox.style.display = "block";
            updateHintsUI(passwordInput.value); // 🚀 Triggers RED instantly on click
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
    setupEye("toggle_signup_password", "password");
    setupEye("toggle_signup_confirm", "password_confirmation");

    // Form submission validation (Preserved perfectly!)
    form.addEventListener("submit", (e) => {
        let isValid = true;
        const email = emailInput.value.trim();
        if (email === "") {
            isValid = false;
            showFieldError(emailInput, "Please enter a valid email address.");
        } else if (!email.includes("@")) {
            isValid = false;
            showFieldError(emailInput, "Email must contain an @ symbol.");
        } else if (!validateEmail(email)) {
            isValid = false;
            showFieldError(emailInput, "Please enter a valid email address.");
        } else {
            clearFieldError(emailInput);
        }

        const phone = contactInput.value.trim();
        if (phone === "") {
            isValid = false;
            showFieldError(
                contactInput,
                "Please enter a 10-digit number starting with 9.",
            );
        } else if (!validatePhone(phone)) {
            isValid = false;
            showFieldError(
                contactInput,
                "Phone number must be 10 digits and start with 9.",
            );
        } else {
            clearFieldError(contactInput);
        }

        const password = passwordInput.value;
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

        const confirmPassword = confirmPasswordInput.value;
        if (password !== confirmPassword) {
            isValid = false;
            showFieldError(confirmPasswordInput, "Passwords do not match.");
        } else {
            clearFieldError(confirmPasswordInput);
        }

        if (!isValid) e.preventDefault();
    });

    if (emailInput) {
        emailInput.addEventListener("input", () => {
            const email = emailInput.value.trim();
            if (email === "")
                showFieldError(
                    emailInput,
                    "Please enter a valid email address.",
                );
            else if (!email.includes("@"))
                showFieldError(emailInput, "Email must contain an @ symbol.");
            else if (!validateEmail(email))
                showFieldError(
                    emailInput,
                    "Please enter a valid email address.",
                );
            else clearFieldError(emailInput);
        });
    }

    if (contactInput) {
        contactInput.addEventListener("input", () => {
            const phone = contactInput.value.trim();
            if (phone === "")
                showFieldError(
                    contactInput,
                    "Please enter a 10-digit number starting with 9.",
                );
            else if (!validatePhone(phone))
                showFieldError(
                    contactInput,
                    "Phone number must be 10 digits and start with 9.",
                );
            else clearFieldError(contactInput);
        });
    }

    function showFieldError(field, message) {
        let errorElement =
            field.parentElement.querySelector(".validation_error") ||
            field.parentElement.querySelector(".client_error");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.className = "validation_error";
            field.parentElement.appendChild(errorElement);
        }
        errorElement.style.display = "block";
        errorElement.textContent = message;
        field.style.borderColor = "#d32f2f";
    }

    function clearFieldError(field) {
        const errorElement =
            field.parentElement.querySelector(".validation_error") ||
            field.parentElement.querySelector(".client_error");
        if (errorElement) {
            errorElement.style.display = "none";
            errorElement.textContent = "";
        }
        field.style.borderColor = "";
    }
});
