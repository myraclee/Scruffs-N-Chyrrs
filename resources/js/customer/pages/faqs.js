/**
 * FAQs Page Interactivity
 * Handles accordion behavior and smooth interactions
 */

document.addEventListener('DOMContentLoaded', () => {
  const faqItems = document.querySelectorAll('.faq_item details');

  faqItems.forEach((item) => {
    item.addEventListener('toggle', (e) => {
      // Close other open items when opening a new one (optional - remove if you want multiple open at once)
      // if (e.target.open) {
      //     faqItems.forEach((otherItem) => {
      //         if (otherItem !== item) {
      //             otherItem.open = false;
      //         }
      //     });
      // }
    });
  });

  // Smooth scroll to FAQ if hash is present
  if (window.location.hash) {
    const target = document.querySelector(window.location.hash);
    if (target && target.closest('.faq_item')) {
      const details = target.closest('details');
      if (details) {
        details.open = true;
        setTimeout(() => {
          target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }, 100);
      }
    }
  }
});
