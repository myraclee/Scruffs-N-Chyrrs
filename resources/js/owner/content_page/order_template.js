document.addEventListener("DOMContentLoaded", function () {
    const templateOverlay = document.getElementById("templateModalOverlay");
    const deleteOverlay = document.getElementById("deleteTemplateModalOverlay");
    const modalTitle = document.getElementById("templateModalTitle");

    // =========================
    // OPEN ADD / EDIT MODAL
    // =========================
    window.openTemplateModal = function (isEdit) {
        if (!templateOverlay) return;
        modalTitle.textContent = isEdit ? "Edit Template" : "Add New Template";
        templateOverlay.classList.add("active");
    };

    // =========================
    // OPEN DELETE MODAL
    // =========================
    window.openDeleteTemplateModal = function () {
        if (!deleteOverlay) return;
        deleteOverlay.classList.add("active");
    };

    // =========================
    // CLOSE ALL MODALS
    // =========================
    window.closeTemplateModals = function () {
        if (templateOverlay) templateOverlay.classList.remove("active");
        if (deleteOverlay) deleteOverlay.classList.remove("active");
    };

    // =========================
    // CLOSE WHEN CLICKING OUTSIDE
    // =========================
    document.querySelectorAll(".modal_overlay").forEach((overlay) => {
        overlay.addEventListener("click", function (e) {
            if (e.target === this) {
                overlay.classList.remove("active");
            }
        });
    });

    // =========================
    // DYNAMIC OPTION ROWS
    // =========================
    const optionList = document.querySelector(".option_list");

    if (optionList) {
        // Update buttons visibility
        function updateOptionButtons() {
            const rows = optionList.querySelectorAll(".option_inputs");
            rows.forEach((row, idx) => {
                const addBtn = row.querySelector(".addProductOptions");
                const delBtn = row.querySelector(".deleteProductOptions");

                if (rows.length === 1) {
                    addBtn.style.display = "inline-block";
                    delBtn.style.display = "none";
                } else {
                    if (idx === rows.length - 1) {
                        addBtn.style.display = "inline-block";
                        delBtn.style.display = "none";
                    } else {
                        addBtn.style.display = "none";
                        delBtn.style.display = "inline-block";
                    }
                }
            });
        }

        // Create a new option row
        function createOptionRow() {
            const newRow = document.createElement("div");
            newRow.classList.add("option_inputs");
            newRow.innerHTML = `
                <input type="text" placeholder="Option">
                <input type="text" placeholder="Price">
                <div class="option_actions">
                    <svg xmlns="http://www.w3.org/2000/svg" class="deleteProductOptions" height="20px" viewBox="0 -960 960 960" width="20px" fill="#c83333"><path d="m339-288 141-141 141 141 51-51-141-141 141-141-51-51-141 141-141-141-51 51 141 141-141 141 51 51ZM480-96q79 0 149-30t122.5-82.5Q804-261 834-331T864-480q0-80-30-149.5t-82.5-122Q699-804 629.5-834T480-864q-80 0-149.5 30t-122 82.5Q156-699 126-629.5T96-480q0 79 30 149t82.5 122.5Q261-156 331-126T480-96Zm0-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z"/></svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="addProductOptions" height="20px" viewBox="0 -960 960 960" width="20px" fill="#682c7a"><path d="M444-288h72v-156h156v-72H516v-156h-72v156H288v72h156v156Zm36.28 192Q401-96 331-126t-122.5-82.5Q156-261 126-330.96t-30-149.5Q96-560 126-629.5q30-69.5 82.5-122T330.96-834q69.96-30 149.5-30t149.04 30q69.5 30 122 82.5T834-629.28q30 69.73 30 149Q864-401 834-331t-82.5 122.5Q699-156 629.28-126q-69.73 30-149 30Zm-.28-72q130 0 221-91t91-221q0-130-91-221t-221-91q-130 0-221 91t-91 221q0 130 91 221t221 91Zm0-312Z"/></svg>
                </div>
            `;

            // Event listeners
            newRow
                .querySelector(".addProductOptions")
                .addEventListener("click", createOptionRow);
            newRow
                .querySelector(".deleteProductOptions")
                .addEventListener("click", function () {
                    newRow.remove();
                    updateOptionButtons();
                });

            optionList.appendChild(newRow);
            updateOptionButtons();
        }

        // Initial setup
        updateOptionButtons();

        // Attach event to initial add button
        const initialAddBtn = optionList.querySelector(".addProductOptions");
        if (initialAddBtn) {
            initialAddBtn.addEventListener("click", createOptionRow);
        }

        // Attach delete to initial rows
        optionList
            .querySelectorAll(".deleteProductOptions")
            .forEach((delBtn) => {
                delBtn.addEventListener("click", function () {
                    delBtn.closest(".option_inputs").remove();
                    updateOptionButtons();
                });
            });
    }
});
