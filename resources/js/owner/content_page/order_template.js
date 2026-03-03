// ================= MODAL CONTROLS =================

// Open Add/Edit Modal
window.openTemplateModal = function(isEdit) {
    const overlay = document.getElementById('templateModalOverlay');
    const title = document.getElementById('templateModalTitle');

    title.innerText = isEdit ? "Edit Template" : "Add New Template";

    if (!isEdit) {
        // Reset all fields when adding new
        document.getElementById('tempCategory').value = '';
        document.getElementById('tempOpt1Label').value = '';
        document.getElementById('tempOpt2Label').value = '';
        document.getElementById('tempDesc').value = '';

        document.getElementById('tempDiscountCheck').checked = false;
        document.getElementById('tempDiscountVal').value = '';
        document.getElementById('tempDiscountQty').value = '';
        document.getElementById('discountFields').classList.remove('active');

        document.getElementById('opt1List').innerHTML = '';
        document.getElementById('opt2List').innerHTML = '';

        addOptionRow('opt1List');
        addOptionRow('opt2List');
    }

    overlay.classList.add('active');
};

// Open Delete Modal
window.openDeleteTemplateModal = function() {
    document.getElementById('deleteTemplateModalOverlay').classList.add('active');
};

// Close Both Modals
window.closeTemplateModals = function() {
    document.getElementById('templateModalOverlay').classList.remove('active');
    document.getElementById('deleteTemplateModalOverlay').classList.remove('active');
};

// Close when clicking outside modal
document.addEventListener('click', function(e) {
    const templateOverlay = document.getElementById('templateModalOverlay');
    const deleteOverlay = document.getElementById('deleteTemplateModalOverlay');

    if (e.target === templateOverlay) {
        closeTemplateModals();
    }

    if (e.target === deleteOverlay) {
        closeTemplateModals();
    }
});

// Close when pressing ESC
document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") {
        closeTemplateModals();
    }
});

// ================= OPTION ROWS =================

window.addOptionRow = function(containerId) {
    const container = document.getElementById(containerId);

    const row = document.createElement('div');
    row.className = 'option_row';

    row.innerHTML = `
        <input type="checkbox" checked>
        <input type="text" class="text_input" placeholder="Option Name">
        <input type="number" class="number_input tiny_input" placeholder="Price">
    `;

    container.appendChild(row);
};

window.clearOptions = function(containerId) {
    document.getElementById(containerId).innerHTML = '';
};

// ================= DISCOUNT TOGGLE =================

window.toggleDiscountFields = function() {
    const isChecked = document.getElementById('tempDiscountCheck').checked;
    const fields = document.getElementById('discountFields');

    if (isChecked) {
        fields.classList.add('active');
    } else {
        fields.classList.remove('active');
        document.getElementById('tempDiscountVal').value = '';
        document.getElementById('tempDiscountQty').value = '';
    }
};