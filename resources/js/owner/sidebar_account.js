document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidenav = document.querySelector('.sidenav');

    const userToggle = document.getElementById('userToggle');
    const userPopup = document.getElementById('userPopup');
    const section = document.getElementById('userSection'); // make sure your <div class="user_section"> has id="userSection"
    const arrow = userToggle.querySelector('svg');

    // menu toggle
    menuToggle.addEventListener('click', () => {
        sidenav.classList.toggle('open');
    });

    userToggle.addEventListener('click', (e) => {
        e.stopPropagation();

        if (!section.classList.contains('active')) {
            // OPEN popup
            section.classList.add('active');
            userPopup.style.maxHeight = userPopup.scrollHeight + "px";
            userPopup.style.opacity = 1;
            userPopup.style.transform = "translateY(0)";
            arrow.style.transform = "rotate(180deg)";
        } else {
            // CLOSE popup
            section.classList.remove('active');
            userPopup.style.maxHeight = "0";
            userPopup.style.opacity = 0;
            userPopup.style.transform = "translateY(6px)";
            arrow.style.transform = "rotate(0deg)";
        }
    });
});
