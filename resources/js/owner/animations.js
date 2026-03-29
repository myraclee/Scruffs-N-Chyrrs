document.addEventListener("DOMContentLoaded", function () {
    const header = document.getElementById("animatedHeader");

    if (!header) return;

    const text = header.textContent.trim();
    header.innerHTML = "";

    text.split("").forEach((char, i) => {
        const span = document.createElement("span");
        span.className = "letter";
        span.style.setProperty("--delay", i);

        span.innerHTML = char === " " ? "&nbsp;" : char;
        span.setAttribute("data-char", char);

        header.appendChild(span);
    });

    const totalDuration = text.length * 50 + 500;
});
