document.addEventListener("DOMContentLoaded", function () {
    const passwordInput = document.getElementById("new_password");
    const confirmPasswordInput = document.getElementById(
        "new_password_confirmation",
    );
    const matchMessage = document.getElementById("password_match_message");

    // Requirement Elements
    const reqLength = document.getElementById("req_length");
    const reqUpper = document.getElementById("req_upper");
    const reqLower = document.getElementById("req_lower");
    const reqNumber = document.getElementById("req_number");
    const reqSymbol = document.getElementById("req_symbol");

    // --- 1. Password Requirements Logic ---
    passwordInput.addEventListener("input", function () {
        const val = passwordInput.value;

        // Test conditions
        updateRequirement(reqLength, val.length >= 8);
        updateRequirement(reqUpper, /[A-Z]/.test(val));
        updateRequirement(reqLower, /[a-z]/.test(val));
        updateRequirement(reqNumber, /[0-9]/.test(val));
        updateRequirement(reqSymbol, /[!@#$%^&*(),.?":{}|<>]/.test(val));

        // Check match if they edit the first password after typing the second
        checkPasswordsMatch();
    });

    function updateRequirement(element, isValid) {
        const icon = element.querySelector("span");
        if (isValid) {
            element.classList.remove("invalid");
            element.classList.add("valid");
            icon.textContent = "✓";
        } else {
            element.classList.remove("valid");
            element.classList.add("invalid");
            icon.textContent = "✗";
        }
    }

    // --- 2. Password Match Logic ---
    confirmPasswordInput.addEventListener("input", checkPasswordsMatch);

    function checkPasswordsMatch() {
        const pass1 = passwordInput.value;
        const pass2 = confirmPasswordInput.value;

        // 1. Clear any inline styles just in case they are stuck
        matchMessage.style.display = "";

        // 2. Hide the message entirely if the confirm box is empty
        if (pass2.length === 0) {
            matchMessage.classList.remove("match", "mismatch");
            return;
        }

        // 3. Check for match/mismatch and apply the correct class
        if (pass1 === pass2) {
            matchMessage.textContent = "✓ Passwords match";
            matchMessage.classList.remove("mismatch");
            matchMessage.classList.add("match");
        } else {
            matchMessage.textContent = "✗ Passwords do not match";
            matchMessage.classList.remove("match");
            matchMessage.classList.add("mismatch");
        }
    }

    // --- 3. View Password Toggle Logic ---
    const togglePasswordButtons = document.querySelectorAll(".toggle_password");

    togglePasswordButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const input = this.previousElementSibling;
            const eyeIcon = this.querySelector(".eye-icon");
            const eyeSlashIcon = this.querySelector(".eye-slash-icon");

            if (input.type === "password") {
                input.type = "text";
                eyeIcon.style.display = "none";
                eyeSlashIcon.style.display = "block";
            } else {
                input.type = "password";
                eyeIcon.style.display = "block";
                eyeSlashIcon.style.display = "none";
            }
        });
    });
});
