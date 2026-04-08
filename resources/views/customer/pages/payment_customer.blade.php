{{-- WARNING MODAL --}}
<div id="modalWarning" class="modal-overlay" style="display: none;">
    <div class="modal-container modal-warning">
        <div class="modal-header">
            <img src="{{ asset('images/brand_elements/label_name.png') }}" alt="Brand Label" class="modal-brand-logo">
        </div>
        <div class="modal-body">
            <div class="warning-title">WARNING!</div>
            <div class="warning-text">
                Please make sure that all the documents and information you will input are correct.
                There is no method of editing your submitted details after you submit the requirements.
                Failure to submit the accurate information may result in the cancellation of your orders without refund.
            </div>
        </div>
        <div class="modal-footer">
            <button class="action_btn cancel_btn" id="warningGoBackBtn">Go Back</button>
            <button class="action_btn submit_btn" id="warningProceedBtn">Proceed</button>
        </div>
    </div>
</div>

{{-- PAYMENT MODAL --}}
<div id="modalPayment" class="modal-overlay" style="display: none;">
    <div class="modal-container modal-payment">
        <div class="modal-header">
            <img src="{{ asset('images/brand_elements/label_name.png') }}" alt="Brand Label" class="modal-brand-logo">
            <h1>Payment</h1>
        </div>
        <div class="modal-body">
            {{-- Payment Method Dropdown --}}
            <div class="form_group">
                <label>Select Payment Method</label>
                <select id="paymentMethodSelect" class="form_input payment-select">
                    <option value="gcash">GCash</option>
                    <option value="bpi">BPI</option>
                    <option value="paymaya">PayMaya</option>
                </select>
            </div>

            {{-- QR Code Section --}}
            <div class="qr-section">
                <div class="qr-code-container">
                    <img id="qrCodeImage" src="" alt="QR Code" class="qr-code-img">
                </div>
                <div class="qr-amount-text">scan the QR and pay this amount: <strong>Php 0.00</strong></div>
            </div>

            {{-- Upload Screenshot --}}
            <div class="form_group">
                <label>Please upload the screenshot of your payment here.</label>
                <div class="upload-box" id="uploadBox">
                    <input type="file" id="paymentScreenshot" accept="image/png, image/jpeg, image/jpg" style="display: none;">
                    
                    {{-- Placeholder Trigger --}}
                    <div class="upload-placeholder" id="uploadPlaceholder" style="cursor: pointer;">
                        <span class="upload-icon">📸</span>
                        <span>Click to upload or drag & drop</span>
                        <span class="upload-hint">PNG, JPG, JPEG (max 5MB)</span>
                    </div>

                    {{-- Preview Container --}}
                    <div class="upload-preview" id="uploadPreview" style="display: none;">
                        <img id="previewImage" src="#" alt="Preview" style="cursor: pointer;" title="Click to change image">
                        <div class="preview-actions">
                            <button type="button" class="preview-btn view-btn" id="viewImageBtn">View</button>
                            <button type="button" class="preview-btn remove-btn" id="removeImageBtn">Remove</button>
                        </div>
                    </div>
                </div>
                <div id="uploadError" class="error_message" style="display: none;">Please upload a screenshot of your payment.</div>
            </div>

            {{-- Reference Number --}}
            <div class="form_group">
                <label>Reference Number</label>
                <input type="text" id="referenceNumber" class="form_input reference_number" placeholder="Enter 10-14 digit reference number" maxlength="14" inputmode="numeric">
                <div id="referenceError" class="error_message" style="display: none;">Reference number must be 10-14 digits.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="action_btn cancel_btn" id="paymentCancelBtn">Cancel</button>
            <button class="action_btn submit_btn" id="paymentPayBtn">Pay</button>
        </div>
    </div>
</div>

{{-- CONFIRMATION MODAL --}}
<div id="modalConfirmation" class="modal-overlay" style="display: none;">
    <div class="modal-container modal-confirmation">
        <div class="modal-header">
            <img src="{{ asset('images/brand_elements/label_name.png') }}" alt="Brand Label" class="modal-brand-logo">
        </div>
        <div class="modal-body">
            <div class="confirmation-icon">✓</div>
            <div class="confirmation-title">Confirm Payment Details</div>
            <div class="confirmation-text">
                Are you sure the information is accurate?<br>
                You can no longer edit the provided details after proceeding.
            </div>
        </div>
        <div class="modal-footer">
            <button class="action_btn cancel_btn" id="confirmationGoBackBtn">Go Back</button>
            <button class="action_btn submit_btn" id="confirmationConfirmBtn">Confirm</button>
        </div>
    </div>
</div>

{{-- FULLSCREEN IMAGE PREVIEW MODAL --}}
<div id="modalImageView" class="modal-overlay" style="display: none; z-index: 10000;">
    <div class="image-view-container">
        <button type="button" class="image-view-close" id="closeImageViewBtn">&times;</button>
        <img id="fullSizePreviewImage" src="#" alt="Full Size Payment Preview">
    </div>
</div>