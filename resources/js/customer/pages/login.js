import Toast from "/resources/js/utils/toast.js";

document.addEventListener("DOMContentLoaded", () => {
    const pendingMessage = sessionStorage.getItem("auth_toast_message");
    if (pendingMessage) {
        setTimeout(() => {
            Toast.warning(pendingMessage);
        }, 300);
        sessionStorage.removeItem("auth_toast_message");
    }

    const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#682c7a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#682c7a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    const toggleLogin = document.getElementById("toggle_login_password");
    const loginInput = document.getElementById("password");

    if (toggleLogin && loginInput) {
        toggleLogin.innerHTML = eyeOpen;
        toggleLogin.addEventListener("click", function () {
            const isPassword = loginInput.getAttribute("type") === "password";
            loginInput.setAttribute("type", isPassword ? "text" : "password");
            this.innerHTML = isPassword ? eyeClosed : eyeOpen;
        });
    }

    // --- FORM VALIDATION LOGIC ---
    const form = document.querySelector("form");
    const emailInput = document.getElementById("email");

    function validateEmail(email) {
        return /^[a-zA-Z0-9._%+\-]{2,}@[a-zA-Z0-9.\-]{2,}\.[a-zA-Z]{2,}$/.test(email);
    }

    function showFieldError(field, message) {
        let wrapper = field.parentElement;
        let container = wrapper;
        
        if (wrapper.classList.contains("password_wrapper")) {
            container = wrapper.parentElement;
        }

        let errorElement = container.querySelector(".validation_error");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.className = "validation_error";
            errorElement.style.color = "#d93025";
            errorElement.style.fontFamily = "Coolvetica, sans-serif";
            errorElement.style.fontSize = "14px";
            errorElement.style.marginTop = "6px";
            errorElement.style.display = "block";
            
            // Insert exactly below the input/wrapper to prevent layout breaks
            if (wrapper.classList.contains("password_wrapper")) {
                container.insertBefore(errorElement, wrapper.nextSibling);
            } else {
                container.insertBefore(errorElement, field.nextSibling);
            }
        }
        
        errorElement.style.display = "block";
        errorElement.textContent = message;
        field.style.setProperty("border", "2px solid #d93025", "important");
        field.style.boxShadow = "0 0 5px rgba(217, 48, 37, 0.3)";

        // Hide backend server error to prevent double messages
        const serverError = container.querySelector(".server_error");
        if(serverError) serverError.style.display = "none";
    }

    function clearFieldError(field) {
        let container = field.parentElement;
        if (container.classList.contains("password_wrapper")) {
            container = container.parentElement;
        }

        const errorElement = container.querySelector(".validation_error");
        if (errorElement) {
            errorElement.style.display = "none";
            errorElement.textContent = "";
        }
        
        field.style.setProperty("border", "2px solid #682c7a", "important");
        field.style.boxShadow = "none";
    }

    function handleEmailValidation() {
        if (!emailInput) return true;
        const email = emailInput.value.trim();
        
        if (email === "") {
            showFieldError(emailInput, "Please enter your email.");
            return false;
        } else if (!email.includes("@")) {
            showFieldError(emailInput, "Email must contain an @ symbol.");
            return false;
        } else if (!validateEmail(email)) {
            showFieldError(emailInput, "Enter a valid email. Each part must be at least 2 characters.");
            return false;
        } else {
            clearFieldError(emailInput);
            return true;
        }
    }

    // Consistent dedicated logic for password
    function handlePasswordValidation() {
        if (!loginInput) return true;
        
        if (loginInput.value.trim() === "") {
            showFieldError(loginInput, "Please enter your password.");
            return false;
        } else {
            clearFieldError(loginInput);
            return true;
        }
    }

    // Real-time input triggers
    if (emailInput) {
        emailInput.addEventListener("input", handleEmailValidation);
        if (emailInput.value.trim() !== "") handleEmailValidation(); // Catch reload data
    }

    if (loginInput) {
        loginInput.addEventListener("input", handlePasswordValidation);
    }

    // Force UI sync if Laravel returned a backend error on page load
    document.querySelectorAll('.server_error').forEach(err => {
        if (err.style.display !== 'none' && err.textContent.trim() !== '') {
            let container = err.closest('.login_container');
            let input = container.querySelector('input');
            if (input) {
                input.style.setProperty("border", "2px solid #d93025", "important");
                input.style.boxShadow = "0 0 5px rgba(217, 48, 37, 0.3)";
            }
        }
    });

    // Form submission intercept
    if (form) {
        form.addEventListener("submit", (e) => {
            let isValid = true;
            
            // Execute both strictly so both turn red if they are empty
            const isEmailValid = handleEmailValidation();
            const isPasswordValid = handlePasswordValidation();

            if (!isEmailValid || !isPasswordValid) {
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });
    }
});