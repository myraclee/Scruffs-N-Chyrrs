document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.edit_profile_form');
  const emailInput = document.getElementById('email');
  const contactInput = document.getElementById('contact_number');

  if (!form) return;

  // Email validation function
  function validateEmail(email) {
    return email.includes('@');
  }

  // Phone validation function
  function validatePhone(phone) {
    return phone.startsWith('+639');
  }

  // Form submission validation
  form.addEventListener('submit', (e) => {
    let isValid = true;

    // Validate email
    const email = emailInput.value.trim();
    if (!validateEmail(email)) {
      isValid = false;
      showFieldError(emailInput, 'Email must contain an @ symbol');
    } else {
      clearFieldError(emailInput);
    }

    // Validate phone
    const phone = contactInput.value.trim();
    if (!validatePhone(phone)) {
      isValid = false;
      showFieldError(contactInput, 'Phone number must start with +639');
    } else {
      clearFieldError(contactInput);
    }

    if (!isValid) {
      e.preventDefault();
    }
  });

  // Real-time validation feedback
  emailInput.addEventListener('blur', () => {
    const email = emailInput.value.trim();
    if (email && !validateEmail(email)) {
      showFieldError(emailInput, 'Email must contain an @ symbol');
    } else {
      clearFieldError(emailInput);
    }
  });

  contactInput.addEventListener('blur', () => {
    const phone = contactInput.value.trim();
    if (phone && !validatePhone(phone)) {
      showFieldError(contactInput, 'Phone number must start with +639');
    } else {
      clearFieldError(contactInput);
    }
  });

  function showFieldError(field, message) {
    let errorElement = field.parentElement.querySelector('.error_message');
    if (!errorElement) {
      errorElement = document.createElement('span');
      errorElement.className = 'error_message';
      field.parentElement.appendChild(errorElement);
    }
    errorElement.textContent = message;
    field.classList.add('input_error');
  }

  function clearFieldError(field) {
    const errorElement = field.parentElement.querySelector('.error_message');
    if (errorElement) {
      errorElement.textContent = '';
    }
    field.classList.remove('input_error');
  }
});
