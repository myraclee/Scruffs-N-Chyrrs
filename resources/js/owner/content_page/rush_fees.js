// ==================== RUSH FEES ====================
import toast from "../../utils/toast.js";
import rushApi from "../../api/rushFeeApi.js";

// ==================== STATE ====================
let rushFees = [];
let editingRushIndex = null;
let pendingDeleteIndex = null;
let isRushLoading = false;

// ==================== SVG HELPER ====================
function createRushSVG(className, pathD) {
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

const DEL_PATH =
    "m339-288 141-141 141 141 51-51-141-141 141-141-51-51-141 141-141-141-51 51 141 141-141 141 51 51ZM480-96q-79 0-149-30t-122.5-82.5Q156-261 126-331T96-480q0-80 30-149.5t82.5-122Q261-804 331-834t149-30q80 0 149.5 30t122 82.5Q804-699 834-629.5T864-480q0 79-30 149t-82.5 122.5Q699-156 629.5-126T480-96Zm0-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z";

// ==================== MODAL STATE ====================
let draftFee = null;

function buildDraftFromIndex(idx) {
    const src = rushFees[idx];
    return {
        id: src.id,
        label: src.label,
        min: src.min ? Math.floor(src.min) : null,
        max: src.max ? Math.floor(src.max) : null,
        timeframes: src.timeframes.map((tf) => ({
            id: tf.id,
            label: tf.label,
            percentage: tf.percentage ? Math.floor(tf.percentage) : "",
        })),
    };
}

function freshDraft() {
    return {
        id: null,
        label: "",
        min: null,
        max: null,
        timeframes: [{ id: null, label: "", percentage: "" }],
    };
}

// ==================== OPEN / CLOSE MODALS ====================
const rushModalOverlay = document.getElementById("rushFeeModalOverlay");
const rushDeleteOverlay = document.getElementById("rushDeleteConfirmOverlay");

function openAddModal() {
    editingRushIndex = null;
    draftFee = freshDraft();
    clearRushValidationErrors();
    document.getElementById("rushModalTitle").textContent = "Add Rush Fee";
    document.getElementById("rushDeleteBtn").classList.add("btn_hidden");
    renderRushModalForm();
    rushModalOverlay.classList.add("active");
}

function openEditModal(idx) {
    editingRushIndex = idx;

    draftFee = buildDraftFromIndex(idx);
    clearRushValidationErrors();

    document.getElementById("rushModalTitle").textContent = "Edit Rush Fee";
    document.getElementById("rushDeleteBtn").classList.remove("btn_hidden");
    renderRushModalForm();
    rushModalOverlay.classList.add("active");
}

function closeRushModal() {
    rushModalOverlay.classList.remove("active");
    clearRushValidationErrors();
    draftFee = null;
    editingRushIndex = null;
}

// ==================== DELETE CONFIRM MODAL ====================
function openDeleteConfirm(idx) {
    pendingDeleteIndex = idx;
    rushDeleteOverlay.classList.add("active");
}

function closeDeleteConfirm() {
    pendingDeleteIndex = null;
    rushDeleteOverlay.classList.remove("active");
}

rushDeleteOverlay.addEventListener("click", (e) => {
    if (e.target === rushDeleteOverlay) closeDeleteConfirm();
});

// ==================== VALIDATION HELPERS ====================

function clearRushValidationErrors() {
    document.querySelectorAll(".rush_input_error").forEach((el) => {
        el.classList.remove("rush_input_error");
    });
    document.querySelectorAll(".rush_error_msg").forEach((msg) => {
        msg.remove();
    });
}

function showFieldError(element, message) {
    element.classList.add("rush_input_error");
    const parent = element.closest(".rush_input_group");

    let errorMsg = parent.querySelector(".rush_error_msg");
    if (!errorMsg) {
        errorMsg = document.createElement("span");
        errorMsg.className = "rush_error_msg";
        parent.appendChild(errorMsg);
    }
    errorMsg.textContent = message;
}

function clearFieldError(element) {
    if (element.classList.contains("rush_input_error")) {
        element.classList.remove("rush_input_error");
        const parent = element.closest(".rush_input_group");
        const errorMsg = parent.querySelector(".rush_error_msg");
        if (errorMsg) {
            errorMsg.remove();
        }
    }
}

// ==================== RENDER FORM ====================
function renderRushModalForm() {
    document.getElementById("rushRangeLabel").value = draftFee.label;

    document.getElementById("rushRangeMin").value =
        draftFee.min !== null ? draftFee.min : "";
    document.getElementById("rushRangeMax").value =
        draftFee.max !== null ? draftFee.max : "";
    renderTimeframeRows();
}

function renderTimeframeRows() {
    const wrapper = document.getElementById("rushTimeframeRows");
    wrapper.innerHTML = "";

    draftFee.timeframes.forEach((tf, ti) => {
        const row = document.createElement("div");
        row.className = "rush_tf_edit_row";

        // Timeframe Column Group
        const tfGroup = document.createElement("div");
        tfGroup.className = "rush_input_group";
        const tfInput = document.createElement("input");
        tfInput.type = "text";
        tfInput.className = "rush_timeframe_input";
        tfInput.placeholder = 'e.g. "2 days"';
        tfInput.value = tf.label;
        tfInput.addEventListener("input", () => {
            draftFee.timeframes[ti].label = tfInput.value;

            clearFieldError(tfInput);
        });

        tfGroup.appendChild(tfInput);

        // Percentage input wrapper
        const pctGroup = document.createElement("div");
        pctGroup.className = "rush_input_group";
        const pctWrap = document.createElement("div");
        pctWrap.className = "rush_pct_wrap";
        const pctInput = document.createElement("input");
        pctInput.type = "text";
        pctInput.className = "rush_pct_input";
        pctInput.placeholder = "0";
        pctInput.value = tf.percentage;

        pctInput.addEventListener("input", (e) => {
            const input = e.target;

            // 1. STRIP EVERYTHING THAT ISN'T A DIGIT IMMEDIATELY
            const numericValue = input.value.replace(/\D/g, "");

            // 2. FORCE THE INPUT TO ONLY SHOW THE NUMBERS
            // If they typed a letter, this effectively deletes it before it renders.
            input.value = numericValue;

            // 3. UPDATE DRAFT STATE
            if (draftFee) {
                draftFee.timeframes[ti].percentage =
                    numericValue === "" ? "" : parseInt(numericValue, 10);
            }

            // 4. ONLY CLEAR ERROR IF A NUMBER WAS ACTUALLY TYPED
            // If numericValue is empty (because they typed a letter), this is skipped.
            if (numericValue.length > 0) {
                clearFieldError(input);
            }
        });
        const pctSym = document.createElement("span");
        pctSym.className = "rush_pct_symbol";
        pctSym.textContent = "%";
        pctWrap.appendChild(pctInput);
        pctWrap.appendChild(pctSym);
        pctGroup.appendChild(pctWrap);

        // Delete icon
        const delSvg = createRushSVG("rush_del_svg", DEL_PATH);
        if (draftFee.timeframes.length <= 1)
            delSvg.classList.add("rush_del_hidden");
        delSvg.addEventListener("click", () => {
            if (draftFee.timeframes.length <= 1) return;
            draftFee.timeframes.splice(ti, 1);
            renderTimeframeRows();
        });

        row.appendChild(tfGroup);
        row.appendChild(pctGroup);
        row.appendChild(delSvg);
        wrapper.appendChild(row);
    });
}

// ==================== LOAD DATA ====================
async function loadRushFees() {
    try {
        const data = await rushApi.getAllRushFees();
        rushFees = data.map((r) => ({
            id: r.id,
            label: r.label,
            min: r.min_price,
            max: r.max_price,
            timeframes: r.timeframes || [],
        }));
        renderRushDisplay();
    } catch (err) {
        console.warn("Rush fees: could not load.", err);
    }
}

// ==================== SAVE ====================
async function saveRushFee() {
    if (isRushLoading) return;

    clearRushValidationErrors();
    let hasError = false;

    const minInput = document.getElementById("rushRangeMin");
    const maxInput = document.getElementById("rushRangeMax");
    const labelInput = document.getElementById("rushRangeLabel");

    // 2. Validate Label
    if (!draftFee.label || draftFee.label.trim() === "") {
        showFieldError(labelInput, "Price Range Label is required.");
        hasError = true;
    }

    // 3. Validate Minimum (Required)
    if (draftFee.min === null || draftFee.min === "") {
        showFieldError(minInput, "Minimum Range is required.");
        hasError = true;
    }

    // 4. Validate Range (Min vs Max)
    if (draftFee.min !== null && draftFee.max !== null) {
        const min = parseInt(draftFee.min);
        const max = parseInt(draftFee.max);

        if (min > max) {
            setMinMaxError(minInput, maxInput, "Min cannot exceed max.");
            hasError = true;
        } else if (min === max) {
            setMinMaxError(minInput, maxInput, "Min and max cannot be equal.");
            hasError = true;
        }
    }

    // Validate Timeframes
    const timeframeRows = document.querySelectorAll(".rush_tf_edit_row");
    draftFee.timeframes.forEach((tf, index) => {
        const row = timeframeRows[index];
        const tfInput = row.querySelector(".rush_timeframe_input");
        const pctInput = row.querySelector(".rush_pct_input");

        if (!tf.label || tf.label.trim() === "") {
            showFieldError(tfInput, "Timeframe is required.");
            hasError = true;
        }

        if (
            tf.percentage === "" ||
            tf.percentage === null ||
            tf.percentage === 0
        ) {
            showFieldError(pctInput, "Percentage is required.");
            hasError = true;
        }
    });

    if (hasError) {
        document.querySelector(".rush_fees_modal_box").scrollTop = 0;
        return; // STOP HERE. Do not proceed to API call.
    }

    isRushLoading = true;
    try {
        const rushFeePayload = {
            label: draftFee.label,
            min_price: draftFee.min,
            max_price: draftFee.max,
            timeframes: draftFee.timeframes.map((tf) => ({
                label: tf.label,
                percentage: parseFloat(tf.percentage) || 0,
            })),
        };

        if (editingRushIndex === null) {
            await rushApi.createRushFee(rushFeePayload);
            toast.success("Rush fee added successfully");
        } else {
            await rushApi.updateRushFee(draftFee.id, rushFeePayload);
            toast.success("Rush fee updated successfully");
        }

        await loadRushFees();
        closeRushModal();
    } catch (error) {
        console.error("Error saving rush fee:", error);
        toast.error(error.message || "Failed to save rush fee");
    } finally {
        isRushLoading = false;
    }
}

// ==================== DELETE ====================
async function deleteRushFee(idx) {
    const feeId = rushFees[idx].id;
    if (!feeId) return;

    if (isRushLoading) return;
    isRushLoading = true;

    try {
        await rushApi.deleteRushFee(feeId);
        toast.success("Rush fee deleted successfully");
        closeDeleteConfirm();
        closeRushModal();
        await loadRushFees();
    } catch (error) {
        toast.error(error.message || "Failed to delete");
    } finally {
        isRushLoading = false;
    }
}

// ==================== READ-ONLY DISPLAY ====================
function renderRushDisplay() {
    const container = document.getElementById("rushFeesCardsContainer");
    const emptyEl = document.getElementById("rushFeesEmpty");

    container.innerHTML = "";

    if (rushFees.length === 0) {
        emptyEl.classList.remove("hidden");
        return;
    }

    emptyEl.classList.add("hidden");

    rushFees.forEach((range, idx) => {
        const card = document.createElement("div");
        card.className = "rush_fee_card";

        // Header - Price Range Label
        const header = document.createElement("h3");
        header.className = "rush_card_header";
        header.textContent = range.label || "—";
        card.appendChild(header);

        // Timeframes table
        if (range.timeframes && range.timeframes.length > 0) {
            const table = document.createElement("table");
            table.className = "rush_card_table";

            const thead = document.createElement("thead");
            const headerRow = document.createElement("tr");

            const timeframeHeader = document.createElement("th");
            timeframeHeader.textContent = "Timeframe";
            headerRow.appendChild(timeframeHeader);

            const percentageHeader = document.createElement("th");
            percentageHeader.textContent = "% Added to Total";
            headerRow.appendChild(percentageHeader);

            thead.appendChild(headerRow);
            table.appendChild(thead);

            const tbody = document.createElement("tbody");

            range.timeframes.forEach((tf) => {
                const row = document.createElement("tr");

                const timeframeCell = document.createElement("td");
                timeframeCell.textContent = tf.label || "—";
                row.appendChild(timeframeCell);

                const percentageCell = document.createElement("td");
                const displayPct = tf.percentage
                    ? Math.floor(tf.percentage)
                    : 0;
                percentageCell.textContent =
                    displayPct > 0 ? `+${displayPct}%` : "—";
                row.appendChild(percentageCell);

                tbody.appendChild(row);
            });

            table.appendChild(tbody);
            card.appendChild(table);
        }

        // Edit button
        const editBtn = document.createElement("button");
        editBtn.type = "button";
        editBtn.className = "rush_card_edit_btn";
        editBtn.textContent = "Edit";
        editBtn.addEventListener("click", () => openEditModal(idx));
        card.appendChild(editBtn);

        container.appendChild(card);
    });
}

// ==================== INIT ====================
function initRushFees() {
    document
        .getElementById("addRushFeeBtn")
        .addEventListener("click", openAddModal);

    document.getElementById("rushRangeLabel").addEventListener("input", (e) => {
        if (draftFee) draftFee.label = e.target.value;
        clearFieldError(e.target);
    });

    document
        .getElementById("rushAddTimeframeBtn")
        .addEventListener("click", () => {
            if (!draftFee) return;
            draftFee.timeframes.push({ id: null, label: "", percentage: "" });
            renderTimeframeRows();
        });

    document
        .getElementById("rushSaveBtn")
        .addEventListener("click", saveRushFee);
    document
        .getElementById("rushCancelBtn")
        .addEventListener("click", closeRushModal);

    document.getElementById("rushDeleteBtn").addEventListener("click", () => {
        if (editingRushIndex === null) return;
        openDeleteConfirm(editingRushIndex);
    });

    document
        .getElementById("rushDeleteConfirmBtn")
        .addEventListener("click", async () => {
            if (pendingDeleteIndex === null) return;
            await deleteRushFee(pendingDeleteIndex);
        });

    loadRushFees();

    const minInput = document.getElementById("rushRangeMin");
    const maxInput = document.getElementById("rushRangeMax");

    const validateRange = () => {
        const minVal = minInput.value === "" ? null : parseInt(minInput.value);
        const maxVal = maxInput.value === "" ? null : parseInt(maxInput.value);

        if (minVal === null || maxVal === null) {
            clearMinMaxError(minInput, maxInput);
            return;
        }

        if (minVal > maxVal) {
            setMinMaxError(minInput, maxInput, "Min cannot exceed max.");
        } else if (minVal === maxVal) {
            setMinMaxError(minInput, maxInput, "Min and max cannot be equal.");
        } else {
            clearMinMaxError(minInput, maxInput);
        }
    };

    minInput.addEventListener("input", (e) => {
        const val = e.target.value.replace(/\D/g, "");
        e.target.value = val;

        if (draftFee) draftFee.min = val === "" ? null : parseInt(val);

        // FIX: Only clear the "Required" error if there is actually a number
        if (val.length > 0) {
            clearFieldError(e.target);
            validateRange(); // Only check range if we have a value
        }
    });

    maxInput.addEventListener("input", (e) => {
        const val = e.target.value.replace(/\D/g, "");
        e.target.value = val;

        if (draftFee) draftFee.max = val === "" ? null : parseInt(val);

        validateRange();
    });
}

document.addEventListener("DOMContentLoaded", initRushFees);

// ==================== MIN AND MAX ERROR ====================

function setMinMaxError(minEl, maxEl, message) {
    // Both turn red
    minEl.classList.add("rush_input_error");
    maxEl.classList.add("rush_input_error");

    // Message only under Min
    const parent = minEl.closest(".rush_input_group");
    let errorMsg = parent.querySelector(".rush_error_msg");
    if (!errorMsg) {
        errorMsg = document.createElement("span");
        errorMsg.className = "rush_error_msg";
        parent.appendChild(errorMsg);
    }
    errorMsg.textContent = message;
}

function clearMinMaxError(minEl, maxEl) {
    minEl.classList.remove("rush_input_error");
    maxEl.classList.remove("rush_input_error");

    const parent = minEl.closest(".rush_input_group");
    const errorMsg = parent.querySelector(".rush_error_msg");

    // Updated to check for both possible range error messages
    const rangeErrors = [
        "Min cannot exceed max.",
        "Min and max cannot be equal.",
    ];
    if (errorMsg && rangeErrors.includes(errorMsg.textContent)) {
        errorMsg.remove();
    }
}
