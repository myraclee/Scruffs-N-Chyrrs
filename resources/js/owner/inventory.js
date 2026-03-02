document.addEventListener('DOMContentLoaded', () => {
    const modalOverlay = document.getElementById('modalOverlay');
    const addModal = document.getElementById('addMaterialModal');
    const editModal = document.getElementById('editMaterialModal');
    const openAddBtn = document.getElementById('openAddModalBtn');

    // Open Add Modal
    if (openAddBtn) {
        openAddBtn.addEventListener('click', () => {
            modalOverlay.classList.add('active');
            addModal.classList.add('active');
        });
    }

    // Function to open Edit Modal with data
    window.openEditModal = function(name, units) {
        document.getElementById('editMaterialName').value = name;
        document.getElementById('editMaterialUnits').value = units;
        modalOverlay.classList.add('active');
        editModal.classList.add('active');
    };

    // Close Modals
    window.closeModals = () => {
        modalOverlay.classList.remove('active');
        addModal.classList.remove('active');
        editModal.classList.remove('active');
    };
});