// ==================== RUSH FEES ====================
import toast from "../../utils/toast.js";

// ==================== STATE ====================
// rushFees = [{ id?, label, min, max, timeframes: [{ label, percentage }], image_url? }]

let rushFees = [];
let editingRushIndex = null;
let pendingDeleteIndex = null;
let isRushLoading = false;
let pendingImageFile = null; // File chosen in the current modal session

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
        label: src.label,
        min: src.min,
        max: src.max,
        timeframes: src.timeframes.map((tf) => ({ ...tf })),
        image_url: src.image_url ?? null,
    };
}

function freshDraft() {
    return {
        label: "",
        min: null,
        max: null,
        timeframes: [{ label: "", percentage: "" }],
        image_url: null,
    };
}

// ==================== OPEN / CLOSE MODALS ====================

const rushModalOverlay = document.getElementById("rushFeeModalOverlay");
const rushDeleteOverlay = document.getElementById("rushDeleteConfirmOverlay");

function openAddModal() {
    editingRushIndex = null;
    draftFee = freshDraft();
    pendingImageFile = null;
    document.getElementById("rushModalTitle").textContent = "Add Rush Fee";
    document.getElementById("rushDeleteBtn").classList.add("btn_hidden");
    renderRushModalForm();
    rushModalOverlay.classList.add("active");
}

function openEditModal(idx) {
    editingRushIndex = idx;
    draftFee = buildDraftFromIndex(idx);
    pendingImageFile = null;
    document.getElementById("rushModalTitle").textContent = "Edit Rush Fee";
    document.getElementById("rushDeleteBtn").classList.remove("btn_hidden");
    renderRushModalForm();
    rushModalOverlay.classList.add("active");
}

function closeRushModal() {
    rushModalOverlay.classList.remove("active");
    draftFee = null;
    editingRushIndex = null;
    pendingImageFile = null;
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

// ==================== IMAGE UPLOAD WIDGET ====================

/**
 * Build the A4 image upload section and inject it into the modal.
 * One image max. PNG / JPG only.
 */
function buildImageUploadSection() {
    const section = document.createElement("div");
    section.className = "rush_image_upload_section";

    // Label row
    const headerRow = document.createElement("div");
    headerRow.className = "rush_image_upload_header";

    const labelEl = document.createElement("span");
    labelEl.className = "rush_field_label";
    labelEl.textContent = "Pricing Image";

    headerRow.appendChild(labelEl);
    section.appendChild(headerRow);

    // Drop zone — A4 aspect ratio (210 : 297)
    const dropZone = document.createElement("div");
    dropZone.className = "rush_image_dropzone";
    dropZone.setAttribute("role", "button");
    dropZone.setAttribute("tabindex", "0");
    dropZone.setAttribute("aria-label", "Upload reference sheet image");

    // Placeholder shown when no image is set
    const placeholder = document.createElement("div");
    placeholder.className = "rush_image_placeholder";

    const plus = document.createElement("span");
    plus.className = "rush_image_plus";
    plus.textContent = "+";

    placeholder.appendChild(plus);

    // Preview
    const preview = document.createElement("img");
    preview.className = "rush_image_preview";
    preview.alt = "Reference sheet preview";

    // Remove button
    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "rush_image_remove_btn";
    removeBtn.setAttribute("aria-label", "Remove image");
    removeBtn.textContent = "Remove";

    dropZone.appendChild(placeholder);
    dropZone.appendChild(preview);

    // Hidden file input
    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.accept = "image/png, image/jpeg";
    fileInput.className = "rush_image_file_input";
    fileInput.setAttribute("aria-hidden", "true");

    section.appendChild(dropZone);
    section.appendChild(fileInput);

    // ---- Helpers ----
    function showPreview(src) {
        preview.src = src;
        dropZone.classList.add("rush_image_dropzone--has_image");
        addRemoveButton();
    }

    function clearPreview() {
        preview.src = "";
        dropZone.classList.remove("rush_image_dropzone--has_image");
        pendingImageFile = null;
        if (draftFee) draftFee.image_url = null;
        fileInput.value = "";

        removeRemoveButton();
    }

    function handleFile(file) {
        if (!file) return;
        if (!["image/png", "image/jpeg"].includes(file.type)) {
            toast.error("Only PNG and JPG images are allowed.");
            return;
        }
        pendingImageFile = file;
        const reader = new FileReader();
        reader.onload = (e) => showPreview(e.target.result);
        reader.readAsDataURL(file);
    }

    // Restore existing image on edit open
    if (draftFee?.image_url) {
        showPreview(draftFee.image_url);
    }

    dropZone.addEventListener("click", (e) => {
        if (e.target === removeBtn) return;

        if (dropZone.classList.contains("rush_image_dropzone--has_image"))
            return;

        fileInput.click();
    });

    dropZone.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            fileInput.click();
        }
    });

    removeBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        clearPreview();
    });

    fileInput.addEventListener("change", () => {
        handleFile(fileInput.files[0] ?? null);
    });

    function addRemoveButton() {
        if (!section.contains(removeBtn)) {
            section.appendChild(removeBtn);
        }
    }

    function removeRemoveButton() {
        if (section.contains(removeBtn)) {
            removeBtn.remove();
        }
    }

    return section;
}

// ==================== RENDER FORM ====================

function renderRushModalForm() {
    document.getElementById("rushRangeLabel").value = draftFee.label;
    document.getElementById("rushRangeMin").value =
        draftFee.min !== null ? draftFee.min : "";
    document.getElementById("rushRangeMax").value =
        draftFee.max !== null ? draftFee.max : "";

    renderTimeframeRows();

    // Inject image upload — remove stale instance first
    const existing = document.getElementById("rushImageUploadSection");
    if (existing) existing.remove();

    const uploadSection = buildImageUploadSection();
    uploadSection.id = "rushImageUploadSection";

    const priceRangeSection = document.querySelector(".rush_range_section");
    if (priceRangeSection) {
        priceRangeSection.parentElement.insertBefore(
            uploadSection,
            priceRangeSection,
        );
    }
}

function renderTimeframeRows() {
    const wrapper = document.getElementById("rushTimeframeRows");
    wrapper.innerHTML = "";

    draftFee.timeframes.forEach((tf, ti) => {
        const row = document.createElement("div");
        row.className = "rush_tf_edit_row";

        const tfInput = document.createElement("input");
        tfInput.type = "text";
        tfInput.className = "rush_timeframe_input";
        tfInput.placeholder = 'e.g. "2 days", "2–3 days"';
        tfInput.value = tf.label;
        tfInput.addEventListener("input", () => {
            draftFee.timeframes[ti].label = tfInput.value;
        });

        const pctWrap = document.createElement("div");
        pctWrap.className = "rush_pct_wrap";

        const pctInput = document.createElement("input");
        pctInput.type = "text";
        pctInput.className = "rush_pct_input";
        pctInput.placeholder = "0";
        pctInput.value = tf.percentage;
        pctInput.addEventListener("input", () => {
            pctInput.value = pctInput.value
                .replace(/[^0-9.]/g, "")
                .replace(/(\..*?)\..*/g, "$1");
            draftFee.timeframes[ti].percentage = pctInput.value;
        });

        const pctSym = document.createElement("span");
        pctSym.className = "rush_pct_symbol";
        pctSym.textContent = "%";

        pctWrap.appendChild(pctInput);
        pctWrap.appendChild(pctSym);

        const delSvg = createRushSVG("rush_del_svg", DEL_PATH);
        delSvg.title = "Remove row";
        if (draftFee.timeframes.length <= 1) {
            delSvg.classList.add("rush_del_hidden");
        }
        delSvg.addEventListener("click", () => {
            if (draftFee.timeframes.length <= 1) return;
            draftFee.timeframes.splice(ti, 1);
            renderTimeframeRows();
        });

        row.appendChild(tfInput);
        row.appendChild(pctWrap);
        row.appendChild(delSvg);
        wrapper.appendChild(row);
    });
}

// ==================== LOAD DATA ====================

async function loadRushFees() {
    try {
        const response = await fetch("/api/rush-fees", {
            headers: { Accept: "application/json" },
        });
        if (!response.ok) return;
        const data = await response.json();
        rushFees = (data.rush_fees ?? []).map((r) => ({
            label: r.label ?? "",
            min: r.min ?? null,
            max: r.max ?? null,
            timeframes: (r.timeframes ?? []).map((tf) => ({
                label: tf.label ?? "",
                percentage: String(tf.percentage ?? ""),
            })),
            image_url: r.image_url ?? null,
        }));
        renderRushDisplay();
    } catch (err) {
        console.warn("Rush fees: could not load.", err);
    }
}

// ==================== SAVE ====================

async function saveRushFee() {
    if (isRushLoading) return;
    isRushLoading = true;

    try {
        // Upload new image first if one was chosen
        if (pendingImageFile) {
            const formData = new FormData();
            formData.append("image", pendingImageFile);

            const imgResponse = await fetch("/api/rush-fees/upload-image", {
                method: "POST",
                body: formData,
            });

            if (!imgResponse.ok) throw new Error("Failed to upload image");
            const imgData = await imgResponse.json();
            draftFee.image_url = imgData.image_url ?? null;
        }

        if (editingRushIndex === null) {
            rushFees.push({ ...draftFee });
        } else {
            rushFees[editingRushIndex] = { ...draftFee };
        }

        const response = await fetch("/api/rush-fees", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({ rush_fees: rushFees }),
        });

        if (!response.ok) throw new Error("Failed to save rush fee");

        toast.success(
            editingRushIndex === null
                ? "Rush fee added successfully"
                : "Rush fee updated successfully",
        );
        await loadRushFees();
        closeRushModal();
    } catch (error) {
        console.error("Error saving rush fee:", error);
        toast.error(error.message || "Failed to save rush fee");
        if (editingRushIndex === null) rushFees.pop();
    } finally {
        isRushLoading = false;
    }
}

// ==================== DELETE ====================

async function deleteRushFee(idx) {
    if (isRushLoading) return;
    isRushLoading = true;

    try {
        rushFees.splice(idx, 1);

        const response = await fetch("/api/rush-fees", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({ rush_fees: rushFees }),
        });

        if (!response.ok) throw new Error("Failed to delete rush fee");

        toast.success("Rush fee deleted successfully");
        closeDeleteConfirm();
        closeRushModal();
        await loadRushFees();
    } catch (error) {
        console.error("Error deleting rush fee:", error);
        toast.error(error.message || "Failed to delete rush fee");
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

        // ---- Image area (A4 proportioned) ----
        const imgArea = document.createElement("div");
        imgArea.className = "rush_card_img_area";

        if (range.image_url) {
            const img = document.createElement("img");
            img.src = range.image_url;
            img.alt = `${range.label || "Rush fee"} reference`;
            img.className = "rush_card_img";
            imgArea.appendChild(img);
        } else {
            const noImg = document.createElement("div");
            noImg.className = "rush_card_img_placeholder";

            // Image icon
            const noImgSvg = document.createElementNS(
                "http://www.w3.org/2000/svg",
                "svg",
            );
            noImgSvg.setAttribute("viewBox", "0 -960 960 960");
            noImgSvg.setAttribute("height", "26px");
            noImgSvg.setAttribute("width", "26px");
            noImgSvg.setAttribute("class", "rush_card_no_img_svg");
            const noImgPath = document.createElementNS(
                "http://www.w3.org/2000/svg",
                "path",
            );
            noImgPath.setAttribute(
                "d",
                "M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm40-80h480L570-480 450-320l-90-120-120 160Zm-40 80v-560 560Z",
            );
            noImgSvg.appendChild(noImgPath);

            const noImgLabel = document.createElement("span");
            noImgLabel.textContent = "No image uploaded";

            noImg.appendChild(noImgSvg);
            noImg.appendChild(noImgLabel);
            imgArea.appendChild(noImg);
        }

        card.appendChild(imgArea);

        // ---- Card body ----
        const body = document.createElement("div");
        body.className = "rush_card_body";

        // Price range label
        const rangeLabel = document.createElement("p");
        rangeLabel.className = "rush_card_range_label";
        rangeLabel.textContent = range.label || "—";
        body.appendChild(rangeLabel);

        // Timeframe dropdown
        if (range.timeframes && range.timeframes.length > 0) {
            const tfLabel = document.createElement("span");
            tfLabel.className = "rush_card_tf_label";
            tfLabel.textContent = "Timeframe & fee";
            body.appendChild(tfLabel);

            const selectWrap = document.createElement("div");
            selectWrap.className = "rush_card_select_wrap";

            const select = document.createElement("select");
            select.className = "rush_card_select";
            select.setAttribute("aria-label", "Timeframe options");

            range.timeframes.forEach((tf) => {
                const opt = document.createElement("option");
                const pct = tf.percentage
                    ? `+${tf.percentage}% added to total`
                    : "—";
                const tfText = tf.label || "—";
                opt.textContent = `${tfText}  ·  ${pct}`;
                select.appendChild(opt);
            });

            selectWrap.appendChild(select);
            body.appendChild(selectWrap);
        }

        card.appendChild(body);

        // ---- Edit button ----
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
    });
    document.getElementById("rushRangeMin").addEventListener("input", (e) => {
        e.target.value = e.target.value.replace(/[^0-9]/g, "");
        if (draftFee)
            draftFee.min =
                e.target.value === "" ? null : parseInt(e.target.value);
    });
    document.getElementById("rushRangeMax").addEventListener("input", (e) => {
        e.target.value = e.target.value.replace(/[^0-9]/g, "");
        if (draftFee)
            draftFee.max =
                e.target.value === "" ? null : parseInt(e.target.value);
    });

    document
        .getElementById("rushAddTimeframeBtn")
        .addEventListener("click", () => {
            if (!draftFee) return;
            draftFee.timeframes.push({ label: "", percentage: "" });
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

    document
        .getElementById("rushDeleteCancelBtn")
        .addEventListener("click", closeDeleteConfirm);

    loadRushFees();
}

document.addEventListener("DOMContentLoaded", initRushFees);
