/**
 * Product Samples Modal Management
 * Handles CRUD operations for product samples with images
 */
import ProductSampleAPI from '../../api/productSampleApi.js';
import Toast from '../../utils/toast.js';

document.addEventListener('DOMContentLoaded', async () => {
    const addSampleBtn = document.querySelector('.add_sample');
    const modal = document.getElementById('addSampleModal');
    const deleteModal = document.getElementById('deleteConfirmModal');

    const grid = document.getElementById('sampleImageGrid');
    const counter = document.getElementById('sampleImageCounter');
    const cancelBtn = document.getElementById('cancelSampleUpload');
    const saveBtn = document.getElementById('saveSampleUpload');
    const nameInput = document.getElementById('sampleNameInput');

    const nameError = document.getElementById('sampleNameError');
    const imageError = document.getElementById('sampleImageError');

    const confirmDeleteBtn = document.getElementById('confirmDeleteSample');

    const emptyText = document.querySelector('.empty_sample_images');
    const samplesWrapper = document.querySelector('.product_samples_wrapper');

    const MAX = 5;

    let samples = [];
    let tempSample = null;
    let editIndex = null;

    // Load existing samples from API on page load
    try {
        const loadedSamples = await ProductSampleAPI.getAllSamples();
        samples = loadedSamples.map(sample => ({
            id: sample.id,
            name: sample.name,
            description: sample.description,
            images: (sample.images || []).map(img => ({
                id: img.id,
                image_path: img.image_path,
                preview: `/storage/${img.image_path}`,
                sort_order: img.sort_order
            }))
        }));
        renderSamples();
    } catch (error) {
        console.error('Failed to load product samples:', error);
        Toast.error('Failed to load product samples');
    }

    addSampleBtn.addEventListener('click', () => {
        tempSample = { id: null, name: '', description: '', images: [] };
        editIndex = null;
        nameInput.value = '';
        hideErrors();
        renderGrid();
        modal.style.display = 'flex';
        removeDeleteButton();
    });

    function closeModal() {
        modal.style.display = 'none';
        tempSample = null;
        editIndex = null;
        hideErrors();
    }

    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', e => {
        if (e.target === modal) closeModal();
    });

    saveBtn.addEventListener('click', async () => {
        hideErrors();

        let valid = true;

        if (!nameInput.value.trim()) {
            nameError.style.display = 'block';
            valid = false;
        }

        if (tempSample.images.length === 0) {
            imageError.style.display = 'block';
            valid = false;
        }

        if (!valid) return;

        try {
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            tempSample.name = nameInput.value.trim();

            // Separate new images (without id) from existing ones
            const newImages = tempSample.images.filter(img => !img.id && img.file);

            if (editIndex === null) {
                // Create new sample
                const formData = new FormData();
                formData.append('name', tempSample.name);
                formData.append('description', tempSample.description || '');

                newImages.forEach((img, idx) => {
                    formData.append(`images[${idx}]`, img.file);
                });

                const createdSample = await ProductSampleAPI.createSample(formData);
                createdSample.images = createdSample.images.map(img => ({
                    id: img.id,
                    image_path: img.image_path,
                    preview: `/storage/${img.image_path}`,
                    sort_order: img.sort_order
                }));
                samples.push(createdSample);
                Toast.success('Product sample created successfully!');
            } else {
                // Update existing sample
                const sampleToUpdate = samples[editIndex];
                const formData = new FormData();
                formData.append('name', tempSample.name);
                formData.append('description', tempSample.description || '');

                newImages.forEach((img, idx) => {
                    formData.append(`images[${idx}]`, img.file);
                });

                const updatedSample = await ProductSampleAPI.updateSample(sampleToUpdate.id, formData);
                updatedSample.images = updatedSample.images.map(img => ({
                    id: img.id,
                    image_path: img.image_path,
                    preview: `/storage/${img.image_path}`,
                    sort_order: img.sort_order
                }));
                samples[editIndex] = updatedSample;
                Toast.success('Product sample updated successfully!');
            }

            renderSamples();
            closeModal();
        } catch (error) {
            console.error('Error saving product sample:', error);
            Toast.error(error.message || 'Failed to save product sample');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        }
    });

    function renderSamples() {
        samplesWrapper.innerHTML = '';

        if (samples.length === 0) {
            emptyText.style.display = 'block';
            return;
        }

        emptyText.style.display = 'none';

        samples.forEach((sample, index) => {
            const card = document.createElement('div');
            card.className = 'product_sample_main_container';

            const firstImagePreview = sample.images.length > 0
                ? sample.images[0].preview
                : '/images/placeholder.png';

            card.innerHTML = `
                <div class="product_sample_container">
                    <img src="${firstImagePreview}">
                    <div class="product_sample_container_name">
                        <h3>${sample.name}</h3>
                    </div>
                </div>
                <button class="edit_sample">Edit</button>
            `;

            card.querySelector('.edit_sample').onclick = () => openEdit(index);
            samplesWrapper.appendChild(card);
        });
    }

    function openEdit(index) {
        editIndex = index;
        tempSample = {
            id: samples[index].id,
            name: samples[index].name,
            description: samples[index].description,
            images: samples[index].images.map(img => ({ ...img }))
        };

        nameInput.value = tempSample.name;
        hideErrors();
        renderGrid();
        modal.style.display = 'flex';

        injectDeleteButton();
    }

    function removeDeleteButton() {
        const deleteBtn = document.getElementById('deleteSampleUpload');
        if (deleteBtn) {
            deleteBtn.remove();
        }
    }

    function injectDeleteButton() {
        let deleteBtn = document.getElementById('deleteSampleUpload');

        if (!deleteBtn) {
            deleteBtn = document.createElement('button');
            deleteBtn.className = 'left_sample_actions';
            deleteBtn.id = 'deleteSampleUpload';
            deleteBtn.textContent = 'Delete';
            deleteBtn.style.background = '#C83333';
            deleteBtn.style.color = '#fff';

            deleteBtn.onclick = () => {
                deleteModal.style.display = 'flex';
            };

            document.querySelector('.sample_image_actions')
                .prepend(deleteBtn);
        }
    }

    confirmDeleteBtn.addEventListener('click', async () => {
        try {
            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.textContent = 'Deleting...';

            const sampleToDelete = samples[editIndex];
            await ProductSampleAPI.deleteSample(sampleToDelete.id);

            samples.splice(editIndex, 1);
            renderSamples();
            deleteModal.style.display = 'none';
            closeModal();
            Toast.success('Product sample deleted successfully!');
        } catch (error) {
            console.error('Error deleting product sample:', error);
            Toast.error(error.message || 'Failed to delete product sample');
        } finally {
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.textContent = 'Delete';
        }
    });

    deleteModal.addEventListener('click', e => {
        if (e.target === deleteModal) deleteModal.style.display = 'none';
    });

    function renderGrid() {
        grid.innerHTML = '';

        tempSample.images.forEach((imgObj, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'sample_image_slot_wrapper';

            const slot = document.createElement('div');
            slot.className = 'sample_image_slot';

            const img = document.createElement('img');
            img.src = imgObj.preview;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';

            slot.appendChild(img);

            const removeBtn = document.createElement('button');
            removeBtn.textContent = 'Remove';
            removeBtn.onclick = () => {
                tempSample.images.splice(index, 1);
                renderGrid();
            };

            wrapper.append(slot, removeBtn);
            grid.appendChild(wrapper);
        });

        if (tempSample.images.length < MAX) addPlusSlot();
        counter.textContent = `${tempSample.images.length} / ${MAX} images selected`;
    }

    function addPlusSlot() {
        const wrapper = document.createElement('div');
        wrapper.className = 'sample_image_slot_wrapper';

        const slot = document.createElement('div');
        slot.className = 'sample_image_slot plus';

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.png,.jpg,.jpeg,.gif,.webp';
        input.hidden = true;

        slot.onclick = () => input.click();

        input.onchange = () => {
            const file = input.files[0];
            if (!file || tempSample.images.length >= MAX) return;

            const reader = new FileReader();
            reader.onload = () => {
                tempSample.images.push({
                    id: null,
                    file: file,
                    preview: reader.result
                });
                renderGrid();
            };
            reader.readAsDataURL(file);
        };

        wrapper.append(slot, input);
        grid.appendChild(wrapper);
    }

    function hideErrors() {
        nameError.style.display = 'none';
        imageError.style.display = 'none';
    }
});