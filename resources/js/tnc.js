document.addEventListener("DOMContentLoaded", () => {
    // --- Elements ---
    const openBtn = document.getElementById("openterms");
    const modal = document.getElementById("tncModal");
    const closeBtn = document.getElementById("closeTnc");
    const tncBody = document.querySelector(".tnc_body");
    const acceptBtn = document.getElementById("acceptTncBtn");
    const declineBtn = document.getElementById("declineTncBtn");
    const checkbox = document.getElementById("tnc_checkbox");
    const submitBtn = document.getElementById("signup_submit");

    if (!openBtn || !modal || !closeBtn) return;

    // --- Helper Functions for Button State ---
    function unlockButton() {
        if (!acceptBtn) return;
        acceptBtn.disabled = false;
        acceptBtn.classList.add("unlocked");
        acceptBtn.textContent = "I Accept";
    }

    function lockButton() {
        if (!acceptBtn) return;
        acceptBtn.disabled = true;
        acceptBtn.classList.remove("unlocked");
        acceptBtn.textContent = "Scroll down";
    }

    // --- MODAL LOGIC ---
    function openModal(e) {
        if (e) e.preventDefault();
        modal.classList.add("active");
        document.body.style.overflow = "hidden"; // Prevents background wobble

        // 👉 THE FIX: Reset the scrollbar to the top and lock the button!
        if (tncBody) tncBody.scrollTop = 0;
        lockButton();

        // Wait 100ms for modal to render, then check if it's too short for a scrollbar
        setTimeout(() => {
            if (
                tncBody &&
                tncBody.clientHeight > 0 &&
                tncBody.scrollHeight <= tncBody.clientHeight + 2
            ) {
                unlockButton();
            }
        }, 100);
    }

    function closeModal() {
        modal.classList.remove("active");
        document.body.style.overflow = "";
    }

    openBtn.addEventListener("click", openModal);
    closeBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });

    // --- SCROLL TO ACCEPT LOGIC ---
    if (tncBody && acceptBtn) {
        tncBody.addEventListener("scroll", () => {
            // Check if scrolled to the bottom (with a 2px buffer for mobile math)
            if (
                tncBody.scrollTop + tncBody.clientHeight >=
                tncBody.scrollHeight - 2
            ) {
                unlockButton();
            }
        });

        acceptBtn.addEventListener("click", () => {
            if (!acceptBtn.disabled) {
                closeModal();
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event("change"));
                }
            }
        });
    }

    // --- DECLINE LOGIC ---
    if (declineBtn) {
        declineBtn.addEventListener("click", () => {
            closeModal();
            if (checkbox) {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event("change"));
            }
        });
    }

    // --- AARON'S SIGNUP FORM LOGIC ---
    if (checkbox && submitBtn) {
        checkbox.addEventListener("change", function () {
            if (this.checked) {
                submitBtn.disabled = false;
                submitBtn.classList.add("active");
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove("active");
            }
        });

        // Initialize button state on page load
        if (!checkbox.checked) {
            submitBtn.disabled = true;
            submitBtn.classList.remove("active");
        }
    }
});
