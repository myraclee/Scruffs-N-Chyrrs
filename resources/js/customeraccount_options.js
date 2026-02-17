document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.querySelector('.account_trigger');
    const popup = document.getElementById('accountPopup');

    if (!trigger || !popup) return;

    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        popup.classList.toggle('show'); // toggle a class
    });

    document.addEventListener('click', () => {
        popup.classList.remove('show');
    });
});

