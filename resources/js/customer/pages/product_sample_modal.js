/**
 * Product Sample Modal Gallery
 * Handles opening, closing, and navigation of product sample image galleries
 * Features: Lazy-loading, keyboard nav, touch/swipe support, fullscreen mode
 */

// ================= STATE MANAGEMENT =================
let currentSample = null;
let currentImageIndex = 0;
const loadedImages = {}; // { sampleId: { imageIndex: true } }

// ================= DOM ELEMENTS =================
let modalOverlay = null;
let modalBox = null;
let closeBtn = null;
let fullscreenBtn = null;
let mainImage = null;
let mainImageWrapper = null;
let thumbnailsGrid = null;
let titleEl = null;
let descriptionEl = null;

// ================= TOUCH/SWIPE TRACKING =================
let touchStartX = 0;
let touchEndX = 0;

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', () => {
  initializeModal();
});

/**
 * Initialize modal DOM references and event listeners
 */
function initializeModal() {
  // Get DOM elements
  modalOverlay = document.getElementById('sampleGalleryModal');
  modalBox = modalOverlay?.querySelector('.sample_gallery_modal');
  closeBtn = modalOverlay?.querySelector('.sample_gallery_close_btn');
  fullscreenBtn = modalOverlay?.querySelector('.sample_gallery_fullscreen_btn');
  mainImage = modalOverlay?.querySelector('.sample_gallery_main_image');
  mainImageWrapper = modalOverlay?.querySelector('.sample_gallery_main_image_wrapper');
  thumbnailsGrid = modalOverlay?.querySelector('.sample_gallery_thumbnails_grid');
  titleEl = modalOverlay?.querySelector('.sample_gallery_title');
  descriptionEl = modalOverlay?.querySelector('.sample_gallery_description');

  if (!modalOverlay) {
    console.warn('Sample gallery modal not found in DOM');
    return;
  }

  // Setup event listeners
  closeBtn?.addEventListener('click', closeSampleModal);
  fullscreenBtn?.addEventListener('click', toggleFullscreen);
  modalOverlay?.addEventListener('click', handleOverlayClick);
  document.addEventListener('keydown', handleKeyboardNav);

  // Touch/swipe listeners on main image
  mainImageWrapper?.addEventListener('touchstart', handleTouchStart, false);
  mainImageWrapper?.addEventListener('touchend', handleTouchEnd, false);
}

// ================= MODAL LIFECYCLE =================

/**
 * Open the sample gallery modal with a specific sample
 * @param {Object} sample - Sample object { id, name, description, images: [] }
 */
function openSampleModal(sample) {
  if (!sample || !sample.images || sample.images.length === 0) {
    console.warn('Invalid sample or no images:', sample);
    return;
  }

  currentSample = sample;
  currentImageIndex = 0;

  // Initialize loaded images tracking for this sample
  if (!loadedImages[sample.id]) {
    loadedImages[sample.id] = {};
  }

  // FIX: Clear stale handlers and image src before loading new sample
  mainImage.onload = null;
  mainImage.onerror = null;
  mainImage.src = '';
  mainImage.classList.remove('loading');

  // Populate header
  titleEl.textContent = sample.name || 'Product Sample';
  descriptionEl.textContent = sample.description || '';

  // Render thumbnails
  renderThumbnails();

  // Load and display first image
  displayMainImage(0);

  // Show modal with fade animation
  modalOverlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

/**
 * Close the sample gallery modal
 */
function closeSampleModal() {
  if (!modalOverlay) return;

  // Remove active class triggers fade-out animation
  modalOverlay.classList.remove('active');
  document.body.style.overflow = 'auto';

  // Reset state after animation completes
  setTimeout(() => {
    // FIX: Fully clear handlers and state to prevent stale event triggers
    mainImage.onload = null;
    mainImage.onerror = null;
    mainImage.src = '';
    mainImage.classList.remove('loading');
    currentSample = null;
    currentImageIndex = 0;
    thumbnailsGrid.innerHTML = '';
  }, 300);
}

// ================= IMAGE DISPLAY & LAZY-LOADING =================

/**
 * Display the main image at the given index (lazy-load on demand)
 * @param {number} index - Image index in currentSample.images array
 */
function displayMainImage(index) {
  if (!currentSample || index < 0 || index >= currentSample.images.length) {
    return;
  }

  currentImageIndex = index;
  const image = currentSample.images[index];
  const imageUrl = `/storage/${image.image_path}`;

  // FIX: Always update src to fix backward navigation bug
  // Clear handlers first to prevent stale event handlers from firing
  mainImage.onload = null;
  mainImage.onerror = null;

  // Check if image is already cached in memory
  const isImageCached = loadedImages[currentSample.id][index];

  if (isImageCached) {
    // Image is cached: set src directly without handlers (browser retrieves from cache)
    mainImage.classList.remove('loading');
    mainImage.src = imageUrl;
  } else {
    // Image not cached: load it with handlers
    mainImage.classList.add('loading');
    mainImage.src = imageUrl;

    mainImage.onload = () => {
      mainImage.classList.remove('loading');
      loadedImages[currentSample.id][index] = true;
    };

    mainImage.onerror = () => {
      mainImage.classList.remove('loading');
      console.warn(
        'Failed to load image at index',
        index,
        '- URL:',
        imageUrl,
        '- This may indicate missing file or storage configuration issue'
      );
    };
  }

  // Update active thumbnail
  updateActiveThumbnail(index);
}

/**
 * Render thumbnail grid for current sample
 */
function renderThumbnails() {
  if (!currentSample || !thumbnailsGrid) return;

  thumbnailsGrid.innerHTML = '';

  currentSample.images.forEach((image, index) => {
    const thumbnail = document.createElement('div');
    thumbnail.className = 'sample_gallery_thumbnail';
    if (index === 0) thumbnail.classList.add('active');

    const img = document.createElement('img');
    // Only load thumbnail src on render (browsers typically lazy-load anyway)
    img.src = `/storage/${image.image_path}`;
    img.alt = `Image ${index + 1}`;
    img.loading = 'lazy';

    thumbnail.appendChild(img);

    // Click to display main image
    thumbnail.addEventListener('click', () => {
      displayMainImage(index);
    });

    thumbnailsGrid.appendChild(thumbnail);
  });
}

/**
 * Update active thumbnail highlight
 * @param {number} index - Index of active thumbnail
 */
function updateActiveThumbnail(index) {
  if (!thumbnailsGrid) return;

  const thumbnails = thumbnailsGrid.querySelectorAll('.sample_gallery_thumbnail');
  thumbnails.forEach((thumb, i) => {
    thumb.classList.toggle('active', i === index);
  });
}

// ================= KEYBOARD NAVIGATION =================

/**
 * Handle keyboard navigation (arrow keys, escape)
 * @param {KeyboardEvent} e - Keyboard event
 */
function handleKeyboardNav(e) {
  if (!currentSample || !modalOverlay.classList.contains('active')) {
    return;
  }

  switch (e.key) {
    case 'ArrowLeft':
      e.preventDefault();
      if (currentImageIndex > 0) {
        displayMainImage(currentImageIndex - 1);
      }
      break;

    case 'ArrowRight':
      e.preventDefault();
      if (currentImageIndex < currentSample.images.length - 1) {
        displayMainImage(currentImageIndex + 1);
      }
      break;

    case 'Escape':
      e.preventDefault();
      closeSampleModal();
      break;

    default:
      break;
  }
}

// ================= TOUCH & SWIPE SUPPORT =================

/**
 * Record touch start position
 * @param {TouchEvent} e - Touch event
 */
function handleTouchStart(e) {
  touchStartX = e.changedTouches[0].clientX;
}

/**
 * Handle touch end - determine if swipe occurred
 * @param {TouchEvent} e - Touch event
 */
function handleTouchEnd(e) {
  touchEndX = e.changedTouches[0].clientX;
  handleSwipe();
}

/**
 * Process swipe gesture
 */
function handleSwipe() {
  if (!currentSample) return;

  const swipeThreshold = 50; // pixels
  const diffX = touchStartX - touchEndX;

  // Swipe left (show next image)
  if (diffX > swipeThreshold && currentImageIndex < currentSample.images.length - 1) {
    displayMainImage(currentImageIndex + 1);
  }

  // Swipe right (show previous image)
  if (diffX < -swipeThreshold && currentImageIndex > 0) {
    displayMainImage(currentImageIndex - 1);
  }
}

// ================= FULLSCREEN MODE =================

/**
 * Toggle fullscreen mode for the main image
 */
function toggleFullscreen() {
  if (!mainImageWrapper) return;

  const isCurrentlyFullscreen =
    document.fullscreenElement ||
    document.webkitFullscreenElement ||
    document.mozFullScreenElement ||
    document.msFullscreenElement;

  if (isCurrentlyFullscreen) {
    // Exit fullscreen
    exitFullscreen();
  } else {
    // Enter fullscreen
    enterFullscreen();
  }
}

/**
 * Request fullscreen for image wrapper
 */
function enterFullscreen() {
  if (!mainImageWrapper) return;

  const elem = mainImageWrapper;

  if (elem.requestFullscreen) {
    elem.requestFullscreen();
  } else if (elem.webkitRequestFullscreen) {
    // Safari
    elem.webkitRequestFullscreen();
  } else if (elem.mozRequestFullScreen) {
    // Firefox
    elem.mozRequestFullScreen();
  } else if (elem.msRequestFullscreen) {
    // IE11
    elem.msRequestFullscreen();
  }
}

/**
 * Exit fullscreen mode
 */
function exitFullscreen() {
  if (document.fullscreenElement) {
    document.exitFullscreen();
  } else if (document.webkitFullscreenElement) {
    document.webkitExitFullscreen();
  } else if (document.mozFullScreenElement) {
    document.mozExitFullScreen();
  } else if (document.msFullscreenElement) {
    document.msExitFullscreen();
  }
}

// ================= EVENT HANDLERS =================

/**
 * Handle overlay click - close if clicking outside modal box
 * @param {MouseEvent} e - Mouse event
 */
function handleOverlayClick(e) {
  if (e.target === modalOverlay) {
    closeSampleModal();
  }
}

// ================= EXPORTS =================
// Make openSampleModal globally accessible for external calls
window.openSampleModal = openSampleModal;
