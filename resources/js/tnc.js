import { Modal } from 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const link = document.getElementById('openterms');
    const modalElement = document.getElementById('termsModal');

    if (!link || !modalElement) return;

    const termsModal = new Modal(modalElement);

    link.addEventListener('click', (e) => {
        e.preventDefault();
        termsModal.show();
    });
});