document.addEventListener('DOMContentLoaded', () => {
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

    addSampleBtn.addEventListener('click', () => {
        tempSample = { name: '', images: [] };
        editIndex = null;
        nameInput.value = '';
        hideErrors();
        renderGrid();
        modal.style.display = 'flex';
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

    saveBtn.addEventListener('click', () => {
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

        tempSample.name = nameInput.value.trim();

        if (editIndex === null) {
            samples.push(tempSample);
        } else {
            samples[editIndex] = tempSample;
        }

        renderSamples();
        closeModal();
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

            card.innerHTML = `
                <div class="product_sample_container">
                    <img src="${sample.images[0].preview}">
                    <h3>${sample.name}</h3>
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
            name: samples[index].name,
            images: [...samples[index].images]
        };

        nameInput.value = tempSample.name;
        hideErrors();
        renderGrid();
        modal.style.display = 'flex';

        injectDeleteButton();
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

    confirmDeleteBtn.addEventListener('click', () => {
        samples.splice(editIndex, 1);
        renderSamples();
        deleteModal.style.display = 'none';
        closeModal();
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
        input.accept = 'image/*';
        input.hidden = true;

        slot.onclick = () => input.click();

        input.onchange = () => {
            const file = input.files[0];
            if (!file || tempSample.images.length >= MAX) return;

            const reader = new FileReader();
            reader.onload = () => {
                tempSample.images.push({ file, preview: reader.result });
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