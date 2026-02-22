document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidenav = document.querySelector('.sidenav');

    const userToggle = document.getElementById('userToggle');
    const userPopup = document.getElementById('userPopup');
    const section = document.getElementById('userSection');
    const arrow = userToggle.querySelector('svg');
    const logoutButton = document.getElementById('sidenavLogoutButton');
    const logoutForm = document.getElementById('ownerLogoutForm');

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

    // Handle logout button click
    if (logoutButton && logoutForm) {
        logoutButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            logoutForm.submit();
        });
    }
});
