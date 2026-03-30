import ProductSampleAPI from '/resources/js/api/productSampleApi.js';
import Toast from '/resources/js/utils/toast.js';

// ================= ELEMENTS =================
const addSampleBtn = document.querySelector('.add_sample');
const addSampleModal = document.getElementById('addSampleModal');
const cancelSampleUpload = document.getElementById('cancelSampleUpload');
const saveSampleUpload = document.getElementById('saveSampleUpload');
const sampleNameInput = document.getElementById('sampleNameInput');
const sampleNameError = document.getElementById('sampleNameError');
const sampleImageGrid = document.getElementById('sampleImageGrid');
const sampleImageError = document.getElementById('sampleImageError');
const sampleImageCounter = document.getElementById('sampleImageCounter');
const productSamplesWrapper = document.querySelector('.product_samples_wrapper');
const emptySampleImagesText = document.querySelector('.empty_sample_images');
const deleteConfirmModal = document.getElementById('deleteConfirmModal');
const confirmDeleteSample = document.getElementById('confirmDeleteSample');

// ================= STATE =================
const MAX_SAMPLE_IMAGES = 5;
let currentSampleFiles = [];
let existingSampleImages = [];
let sampleList = [];
let editSampleId = null;
let sampleToDelete = null;

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', async () => {
    initSampleUploadBox();
    await loadSamples();
});

async function loadSamples() {
    try {
        sampleList = await ProductSampleAPI.getAllSamples();
        renderSamples();
    } catch (error) {
        console.error('Error loading samples:', error);
        Toast.error('Failed to load product samples');
    }
}

// ================= VALIDATION =================
function validateSampleForm() {
    let isValid = true;
    const sampleName = sampleNameInput.value.trim();

    // Validate Title
    if (!sampleName) {
        sampleNameInput.classList.add('input_error_state');
        sampleNameError.classList.remove('hidden');
        isValid = false;
    } else {
        sampleNameInput.classList.remove('input_error_state');
        sampleNameError.classList.add('hidden');
    }

    // Validate Images
    const totalImages = existingSampleImages.length + currentSampleFiles.length;
    // Look specifically for our big box class
    const addBox = sampleImageGrid.querySelector('.products_add_box');
    
    if (totalImages === 0) {
        if (addBox) addBox.classList.add('image_box_error');
        sampleImageError.classList.remove('hidden');
        isValid = false;
    } else {
        if (addBox) addBox.classList.remove('image_box_error');
        sampleImageError.classList.add('hidden');
    }

    return isValid;
}

sampleNameInput.addEventListener('input', () => {
    sampleNameInput.classList.remove('input_error_state');
    sampleNameError.classList.add('hidden');
});

// ================= IMAGE GRID =================
function updateSampleCounter() {
    const total = existingSampleImages.length + currentSampleFiles.length;
    sampleImageCounter.textContent = `${total} / ${MAX_SAMPLE_IMAGES} images selected`;

    const addBox = sampleImageGrid.querySelector('.products_add_box');
    if (addBox) {
        addBox.style.display = total >= MAX_SAMPLE_IMAGES ? 'none' : 'flex';
        // Clear error if we have images
        if (total > 0) {
            addBox.classList.remove('image_box_error');
            sampleImageError.classList.add('hidden');
        }
    }
}

function initSampleUploadBox() {
    sampleImageGrid.innerHTML = '';
    const addBox = document.createElement('div');
    
    // FIX: Using your official CSS class so it stays a big square!
    addBox.className = 'products_add_box'; 
    
    addBox.textContent = '+';
    addBox.onclick = () => {
        const total = existingSampleImages.length + currentSampleFiles.length;
        if (total >= MAX_SAMPLE_IMAGES) {
            Toast.error(`Maximum ${MAX_SAMPLE_IMAGES} images allowed`);
            return;
        }

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png, image/jpeg, image/jpg';
        input.multiple = true;

        input.onchange = (e) => {
            const files = Array.from(e.target.files);
            const currentTotal = existingSampleImages.length + currentSampleFiles.length;
            const allowedCount = MAX_SAMPLE_IMAGES - currentTotal;
            const filesToAdd = files.slice(0, allowedCount);

            if (files.length > allowedCount) {
                Toast.error(`You can only add ${allowedCount} more images`);
            }

            filesToAdd.forEach(file => {
                currentSampleFiles.push({
                    id: Date.now() + Math.random(),
                    file: file
                });
                const reader = new FileReader();
                reader.onload = (event) => {
                    const wrapper = buildSampleImageWrapper(event.target.result, true, currentSampleFiles[currentSampleFiles.length - 1].id);
                    sampleImageGrid.insertBefore(wrapper, addBox);
                };
                reader.readAsDataURL(file);
            });
            updateSampleCounter();
        };
        input.click();
    };
    sampleImageGrid.appendChild(addBox);
    updateSampleCounter();
}

function buildSampleImageWrapper(src, isNew, identifier) {
    const wrapper = document.createElement('div');
    wrapper.className = 'sample_image_wrapper';

    const img = document.createElement('img');
    img.src = src;
    img.style.objectFit = "cover";

    const removeBtn = document.createElement('button');
    removeBtn.className = 'remove_sample_image';
    removeBtn.textContent = 'Remove';
    removeBtn.onclick = () => {
        if (isNew) {
            currentSampleFiles = currentSampleFiles.filter(item => item.id !== identifier);
        } else {
            existingSampleImages = existingSampleImages.filter(item => item.id !== identifier);
        }
        wrapper.remove();
        updateSampleCounter();
    };

    wrapper.appendChild(img);
    wrapper.appendChild(removeBtn);
    return wrapper;
}

function populateSampleGrid() {
    initSampleUploadBox();
    const addBox = sampleImageGrid.querySelector('.products_add_box');

    existingSampleImages.forEach(img => {
        const wrapper = buildSampleImageWrapper(`/storage/${img.image_path}`, false, img.id);
        sampleImageGrid.insertBefore(wrapper, addBox);
    });

    updateSampleCounter();
}

// ================= MODAL CONTROLS =================
addSampleBtn.addEventListener('click', () => {
    resetSampleModal();
    document.querySelector('#addSampleModal h2').textContent = 'Add Sample Products';
    addSampleModal.style.display = 'flex';
});

cancelSampleUpload.addEventListener('click', () => {
    addSampleModal.style.display = 'none';
    resetSampleModal();
});

function resetSampleModal() {
    editSampleId = null;
    sampleNameInput.value = '';
    currentSampleFiles = [];
    existingSampleImages = [];
    
    // Clear validation styling
    sampleNameInput.classList.remove('input_error_state');
    sampleNameError.classList.add('hidden');
    sampleImageError.classList.add('hidden');
    
    initSampleUploadBox();
}

// ================= SAVE/UPDATE =================
saveSampleUpload.addEventListener('click', async () => {
    if (!validateSampleForm()) return;

    saveSampleUpload.disabled = true;
    saveSampleUpload.textContent = 'Saving...';

    try {
        const formData = new FormData();
        formData.append('name', sampleNameInput.value.trim());

        currentSampleFiles.forEach((item, index) => {
            formData.append(`images[${index}]`, item.file);
        });

        existingSampleImages.forEach((img, index) => {
            formData.append(`existing_image_ids[${index}]`, img.id);
        });

        if (editSampleId) {
            await ProductSampleAPI.updateSample(editSampleId, formData);
            Toast.success('Product sample updated!');
        } else {
            await ProductSampleAPI.createSample(formData);
            Toast.success('Product sample added!');
        }

        addSampleModal.style.display = 'none';
        resetSampleModal();
        await loadSamples();
    } catch (error) {
        console.error('Save error:', error);
        Toast.error(error.message || 'Failed to save product sample');
    } finally {
        saveSampleUpload.disabled = false;
        saveSampleUpload.textContent = 'Save';
    }
});

// ================= RENDER =================
function renderSamples() {
    productSamplesWrapper.innerHTML = '';

    if (!sampleList || sampleList.length === 0) {
        emptySampleImagesText.style.display = 'block';
        return;
    }

    emptySampleImagesText.style.display = 'none';

    sampleList.forEach(sample => {
        const card = document.createElement('div');
        card.className = 'sample_card';

        const imgContainer = document.createElement('div');
        imgContainer.className = 'sample_card_image_container';

        if (sample.images && sample.images.length > 0) {
            const firstImage = sample.images[0];
            const img = document.createElement('img');
            img.src = `/storage/${firstImage.image_path}`;
            img.alt = sample.name;
            imgContainer.appendChild(img);

            if (sample.images.length > 1) {
                const countBadge = document.createElement('div');
                countBadge.className = 'sample_image_count';
                countBadge.textContent = `+${sample.images.length - 1}`;
                imgContainer.appendChild(countBadge);
            }
        }

        const title = document.createElement('h4');
        title.className = 'sample_card_title';
        title.textContent = sample.name;

        const editBtn = document.createElement('button');
        editBtn.className = 'edit_sample_btn';
        editBtn.textContent = 'Edit';
        editBtn.onclick = () => openEditModal(sample);

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'delete_sample_btn';
        deleteBtn.textContent = 'Delete';
        deleteBtn.onclick = () => confirmDelete(sample.id);

        const actions = document.createElement('div');
        actions.className = 'sample_card_actions';
        actions.appendChild(editBtn);
        actions.appendChild(deleteBtn);

        card.appendChild(imgContainer);
        card.appendChild(title);
        card.appendChild(actions);

        productSamplesWrapper.appendChild(card);
    });
}

function openEditModal(sample) {
    resetSampleModal();
    editSampleId = sample.id;
    sampleNameInput.value = sample.name;
    existingSampleImages = [...(sample.images || [])];
    document.querySelector('#addSampleModal h2').textContent = 'Edit Sample Product';
    populateSampleGrid();
    addSampleModal.style.display = 'flex';
}

function confirmDelete(id) {
    sampleToDelete = id;
    deleteConfirmModal.style.display = 'flex';
}

confirmDeleteSample.addEventListener('click', async () => {
    if (!sampleToDelete) return;

    confirmDeleteSample.disabled = true;
    confirmDeleteSample.textContent = 'Deleting...';

    try {
        await ProductSampleAPI.deleteSample(sampleToDelete);
        Toast.success('Product sample deleted');
        deleteConfirmModal.style.display = 'none';
        sampleToDelete = null;
        await loadSamples();
    } catch (error) {
        console.error('Delete error:', error);
        Toast.error(error.message || 'Failed to delete sample');
    } finally {
        confirmDeleteSample.disabled = false;
        confirmDeleteSample.textContent = 'Delete Sample';
    }
});

document.querySelector('.delete_confirm_modal').addEventListener('click', (e) => {
    if (e.target.className === 'delete_confirm_modal') {
        deleteConfirmModal.style.display = 'none';
        sampleToDelete = null;
    }
});