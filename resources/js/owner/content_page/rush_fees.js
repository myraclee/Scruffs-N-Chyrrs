// ==================== RUSH FEES ====================
import toast from "../../utils/toast.js";

// ==================== STATE ====================
// rushFees = [{ id?, label, min, max, timeframes: [{ label, percentage }] }]

let rushFees = [];
let editingRushIndex = null; // null = adding new, number = editing existing
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

// Working copy — user edits this, only committed on Save
let draftFee = null;

function buildDraftFromIndex(idx) {
    const src = rushFees[idx];
    return {
        label: src.label,
        min: src.min,
        max: src.max,
        timeframes: src.timeframes.map((tf) => ({ ...tf })),
    };
}

function freshDraft() {
    return {
        label: "",
        min: null,
        max: null,
        timeframes: [{ label: "", percentage: "" }],
    };
}

// ==================== OPEN / CLOSE MODALS ====================

const rushModalOverlay = document.getElementById("rushFeeModalOverlay");
const rushDeleteOverlay = document.getElementById("rushDeleteConfirmOverlay");

function openAddModal() {
    editingRushIndex = null;
    draftFee = freshDraft();
    document.getElementById("rushModalTitle").textContent = "Add Rush Fee";
    document.getElementById("rushDeleteBtn").classList.add("btn_hidden");
    renderRushModalForm();
    rushModalOverlay.classList.add("active");
}

function openEditModal(idx) {
    editingRushIndex = idx;
    draftFee = buildDraftFromIndex(idx);
    document.getElementById("rushModalTitle").textContent = "Edit Rush Fee";
    document.getElementById("rushDeleteBtn").classList.remove("btn_hidden");
    renderRushModalForm();
    rushModalOverlay.classList.add("active");
}

function closeRushModal() {
    rushModalOverlay.classList.remove("active");
    draftFee = null;
    editingRushIndex = null;
}

/*
 * Change #2: Clicking outside the rush fee modal no longer closes it.
 * The modal can only be closed via the Cancel button.
 * The delete CONFIRMATION modal still closes on outside click (see below).
 */

// ==================== DELETE CONFIRM MODAL ====================

function openDeleteConfirm(idx) {
    pendingDeleteIndex = idx;
    rushDeleteOverlay.classList.add("active");
}

function closeDeleteConfirm() {
    pendingDeleteIndex = null;
    rushDeleteOverlay.classList.remove("active");
}

// Delete confirm modal still closes on outside click — intentional
rushDeleteOverlay.addEventListener("click", (e) => {
    if (e.target === rushDeleteOverlay) closeDeleteConfirm();
});

// ==================== RENDER FORM ====================

function renderRushModalForm() {
    // Range label
    document.getElementById("rushRangeLabel").value = draftFee.label;
    document.getElementById("rushRangeMin").value =
        draftFee.min !== null ? draftFee.min : "";
    document.getElementById("rushRangeMax").value =
        draftFee.max !== null ? draftFee.max : "";

    // Timeframe rows
    renderTimeframeRows();
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

        // Delete row SVG — only show when there's more than one row
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
        // Commit draft into rushFees
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
        // Roll back optimistic push if needed
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

        // Table
        const table = document.createElement("table");
        table.className = "rush_display_table";

        const thead = document.createElement("thead");
        const colRow = document.createElement("tr");

        const tfTh = document.createElement("th");
        tfTh.className = "rush_display_col_header";
        tfTh.textContent = range.label || "—";

        const pctTh = document.createElement("th");
        pctTh.className = "rush_display_col_header rush_display_col_pct";
        pctTh.textContent = "Fee";

        colRow.appendChild(tfTh);
        colRow.appendChild(pctTh);
        thead.appendChild(colRow);
        table.appendChild(thead);

        const tbody = document.createElement("tbody");
        (range.timeframes ?? []).forEach((tf) => {
            const tr = document.createElement("tr");

            const tdTf = document.createElement("td");
            tdTf.className = "rush_display_tf";
            tdTf.textContent = tf.label || "—";

            const tdPct = document.createElement("td");
            tdPct.className = "rush_display_pct";
            tdPct.textContent = tf.percentage ? `+${tf.percentage}%` : "—";

            tr.appendChild(tdTf);
            tr.appendChild(tdPct);
            tbody.appendChild(tr);
        });

        table.appendChild(tbody);
        card.appendChild(table);

        // Edit button below the table
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
    // Add rush fee button
    document
        .getElementById("addRushFeeBtn")
        .addEventListener("click", openAddModal);

    // Modal form field listeners (range label / min / max)
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

    // Add timeframe row
    document
        .getElementById("rushAddTimeframeBtn")
        .addEventListener("click", () => {
            if (!draftFee) return;
            draftFee.timeframes.push({ label: "", percentage: "" });
            renderTimeframeRows();
        });

    // Save
    document
        .getElementById("rushSaveBtn")
        .addEventListener("click", saveRushFee);

    // Cancel — only way to close the rush fee modal
    document
        .getElementById("rushCancelBtn")
        .addEventListener("click", closeRushModal);

    // Delete button (inside edit modal — opens confirmation)
    document.getElementById("rushDeleteBtn").addEventListener("click", () => {
        if (editingRushIndex === null) return;
        openDeleteConfirm(editingRushIndex);
    });

    // Delete confirm — proceed
    document
        .getElementById("rushDeleteConfirmBtn")
        .addEventListener("click", async () => {
            if (pendingDeleteIndex === null) return;
            await deleteRushFee(pendingDeleteIndex);
        });

    // Delete confirm — cancel
    document
        .getElementById("rushDeleteCancelBtn")
        .addEventListener("click", closeDeleteConfirm);

    loadRushFees();
}

document.addEventListener("DOMContentLoaded", initRushFees);