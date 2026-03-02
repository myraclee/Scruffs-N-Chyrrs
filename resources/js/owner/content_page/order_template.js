// Globally expose functions so HTML onclicks can use them
window.openTemplateModal = function(isEdit) {
    const title = document.getElementById('templateModalTitle');
    title.innerText = isEdit ? "Edit Template" : "Add New Template";
    
    // Auto-populate 1 empty row if adding new
    if(!isEdit) {
        document.getElementById('opt1List').innerHTML = '';
        document.getElementById('opt2List').innerHTML = '';
        addOptionRow('opt1List');
        addOptionRow('opt2List');
    }

    document.getElementById('templateModalOverlay').classList.add('active');
};

window.openDeleteTemplateModal = function() {
    document.getElementById('deleteTemplateModalOverlay').classList.add('active');
};

window.closeTemplateModals = function() {
    document.getElementById('templateModalOverlay').classList.remove('active');
    document.getElementById('deleteTemplateModalOverlay').classList.remove('active');
};

// Dynamic Option Row Adder
window.addOptionRow = function(containerId) {
    const container = document.getElementById(containerId);
    
    const row = document.createElement('div');
    row.className = 'option_row';
    row.innerHTML = `
        <input type="checkbox" checked>
        <input type="text" class="text_input" placeholder="Option Name" style="flex: 1;">
        <input type="number" class="number_input tiny_input" placeholder="Price">
    `;
    
    container.appendChild(row);
};

// Clear Options
window.clearOptions = function(containerId) {
    document.getElementById(containerId).innerHTML = '';
};

// Toggle Discount Fields
window.toggleDiscountFields = function() {
    const isChecked = document.getElementById('tempDiscountCheck').checked;
    const fields = document.getElementById('discountFields');
    
    if(isChecked) {
        fields.classList.add('active');
    } else {
        fields.classList.remove('active');
        document.getElementById('tempDiscountVal').value = '';
        document.getElementById('tempDiscountQty').value = '';
    }
};