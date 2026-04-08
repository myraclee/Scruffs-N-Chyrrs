import Toast from "/resources/js/utils/toast.js";

document.addEventListener("DOMContentLoaded", () => {
    // --- 🚀 FORCE RED ERRORS ON LOAD VIA JS (Bypasses CSS entirely) 🚀 ---
    document.querySelectorAll('.server_error, .validation_error').forEach(el => {
        el.style.setProperty('color', '#d93025', 'important');
    });

    // --- DOM Elements ---
    const emailInput = document.getElementById("email");
    const loginInput = document.getElementById("password");
    const form = document.querySelector("form");
    const submitButton = form?.querySelector('button[type="submit"]');
    const toggleLogin = document.getElementById("toggle_login_password");
    const remediationOverlay = document.getElementById(
        "email_remediation_overlay",
    );
    const remediationForm = document.getElementById("email_remediation_form");
    const remediationInput = document.getElementById("remediation_email");
    const forceRemediation =
        document.getElementById("force_email_remediation_flag")?.value === "1";

    // Unlock modal elements
    const unlockModal = document.getElementById("unlockAccountModal");
    const unlockCloseBtn = document.getElementById("closeUnlockModal");
    const unlockForm = document.getElementById("unlockForm");
    const unlockEmailInput = document.getElementById("unlock_email");
    const forceUnlockModal =
        document.getElementById("force_unlock_modal")?.value === "1";

    let isSubmitted = false;
    let countdownInterval = null;
    let cleanupRemediationGuards = null;

    // Eye icons (unchanged)
    const eyeOpen = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#682c7a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
    const eyeClosed = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#682c7a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

    // --- Utilities ---
    const validateLoginEmail = (email) => {
        if (!email) return false;
        if (email.includes(" ")) return false;
        if (!email.includes("@")) return false;
        
        // 🚀 Reject emails ending or starting with @
        if (email.endsWith("@") || email.startsWith("@")) return false;
        
        const parts = email.split("@");
        if (parts.length !== 2) return false;
        const [prefix, domain] = parts;
        if (!prefix || !domain) return false;
        return true;
    };

    const validateRemediationEmail = (email) => {
        if (!validateLoginEmail(email)) return false;
        const [prefix, domain] = email.split("@");
        if (domain !== "gmail.com" && domain !== "ust.edu.ph") return false;
        if (!/^[a-z0-9.]+$/.test(prefix)) return false;
        if (
            prefix.startsWith(".") ||
            prefix.endsWith(".") ||
            prefix.includes("..")
        )
            return false;
        return true;
    };

    const lockPageInteraction = () => {
        const preventEscape = (event) => {
            if (event.key === "Escape") event.preventDefault();
        };
        const pushGuardState = () => {
            window.history.pushState(
                { remediation: true },
                "",
                window.location.href,
            );
        };
        const onPopState = () => pushGuardState();
        const beforeUnloadHandler = (event) => {
            event.preventDefault();
            event.returnValue = "A required email update is still pending.";
            return event.returnValue;
        };
        pushGuardState();
        document.addEventListener("keydown", preventEscape);
        window.addEventListener("popstate", onPopState);
        window.addEventListener("beforeunload", beforeUnloadHandler);
        return () => {
            document.removeEventListener("keydown", preventEscape);
            window.removeEventListener("popstate", onPopState);
            window.removeEventListener("beforeunload", beforeUnloadHandler);
        };
    };

    const applyErrorStyles = (field) => {
        if (!field) return;
        field.style.setProperty("border", "2px solid #d93025", "important");
        field.style.boxShadow = "0 0 5px rgba(217, 48, 37, 0.3)";
    };

    const clearFieldError = (field) => {
        if (!field) return;
        field.style.setProperty("border", "2px solid #682c7a", "important");
        field.style.boxShadow = "none";
        const container = field.closest(".password_wrapper") || field;
        const err = container.parentElement.querySelector(
            ".validation_error, .server_error",
        );
        if (err) {
            err.textContent = "";
            err.style.display = "none";
        }
    };

    const showFieldError = (field, message) => {
        applyErrorStyles(field);
        if (!message) return;
        const targetElement = field.closest(".password_wrapper") || field;
        let err =
            targetElement.parentElement.querySelector(".validation_error");
        if (!err) {
            err = document.createElement("span");
            err.className = "validation_error";
            // 🚀 Force JS inserted styles to be red 🚀
            err.style.cssText =
                "color: #d93025 !important; font-family: Coolvetica, sans-serif; font-size: 14px; margin-top: 4px; display: block; text-align: left;";
            targetElement.after(err);
        }
        err.textContent = message;
        err.style.setProperty('color', '#d93025', 'important');
        err.style.display = "block";
    };

    const formatTime = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}m ${secs}s`;
    };

    const disableButton = () => {
        if (!submitButton) return;
        submitButton.disabled = true;
        submitButton.style.opacity = "0.5";
        submitButton.style.cursor = "not-allowed";
    };

    const enableButton = () => {
        if (!submitButton) return;
        submitButton.disabled = false;
        submitButton.style.opacity = "1";
        submitButton.style.cursor = "pointer";
    };

    // --- Countdown for temporary lock ---
    const startCountdown = (lockoutTimestamp) => {
        if (countdownInterval) clearInterval(countdownInterval);
        const updateCountdown = () => {
            const now = Math.floor(Date.now() / 1000);
            const secondsLeft = lockoutTimestamp - now;
            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
                countdownInterval = null;
                clearFieldError(emailInput);
                clearFieldError(loginInput);
                enableButton();
                return;
            }
            const message = `Too many failed attempts. Try again in ${formatTime(secondsLeft)}.`;
            applyErrorStyles(emailInput);
            applyErrorStyles(loginInput);
            showFieldError(loginInput, message);
            disableButton();
        };
        updateCountdown();
        countdownInterval = setInterval(updateCountdown, 1000);
    };

    // --- Show Unlock Modal (for permanent lock or reset required) ---
    const showUnlockModal = () => {
        if (unlockModal) {
            unlockModal.classList.add("active");
            document.body.style.overflow = "hidden";
            if (unlockEmailInput) unlockEmailInput.focus();
        }
    };

    const hideUnlockModal = () => {
        if (unlockModal) {
            unlockModal.classList.remove("active");
            document.body.style.overflow = "";
        }
    };

    // --- Handle Unlock Form Submission (AJAX to avoid page reload) ---
    if (unlockForm) {
        unlockForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const email = unlockEmailInput?.value.trim();
            if (!email || !validateLoginEmail(email)) {
                Toast.warning("Please enter a valid email address.");
                return;
            }
            const submitBtn = unlockForm.querySelector('button[type="submit"]');
            const originalText = submitBtn?.innerHTML;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = "Sending...";
            }
            try {
                const response = await fetch(unlockForm.action, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                    body: JSON.stringify({ email }),
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    Toast.success(
                        "Verification link sent to your email. Please check your inbox.",
                    );
                    hideUnlockModal();
                    // Optionally clear error styles
                    clearFieldError(emailInput);
                    clearFieldError(loginInput);
                    enableButton();
                } else {
                    Toast.error(
                        data.message ||
                            "Failed to send unlock link. Please try again.",
                    );
                }
            } catch (error) {
                Toast.error("Network error. Please try again.");
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        });
    }

    if (unlockCloseBtn) {
        unlockCloseBtn.addEventListener("click", hideUnlockModal);
    }

    // Close modal when clicking outside the container
    if (unlockModal) {
        unlockModal.addEventListener("click", (e) => {
            if (e.target === unlockModal) hideUnlockModal();
        });
    }

    // --- Auto-show unlock modal if server indicated permanent lock or reset required ---
    if (forceUnlockModal) {
        showUnlockModal();
    }

    // --- Password Toggle ---
    if (toggleLogin && loginInput) {
        toggleLogin.innerHTML = eyeOpen;
        toggleLogin.addEventListener("click", () => {
            const isPass = loginInput.type === "password";
            loginInput.type = isPass ? "text" : "password";
            toggleLogin.innerHTML = isPass ? eyeClosed : eyeOpen;
        });
    }

    // --- Input sanitization and validation ---
    if (emailInput) {
        emailInput.addEventListener("input", () => {
            emailInput.value = emailInput.value
                .replace(/[^a-zA-Z0-9@.]/g, "")
                .toLowerCase();
            if (isSubmitted && validateLoginEmail(emailInput.value.trim())) {
                clearFieldError(emailInput);
            }
        });
    }

    if (loginInput) {
        loginInput.addEventListener("input", () => {
            if (isSubmitted && loginInput.value.trim() !== "") {
                clearFieldError(loginInput);
                clearFieldError(emailInput);
            }
        });
    }

    // --- Form submit validation ---
    if (form) {
        form.addEventListener("submit", (e) => {
            isSubmitted = true;
            let emailVal = emailInput.value.trim();
            let passVal = loginInput.value.trim();
            let valid = true;

            if (!emailVal) {
                showFieldError(emailInput, "The email field is required.");
                valid = false;
            } else if (!validateLoginEmail(emailVal)) {
                showFieldError(
                    emailInput,
                    "The email field format is invalid.",
                );
                valid = false;
            }

            if (!passVal) {
                showFieldError(loginInput, "The password field is required.");
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });
    }

    // --- Remediation overlay ---
    if (forceRemediation && remediationOverlay) {
        remediationOverlay.classList.add("active");
        document.body.style.overflow = "hidden";
        cleanupRemediationGuards = lockPageInteraction();
        if (remediationInput) {
            remediationInput.focus();
            remediationInput.addEventListener("input", () => {
                remediationInput.value = remediationInput.value
                    .replace(/[^a-zA-Z0-9@.]/g, "")
                    .toLowerCase();
            });
        }
        remediationOverlay.addEventListener("click", (event) => {
            if (event.target === remediationOverlay) event.preventDefault();
        });
    }

    if (remediationForm) {
        remediationForm.addEventListener("submit", (event) => {
            const email = remediationInput?.value.trim() ?? "";
            if (!validateRemediationEmail(email)) {
                event.preventDefault();
                if (remediationInput) remediationInput.focus();
                return;
            }
            if (cleanupRemediationGuards) cleanupRemediationGuards();
        });
    }

    // --- Handle lockout timestamp for temporary lock ---
    const lockoutTimestamp =
        document.getElementById("lockout_timestamp")?.value;
    if (lockoutTimestamp) {
        const timestamp = parseInt(lockoutTimestamp, 10);
        startCountdown(timestamp);
    } else {
        // For permanent lock or reset required errors
        const allErrors = document.querySelectorAll(
            ".server_error, .validation_error",
        );
        allErrors.forEach((err) => {
            const text = err.textContent.trim();
            const isPermanentLock = text.includes("Account locked");
            const isResetRequired = text.includes("Password reset required");
            const isInvalidCreds =
                text.includes("Invalid email or password") ||
                text.includes("Invalid credentials");

            if (isPermanentLock || isResetRequired) {
                err.style.display = "none";
                if (!forceUnlockModal) showUnlockModal();
            } else if (isInvalidCreds) {
                applyErrorStyles(emailInput);
                applyErrorStyles(loginInput);
                err.style.display = "none";
                showFieldError(loginInput, text);
            }
        });
    }

    // --- Toast for any pending message ---
    const pendingMessage = sessionStorage.getItem("auth_toast_message");
    if (pendingMessage) {
        setTimeout(() => {
            Toast.warning(pendingMessage);
            sessionStorage.removeItem("auth_toast_message");
        }, 300);
    }
});