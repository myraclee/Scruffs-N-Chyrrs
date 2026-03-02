// ================= ELEMENTS =================
const products_add_btn = document.getElementById('products_add_btn');
const products_modal = document.getElementById('products_modal');
const products_title_input = document.getElementById('products_title_input');

const products_main_add_box = document.getElementById('products_main_add_box');
const products_main_image_preview = document.getElementById('products_main_image_preview');
const products_remove_main_image_btn = document.getElementById('products_remove_main_image_btn');

const products_price_images_wrapper = document.getElementById('products_price_images_wrapper');

const products_cancel_btn = document.getElementById('products_cancel_btn');
const products_save_btn = document.getElementById('products_save_btn');
const products_delete_btn = document.getElementById('products_delete_btn');

const products_container = document.getElementById('products_container');
const products_no_items_text = document.getElementById('products_no_items_text');

const products_delete_confirm_modal = document.getElementById('products_delete_confirm_modal');
const products_confirm_delete_btn = document.getElementById('products_confirm_delete_btn');

const products_price_viewer_modal = document.getElementById('products_price_viewer_modal');
const products_price_viewer_image = document.getElementById('products_price_viewer_image');
const products_prev_price_image = document.getElementById('products_prev_price_image');
const products_next_price_image = document.getElementById('products_next_price_image');
const products_viewer_title = document.getElementById('products_viewer_title');

const products_title_error = document.getElementById('products_title_error');
const products_cover_error = document.getElementById('products_cover_error');
const products_prices_error = document.getElementById('products_prices_error');

// ================= STATE =================
let products_list = [];
let products_edit_index = null;
let products_current_viewer_index = 0;
let products_has_cover = false; // COVER REQUIRED

// ================= COVER IMAGE =================
products_main_add_box.addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/png, image/jpg, image/jpeg';

    input.onchange = e => {
        const file = e.target.files[0];
        if (!file) return;

        products_main_image_preview.src = URL.createObjectURL(file);
        products_main_image_preview.style.display = 'block';
        products_main_image_preview.style.objectFit = 'cover';

        products_main_add_box.style.display = 'none';
        products_remove_main_image_btn.style.display = 'block';

        products_has_cover = true;
        products_cover_error.textContent = '';
    };

    input.click();
});

products_remove_main_image_btn.addEventListener('click', () => {
    products_main_image_preview.src = '';
    products_main_image_preview.style.display = 'none';
    products_remove_main_image_btn.style.display = 'none';
    products_main_add_box.style.display = 'flex';

    products_has_cover = false;
});

// ================= PRICE IMAGES =================
function createPriceBox(existingSrc = null) {
    const wrapper = document.createElement('div'); // NEW wrapper
    wrapper.className = 'products_price_box_wrapper';
    wrapper.style.display = 'flex';
    wrapper.style.flexDirection = 'column';
    wrapper.style.alignItems = 'center';

    const box = document.createElement('div');
    box.className = 'products_price_box';
    box.style.flexDirection = 'column';

    if (!existingSrc) {
        box.textContent = '+';
    } else {
        const img = document.createElement('img');
        img.src = existingSrc;
        img.style.objectFit = 'cover';
        box.appendChild(img);

        const removeBtn = document.createElement('button');
        removeBtn.textContent = 'Remove';
        removeBtn.className = 'products_button_remove';
        removeBtn.onclick = () => {
            wrapper.remove();
            createAddPriceBox();
        };
        wrapper.appendChild(box);
        wrapper.appendChild(removeBtn);
        return wrapper;
    }

    box.addEventListener('click', () => {
        if (box.querySelector('img')) return;

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png, image/jpg, image/jpeg';

        input.onchange = e => {
            const file = e.target.files[0];
            if (!file) return;

            box.innerHTML = '';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.objectFit = 'cover';
            box.appendChild(img);

            const removeBtn = document.createElement('button');
            removeBtn.textContent = 'Remove';
            removeBtn.className = 'products_button_remove';
            removeBtn.onclick = () => {
                wrapper.remove();
                createAddPriceBox();
            };

            wrapper.appendChild(box);
            wrapper.appendChild(removeBtn);
            products_prices_error.textContent = '';
            createAddPriceBox();
        };

        input.click();
    });

    wrapper.appendChild(box);
    return wrapper;
}

function createAddPriceBox() {
    const hasAddBox = [...products_price_images_wrapper.children]
        .some(wrapper => wrapper.querySelector('.products_price_box').textContent === '+');

    if (!hasAddBox) {
        products_price_images_wrapper.appendChild(createPriceBox());
    }
}

createAddPriceBox();

// ================= MODAL OPEN / CLOSE =================
products_add_btn.addEventListener('click', () => {
    products_reset_modal();
    products_delete_btn.style.display = 'none';
    products_modal.style.display = 'flex';
});

window.addEventListener('click', e => {
    if (
        e.target === products_modal ||
        e.target === products_price_viewer_modal ||
        e.target === products_delete_confirm_modal
    ) {
        e.target.style.display = 'none';
        if (e.target === products_modal) products_reset_modal();
    }
});

products_cancel_btn.addEventListener('click', () => {
    products_modal.style.display = 'none';
    products_reset_modal();
});

// ================= SAVE =================
products_save_btn.addEventListener('click', () => {
    let hasError = false;

    if (!products_title_input.value.trim()) {
        products_title_error.textContent = 'Product title is required';
        hasError = true;
    } else {
        products_title_error.textContent = '';
    }

    if (!products_has_cover) {
        products_cover_error.textContent = 'Cover image is required';
        hasError = true;
    } else {
        products_cover_error.textContent = '';
    }

    const priceImages = [...products_price_images_wrapper.querySelectorAll('img')].map(i => i.src);
    if (!priceImages.length) {
        products_prices_error.textContent = 'At least one price image is required';
        hasError = true;
    } else {
        products_prices_error.textContent = '';
    }

    if (hasError) return;

    const product = {
        title: products_title_input.value,
        mainImage: products_main_image_preview.src,
        priceImages
    };

    if (products_edit_index !== null) {
        products_list[products_edit_index] = product;
    } else {
        products_list.push(product);
    }

    renderProducts();
    products_modal.style.display = 'none';
    products_reset_modal();
});

// ================= RENDER PRODUCTS =================
function renderProducts() {
    products_container.innerHTML = '';

    if (!products_list.length) {
        products_container.appendChild(products_no_items_text);
        return;
    }

    products_list.forEach((product, index) => {
        const card = document.createElement('div');
        card.className = 'products_card';

        const img = document.createElement('img');
        img.src = product.mainImage;
        img.onclick = () => products_view_price_images(index);

        const title = document.createElement('p');
        title.textContent = product.title;

        const editBtn = document.createElement('button');
        editBtn.textContent = 'Edit';
        editBtn.className = 'products_button_save';
        editBtn.onclick = () => editProduct(index);

        card.append(img, title, editBtn);
        products_container.appendChild(card);
    });
}

// ================= EDIT PRODUCT =================
function editProduct(index) {
    const product = products_list[index];
    products_edit_index = index;

    products_title_input.value = product.title;

    products_main_image_preview.src = product.mainImage;
    products_main_image_preview.style.display = 'block';
    products_main_image_preview.style.objectFit = 'cover';
    products_main_add_box.style.display = 'none';
    products_remove_main_image_btn.style.display = 'block';
    products_has_cover = true;

    products_price_images_wrapper.innerHTML = '';
    product.priceImages.forEach(src => {
        products_price_images_wrapper.appendChild(createPriceBox(src));
    });

    createAddPriceBox();
    products_delete_btn.style.display = 'block';
    products_modal.style.display = 'flex';
}

// ================= DELETE =================
products_delete_btn.addEventListener('click', (e) => {
    e.stopPropagation(); // ⬅️ prevents window click from firing
    products_delete_confirm_modal.style.display = 'flex';
});

products_confirm_delete_btn.addEventListener('click', () => {
    products_list.splice(products_edit_index, 1);
    renderProducts();
    products_delete_confirm_modal.style.display = 'none';
    products_reset_modal();
});

// ================= PRICE VIEWER =================
function products_view_price_images(index) {
    const product = products_list[index];
    products_current_viewer_index = 0;

    products_viewer_title.textContent = product.title;
    products_price_viewer_image.src = product.priceImages[0];
    products_price_viewer_image.style.objectFit = 'contain';

    products_price_viewer_modal.style.display = 'flex';

    if (product.priceImages.length <= 1) {
        products_prev_price_image.style.display = 'none';
        products_next_price_image.style.display = 'none';
    } else {
        products_prev_price_image.style.display = 'block';
        products_next_price_image.style.display = 'block';
    }

    products_prev_price_image.onclick = () => {
        products_current_viewer_index =
            (products_current_viewer_index - 1 + product.priceImages.length) % product.priceImages.length;
        products_price_viewer_image.src = product.priceImages[products_current_viewer_index];
    };

    products_next_price_image.onclick = () => {
        products_current_viewer_index =
            (products_current_viewer_index + 1) % product.priceImages.length;
        products_price_viewer_image.src = product.priceImages[products_current_viewer_index];
    };
}

// ================= RESET =================
function products_reset_modal() {
    products_edit_index = null;
    products_has_cover = false;

    products_title_input.value = '';
    products_title_error.textContent = '';
    products_cover_error.textContent = '';
    products_prices_error.textContent = '';

    products_main_image_preview.src = '';
    products_main_image_preview.style.display = 'none';
    products_remove_main_image_btn.style.display = 'none';
    products_main_add_box.style.display = 'flex';

    products_price_images_wrapper.innerHTML = '';
    createAddPriceBox();
}