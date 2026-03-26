// ================= IMPORTS =================
import orderTemplateApi from "../../api/orderTemplateApi.js";
import toast from "../../utils/toast.js";

// ==================== PRODUCT STORE ====================

let products = [];
let editingProductId = null;
let pendingDeleteId = null;
let isLoading = false;
let currentTab = "details"; // "details" | "pricing" | "additional_fees"

// ==================== MODAL ====================

const modalOverlay = document.getElementById("templateModalOverlay");

document
    .getElementById("open_add_template_btn")
    .addEventListener("click", () => {
        editingProductId = null;
        document.getElementById("templateModalTitle").textContent =
            "Add New Order Template";
        document.getElementById("deleteProductBtn").classList.add("btn_hidden");
        openModal();
    });

function openModal() {
    modalOverlay.classList.add("active");
}
function closeModal() {
    modalOverlay.classList.remove("active");
}

/*
 * Change #4: Outside-area click no longer closes the template modal.
 * The modal can only be closed via the Cancel button.
 * (The delete confirmation modal still closes on outside click — see setupDeleteButton.)
 */

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

    if (currentVal) {
        const match = Array.from(select.options).find(
            (o) => o.value === currentVal,
        );
        if (match) select.value = currentVal;
    }
}

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
    nameInput.addEventListener("input", () => {
        clearError(nameInput);
        updateTabLockStates();
    });
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
        updateTabLockStates();
    });
    delSvg.addEventListener("click", () => {
        const wrapper = row.parentElement;
        if (wrapper.querySelectorAll(".spec_input_row").length <= 1) return;
        row.remove();
        refreshSpecButtons(wrapper);
        updateTabLockStates();
    });

    actionsRow.appendChild(delSvg);
    actionsRow.appendChild(addSvg);
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
    input.addEventListener("input", () => {
        clearError(input);
        updateTabLockStates();
    });
    labelWrap.appendChild(lbl);
    labelWrap.appendChild(input);

    const specWrapper = document.createElement("div");
    specWrapper.className = "spec_rows_wrapper";

    const specHeaderDiv = document.createElement("div");
    specHeaderDiv.className = "spec_header_div";
    specHeaderDiv.appendChild(createSpecLabelRow());

    const specInputsDiv = document.createElement("div");
    specInputsDiv.className = "spec_inputs_div";
    specInputsDiv.appendChild(createSpecRow());

    specWrapper.appendChild(specHeaderDiv);
    specWrapper.appendChild(specInputsDiv);
    refreshSpecButtons(specInputsDiv);

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
        updateTabLockStates();
    });
    delBtn.addEventListener("click", () => {
        if (getAllOptions().length <= 1) return;
        container.remove();
        refreshOptionButtons();
        renumberOptions();
        updateTabLockStates();
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

// ==================== SILENT VALIDATORS (no error display) ====================

function isDetailsValid() {
    const nameSelect = document.getElementById("productName");
    if (!nameSelect.value || !nameSelect.value.trim()) return false;
    for (const opt of getAllOptions()) {
        const labelInput = opt.querySelector(".product_option_input");
        if (!labelInput.value.trim()) return false;
        for (const row of opt.querySelectorAll(".spec_input_row")) {
            const typeInput = row.querySelector(".product_option_name input");
            if (!typeInput.value.trim()) return false;
        }
    }
    return true;
}

function isPricingValid() {
    const inputs = document.querySelectorAll(".combination_price_input");
    if (inputs.length === 0) return false;
    for (const input of inputs) {
        const val = input.value.trim();
        if (!val || isNaN(parseFloat(val))) return false;
    }
    return true;
}

// ==================== TAB LOCK STATE ====================

function updateTabLockStates() {
    const detailsOk = isDetailsValid();
    const pricingOk = detailsOk && isPricingValid();

    document
        .getElementById("tab_pricing")
        .classList.toggle("tab_locked", !detailsOk);
    document
        .getElementById("tab_additional_fees")
        .classList.toggle("tab_locked", !pricingOk);
}

// ==================== TABS & NAVIGATION ====================

function setupTabs() {
    document.getElementById("tab_details").addEventListener("click", () => {
        switchTab("details");
    });

    document.getElementById("tab_pricing").addEventListener("click", () => {
        if (!isDetailsValid()) {
            validateProductDetails(); // show field errors
            return;
        }
        switchTab("pricing");
        generateCombinations();
    });

    document
        .getElementById("tab_additional_fees")
        .addEventListener("click", () => {
            if (!isDetailsValid()) {
                validateProductDetails();
                switchTab("details");
                return;
            }
            if (!isPricingValid()) {
                switchTab("pricing");
                validatePricing();
                toast.error("Please fill in all combination prices.");
                return;
            }
            switchTab("additional_fees");
        });
}

function switchTab(tab) {
    currentTab = tab;
    const allTabs = ["details", "pricing", "additional_fees"];
    allTabs.forEach((t) => {
        document
            .getElementById(`tab_${t}`)
            .classList.toggle("modal_tab_active", t === tab);
        document
            .getElementById(`panel_${t}`)
            .classList.toggle("modal_panel_hidden", t !== tab);
    });

    const isAdditional = tab === "additional_fees";
    document
        .getElementById("nextBtn")
        .classList.toggle("btn_hidden", isAdditional);
    document
        .querySelector(".save_product")
        .classList.toggle("btn_hidden", !isAdditional);

    updateTabLockStates();
}

function setupNextButton() {
    document.getElementById("nextBtn").addEventListener("click", () => {
        if (currentTab === "details") {
            if (!validateProductDetails()) return;
            switchTab("pricing");
            generateCombinations();
        } else if (currentTab === "pricing") {
            if (!validatePricing()) {
                toast.error("Please fill in all combination prices.");
                return;
            }
            switchTab("additional_fees");
        }
    });
}

// ==================== VALIDATION (with error display) ====================

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

    updateTabLockStates();
    return valid;
}

function validatePricing() {
    let valid = true;
    document.querySelectorAll(".combination_price_input").forEach((input) => {
        const val = input.value.trim();
        if (!val || isNaN(parseFloat(val))) {
            input.classList.add("input_error");
            valid = false;
        } else {
            input.classList.remove("input_error");
        }
    });
    updateTabLockStates();
    return valid;
}

// ==================== ADDITIONAL FEES VALIDATION ====================

/*
 * Change #5: If a checkbox in Additional Fees is enabled but its
 * associated text input is empty, block save and highlight the field.
 * Discount rows are individually checked; min order and layout fee
 * use the existing setError helper.
 */
function validateAdditionalFees() {
    let valid = true;

    if (document.getElementById("applyProductDiscount").checked) {
        document.querySelectorAll(".discount_row").forEach((row) => {
            const qty = row.querySelector(".discount_qty_input");
            const price = row.querySelector(".discount_price_input");
            if (!qty.value.trim()) {
                qty.classList.add("input_error");
                valid = false;
            } else {
                qty.classList.remove("input_error");
            }
            if (!price.value.trim()) {
                price.classList.add("input_error");
                valid = false;
            } else {
                price.classList.remove("input_error");
            }
        });
    }

    if (document.getElementById("applyMinOrder").checked) {
        const input = document.getElementById("minOrderQty");
        if (!input.value.trim()) {
            setError(input, "Minimum quantity is required.");
            valid = false;
        } else {
            clearError(input);
        }
    }

    if (document.getElementById("applyLayoutFee").checked) {
        const input = document.getElementById("layoutFeeAmount");
        if (!input.value.trim()) {
            setError(input, "Layout fee amount is required.");
            valid = false;
        } else {
            clearError(input);
        }
    }

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
            if (priceInput.value.trim())
                priceInput.classList.remove("input_error");
            updateTabLockStates();
        });

        if (savedData[labelText]) priceInput.value = savedData[labelText].price;

        row.appendChild(label);
        row.appendChild(priceInput);
        container.appendChild(row);
    });

    updateTabLockStates();
}

// ==================== DISCOUNT ROWS ====================

function setDiscountSectionVisible(show) {
    document
        .getElementById("discountHeaderRow")
        .classList.toggle("hidden", !show);
    document
        .getElementById("discountRowsWrapper")
        .classList.toggle("hidden", !show);
}

/*
 * Row layout: [qty input 100px] [price input 100px] [actions 44px]
 * The actions wrapper has a FIXED width holding both icons side-by-side.
 * Hidden icons use visibility:hidden (not display:none) so the wrapper
 * never changes size — rows always stay perfectly aligned.
 * Order inside the wrapper: [delete icon] [add icon]
 */
function createDiscountRow() {
    const row = document.createElement("div");
    row.className = "discount_row";

    const qtyInput = document.createElement("input");
    qtyInput.type = "text";
    qtyInput.inputMode = "numeric";
    qtyInput.className = "discount_qty_input";
    qtyInput.placeholder = "e.g. 50";
    enforceNumeric(qtyInput);
    qtyInput.addEventListener("input", () =>
        qtyInput.classList.remove("input_error"),
    );

    const priceInput = document.createElement("input");
    priceInput.type = "text";
    priceInput.inputMode = "decimal";
    priceInput.className = "discount_price_input";
    priceInput.placeholder = "0.00";
    enforceNumeric(priceInput);
    priceInput.addEventListener("input", () =>
        priceInput.classList.remove("input_error"),
    );

    // Fixed-width wrapper keeps both icons together and preserves column width
    const actionsWrap = document.createElement("div");
    actionsWrap.className = "discount_row_actions";

    const delSvg = createSVG(
        "delete_discount_row_svg",
        "m339-288 141-141 141 141 51-51-141-141 141-141-51-51-141 141-141-141-51 51 141 141-141 141 51 51ZM480-96q-79 0-149-30t-122.5-82.5Q156-261 126-331T96-480q0-80 30-149.5t82.5-122Q261-804 331-834t149-30q80 0 149.5 30t122 82.5Q804-699 834-629.5T864-480q0 79-30 149t-82.5 122.5Q699-156 629.5-126T480-96Zm0-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z",
    );

    const addSvg = createSVG(
        "add_discount_row_svg",
        "M444-288h72v-156h156v-72H516v-156h-72v156H288v72h156v156Zm36.28 192Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm-.28-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z",
    );

    delSvg.addEventListener("click", () => {
        const wrapper = row.parentElement;
        if (wrapper.querySelectorAll(".discount_row").length <= 1) return;
        row.remove();
        refreshDiscountButtons(wrapper);
    });
    addSvg.addEventListener("click", () => {
        row.after(createDiscountRow());
        refreshDiscountButtons(row.parentElement);
    });

    actionsWrap.appendChild(delSvg);
    actionsWrap.appendChild(addSvg);

    row.appendChild(qtyInput);
    row.appendChild(priceInput);
    row.appendChild(actionsWrap);
    return row;
}

function refreshDiscountButtons(wrapper) {
    const rows = Array.from(wrapper.querySelectorAll(".discount_row"));
    const isSingle = rows.length <= 1;

    rows.forEach((row, i) => {
        const addSvg = row.querySelector(".add_discount_row_svg");
        const delSvg = row.querySelector(".delete_discount_row_svg");

        // Add icon: only the last row shows it.
        // Use visibility:hidden (not display:none) on non-last rows so the
        // right-side slot stays reserved and columns don't shift.
        addSvg.classList.toggle("discount_icon_hidden", i !== rows.length - 1);

        // Delete icon:
        // - Single row → display:none so it takes up zero space, letting the
        //   add icon slide left into that same position.
        // - Multiple rows → always fully visible.
        delSvg.classList.toggle("discount_del_gone", isSingle);
    });
}

function setupDiscount() {
    const checkbox = document.getElementById("applyProductDiscount");
    const wrapper = document.getElementById("discountRowsWrapper");
    checkbox.addEventListener("change", () => {
        if (checkbox.checked) {
            setDiscountSectionVisible(true);
            if (wrapper.querySelectorAll(".discount_row").length === 0) {
                wrapper.appendChild(createDiscountRow());
                refreshDiscountButtons(wrapper);
            }
        } else {
            setDiscountSectionVisible(false);
        }
    });
}

// ==================== MINIMUM ORDER ====================

function setupMinOrder() {
    const checkbox = document.getElementById("applyMinOrder");
    const wrapper = document.getElementById("minOrderWrapper");
    checkbox.addEventListener("change", () => {
        wrapper.classList.toggle("hidden", !checkbox.checked);
    });
    const minInput = document.getElementById("minOrderQty");
    enforceNumeric(minInput);
    // Clear validation error as the user types
    minInput.addEventListener("input", () => clearError(minInput));
}

// ==================== LAYOUT FEE ====================

function setupLayoutFee() {
    const checkbox = document.getElementById("applyLayoutFee");
    const wrapper = document.getElementById("layoutFeeWrapper");
    checkbox.addEventListener("change", () => {
        wrapper.classList.toggle("hidden", !checkbox.checked);
    });
    const layoutInput = document.getElementById("layoutFeeAmount");
    enforceNumeric(layoutInput);
    // Clear validation error as the user types
    layoutInput.addEventListener("input", () => clearError(layoutInput));
}

// ==================== COLLECT DATA ====================

function collectProductData() {
    const options = getAllOptions().map((opt, idx) => ({
        label: opt.querySelector(".product_option_input").value.trim(),
        position: idx,
        option_types: Array.from(opt.querySelectorAll(".spec_input_row"))
            .map((row, typeIdx) => ({
                type_name: row
                    .querySelector(".product_option_name input")
                    .value.trim(),
                is_available: row.querySelector(".product_availability input")
                    .checked,
                position: typeIdx,
            }))
            .filter((s) => s.type_name !== ""),
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
        qty: row.querySelector(".discount_qty_input").value.trim(),
        reduction: row.querySelector(".discount_price_input").value.trim(),
    }));

    const minOrderEnabled = document.getElementById("applyMinOrder").checked;
    const minOrderQty = document.getElementById("minOrderQty").value.trim();

    const layoutFeeEnabled = document.getElementById("applyLayoutFee").checked;
    const layoutFeeAmount = document
        .getElementById("layoutFeeAmount")
        .value.trim();

    return {
        options,
        combinations,
        discountEnabled,
        discountRows,
        minOrderEnabled,
        minOrderQty,
        layoutFeeEnabled,
        layoutFeeAmount,
    };
}

// ==================== SAVE ====================

function setupSaveButton() {
    document
        .querySelector(".save_product")
        .addEventListener("click", async () => {
            if (!validateProductDetails()) {
                switchTab("details");
                return;
            }

            generateCombinations();

            if (!validatePricing()) {
                switchTab("pricing");
                toast.error("Please fill in all combination prices.");
                return;
            }

            // Change #5: Validate additional fees before saving
            if (!validateAdditionalFees()) {
                switchTab("additional_fees");
                toast.error(
                    "Please fill in all required additional fee fields.",
                );
                return;
            }

            if (isLoading) return;
            isLoading = true;

            try {
                const data = collectProductData();
                const nameSelect = document.getElementById("productName");
                const selectedProductName = nameSelect.value.trim();

                const allProducts = await fetch("/api/products")
                    .then((r) => r.json())
                    .then((d) => d.data || []);

                const selectedProduct = allProducts.find(
                    (p) => p.name === selectedProductName,
                );
                if (!selectedProduct) {
                    toast.error("Selected product not found");
                    isLoading = false;
                    return;
                }

                const payload = {
                    product_id: selectedProduct.id,
                    options: data.options,
                    pricings: Object.entries(data.combinations).map(
                        ([key, price]) => ({
                            combination_key: key,
                            price: parseFloat(price) || 0,
                        }),
                    ),
                    discounts:
                        data.discountEnabled && data.discountRows.length > 0
                            ? data.discountRows.map((row, idx) => ({
                                min_quantity: parseInt(row.qty) || 0,
                                price_reduction:
                                    parseFloat(row.reduction) || 0,
                                position: idx,
                            }))
                            : [],
                    min_order:
                        data.minOrderEnabled && data.minOrderQty
                            ? parseInt(data.minOrderQty) || null
                            : null,
                    layout_fee:
                        data.layoutFeeEnabled && data.layoutFeeAmount
                            ? parseFloat(data.layoutFeeAmount) || null
                            : null,
                };

                if (editingProductId !== null) {
                    await orderTemplateApi.updateOrderTemplate(
                        editingProductId,
                        payload,
                    );
                    toast.success("Order template updated successfully");
                } else {
                    await orderTemplateApi.createOrderTemplate(payload);
                    toast.success("Order template created successfully");
                }

                await loadOrderTemplates();
                resetModal();
                closeModal();
            } catch (error) {
                console.error("Error saving order template:", error);
                toast.error(error.message || "Failed to save order template");
            } finally {
                isLoading = false;
            }
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
        .addEventListener("click", async () => {
            if (pendingDeleteId === null) return;
            if (isLoading) return;
            isLoading = true;

            try {
                await orderTemplateApi.deleteOrderTemplate(pendingDeleteId);
                toast.success("Order template deleted successfully");
                pendingDeleteId = null;
                confirmOverlay.classList.remove("active");
                await loadOrderTemplates();
                resetModal();
                closeModal();
            } catch (error) {
                console.error("Error deleting order template:", error);
                toast.error(error.message || "Failed to delete order template");
            } finally {
                isLoading = false;
            }
        });

    /*
     * Change #4 note: The delete CONFIRMATION modal still closes on
     * outside click — this is intentional per the requirement.
     */
    confirmOverlay.addEventListener("click", (e) => {
        if (e.target === confirmOverlay) {
            pendingDeleteId = null;
            confirmOverlay.classList.remove("active");
        }
    });
}

// ==================== RENDER PRODUCT CARDS ====================

async function loadOrderTemplates() {
    try {
        isLoading = true;
        products = await orderTemplateApi.getAllOrderTemplates();
        renderProducts();
    } catch (error) {
        console.error("Error loading order templates:", error);
        toast.error("Failed to load order templates");
    } finally {
        isLoading = false;
    }
}

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

/*
 * Change #1: Cards no longer open a detail modal on click.
 * All additional fee info (bulk discount, min order, layout fee)
 * is now displayed inline at the bottom of each card.
 */
function createProductCard(template) {
    const card = document.createElement("div");
    card.className = "product_card";

    const name = document.createElement("h3");
    name.className = "product_card_name";
    name.textContent = template.product.name;
    card.appendChild(name);

    const optionsDiv = document.createElement("div");
    optionsDiv.className = "product_card_options";
    const selects = [];

    template.options.forEach((opt) => {
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

        opt.option_types
            .filter((s) => s.is_available)
            .forEach((spec) => {
                const option = document.createElement("option");
                option.value = spec.type_name;
                option.textContent = spec.type_name;
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
        const pricing = template.pricings.find(
            (p) => p.combination_key === key,
        );
        const price = pricing?.price;
        priceDiv.textContent = price ? `₱${parseFloat(price).toFixed(2)}` : "—";
    }
    selects.forEach((s) => s.addEventListener("change", updateCardPrice));
    updateCardPrice();
    card.appendChild(priceDiv);

    // ---- Additional fee summary (inline, replaces detail modal) ----
    const feeSummary = document.createElement("div");
    feeSummary.className = "product_card_fee_summary";

    const makeFeeLine = (text) => {
        const p = document.createElement("p");
        p.className = "product_card_fee_line";
        p.textContent = text;
        return p;
    };

    // Bulk Discount — template.discounts is an array (may be empty or absent)
    const discounts = Array.isArray(template.discounts)
        ? template.discounts
        : [];
    if (discounts.length > 0) {
        const tiers = discounts
            .map((d) => {
                const qty = parseInt(d.min_quantity ?? d.qty ?? 0);
                const reduction = parseFloat(
                    d.price_reduction ?? d.reduction ?? 0,
                );
                return `Min. ${qty}, -₱${reduction.toFixed(2)}`;
            })
            .join(" | ");
        feeSummary.appendChild(makeFeeLine(`Bulk Discount: ${tiers}`));
    } else {
        feeSummary.appendChild(makeFeeLine("Bulk Discount: None"));
    }

    // Minimum Order — may be an object with min_quantity, a number, a string, or null/undefined
    let minOrder = null;
    const minOrderData = template.min_order ?? template.minOrder ?? null;
    if (minOrderData != null) {
        // If it's an object with min_quantity property, extract that; otherwise treat as value
        minOrder = typeof minOrderData === 'object' && minOrderData.min_quantity != null
            ? parseInt(minOrderData.min_quantity)
            : parseInt(minOrderData);
    }
    feeSummary.appendChild(
        makeFeeLine(
            minOrder != null && !isNaN(minOrder)
                ? `Minimum Order: ${minOrder}`
                : "Minimum Order: None",
        ),
    );

    // Layout Fee — may be an object with fee_amount, a number, a string, or null/undefined
    let layoutFee = null;
    const layoutFeeData = template.layout_fee ?? template.layoutFee ?? null;
    if (layoutFeeData != null) {
        // If it's an object with fee_amount property, extract that; otherwise treat as value
        layoutFee = typeof layoutFeeData === 'object' && layoutFeeData.fee_amount != null
            ? parseFloat(layoutFeeData.fee_amount)
            : parseFloat(layoutFeeData);
    }
    feeSummary.appendChild(
        makeFeeLine(
            layoutFee != null && !isNaN(layoutFee)
                ? `Layout Fee: ₱${layoutFee.toFixed(2)}`
                : "Layout Fee: None",
        ),
    );

    card.appendChild(feeSummary);

    // Edit button
    const editBtn = document.createElement("button");
    editBtn.className = "product_card_edit_btn";
    editBtn.textContent = "Edit";
    editBtn.type = "button";
    editBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        openEditModal(template.id);
    });
    card.appendChild(editBtn);

    // No click-to-open-detail listener — Change #1
    return card;
}

// ==================== EDIT MODAL ====================

function openEditModal(templateId) {
    const template = products.find((p) => p.id === templateId);
    if (!template) return;
    editingProductId = templateId;

    document.getElementById("templateModalTitle").textContent =
        "Edit Order Template";
    document.getElementById("deleteProductBtn").classList.remove("btn_hidden");

    const nameSelect = document.getElementById("productName");
    nameSelect.value = template.product.name;

    const wrapper = getOptionsWrapper();
    wrapper.innerHTML = "";
    template.options.forEach((opt, i) => {
        const container = createOptionContainer(i + 1);
        wrapper.appendChild(container);
        container.querySelector(".product_option_input").value = opt.label;

        const specInputsDiv = container.querySelector(".spec_inputs_div");
        specInputsDiv
            .querySelectorAll(".spec_input_row")
            .forEach((r) => r.remove());

        opt.option_types.forEach((optType) => {
            const row = createSpecRow();
            row.querySelector(".product_availability input").checked =
                optType.is_available;
            row.querySelector(".product_option_name input").value =
                optType.type_name;
            specInputsDiv.appendChild(row);
        });
        refreshSpecButtons(specInputsDiv);
    });
    refreshOptionButtons();

    // Pricing combinations
    const combContainer = document.getElementById("pricingCombinations");
    combContainer.innerHTML = "";
    template.pricings.forEach((pricing) => {
        const row = document.createElement("div");
        row.className = "combination_row";
        const label = document.createElement("span");
        label.className = "combination_label";
        label.textContent = pricing.combination_key;
        const priceInput = document.createElement("input");
        priceInput.type = "text";
        priceInput.className = "combination_price_input";
        priceInput.value = pricing.price || "";
        priceInput.addEventListener("input", () => {
            priceInput.value = priceInput.value
                .replace(/[^0-9.]/g, "")
                .replace(/(\..*?)\..*/g, "$1");
            if (priceInput.value.trim())
                priceInput.classList.remove("input_error");
            updateTabLockStates();
        });
        row.appendChild(label);
        row.appendChild(priceInput);
        combContainer.appendChild(row);
    });

    // Bulk discount
    const checkbox = document.getElementById("applyProductDiscount");
    const hasDiscounts = template.discounts && template.discounts.length > 0;
    checkbox.checked = hasDiscounts;
    const discountWrapper = document.getElementById("discountRowsWrapper");
    discountWrapper.innerHTML = "";
    if (hasDiscounts) {
        setDiscountSectionVisible(true);
        template.discounts.forEach((discount) => {
            const row = createDiscountRow();
            row.querySelector(".discount_qty_input").value =
                discount.min_quantity;
            row.querySelector(".discount_price_input").value =
                discount.price_reduction;
            discountWrapper.appendChild(row);
        });
        refreshDiscountButtons(discountWrapper);
    } else {
        setDiscountSectionVisible(false);
    }

    // Min order
    const applyMinOrder = document.getElementById("applyMinOrder");
    const minOrderWrapper = document.getElementById("minOrderWrapper");
    const minOrderData = template.min_order ?? template.minOrder ?? null;
    if (minOrderData != null) {
        applyMinOrder.checked = true;
        minOrderWrapper.classList.remove("hidden");
        // Extract min_quantity from object if needed
        const minQtyValue = typeof minOrderData === 'object' && minOrderData.min_quantity != null
            ? minOrderData.min_quantity
            : minOrderData;
        document.getElementById("minOrderQty").value = minQtyValue;
    } else {
        applyMinOrder.checked = false;
        minOrderWrapper.classList.add("hidden");
        document.getElementById("minOrderQty").value = "";
    }

    // Layout fee
    const applyLayoutFee = document.getElementById("applyLayoutFee");
    const layoutFeeWrapper = document.getElementById("layoutFeeWrapper");
    const layoutFeeData = template.layout_fee ?? template.layoutFee ?? null;
    if (layoutFeeData != null) {
        applyLayoutFee.checked = true;
        layoutFeeWrapper.classList.remove("hidden");
        // Extract fee_amount from object if needed
        const feeAmountValue = typeof layoutFeeData === 'object' && layoutFeeData.fee_amount != null
            ? layoutFeeData.fee_amount
            : layoutFeeData;
        document.getElementById("layoutFeeAmount").value = feeAmountValue;
    } else {
        applyLayoutFee.checked = false;
        layoutFeeWrapper.classList.add("hidden");
        document.getElementById("layoutFeeAmount").value = "";
    }

    // Clear stale errors
    document
        .querySelectorAll(".input_error")
        .forEach((el) => el.classList.remove("input_error"));
    document
        .querySelectorAll(".input_error_msg")
        .forEach((el) => el.classList.add("hidden"));

    switchTab("details");
    updateTabLockStates();
    openModal();
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
        "Add New Order Template";
    document.getElementById("deleteProductBtn").classList.add("btn_hidden");

    document
        .querySelectorAll(".input_error")
        .forEach((el) => el.classList.remove("input_error"));
    document
        .querySelectorAll(".input_error_msg")
        .forEach((el) => el.classList.add("hidden"));

    // Reset discount
    document.getElementById("applyProductDiscount").checked = false;
    const discountWrapper = document.getElementById("discountRowsWrapper");
    discountWrapper.innerHTML = "";
    setDiscountSectionVisible(false);

    // Reset min order
    document.getElementById("applyMinOrder").checked = false;
    document.getElementById("minOrderWrapper").classList.add("hidden");
    const minInput = document.getElementById("minOrderQty");
    minInput.value = "";
    clearError(minInput);

    // Reset layout fee
    document.getElementById("applyLayoutFee").checked = false;
    document.getElementById("layoutFeeWrapper").classList.add("hidden");
    const layoutInput = document.getElementById("layoutFeeAmount");
    layoutInput.value = "";
    clearError(layoutInput);

    const wrapper = getOptionsWrapper();
    wrapper.innerHTML = "";
    wrapper.appendChild(createOptionContainer(1));
    refreshOptionButtons();
    document.getElementById("pricingCombinations").innerHTML = "";

    switchTab("details");
    updateTabLockStates();
}

// ==================== INIT ====================

function initModal() {
    const nameSelect = document.getElementById("productName");
    nameSelect.addEventListener("change", () => {
        clearError(nameSelect);
        updateTabLockStates();
    });

    getOptionsWrapper().appendChild(createOptionContainer(1));
    refreshOptionButtons();
    setupTabs();
    setupNextButton();
    setupDiscount();
    setupMinOrder();
    setupLayoutFee();
    setupCancelButton();
    setupSaveButton();
    setupDeleteButton();

    loadProductNamesFromAPI();
    loadOrderTemplates();
    updateTabLockStates();
}

document.addEventListener("DOMContentLoaded", initModal);
