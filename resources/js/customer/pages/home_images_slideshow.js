/**
 * Home Page Images Slideshow
 * Fetches home images from API and displays as automated slideshow
 */

// ================= IMPORTS =================
import HomeImageAPI from '/resources/js/api/homeImageApi.js';

// ================= STATE =================
let homeImages = [];
let currentSlideIndex = 0;
let autoplayInterval = null;
const AUTOPLAY_DELAY = 4000; // 4 seconds

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', async () => {
  await loadHomeImages();
  setupSlideshow();
});

/**
 * Load home images from API
 */
async function loadHomeImages() {
  const slideshow = document.querySelector('.home_images_slideshow');

  if (!slideshow) {
    console.warn('Home images slideshow container not found');
    return;
  }

  try {
    // Fetch home images
    homeImages = await HomeImageAPI.getAllImages();

    if (!homeImages || homeImages.length === 0) {
      slideshow.classList.add('empty');
      return;
    }

    // Render slides
    renderSlides();
    renderDots();
    showSlide(0);
  } catch (error) {
    console.error('Error loading home images:', error);
    slideshow.classList.add('empty');
  }
}

/**
 * Render all slide elements
 */
function renderSlides() {
  const slideshow = document.querySelector('.home_images_slideshow');

  // Remove existing slides (except empty state)
  slideshow.querySelectorAll('.home_images_slide').forEach(slide => slide.remove());

  // Create slides
  homeImages.forEach((image, index) => {
    const slide = document.createElement('div');
    slide.className = 'home_images_slide';
    if (index === 0) slide.classList.add('active');

    const img = document.createElement('img');
    img.src = `/storage/${image.image_path}`;
    img.alt = `Home image ${index + 1}`;
    img.loading = 'lazy';

    slide.appendChild(img);
    slideshow.appendChild(slide);
  });

  // Update counter
  updateCounter();
}

/**
 * Render dot indicators
 */
function renderDots() {
  const slideshow = document.querySelector('.home_images_slideshow');

  // Remove existing dots
  slideshow.querySelectorAll('.home_images_dot').forEach(dot => dot.remove());

  // Create dots container if it doesn't exist
  let dotsContainer = slideshow.querySelector('.home_images_dots_container');
  if (!dotsContainer) {
    dotsContainer = document.createElement('div');
    dotsContainer.className = 'home_images_dots_container';
    slideshow.appendChild(dotsContainer);
  }

  // Create dots
  homeImages.forEach((_, index) => {
    const dot = document.createElement('button');
    dot.className = 'home_images_dot';
    if (index === 0) dot.classList.add('active');

    dot.addEventListener('click', () => {
      currentSlideIndex = index;
      showSlide(index);
      resetAutoplay();
    });

    dot.setAttribute('aria-label', `Go to slide ${index + 1}`);
    dotsContainer.appendChild(dot);
  });
}

/**
 * Show specific slide
 */
function showSlide(index) {
  const slides = document.querySelectorAll('.home_images_slide');
  const dots = document.querySelectorAll('.home_images_dot');

  // Validate index
  if (index >= homeImages.length) {
    currentSlideIndex = 0;
  } else if (index < 0) {
    currentSlideIndex = homeImages.length - 1;
  } else {
    currentSlideIndex = index;
  }

  // Update slides
  slides.forEach(slide => slide.classList.remove('active'));
  if (slides[currentSlideIndex]) {
    slides[currentSlideIndex].classList.add('active');
  }

  // Update dots
  dots.forEach(dot => dot.classList.remove('active'));
  if (dots[currentSlideIndex]) {
    dots[currentSlideIndex].classList.add('active');
  }

  updateCounter();
}

/**
 * Update image counter display
 */
function updateCounter() {
  let counter = document.querySelector('.home_images_counter');

  if (!counter) {
    counter = document.createElement('div');
    counter.className = 'home_images_counter';
    document.querySelector('.home_images_slideshow').appendChild(counter);
  }

  counter.textContent = `${currentSlideIndex + 1} / ${homeImages.length}`;
}

/**
 * Navigate to next slide
 */
function nextSlide() {
  showSlide(currentSlideIndex + 1);
  resetAutoplay();
}

/**
 * Navigate to previous slide
 */
function prevSlide() {
  showSlide(currentSlideIndex - 1);
  resetAutoplay();
}

/**
 * Setup slideshow controls and autoplay
 */
function setupSlideshow() {
  const slideshow = document.querySelector('.home_images_slideshow');

  if (!slideshow || homeImages.length === 0) {
    return;
  }

  // Create navigation buttons
  const prevBtn = document.createElement('button');
  prevBtn.className = 'home_images_nav_btn home_images_prev';
  prevBtn.textContent = '❮';
  prevBtn.setAttribute('aria-label', 'Previous slide');
  prevBtn.addEventListener('click', prevSlide);

  const nextBtn = document.createElement('button');
  nextBtn.className = 'home_images_nav_btn home_images_next';
  nextBtn.textContent = '❯';
  nextBtn.setAttribute('aria-label', 'Next slide');
  nextBtn.addEventListener('click', nextSlide);

  slideshow.appendChild(prevBtn);
  slideshow.appendChild(nextBtn);

  // Start autoplay
  startAutoplay();

  // Pause autoplay on hover
  slideshow.addEventListener('mouseenter', stopAutoplay);
  slideshow.addEventListener('mouseleave', startAutoplay);
}

/**
 * Start automatic slideshow
 */
function startAutoplay() {
  if (homeImages.length <= 1) return;

  autoplayInterval = setInterval(() => {
    currentSlideIndex++;
    if (currentSlideIndex >= homeImages.length) {
      currentSlideIndex = 0;
    }
    showSlide(currentSlideIndex);
  }, AUTOPLAY_DELAY);
}

/**
 * Stop automatic slideshow
 */
function stopAutoplay() {
  if (autoplayInterval) {
    clearInterval(autoplayInterval);
    autoplayInterval = null;
  }
}

/**
 * Reset autoplay timer when user interacts
 */
function resetAutoplay() {
  stopAutoplay();
  startAutoplay();
}
