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
        // Updated Logic:
        // [a-zA-Z0-9.-]+       -> Domain part allowing dots (ust.edu)
        // \.[a-zA-Z0-9]{2,}    -> The final dot followed by 2 or MORE alphanumeric chars (ph, com, online)
        // [a-zA-Z0-9]$         -> Ensuring the very last char is not a special character
        const regex =
            /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.-]+\.[a-zA-Z0-9]{1,}[a-zA-Z0-9]$/;

        // Alternative standard version that explicitly hits your "at least 2" requirement:
        // /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.-]+\.[a-zA-Z0-9]{2,}$/

        return /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.-]+\.[a-zA-Z0-9]{2,}$/.test(
            email,
        );
    }

    function validatePhone(phone) {
        return /^9[0-9]{9}$/.test(phone);
    }

    // --- INPUT BLOCKER FOR NAMES ---
    function setupNameInputBlocker(input) {
        if (!input) return;
        input.addEventListener("input", () => {
            // Instantly strips anything that isn't a letter or space
            input.value = input.value.replace(/[^A-Za-z\s]/g, "");
        });
    }

    setupNameInputBlocker(firstNameInput);
    setupNameInputBlocker(lastNameInput);

    // --- 2. DYNAMIC UI HELPERS ---
    function showFieldError(field, message) {
        let container = field.parentElement;
        if (field.id === "contact_number") container = container.parentElement;

        let errorElement =
            container.querySelector(".error_message") ||
            container.querySelector(".validation_error");

        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.className = "validation_error";
            errorElement.style.cssText =
                "color: #d93025; font-family: Coolvetica, sans-serif; font-size: 14px; margin-top: 6px; display: block;";
            container.appendChild(errorElement);
        }

        errorElement.style.display = "block";
        errorElement.textContent = message;
        field.style.setProperty("border", "2px solid #d93025", "important");
        field.style.boxShadow = "0 0 5px rgba(217, 48, 37, 0.3)";

        const serverError = container.querySelector(".server_error");
        if (serverError) serverError.style.display = "none";
    }

    function clearFieldError(field) {
        let container = field.parentElement;
        if (field.id === "contact_number") container = container.parentElement;

        const errorElement =
            container.querySelector(".error_message") ||
            container.querySelector(".validation_error");
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
            showFieldError(firstNameInput, "The first name field is required.");
            return false;
        }
        clearFieldError(firstNameInput);
        return true;
    }

    function checkLastName() {
        if (!lastNameInput) return true;
        const val = lastNameInput.value.trim();
        if (val === "") {
            showFieldError(lastNameInput, "The last name field is required.");
            return false;
        }
        clearFieldError(lastNameInput);
        return true;
    }

    function checkEmail() {
        if (!emailInput) return true;
        const email = emailInput.value.trim();
        if (email === "") {
            showFieldError(emailInput, "The email field is required.");
            return false;
        } else if (!validateEmail(email)) {
            showFieldError(emailInput, "Please enter a valid email.");
            return false;
        }
        clearFieldError(emailInput);
        return true;
    }

    function checkPhone() {
        if (!contactInput) return true;
        const phone = contactInput.value.trim();
        if (phone === "") {
            showFieldError(
                contactInput,
                "The contact number field is required.",
            );
            return false;
        } else if (!validatePhone(phone)) {
            showFieldError(
                contactInput,
                "Phone number must be 10 digits and start with 9.",
            );
            return false;
        }
        clearFieldError(contactInput);
        return true;
    }

    // --- 4. REAL-TIME VALIDATION ---
    if (firstNameInput)
        firstNameInput.addEventListener("input", checkFirstName);
    if (lastNameInput) lastNameInput.addEventListener("input", checkLastName);
    if (emailInput) emailInput.addEventListener("input", checkEmail);
    if (contactInput) contactInput.addEventListener("input", checkPhone);

    // --- 5. SUBMISSION INTERCEPT ---
    form.addEventListener("submit", (e) => {
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
