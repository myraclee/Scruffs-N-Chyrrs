document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form[action*="signup"]');
  const emailInput = document.getElementById('email');
  const contactInput = document.getElementById('contact_number');
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('password_confirmation');

  if (!form) return;

  // Email validation function
  function validateEmail(email) {
    return email.includes('@');
  }

  // Phone validation function
  function validatePhone(phone) {
    return phone.startsWith('+639');
  }

  // Password validation function
  function validatePassword(password) {
    const errors = [];

    if (password.length < 8) {
      errors.push('At least 8 characters');
    }
    if (!/[A-Z]/.test(password)) {
      errors.push('At least 1 uppercase letter');
    }
    if (!/[a-z]/.test(password)) {
      errors.push('At least 1 lowercase letter');
    }
    if (!/[0-9]/.test(password)) {
      errors.push('At least 1 number');
    }
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
      errors.push('At least 1 symbol (!@#$%^&* etc.)');
    }

    return errors;
  }

  // Show password validation hints
  if (passwordInput) {
    passwordInput.addEventListener('input', () => {
      const password = passwordInput.value;
      const errors = validatePassword(password);

      let hintsContainer = passwordInput.parentElement.querySelector('.password_hints');
      if (!hintsContainer) {
        hintsContainer = document.createElement('div');
        hintsContainer.className = 'password_hints';
        passwordInput.parentElement.appendChild(hintsContainer);
      }

      if (password.length === 0) {
        hintsContainer.innerHTML = '';
        return;
      }

      if (errors.length === 0) {
        hintsContainer.innerHTML = '<p class="hint_success">✓ Password meets all requirements</p>';
      } else {
        let hintsHTML = '<p class="hint_label">Password must have:</p><ul class="hints_list">';
        const allChecks = [
          { text: 'At least 8 characters', regex: /.{8,}/ },
          { text: 'At least 1 uppercase letter', regex: /[A-Z]/ },
          { text: 'At least 1 lowercase letter', regex: /[a-z]/ },
          { text: 'At least 1 number', regex: /[0-9]/ },
          { text: 'At least 1 symbol (!@#$%^&* etc.)', regex: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/ }
        ];

        allChecks.forEach(check => {
          const isMet = check.regex.test(password);
          hintsHTML += `<li class="${isMet ? 'hint_met' : 'hint_unmet'}">${isMet ? '✓' : '✗'} ${check.text}</li>`;
        });

        hintsHTML += '</ul>';
        hintsContainer.innerHTML = hintsHTML;
      }
    });
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

    // Validate password
    const password = passwordInput.value;
    const passwordErrors = validatePassword(password);
    if (passwordErrors.length > 0) {
      isValid = false;
      const errorMessage = 'Password must have: ' + passwordErrors.join(', ');
      showFieldError(passwordInput, errorMessage);
    } else {
      clearFieldError(passwordInput);
    }

    // Validate password confirmation
    const confirmPassword = confirmPasswordInput.value;
    if (password !== confirmPassword) {
      isValid = false;
      showFieldError(confirmPasswordInput, 'Passwords do not match');
    } else {
      clearFieldError(confirmPasswordInput);
    }

    if (!isValid) {
      e.preventDefault();
    }
  });

  // Real-time validation feedback
  if (emailInput) {
    emailInput.addEventListener('blur', () => {
      const email = emailInput.value.trim();
      if (email && !validateEmail(email)) {
        showFieldError(emailInput, 'Email must contain an @ symbol');
      } else {
        clearFieldError(emailInput);
      }
    });
  }

  if (contactInput) {
    contactInput.addEventListener('blur', () => {
      const phone = contactInput.value.trim();
      if (phone && !validatePhone(phone)) {
        showFieldError(contactInput, 'Phone number must start with +639');
      } else {
        clearFieldError(contactInput);
      }
    });
  }

  function showFieldError(field, message) {
    let errorElement = field.parentElement.querySelector('.validation_error');
    if (!errorElement) {
      errorElement = document.createElement('span');
      errorElement.className = 'validation_error';
      field.parentElement.appendChild(errorElement);
    }
    errorElement.textContent = message;
    field.style.borderColor = '#d32f2f';
  }

  function clearFieldError(field) {
    const errorElement = field.parentElement.querySelector('.validation_error');
    if (errorElement) {
      errorElement.textContent = '';
    }
    field.style.borderColor = '';
  }
});
