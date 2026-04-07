// resources/js/customer/pages/payment_customer.js

let currentFile = null;
let currentModal = null;
let pendingConfirmAction = null;
let isConfirming = false;
let currentPayableAmount = "Php 0.00";

const qrImages = {
    gcash: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%230066CC"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="20" font-family="monospace"%3EGCash QR%3C/text%3E%3C/svg%3E',
    bpi: 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23C1272D"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="20" font-family="monospace"%3EBPI QR%3C/text%3E%3C/svg%3E',
    paymaya:
        'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect width="200" height="200" fill="%23005C9E"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="20" font-family="monospace"%3EPayMaya QR%3C/text%3E%3C/svg%3E',
};

function lockBodyScroll() {
    document.body.style.overflow = "hidden";
    document.body.style.paddingRight = "15px";
}
function unlockBodyScroll() {
    document.body.style.overflow = "";
    document.body.style.paddingRight = "";
}

function showModal(modalId) {
    document.getElementById("modalWarning").style.display = "none";
    document.getElementById("modalPayment").style.display = "none";
    document.getElementById("modalConfirmation").style.display = "none";
    const target = document.getElementById(modalId);
    if (target) {
        target.style.display = "flex";
        currentModal = modalId;
        lockBodyScroll();
    }
}
function closeAllModals() {
    document.getElementById("modalWarning").style.display = "none";
    document.getElementById("modalPayment").style.display = "none";
    document.getElementById("modalConfirmation").style.display = "none";
    currentModal = null;
    unlockBodyScroll();
}

function resetPaymentForm() {
    const fileInput = document.getElementById("paymentScreenshot");
    if (fileInput) fileInput.value = "";
    currentFile = null;
    document.getElementById("uploadPlaceholder").style.display = "flex";
    document.getElementById("uploadPreview").style.display = "none";
    document.getElementById("previewImage").src = "#";
    const refInput = document.getElementById("referenceNumber");
    if (refInput) refInput.value = "";
    const paymentSelect = document.getElementById("paymentMethodSelect");
    if (paymentSelect) paymentSelect.value = "gcash";
    updateQRCode();
    document.getElementById("uploadError").style.display = "none";
    document.getElementById("referenceError").style.display = "none";
}

function updateQRCode() {
    const select = document.getElementById("paymentMethodSelect");
    const method = select.value;
    const qrImg = document.getElementById("qrCodeImage");
    if (qrImg && qrImages[method]) qrImg.src = qrImages[method];

    const amountText = document.querySelector(".qr-amount-text strong");
    if (amountText) {
        amountText.textContent = currentPayableAmount;
    }
}

function validatePaymentDetails() {
    let isValid = true;
    if (!currentFile) {
        document.getElementById("uploadError").style.display = "block";
        isValid = false;
    } else {
        document.getElementById("uploadError").style.display = "none";
    }
    const refValue = document.getElementById("referenceNumber").value.trim();
    const digitsOnly = refValue.replace(/\D/g, "");
    if (digitsOnly.length < 10 || digitsOnly.length > 14) {
        document.getElementById("referenceError").style.display = "block";
        isValid = false;
    } else {
        document.getElementById("referenceError").style.display = "none";
        if (refValue !== digitsOnly)
            document.getElementById("referenceNumber").value = digitsOnly;
    }
    return isValid;
}

function setupFileUpload() {
    const fileInput = document.getElementById("paymentScreenshot");
    const uploadBox = document.getElementById("uploadBox");
    const placeholder = document.getElementById("uploadPlaceholder");
    const previewContainer = document.getElementById("uploadPreview");
    const previewImg = document.getElementById("previewImage");
    const viewBtn = document.getElementById("viewImageBtn");
    const removeBtn = document.getElementById("removeImageBtn");

    uploadBox.addEventListener("click", (e) => {
        if (e.target === removeBtn || e.target.closest(".remove-btn")) return;
        fileInput.click();
    });
    fileInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const allowedTypes = ["image/png", "image/jpeg", "image/jpg"];
        if (!allowedTypes.includes(file.type)) {
            alert("Only PNG, JPG, JPEG images are allowed.");
            fileInput.value = "";
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert("File size must be less than 5MB.");
            fileInput.value = "";
            return;
        }
        currentFile = file;
        const reader = new FileReader();
        reader.onload = (ev) => {
            previewImg.src = ev.target.result;
            placeholder.style.display = "none";
            previewContainer.style.display = "flex";
            document.getElementById("uploadError").style.display = "none";
        };
        reader.readAsDataURL(file);
    });
    if (viewBtn) {
        viewBtn.addEventListener("click", () => {
            if (previewImg.src && previewImg.src !== "#")
                window.open(previewImg.src, "_blank");
        });
    }
    if (removeBtn) {
        removeBtn.addEventListener("click", () => {
            fileInput.value = "";
            currentFile = null;
            placeholder.style.display = "flex";
            previewContainer.style.display = "none";
            previewImg.src = "#";
            document.getElementById("uploadError").style.display = "none";
        });
    }
}

function setupReferenceValidation() {
    const refInput = document.getElementById("referenceNumber");
    refInput.addEventListener("input", (e) => {
        let value = e.target.value;
        value = value.replace(/\D/g, "");
        if (value.length > 14) value = value.slice(0, 14);
        e.target.value = value;
    });
}

function submitPayment() {
    const paymentMethod = document.getElementById("paymentMethodSelect").value;
    const referenceNo = document.getElementById("referenceNumber").value.trim();
    return {
        paymentMethod,
        referenceNo,
        file: currentFile,
    };
}

function initModalEvents() {
    document
        .getElementById("warningProceedBtn")
        .addEventListener("click", () => showModal("modalPayment"));
    document
        .getElementById("warningGoBackBtn")
        .addEventListener("click", () => {
            closeAllModals();
            resetPaymentForm();
        });
    document
        .getElementById("paymentCancelBtn")
        .addEventListener("click", () => {
            closeAllModals();
            resetPaymentForm();
        });
    document.getElementById("paymentPayBtn").addEventListener("click", () => {
        if (validatePaymentDetails()) showModal("modalConfirmation");
    });
    document
        .getElementById("confirmationGoBackBtn")
        .addEventListener("click", () => showModal("modalPayment"));
    document
        .getElementById("confirmationConfirmBtn")
        .addEventListener("click", async () => {
            if (isConfirming) return;

            const paymentData = submitPayment();
            const confirmBtn = document.getElementById("confirmationConfirmBtn");

            try {
                isConfirming = true;
                if (confirmBtn) {
                    confirmBtn.disabled = true;
                    confirmBtn.textContent = "Processing...";
                }

                if (typeof pendingConfirmAction === "function") {
                    const actionResult = await pendingConfirmAction(paymentData);
                    if (actionResult === false) {
                        return;
                    }

                    pendingConfirmAction = null;
                } else {
                    const container = document.querySelector(".orders_container");
                    if (container) {
                        const successDiv = document.createElement("div");
                        successDiv.className = "alert alert_success";
                        successDiv.innerHTML =
                            '<span class="alert_icon">✓</span> Payment proof submitted successfully!';
                        container.insertBefore(successDiv, container.firstChild);
                        setTimeout(() => successDiv.remove(), 5000);
                    } else {
                        alert("Payment confirmation submitted! (Demo)");
                    }
                }

                closeAllModals();
                resetPaymentForm();
            } catch (error) {
                console.error("Payment confirmation action failed:", error);
            } finally {
                isConfirming = false;
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = "Confirm";
                }
            }
        });
    const paymentSelect = document.getElementById("paymentMethodSelect");
    if (paymentSelect) paymentSelect.addEventListener("change", updateQRCode);
}

window.openPaymentModal = function (event, options = {}) {
    if (event) event.preventDefault();
    pendingConfirmAction =
        typeof options.onConfirm === "function" ? options.onConfirm : null;
    currentPayableAmount =
        typeof options.payableAmount === "string" && options.payableAmount.trim()
            ? options.payableAmount.trim()
            : "Php 0.00";
    resetPaymentForm();
    showModal("modalWarning");
};

document.addEventListener("DOMContentLoaded", () => {
    setupFileUpload();
    setupReferenceValidation();
    initModalEvents();
    updateQRCode();
    closeAllModals();
    // Prevent closing when clicking overlay (do nothing)
    document.querySelectorAll(".modal-overlay").forEach((overlay) => {
        overlay.addEventListener("click", (e) => e.stopPropagation());
    });
    document.querySelectorAll(".modal-container").forEach((modal) => {
        modal.addEventListener("click", (e) => e.stopPropagation());
    });
});
