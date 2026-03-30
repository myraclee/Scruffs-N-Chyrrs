document.addEventListener("DOMContentLoaded", function () {
    // --- 1. Variable Declarations ---
    const passwordInput = document.getElementById("new_password");
    const confirmPasswordInput = document.getElementById(
        "new_password_confirmation",
    );
    const matchMessage = document.getElementById("password_match_message");

    const reqLength = document.getElementById("req_length");
    const reqUpper = document.getElementById("req_upper");
    const reqLower = document.getElementById("req_lower");
    const reqNumber = document.getElementById("req_number");
    const reqSymbol = document.getElementById("req_symbol");

    const form = document.querySelector("form");
    const modal = document.getElementById("success_modal");
    const modalContinueBtn = document.getElementById("modal_continue_btn");

    // Make sure our main inputs exist before trying to run logic on them
    if (!passwordInput || !confirmPasswordInput) return;

    // --- 2. Password Requirements Logic ---
    passwordInput.addEventListener("input", function () {
        const val = passwordInput.value;

        if (reqLength) updateRequirement(reqLength, val.length >= 8);
        if (reqUpper) updateRequirement(reqUpper, /[A-Z]/.test(val));
        if (reqLower) updateRequirement(reqLower, /[a-z]/.test(val));
        if (reqNumber) updateRequirement(reqNumber, /[0-9]/.test(val));
        if (reqSymbol)
            updateRequirement(reqSymbol, /[!@#$%^&*(),.?":{}|<>]/.test(val));

        checkPasswordsMatch();
    });

    function updateRequirement(element, isValid) {
        if (!element) return;
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

    // --- 3. Password Match Logic ---
    confirmPasswordInput.addEventListener("input", checkPasswordsMatch);

    function checkPasswordsMatch() {
        if (!matchMessage) return;

        const pass1 = passwordInput.value;
        const pass2 = confirmPasswordInput.value;

        matchMessage.style.display = ""; // Clear inline styles

        if (pass2.length === 0) {
            matchMessage.classList.remove("match", "mismatch");
            return;
        }

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

    // --- 4. View Password Toggle Logic ---
    const togglePasswordButtons = document.querySelectorAll(".toggle_password");

    togglePasswordButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const input = this.previousElementSibling;
            const eyeIcon = this.querySelector(".eye-icon");
            const eyeSlashIcon = this.querySelector(".eye-slash-icon");

            if (input.type === "password") {
                input.type = "text";
                if (eyeIcon) eyeIcon.style.display = "none";
                if (eyeSlashIcon) eyeSlashIcon.style.display = "block";
            } else {
                input.type = "password";
                if (eyeIcon) eyeIcon.style.display = "block";
                if (eyeSlashIcon) eyeSlashIcon.style.display = "none";
            }
        });
    });

    // --- 5. Form Submit & Popup Animation Logic ---
    if (form) {
        form.addEventListener("submit", function (e) {
            const pass1 = passwordInput.value;
            const pass2 = confirmPasswordInput.value;

            // Ensure passwords match AND the field isn't empty
            if (pass1 === pass2 && pass1.length > 0) {
                // If the modal exists, use it. Otherwise, just let the form submit normally.
                if (modal) {
                    e.preventDefault();
                    modal.classList.add("active");
                }
            } else {
                e.preventDefault();
                alert(
                    "Please make sure your passwords match before continuing.",
                );
            }
        });
    }

    if (modalContinueBtn && form) {
        modalContinueBtn.addEventListener("click", function () {
            form.submit();
        });
    }
});
