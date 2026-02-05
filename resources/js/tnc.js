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
