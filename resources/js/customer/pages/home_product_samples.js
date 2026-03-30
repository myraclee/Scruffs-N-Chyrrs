/**
 * Home Page Product Samples Loader
 * Fetches and displays product samples from the database on the home page
 */

// ================= IMPORTS =================
import ProductSampleAPI from "/resources/js/api/productSampleApi.js";

// ================= INITIALIZATION =================
document.addEventListener("DOMContentLoaded", async () => {
    await loadProductSamples();
});

/**
 * Load product samples from API and display on page
 */
async function loadProductSamples() {
    const container = document.querySelector(".home_page_product_samples");

    if (!container) {
        console.warn("Product samples container not found");
        return;
    }

    try {
        // Show loading state
        container.innerHTML =
            '<p style="text-align: center; color: #999; padding: 40px;">Loading product samples...</p>';

        // Fetch all product samples
        const samples = await ProductSampleAPI.getAllSamples();

        // Clear container
        container.innerHTML = "";

        // Handle empty state
        if (!samples || samples.length === 0) {
            container.innerHTML =
                '<p style="text-align: center; color: #999; padding: 40px;">No product samples available yet.</p>';
            return;
        }

        // Render each sample
        samples.forEach((sample, index) => {
            const sampleCard = document.createElement("div");
            sampleCard.className = "product_sample_card";

            // Use first sample image or placeholder
            const imageUrl =
                sample.images && sample.images.length > 0
                    ? `/storage/${sample.images[0].image_path}`
                    : "/images/placeholder.png";

            sampleCard.innerHTML = `
                <div class="product_sample_image">
                    <img src="${imageUrl}" alt="${sample.name}" loading="lazy" />
                </div>
                <div class="product_sample_info">
                    <h3>${sample.name}</h3>
                    ${sample.description ? `<p>${sample.description}</p>` : ""}
                </div>
            `;

            // Add click listener to open modal
            sampleCard.addEventListener("click", () => {
                window.openSampleModal(sample);
            });

            container.appendChild(sampleCard);
        });
    } catch (error) {
        console.error("Error loading product samples:", error);
        container.innerHTML =
            '<p style="text-align: center; color: #999; padding: 40px;">Unable to load product samples. Please try again later.</p>';
    }
}
