/**
 * Products Page - Customer Facing
 * Fetches and displays products from the database
 * Navigates to product detail page on click
 */

import ProductAPI from '/resources/js/api/productApi.js';

// ================= STATE =================
let products = [];

// ================= DOM ELEMENTS =================
const productsGrid = document.getElementById('productsGrid');

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', async () => {
  await loadAndRenderProducts();
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

  // Add click listener to navigate to product detail page
  card.addEventListener('click', () => {
    navigateToProductDetail(product);
  });

  return card;
}

/**
 * Navigate to the product detail page
 * @param {Object} product - Product data
 */
function navigateToProductDetail(product) {
  // Use product slug if available, fallback to generating slug from name
  const slug = product.slug || generateSlug(product.name);
  window.location.href = `/products/${slug}`;
}

/**
 * Generate a slug from a product name
 * @param {string} name - Product name
 * @returns {string} URL-safe slug
 */
function generateSlug(name) {
  return name
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '') // Remove special characters
    .replace(/\s+/g, '-') // Replace spaces with hyphens
    .replace(/-+/g, '-'); // Replace multiple hyphens with single hyphen
}
