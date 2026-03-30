document.addEventListener("DOMContentLoaded", () => {
    const buttons = document.querySelectorAll(".content_option");
    const sections = document.querySelectorAll(".content_section");

    buttons.forEach((button) => {
        button.addEventListener("click", () => {
            const targetId = button.dataset.section;

            buttons.forEach((btn) => btn.classList.remove("active"));
            sections.forEach((section) => section.classList.remove("active"));

            button.classList.add("active");

            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.classList.add("active");
            }
        });
    });

    if (buttons.length) {
        buttons[0].click();
    }
});
