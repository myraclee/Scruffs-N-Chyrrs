document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".edit_profile_form");
    const firstNameInput = document.getElementById("first_name");
    const lastNameInput = document.getElementById("last_name");
    const emailInput = document.getElementById("email");
    const contactInput = document.getElementById("contact_number");

    if (!form) return;

    // --- 1. STRICT REGEX VALIDATORS ---
    function validateName(name) {
        return /^[A-Za-z\s]*[A-Za-z][A-Za-z\s]*$/.test(name);
    }

    function validateEmail(email) {
        return /^[a-zA-Z0-9._%+\-]{2,}@[a-zA-Z0-9.\-]{2,}\.[a-zA-Z]{2,}$/.test(email);
    }

    function validatePhone(phone) {
        // Now checks for exactly 10 digits starting with 9 (since +63 is outside the input)
        return /^9[0-9]{9}$/.test(phone);
    }

    // --- 2. DYNAMIC UI HELPERS ---
    function showFieldError(field, message) {
        let container = field.parentElement;
        
        // If it's the contact number, target the outer wrapper so it doesn't break the +63 layout
        if (field.id === "contact_number") {
            container = container.parentElement;
        }

        let errorElement = container.querySelector(".error_message") || container.querySelector(".validation_error");
        
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.className = "validation_error";
            errorElement.style.color = "#d93025";
            errorElement.style.fontFamily = "Coolvetica, sans-serif";
            errorElement.style.fontSize = "14px";
            errorElement.style.marginTop = "6px";
            errorElement.style.display = "block";
            container.appendChild(errorElement);
        }
        
        errorElement.style.display = "block";
        errorElement.textContent = message;
        field.style.setProperty("border", "2px solid #d93025", "important");
        field.style.boxShadow = "0 0 5px rgba(217, 48, 37, 0.3)";

        // Hide Laravel's server error if frontend catches it first to avoid duplicates
        const serverError = container.querySelector(".server_error");
        if (serverError) serverError.style.display = "none";
    }

    function clearFieldError(field) {
        let container = field.parentElement;
        if (field.id === "contact_number") {
            container = container.parentElement;
        }

        const errorElement = container.querySelector(".error_message") || container.querySelector(".validation_error");
        if (errorElement) {
            errorElement.style.display = "none";
            errorElement.textContent = "";
        }
        
        field.style.setProperty("border", "2px solid #682c7a", "important");
        field.style.boxShadow = "none";
    }

    // --- 3. FIELD CHECKERS ---
    function checkFirstName() {
        if (!firstNameInput) return true;
        const val = firstNameInput.value.trim();
        if (val === "") {
            showFieldError(firstNameInput, "First name is required.");
            return false;
        } else if (!validateName(val)) {
            showFieldError(firstNameInput, "Please enter a valid first name (letters only, cannot be blank).");
            return false;
        }
        clearFieldError(firstNameInput);
        return true;
    }

    function checkLastName() {
        if (!lastNameInput) return true;
        const val = lastNameInput.value.trim();
        if (val === "") {
            showFieldError(lastNameInput, "Last name is required.");
            return false;
        } else if (!validateName(val)) {
            showFieldError(lastNameInput, "Please enter a valid last name (letters only, cannot be blank).");
            return false;
        }
        clearFieldError(lastNameInput);
        return true;
    }

    function checkEmail() {
        if (!emailInput) return true;
        const email = emailInput.value.trim();
        if (email === "") {
            showFieldError(emailInput, "Email is required.");
            return false;
        } else if (!email.includes("@")) {
            showFieldError(emailInput, "Email must contain an @ symbol.");
            return false;
        } else if (!validateEmail(email)) {
            showFieldError(emailInput, "Enter a valid email. Each part must be at least 2 characters.");
            return false;
        }
        clearFieldError(emailInput);
        return true;
    }

    function checkPhone() {
        if (!contactInput) return true;
        const phone = contactInput.value.trim();
        if (phone === "") {
            showFieldError(contactInput, "Phone number is required.");
            return false;
        } else if (!validatePhone(phone)) {
            showFieldError(contactInput, "Phone number must be 10 digits and start with 9.");
            return false;
        }
        clearFieldError(contactInput);
        return true;
    }

    // --- 4. REAL-TIME VALIDATION (Triggers instantly on type) ---
    if (firstNameInput) firstNameInput.addEventListener("input", checkFirstName);
    if (lastNameInput) lastNameInput.addEventListener("input", checkLastName);
    if (emailInput) emailInput.addEventListener("input", checkEmail);
    if (contactInput) contactInput.addEventListener("input", checkPhone);

    // --- 5. SUBMISSION INTERCEPT ---
    form.addEventListener("submit", (e) => {
        // Execute all checks so that ALL invalid fields turn red simultaneously
        const isFirstValid = checkFirstName();
        const isLastValid = checkLastName();
        const isEmailValid = checkEmail();
        const isPhoneValid = checkPhone();

        if (!isFirstValid || !isLastValid || !isEmailValid || !isPhoneValid) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });
});