// ==================== PRODUCT STORE ====================

let products = [];
let editingProductId = null;
let pendingDeleteId = null;

// ==================== MODAL ====================

const modalOverlay = document.getElementById("templateModalOverlay");

document
    .getElementById("open_add_template_btn")
    .addEventListener("click", () => {
        editingProductId = null;
        document.getElementById("templateModalTitle").textContent =
            "Add New Template";
        document.getElementById("deleteProductBtn").classList.add("btn_hidden");
        openModal();
    });

function openModal() {
    modalOverlay.classList.add("active");
}
function closeModal() {
    modalOverlay.classList.remove("active");
}

modalOverlay.addEventListener("click", (e) => {
    if (e.target === modalOverlay) closeModal();
});

// ==================== SVG HELPER ====================

function createSVG(className, pathD) {
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.setAttribute("class", className);
    svg.setAttribute("height", "20px");
    svg.setAttribute("viewBox", "0 -960 960 960");
    svg.setAttribute("width", "20px");
    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
    path.setAttribute("d", pathD);
    svg.appendChild(path);
    return svg;
}

// ==================== VALIDATION HELPERS ====================

function setError(el, message) {
    el.classList.add("input_error");
    let msg = el.parentElement.querySelector(".input_error_msg");
    if (!msg) {
        msg = document.createElement("span");
        msg.className = "input_error_msg";
        el.parentElement.appendChild(msg);
    }
    msg.textContent = message;
    msg.classList.remove("hidden");
}

function clearError(el) {
    el.classList.remove("input_error");
    const msg = el.parentElement.querySelector(".input_error_msg");
    if (msg) msg.classList.add("hidden");
}

// ==================== NUMERIC-ONLY HELPER ====================

function enforceNumeric(input) {
    input.addEventListener("input", () => {
        input.value = input.value
            .replace(/[^0-9.]/g, "")
            .replace(/(\..*?)\..*/g, "$1");
    });
}

// ==================== PRODUCT NAME DROPDOWN ====================

/**
 * Populate the Product Name <select> from a list of product objects.
 * Preserves the currently selected value if it still exists.
 */
function populateProductSelect(productList) {
    const select = document.getElementById("productName");
    const currentVal = select.value;

    select.innerHTML = "";

    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "— Select a product —";
    placeholder.disabled = true;
    placeholder.selected = true;
    select.appendChild(placeholder);

    productList.forEach((p) => {
        const opt = document.createElement("option");
        opt.value = p.name;
        opt.textContent = p.name;
        select.appendChild(opt);
    });

    // Re-select previous value if it still exists in the list
    if (currentVal) {
        const match = Array.from(select.options).find(
            (o) => o.value === currentVal,
        );
        if (match) select.value = currentVal;
    }
}

/**
 * Fetch product names from the API and populate the select.
 * Falls back gracefully if the API is unavailable.
 */
async function loadProductNamesFromAPI() {
    try {
        const response = await fetch("/api/products", {
            headers: { Accept: "application/json" },
        });
        if (!response.ok) return;
        const data = await response.json();
        const productList = Array.isArray(data) ? data : (data.data ?? []);
        populateProductSelect(productList);
    } catch (err) {
        console.warn("Order template: could not load product names.", err);
    }
}

// Listen for products being saved/deleted on the same page (same-page sync)
window.addEventListener("productsUpdated", (e) => {
    populateProductSelect(e.detail ?? []);
});

// ==================== SPEC ROWS ====================

function createSpecLabelRow() {
    const row = document.createElement("div");
    row.className = "product_option_specification_container spec_label_row";

    const avail = document.createElement("div");
    avail.className = "product_availability";
    avail.appendChild(
        Object.assign(document.createElement("label"), {
            textContent: "Availability",
        }),
    );

    const optName = document.createElement("div");
    optName.className = "product_option_name";
    optName.appendChild(
        Object.assign(document.createElement("label"), {
            textContent: "Option Type",
        }),
    );

    const spacer = document.createElement("div");
    spacer.className =
        "product_option_specification_actions_container spec_label_spacer";

    row.appendChild(avail);
    row.appendChild(optName);
    row.appendChild(spacer);
    return row;
}

function createSpecRow() {
    const row = document.createElement("div");
    row.className = "product_option_specification_container spec_input_row";

    const avail = document.createElement("div");
    avail.className = "product_availability";
    const availInput = document.createElement("input");
    availInput.type = "checkbox";
    availInput.checked = true;
    avail.appendChild(availInput);

    const optName = document.createElement("div");
    optName.className = "product_option_name";
    const nameInput = document.createElement("input");
    nameInput.type = "text";
    nameInput.addEventListener("input", () => clearError(nameInput));
    optName.appendChild(nameInput);

    const actionsWrap = document.createElement("div");
    actionsWrap.className = "product_option_specification_actions_container";
    const actionsRow = document.createElement("div");
    actionsRow.className = "product_option_specification_actions";

    const addSvg = createSVG(
        "add_product_option_specification btn_hidden",
        "M444-288h72v-156h156v-72H516v-156h-72v156H288v72h156v156Zm36.28 192Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm-.28-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z",
    );

    const delSvg = createSVG(
        "delete_product_option_specification btn_hidden",
        "m339-288 141-141 141 141 51-51-141-141 141-141-51-51-141 141-141-141-51 51 141 141-141 141 51 51ZM480-96q-79 0-149-30t-122.5-82.5Q156-261 126-331T96-480q0-80 30-149.5t82.5-122Q261-804 331-834t149-30q80 0 149.5 30t122 82.5Q804-699 834-629.5T864-480q0 79-30 149t-82.5 122.5Q699-156 629.5-126T480-96Zm0-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z",
    );

    addSvg.addEventListener("click", () => {
        const wrapper = row.parentElement;
        row.after(createSpecRow());
        refreshSpecButtons(wrapper);
    });
    delSvg.addEventListener("click", () => {
        const wrapper = row.parentElement;
        if (wrapper.querySelectorAll(".spec_input_row").length <= 1) return;
        row.remove();
        refreshSpecButtons(wrapper);
    });

    actionsRow.appendChild(addSvg);
    actionsRow.appendChild(delSvg);
    actionsWrap.appendChild(actionsRow);
    row.appendChild(avail);
    row.appendChild(optName);
    row.appendChild(actionsWrap);
    return row;
}

function refreshSpecButtons(wrapper) {
    const rows = Array.from(wrapper.querySelectorAll(".spec_input_row"));
    rows.forEach((row, i) => {
        row.querySelector(".add_product_option_specification").classList.toggle(
            "btn_hidden",
            i !== rows.length - 1,
        );
        row.querySelector(
            ".delete_product_option_specification",
        ).classList.toggle("btn_hidden", rows.length <= 1);
    });
}

// ==================== PRODUCT OPTIONS ====================

function getOptionsWrapper() {
    return document.getElementById("productOptionsWrapper");
}
function getAllOptions() {
    return Array.from(
        getOptionsWrapper().querySelectorAll(
            ":scope > .product_option_container",
        ),
    );
}

function createOptionContainer(index) {
    const container = document.createElement("div");
    container.className = "product_option_container";

    const labelWrap = document.createElement("div");
    labelWrap.className = "product_option_label";
    const lbl = document.createElement("label");
    lbl.className = "product_option_header";
    lbl.textContent = `${index}. Product Option Label`;
    const input = document.createElement("input");
    input.type = "text";
    input.className = "product_option_input";
    input.addEventListener("input", () => clearError(input));
    labelWrap.appendChild(lbl);
    labelWrap.appendChild(input);

    const specWrapper = document.createElement("div");
    specWrapper.className = "spec_rows_wrapper";
    specWrapper.appendChild(createSpecLabelRow());
    specWrapper.appendChild(createSpecRow());
    refreshSpecButtons(specWrapper);

    const actions = document.createElement("div");
    actions.className = "product_option_actions";

    const addBtn = document.createElement("button");
    addBtn.className = "add_product_option btn_hidden";
    addBtn.textContent = "Add Option";
    addBtn.type = "button";

    const delBtn = document.createElement("button");
    delBtn.className = "delete_product_option btn_hidden";
    delBtn.textContent = "Delete Option";
    delBtn.type = "button";

    addBtn.addEventListener("click", () => {
        addBtn.classList.add("btn_hidden");
        container.after(createOptionContainer(getAllOptions().length + 1));
        refreshOptionButtons();
        renumberOptions();
    });
    delBtn.addEventListener("click", () => {
        if (getAllOptions().length <= 1) return;
        container.remove();
        refreshOptionButtons();
        renumberOptions();
    });

    actions.appendChild(delBtn);
    actions.appendChild(addBtn);
    container.appendChild(labelWrap);
    container.appendChild(specWrapper);
    container.appendChild(actions);
    return container;
}

function refreshOptionButtons() {
    const options = getAllOptions();
    options.forEach((opt, i) => {
        opt.querySelector(".add_product_option").classList.toggle(
            "btn_hidden",
            i !== options.length - 1,
        );
        opt.querySelector(".delete_product_option").classList.toggle(
            "btn_hidden",
            options.length <= 1,
        );
    });
}

function renumberOptions() {
    getAllOptions().forEach((opt, i) => {
        const lbl = opt.querySelector(".product_option_header");
        if (lbl) lbl.textContent = `${i + 1}. Product Option Label`;
    });
}

// ==================== TABS & VALIDATION ====================

function setupTabs() {
    document
        .getElementById("tab_details")
        .addEventListener("click", () => switchTab("details"));
    document.getElementById("tab_pricing").addEventListener("click", () => {
        if (!validateProductDetails()) return;
        switchTab("pricing");
        generateCombinations();
    });
}

function switchTab(tab) {
    const isDetails = tab === "details";
    document
        .getElementById("tab_details")
        .classList.toggle("modal_tab_active", isDetails);
    document
        .getElementById("tab_pricing")
        .classList.toggle("modal_tab_active", !isDetails);
    document
        .getElementById("panel_details")
        .classList.toggle("modal_panel_hidden", !isDetails);
    document
        .getElementById("panel_pricing")
        .classList.toggle("modal_panel_hidden", isDetails);
}

function validateProductDetails() {
    let valid = true;

    const nameSelect = document.getElementById("productName");
    if (!nameSelect.value || nameSelect.value.trim() === "") {
        setError(nameSelect, "Product name is required.");
        valid = false;
    } else {
        clearError(nameSelect);
    }

    getAllOptions().forEach((opt) => {
        const labelInput = opt.querySelector(".product_option_input");
        if (!labelInput.value.trim()) {
            setError(labelInput, "Option label is required.");
            valid = false;
        } else clearError(labelInput);

        opt.querySelectorAll(".spec_input_row").forEach((row) => {
            const typeInput = row.querySelector(".product_option_name input");
            if (!typeInput.value.trim()) {
                setError(typeInput, "Option type is required.");
                valid = false;
            } else clearError(typeInput);
        });
    });
    return valid;
}

// ==================== PRICING COMBINATIONS ====================

function cartesian(arrays) {
    return arrays.reduce(
        (acc, arr) =>
            acc.flatMap((combo) => arr.map((item) => [...combo, item])),
        [[]],
    );
}

function generateCombinations() {
    const container = document.getElementById("pricingCombinations");

    const savedData = {};
    container.querySelectorAll(".combination_row").forEach((row) => {
        const label = row.querySelector(".combination_label").textContent;
        savedData[label] = {
            price: row.querySelector(".combination_price_input").value,
        };
    });

    container.innerHTML = "";

    const groups = getAllOptions()
        .map((opt) =>
            Array.from(opt.querySelectorAll(".spec_input_row"))
                .map((row) =>
                    row
                        .querySelector(".product_option_name input")
                        .value.trim(),
                )
                .filter((v) => v !== ""),
        )
        .filter((group) => group.length > 0);

    if (groups.length === 0) return;

    cartesian(groups).forEach((combo) => {
        const labelText = combo.join(" | ");
        const row = document.createElement("div");
        row.className = "combination_row";

        const label = document.createElement("span");
        label.className = "combination_label";
        label.textContent = labelText;

        const priceInput = document.createElement("input");
        priceInput.type = "text";
        priceInput.className = "combination_price_input";
        priceInput.placeholder = "0.00";
        priceInput.addEventListener("input", () => {
            priceInput.value = priceInput.value
                .replace(/[^0-9.]/g, "")
                .replace(/(\..*?)\..*/g, "$1");
        });

        if (savedData[labelText]) {
            priceInput.value = savedData[labelText].price;
        }

        row.appendChild(label);
        row.appendChild(priceInput);
        container.appendChild(row);
    });
}

// ==================== DISCOUNT ROWS ====================

function createDiscountRow() {
    const row = document.createElement("div");
    row.className = "discount_row";

    const qtyWrap = document.createElement("div");
    qtyWrap.className = "product_discount_quantity";
    const qtyLbl = document.createElement("label");
    qtyLbl.textContent = "Min. Quantity (≥)";
    const qtyInput = document.createElement("input");
    qtyInput.type = "text";
    qtyInput.inputMode = "numeric";
    enforceNumeric(qtyInput);
    qtyWrap.appendChild(qtyLbl);
    qtyWrap.appendChild(qtyInput);

    const priceWrap = document.createElement("div");
    priceWrap.className = "product_discount_print";
    const priceLbl = document.createElement("label");
    priceLbl.textContent = "Price Reduction / pc";
    const priceInput = document.createElement("input");
    priceInput.type = "text";
    priceInput.inputMode = "decimal";
    enforceNumeric(priceInput);
    priceWrap.appendChild(priceLbl);
    priceWrap.appendChild(priceInput);

    const actionsWrap = document.createElement("div");
    actionsWrap.className = "discount_row_actions";

    const addSvg = createSVG(
        "add_discount_row_svg btn_hidden",
        "M444-288h72v-156h156v-72H516v-156h-72v156H288v72h156v156Zm36.28 192Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm-.28-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z",
    );

    const delSvg = createSVG(
        "delete_discount_row_svg btn_hidden",
        "m339-288 141-141 141 141 51-51-141-141 141-141-51-51-141 141-141-141-51 51 141 141-141 141 51 51ZM480-96q-79 0-149-30t-122.5-82.5Q156-261 126-331T96-480q0-80 30-149.5t82.5-122Q261-804 331-834t149-30q80 0 149.5 30t122 82.5Q804-699 834-629.5T864-480q0 79-30 149t-82.5 122.5Q699-156 629.5-126T480-96Zm0-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z",
    );

    addSvg.addEventListener("click", () => {
        row.after(createDiscountRow());
        refreshDiscountButtons(row.parentElement);
    });
    delSvg.addEventListener("click", () => {
        const wrapper = row.parentElement;
        if (wrapper.querySelectorAll(".discount_row").length <= 1) return;
        row.remove();
        refreshDiscountButtons(wrapper);
    });

    actionsWrap.appendChild(addSvg);
    actionsWrap.appendChild(delSvg);
    row.appendChild(qtyWrap);
    row.appendChild(priceWrap);
    row.appendChild(actionsWrap);
    return row;
}

function refreshDiscountButtons(wrapper) {
    const rows = Array.from(wrapper.querySelectorAll(".discount_row"));
    rows.forEach((row, i) => {
        row.querySelector(".add_discount_row_svg").classList.toggle(
            "btn_hidden",
            i !== rows.length - 1,
        );
        row.querySelector(".delete_discount_row_svg").classList.toggle(
            "btn_hidden",
            rows.length <= 1,
        );
    });
}

function setupDiscount() {
    const checkbox = document.getElementById("applyProductDiscount");
    const wrapper = document.getElementById("discountRowsWrapper");
    checkbox.addEventListener("change", () => {
        if (checkbox.checked) {
            wrapper.classList.remove("hidden");
            if (wrapper.querySelectorAll(".discount_row").length === 0) {
                wrapper.appendChild(createDiscountRow());
                refreshDiscountButtons(wrapper);
            }
        } else {
            wrapper.classList.add("hidden");
        }
    });
}

// ==================== COLLECT DATA ====================

function collectProductData() {
    const name = document.getElementById("productName").value.trim();

    const options = getAllOptions().map((opt) => ({
        label: opt.querySelector(".product_option_input").value.trim(),
        specs: Array.from(opt.querySelectorAll(".spec_input_row"))
            .map((row) => ({
                available: row.querySelector(".product_availability input")
                    .checked,
                type: row
                    .querySelector(".product_option_name input")
                    .value.trim(),
            }))
            .filter((s) => s.type !== ""),
    }));

    const combinations = {};
    document.querySelectorAll(".combination_row").forEach((row) => {
        const label = row.querySelector(".combination_label").textContent;
        combinations[label] = row
            .querySelector(".combination_price_input")
            .value.trim();
    });

    const discountEnabled = document.getElementById(
        "applyProductDiscount",
    ).checked;
    const discountRows = Array.from(
        document.querySelectorAll(".discount_row"),
    ).map((row) => ({
        qty: row.querySelector(".product_discount_quantity input").value.trim(),
        reduction: row
            .querySelector(".product_discount_print input")
            .value.trim(),
    }));

    return { name, options, combinations, discountEnabled, discountRows };
}

// ==================== SAVE ====================

function setupSaveButton() {
    document.querySelector(".save_product").addEventListener("click", () => {
        if (!validateProductDetails()) {
            switchTab("details");
            return;
        }
        const data = collectProductData();
        if (editingProductId !== null) {
            const idx = products.findIndex((p) => p.id === editingProductId);
            if (idx !== -1) products[idx] = { ...data, id: editingProductId };
        } else {
            data.id = Date.now();
            products.push(data);
        }
        renderProducts();
        resetModal();
        closeModal();
    });
}

// ==================== DELETE CONFIRMATION ====================

function setupDeleteButton() {
    const confirmOverlay = document.getElementById(
        "deleteTemplateModalOverlay",
    );

    document
        .getElementById("deleteProductBtn")
        .addEventListener("click", () => {
            if (editingProductId === null) return;
            pendingDeleteId = editingProductId;
            confirmOverlay.classList.add("active");
        });

    document
        .getElementById("deleteConfirmProceedBtn")
        .addEventListener("click", () => {
            if (pendingDeleteId === null) return;
            products = products.filter((p) => p.id !== pendingDeleteId);
            pendingDeleteId = null;
            confirmOverlay.classList.remove("active");
            renderProducts();
            resetModal();
            closeModal();
        });

    confirmOverlay.addEventListener("click", (e) => {
        if (e.target === confirmOverlay) {
            pendingDeleteId = null;
            confirmOverlay.classList.remove("active");
        }
    });
}

// ==================== RENDER PRODUCT CARDS ====================

function renderProducts() {
    const container = document.getElementById("productCardsContainer");
    const emptyMsg = document.getElementById("emptyOrderTemplate");
    container.innerHTML = "";
    if (products.length === 0) {
        emptyMsg.classList.remove("hidden");
        return;
    }
    emptyMsg.classList.add("hidden");
    products.forEach((product) =>
        container.appendChild(createProductCard(product)),
    );
}

function createProductCard(product) {
    const card = document.createElement("div");
    card.className = "product_card";

    const name = document.createElement("h3");
    name.className = "product_card_name";
    name.textContent = product.name;
    card.appendChild(name);

    const optionsDiv = document.createElement("div");
    optionsDiv.className = "product_card_options";
    const selects = [];

    product.options.forEach((opt) => {
        const wrap = document.createElement("div");
        wrap.className = "product_card_option_wrap";

        const lbl = document.createElement("label");
        lbl.className = "product_card_option_label";
        lbl.textContent = opt.label;

        const selectWrap = document.createElement("div");
        selectWrap.className = "select_wrapper";

        const select = document.createElement("select");
        select.className = "product_card_select";
        select.addEventListener("click", (e) => e.stopPropagation());

        opt.specs
            .filter((s) => s.available)
            .forEach((spec) => {
                const option = document.createElement("option");
                option.value = spec.type;
                option.textContent = spec.type;
                select.appendChild(option);
            });

        selects.push(select);
        selectWrap.appendChild(select);
        wrap.appendChild(lbl);
        wrap.appendChild(selectWrap);
        optionsDiv.appendChild(wrap);
    });

    card.appendChild(optionsDiv);

    const priceDiv = document.createElement("div");
    priceDiv.className = "product_card_price";

    function updateCardPrice() {
        const key = selects.map((s) => s.value).join(" | ");
        const price = product.combinations[key];
        priceDiv.textContent = price ? `₱${parseFloat(price).toFixed(2)}` : "—";
    }
    selects.forEach((s) => s.addEventListener("change", updateCardPrice));
    updateCardPrice();
    card.appendChild(priceDiv);

    const editBtn = document.createElement("button");
    editBtn.className = "product_card_edit_btn";
    editBtn.textContent = "Edit";
    editBtn.type = "button";
    editBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        openEditModal(product.id);
    });
    card.appendChild(editBtn);

    card.addEventListener("click", () => openDetailModal(product.id));
    return card;
}

// ==================== EDIT MODAL ====================

function openEditModal(productId) {
    const product = products.find((p) => p.id === productId);
    if (!product) return;
    editingProductId = productId;

    document.getElementById("templateModalTitle").textContent = "Edit Template";
    document.getElementById("deleteProductBtn").classList.remove("btn_hidden");

    const nameSelect = document.getElementById("productName");
    nameSelect.value = product.name;

    const wrapper = getOptionsWrapper();
    wrapper.innerHTML = "";
    product.options.forEach((opt, i) => {
        const container = createOptionContainer(i + 1);
        wrapper.appendChild(container);
        container.querySelector(".product_option_input").value = opt.label;
        const specWrapper = container.querySelector(".spec_rows_wrapper");
        specWrapper
            .querySelectorAll(".spec_input_row")
            .forEach((r) => r.remove());
        opt.specs.forEach((spec) => {
            const row = createSpecRow();
            row.querySelector(".product_availability input").checked =
                spec.available;
            row.querySelector(".product_option_name input").value = spec.type;
            specWrapper.appendChild(row);
        });
        refreshSpecButtons(specWrapper);
    });
    refreshOptionButtons();

    const combContainer = document.getElementById("pricingCombinations");
    combContainer.innerHTML = "";
    Object.entries(product.combinations).forEach(([labelText, data]) => {
        const row = document.createElement("div");
        row.className = "combination_row";
        const label = document.createElement("span");
        label.className = "combination_label";
        label.textContent = labelText;
        const priceInput = document.createElement("input");
        priceInput.type = "text";
        priceInput.className = "combination_price_input";
        priceInput.value = data || "";
        priceInput.addEventListener("input", () => {
            priceInput.value = priceInput.value
                .replace(/[^0-9.]/g, "")
                .replace(/(\..*?)\..*/g, "$1");
        });
        row.appendChild(label);
        row.appendChild(priceInput);
        combContainer.appendChild(row);
    });

    const checkbox = document.getElementById("applyProductDiscount");
    checkbox.checked = product.discountEnabled;
    const discountWrapper = document.getElementById("discountRowsWrapper");
    discountWrapper.innerHTML = "";
    if (product.discountEnabled) {
        discountWrapper.classList.remove("hidden");
        product.discountRows.forEach((dr) => {
            const row = createDiscountRow();
            row.querySelector(".product_discount_quantity input").value =
                dr.qty;
            row.querySelector(".product_discount_print input").value =
                dr.reduction;
            discountWrapper.appendChild(row);
        });
        refreshDiscountButtons(discountWrapper);
    } else {
        discountWrapper.classList.add("hidden");
    }

    switchTab("details");
    document
        .querySelectorAll(".input_error")
        .forEach((el) => el.classList.remove("input_error"));
    document
        .querySelectorAll(".input_error_msg")
        .forEach((el) => el.classList.add("hidden"));
    openModal();
}

// ==================== DETAIL MODAL ====================

const detailOverlay = document.getElementById("detailModalOverlay");

document.getElementById("detailCloseBtn").addEventListener("click", () => {
    detailOverlay.classList.remove("active");
});
detailOverlay.addEventListener("click", (e) => {
    if (e.target === detailOverlay) detailOverlay.classList.remove("active");
});

function openDetailModal(productId) {
    const product = products.find((p) => p.id === productId);
    if (!product) return;

    document.getElementById("detailProductName").textContent = product.name;

    const optContainer = document.getElementById("detailOptionsContainer");
    optContainer.innerHTML = "";
    const selects = [];

    product.options.forEach((opt) => {
        const wrap = document.createElement("div");
        wrap.className = "detail_option_wrap";
        const lbl = document.createElement("label");
        lbl.className = "detail_option_label";
        lbl.textContent = opt.label;
        const selectWrap = document.createElement("div");
        selectWrap.className = "select_wrapper";
        const select = document.createElement("select");
        select.className = "detail_select";
        opt.specs
            .filter((s) => s.available)
            .forEach((spec) => {
                const option = document.createElement("option");
                option.value = spec.type;
                option.textContent = spec.type;
                select.appendChild(option);
            });
        selects.push(select);
        selectWrap.appendChild(select);
        wrap.appendChild(lbl);
        wrap.appendChild(selectWrap);
        optContainer.appendChild(wrap);
    });

    const priceEl = document.getElementById("detailPriceValue");

    function updateDetailPrice() {
        const key = selects.map((s) => s.value).join(" | ");
        const price = product.combinations[key];
        priceEl.textContent = price ? `₱${parseFloat(price).toFixed(2)}` : "—";
    }
    selects.forEach((s) => s.addEventListener("change", updateDetailPrice));
    updateDetailPrice();

    detailOverlay.classList.add("active");
}

// ==================== CANCEL ====================

function setupCancelButton() {
    document.querySelector(".cancel_product").addEventListener("click", () => {
        resetModal();
        closeModal();
    });
}

function resetModal() {
    const nameSelect = document.getElementById("productName");
    nameSelect.value = "";
    editingProductId = null;
    document.getElementById("templateModalTitle").textContent =
        "Add New Template";
    document.getElementById("deleteProductBtn").classList.add("btn_hidden");
    switchTab("details");
    document
        .querySelectorAll(".input_error")
        .forEach((el) => el.classList.remove("input_error"));
    document
        .querySelectorAll(".input_error_msg")
        .forEach((el) => el.classList.add("hidden"));
    document.getElementById("applyProductDiscount").checked = false;
    const discountWrapper = document.getElementById("discountRowsWrapper");
    discountWrapper.classList.add("hidden");
    discountWrapper.innerHTML = "";
    const wrapper = getOptionsWrapper();
    wrapper.innerHTML = "";
    wrapper.appendChild(createOptionContainer(1));
    refreshOptionButtons();
    document.getElementById("pricingCombinations").innerHTML = "";
}

// ==================== INIT ====================

function initModal() {
    const nameSelect = document.getElementById("productName");
    nameSelect.addEventListener("change", () => clearError(nameSelect));

    getOptionsWrapper().appendChild(createOptionContainer(1));
    refreshOptionButtons();
    setupTabs();
    setupDiscount();
    setupCancelButton();
    setupSaveButton();
    setupDeleteButton();

    // Load product names into the dropdown from the API
    loadProductNamesFromAPI();
}

document.addEventListener("DOMContentLoaded", initModal);
