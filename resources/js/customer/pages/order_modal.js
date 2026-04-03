// ====== EXCEL-STYLE ORDER MODAL LOGIC ====== //

document.addEventListener("DOMContentLoaded", () => {
    // Only run this script if the modal actually exists on the page
    const orderModal = document.getElementById("orderModal");
    if (!orderModal) return;

    // --- State Variables (The Cart) ---
    let orderItems = [];
    let grandTotal = 0;

    // --- DOM Elements ---
    const closeBtn = document.getElementById("closeOrderModal");
    const addItemBtn = document.getElementById("addItemBtn");
    const submitBtn = document.getElementById("submitMasterOrderBtn");

    // Inputs
    const itemType = document.getElementById("itemType");
    const itemLamination = document.getElementById("itemLamination");
    const itemQuantity = document.getElementById("itemQuantity");
    const itemFileName = document.getElementById("itemFileName");
    const rushFeeSelect = document.getElementById("rushFeeSelect");
    const generalDriveLink = document.getElementById("generalDriveLink");

    // Displays
    const cartContainer = document.getElementById("cartItemsContainer");
    const emptyMsg = document.getElementById("emptyCartMsg");
    const grandTotalDisplay = document.getElementById("grandTotalDisplay");

    // --- Open/Close Modal (Attached to window so the HTML button can see it!) ---
    // --- Open/Close Modal ---
    window.closeOrderModal = function () {
        orderModal.classList.remove("active");
        orderModal.style.display = "none"; // Ensure it hides!
        document.body.style.overflow = "auto";
    };

    window.openOrderModal = function () {
        orderModal.classList.add("active");
        orderModal.style.display = "flex"; // Uses flex to center it
        document.body.style.overflow = "hidden";
    };

    closeBtn.addEventListener("click", () => {
        orderModal.classList.remove("active");
        document.body.style.overflow = "auto";
    });

    // --- Format Currency ---
    const formatMoney = (amount) => {
        return new Intl.NumberFormat("en-PH", {
            style: "currency",
            currency: "PHP",
        }).format(amount);
    };

    // --- Add Item to Cart ---
    addItemBtn.addEventListener("click", () => {
        const qty = parseInt(itemQuantity.value) || 1;
        const category = document.getElementById("currentItemCategory").value;
        const basePrice = parseFloat(
            document.getElementById("currentItemBasePrice").value,
        );

        // Create the item object
        const newItem = {
            id: Date.now(), // Unique ID for deleting
            category: category,
            type: itemType.value,
            lamination: itemLamination.value,
            quantity: qty,
            price: basePrice,
            design_name_link: itemFileName.value || "Not provided",
            needs_layout: true, // Defaulting to true for stickers as requested by SA
        };

        orderItems.push(newItem);
        updateCartDisplay();

        // Reset form for next item
        itemQuantity.value = 1;
        itemFileName.value = "";
    });

    // --- Update Cart UI and Totals ---
    function updateCartDisplay() {
        cartContainer.innerHTML = ""; // Clear current display
        let currentTotal = parseFloat(rushFeeSelect.value); // Start with rush fee

        if (orderItems.length === 0) {
            cartContainer.appendChild(emptyMsg);
            grandTotalDisplay.innerText = formatMoney(currentTotal);
            return;
        }

        orderItems.forEach((item) => {
            // 1. Calculate this specific item's total (Excel Logic)
            let layoutFee = 0;
            let discount = 0;

            if (item.category === "Stickers") {
                if (item.needs_layout) layoutFee = 35;
                if (item.quantity >= 7) discount = 5 * item.quantity;
            } else if (item.category === "Button Pins") {
                if (item.quantity >= 10) discount = 3 * item.quantity;
            }

            const itemTotal = item.price * item.quantity + layoutFee - discount;
            currentTotal += itemTotal;

            // 2. Build the HTML for the row
            const rowHtml = `
                <div class="file_spec_row" id="item-${item.id}">
                    <div class="file_spec_row_header">
                        <span class="file_spec_number">${item.type} | ${item.lamination}</span>
                        <button type="button" class="remove_file_spec_btn" onclick="removeItem(${item.id})">✕</button>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-family: 'Coolvetica', sans-serif; font-size: 13px;">
                        <span>File: ${item.design_name_link}</span>
                        <span>Qty: ${item.quantity}</span>
                        <span style="font-weight: bold; color: #682c7a;">${formatMoney(itemTotal)}</span>
                    </div>
                    ${discount > 0 ? `<div style="color: #2e7d32; font-size: 11px; margin-top: 5px;">Includes ${formatMoney(discount)} Bulk Discount!</div>` : ""}
                </div>
            `;
            cartContainer.insertAdjacentHTML("beforeend", rowHtml);
        });

        grandTotalDisplay.innerText = formatMoney(currentTotal);
    }

    // --- Remove Item ---
    window.removeItem = function (id) {
        orderItems = orderItems.filter((item) => item.id !== id);
        updateCartDisplay();
    };

    // --- Listen for Rush Fee Changes ---
    rushFeeSelect.addEventListener("change", updateCartDisplay);

    // --- Submit to Backend ---
    submitBtn.addEventListener("click", async () => {
        // Validation
        if (!generalDriveLink.value) {
            alert("Please provide your main Google Drive link!");
            return;
        }
        if (orderItems.length === 0) {
            alert("Please add at least one design to your order!");
            return;
        }

        // Change button to loading state
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
        submitBtn.disabled = true;

        try {
            // Send data to the Controller we built!
            const response = await fetch("/place-custom-order", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    general_gdrive_link: generalDriveLink.value,
                    rush_fee: rushFeeSelect.value,
                    items: orderItems,
                }),
            });

            const data = await response.json();

            if (data.success) {
                // Success! Close modal and empty cart
                alert(data.message);
                orderItems = [];
                generalDriveLink.value = "";
                rushFeeSelect.value = "0";
                updateCartDisplay();
                orderModal.classList.remove("active");
            } else {
                alert("Something went wrong. Please try again.");
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Connection error. Are you logged in?");
        } finally {
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
});
