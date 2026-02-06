// TERMS AND CONDITIONS POP-UP MODAL
document.addEventListener("DOMContentLoaded", () => {
    const openBtn = document.getElementById("openterms");
    const modal = document.getElementById("tncModal");
    const closeBtn = document.getElementById("closeTnc");

    if (!openBtn || !modal || !closeBtn) return;

    openBtn.addEventListener("click", (e) => {
        e.preventDefault();
        modal.classList.add("active");
    });

    closeBtn.addEventListener("click", () => {
        modal.classList.remove("active");
    });

    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.remove("active");
        }
    });
});

// PROCEED WITH SIGNUP

document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('tnc_checkbox');
    const submitBtn = document.getElementById('signup_submit');

    checkbox.addEventListener('change', function() {
        if (this.checked) {
            submitBtn.disabled = false;
            submitBtn.classList.add('active'); // add class instead of inline style
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.remove('active'); // remove class
        }
    });

    // Initialize button state on page load
    if (!checkbox.checked) {
        submitBtn.disabled = true;
        submitBtn.classList.remove('active');
    }
});
