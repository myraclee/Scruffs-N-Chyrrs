/**
 * Products Page - Customer Facing
 * Fetches and displays products from the database
 * Handles price list modal interactions
 */

import ProductAPI from '/resources/js/api/productApi.js';

// ================= STATE =================
let products = [];
let currentModalProduct = null;
let currentPriceImageIndex = 0;
let currentPriceImages = [];

// ================= DOM ELEMENTS =================
const productsGrid = document.getElementById('productsGrid');
const priceListModal = document.getElementById('priceListModal');
const closePriceModalBtn = document.getElementById('closePriceModal');
const priceModalTitle = document.getElementById('priceModalTitle');
const priceListImage = document.getElementById('priceListImage');
const prevPriceImageBtn = document.getElementById('prevPriceImage');
const nextPriceImageBtn = document.getElementById('nextPriceImage');
const priceImageCounter = document.getElementById('priceImageCounter');

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', async () => {
  await loadAndRenderProducts();
  setupModalListeners();
});

/**
 * Load products from API and render them
 */
async function loadAndRenderProducts() {
  try {
    productsGrid.innerHTML = '<p style="text-align: center; color: #999; padding: 40px; grid-column: 1 / -1; font-family: Coolvetica;">Loading products...</p>';

    const productsData = await ProductAPI.getAllProducts();
    products = productsData || [];

    console.log('Loaded products from API:', products);

    if (!products || products.length === 0) {
      productsGrid.innerHTML = '<div class="products_empty_state">No products available yet. Check back soon!</div>';
      return;
    }

    // Debug: Log first product structure to see property names
    if (products.length > 0) {
      console.log('First product structure:', products[0]);
      console.log('Price images property (camelCase):', products[0].priceImages);
      console.log('Price images property (snake_case):', products[0].price_images);
    }

    renderProducts();
  } catch (error) {
    console.error('Error loading products:', error);
    productsGrid.innerHTML = '<div class="products_empty_state">Unable to load products. Please try again later.</div>';
  }
}

/**
 * Render all products as cards in the grid
 */
function renderProducts() {
  productsGrid.innerHTML = '';

  products.forEach((product, index) => {
    const card = createProductCard(product, index);
    productsGrid.appendChild(card);
  });
}

/**
 * Create a product card element
 * @param {Object} product - Product data from API
 * @param {number} index - Card index for rotation
 * @returns {HTMLElement} Product card element
 */
function createProductCard(product, index) {
  const card = document.createElement('div');
  card.className = 'product_card';

  // Alternate rotation for visual interest (same as home page)
  card.style.setProperty('--card-rotation', index % 2 === 0 ? '-2deg' : '2deg');

  const imageUrl = product.cover_image_path
    ? `/storage/${product.cover_image_path}`
    : '/images/placeholder.png';

  const description = product.description ? product.description.substring(0, 50) : '';

  card.innerHTML = `
        <div class="product_card_image">
            <img src="${imageUrl}" alt="${product.name}" loading="lazy" />
        </div>
        <div class="product_card_info">
            <h3>${product.name}</h3>
            ${description ? `<p class="product_card_description">${description}${product.description.length > 50 ? '...' : ''}</p>` : ''}
        </div>
    `;

  // Add click listener to open price list modal
  card.addEventListener('click', () => {
    openPriceListModal(product);
  });

  return card;
}

/**
 * Open the price list modal for a product
 * @param {Object} product - Product data
 */
function openPriceListModal(product) {
  currentModalProduct = product;

  // Handle both camelCase (priceImages) and snake_case (price_images) from API response
  const priceImagesData = product.priceImages || product.price_images || [];

  currentPriceImages = priceImagesData && priceImagesData.length > 0
    ? priceImagesData.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0))
    : [];

  if (currentPriceImages.length === 0) {
    console.warn('No price list images for product:', product.name, 'Product data:', product);
    alert('No price list images available for this product.');
    return;
  }

  currentPriceImageIndex = 0;
  priceModalTitle.textContent = `${product.name} - Price List`;
  displayPriceImage(0);
  priceListModal.classList.add('active');
}

/**
 * Display a price image at the given index
 * @param {number} index - Image index
 */
function displayPriceImage(index) {
  if (index < 0 || index >= currentPriceImages.length) {
    return;
  }

  currentPriceImageIndex = index;
  const image = currentPriceImages[index];
  const imageUrl = `/storage/${image.image_path}`;

  priceListImage.src = imageUrl;
  priceListImage.alt = `${currentModalProduct.name} - Price List ${index + 1}`;
  priceImageCounter.textContent = `${index + 1} / ${currentPriceImages.length}`;

  // Update button states
  prevPriceImageBtn.disabled = index === 0;
  nextPriceImageBtn.disabled = index === currentPriceImages.length - 1;
}

/**
 * Close the price list modal
 */
function closePriceListModal() {
  priceListModal.classList.remove('active');
  currentModalProduct = null;
  currentPriceImages = [];
  currentPriceImageIndex = 0;
}

/**
 * Setup modal event listeners
 */
function setupModalListeners() {
  // Close button
  closePriceModalBtn.addEventListener('click', closePriceListModal);

  // Close on overlay click
  priceListModal.addEventListener('click', (e) => {
    if (e.target === priceListModal) {
      closePriceListModal();
    }
  });

  // Navigation buttons
  prevPriceImageBtn.addEventListener('click', () => {
    if (currentPriceImageIndex > 0) {
      displayPriceImage(currentPriceImageIndex - 1);
    }
  });

  nextPriceImageBtn.addEventListener('click', () => {
    if (currentPriceImageIndex < currentPriceImages.length - 1) {
      displayPriceImage(currentPriceImageIndex + 1);
    }
  });

  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    if (!priceListModal.classList.contains('active')) return;

    if (e.key === 'ArrowLeft') {
      prevPriceImageBtn.click();
    } else if (e.key === 'ArrowRight') {
      nextPriceImageBtn.click();
    } else if (e.key === 'Escape') {
      closePriceListModal();
    }
  });
}
