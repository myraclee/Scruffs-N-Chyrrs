document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('homeImageGrid');
    const counter = document.getElementById('homeImageCounter');
    const cancelBtn = document.getElementById('cancelUpload');
    const modal = document.getElementById('editHomeImagesModal');
    const editHomeImageBtn = document.getElementById('editHomeImage');
    const saveBtn = document.querySelector('.home_image_save');

    const mainUploads = document.querySelector('.home_images_uploads');
    const emptyText = document.querySelector('.empty_home_images');

    const MAX = 5;

    let savedImages = [];
    let tempImages = []; 

    editHomeImageBtn.addEventListener('click', () => {
        tempImages = savedImages.map(img => ({ ...img }));
        renderGrid();
        modal.style.display = 'flex';
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.style.display = 'none';
    });

    function updateCounter() {
        counter.textContent = `${tempImages.length} / ${MAX} images selected`;
    }

    function renderGrid() {
        grid.innerHTML = '';

        tempImages.forEach((fileObj, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'add_home_image_slot_wrapper';

            const slot = document.createElement('div');
            slot.className = 'image_slot';

            const img = document.createElement('img');
            img.src = fileObj.preview;
            slot.appendChild(img);

            const clearBtn = document.createElement('button');
            clearBtn.className = 'clear_image';
            clearBtn.textContent = 'Remove';
            clearBtn.onclick = () => {
                tempImages.splice(index, 1);
                renderGrid();
            };

            wrapper.append(slot, clearBtn);
            grid.appendChild(wrapper);
        });

        if (tempImages.length < MAX) addPlusSlot();
        updateCounter();
    }

    function addPlusSlot() {
        const wrapper = document.createElement('div');
        wrapper.className = 'add_home_image_slot_wrapper';

        const slot = document.createElement('div');
        slot.className = 'image_slot plus';

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.jpg,.jpeg,.png';
        input.hidden = true;

        slot.onclick = () => input.click();

        input.onchange = () => {
            const file = input.files[0];
            if (!file || tempImages.length >= MAX) return;

            const reader = new FileReader();
            reader.onload = () => {
                tempImages.push({ file, preview: reader.result });
                renderGrid();
            };
            reader.readAsDataURL(file);
        };

        wrapper.append(slot, input);
        grid.appendChild(wrapper);
    }

    function renderMainImages() {
        mainUploads.innerHTML = '';

        if (savedImages.length === 0) {
            emptyText.style.display = 'block';
            return;
        }

        emptyText.style.display = 'none';

        savedImages.forEach(imgObj => {
            const box = document.createElement('div');
            box.className = 'home_image_item';

            const img = document.createElement('img');
            img.src = imgObj.preview;

            box.appendChild(img);
            mainUploads.appendChild(box);
        });
    }

    saveBtn.onclick = () => {
        savedImages = tempImages.map(img => ({ ...img }));
        renderMainImages();
        modal.style.display = 'none';
    };

    cancelBtn.onclick = () => {
        tempImages = [];
        modal.style.display = 'none';
    };
});