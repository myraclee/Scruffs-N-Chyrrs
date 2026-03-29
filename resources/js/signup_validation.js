document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('custom_signup_form');
  const emailInput = document.getElementById('email');
  const contactInput = document.getElementById('contact_number');
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('password_confirmation');

  if (!form) {
      console.error("Validation Script Error: Could not find the signup form!");
      return;
  }

  // Strict email validation
  function validateEmail(email) {
    const re = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    return re.test(email);
  }

  // Phone validation function 
  function validatePhone(phone) {
    return /^9[0-9]{9}$/.test(phone);
  }

  // Password validation function
  function validatePassword(password) {
    const errors = [];
    if (password.length < 8) errors.push('At least 8 characters');
    if (!/[A-Z]/.test(password)) errors.push('At least 1 uppercase letter');
    if (!/[a-z]/.test(password)) errors.push('At least 1 lowercase letter');
    if (!/[0-9]/.test(password)) errors.push('At least 1 number');
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) errors.push('At least 1 symbol (!@#$%^&* etc.)');
    return errors;
  }

  // Show password validation hints (Real-time by default)
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
    if (email === '') {
        isValid = false;
        showFieldError(emailInput, 'Please enter a valid email address.');
    } else if (!email.includes('@')) {
        isValid = false;
        showFieldError(emailInput, 'Email must contain an @ symbol.');
    } else if (!validateEmail(email)) {
        isValid = false;
        showFieldError(emailInput, 'Please enter a valid email address.');
    } else {
        clearFieldError(emailInput);
    }

    // Validate phone 
    const phone = contactInput.value.trim();
    if (phone === '') {
        isValid = false;
        showFieldError(contactInput, 'Please enter a 10-digit number starting with 9.');
    } else if (!validatePhone(phone)) {
        isValid = false;
        showFieldError(contactInput, 'Phone number must be 10 digits and start with 9.');
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
      showFieldError(confirmPasswordInput, 'Passwords do not match.');
    } else {
      clearFieldError(confirmPasswordInput);
    }

    if (!isValid) {
      e.preventDefault();
    }
  });

  // CHANGED TO 'input': Real-time validation feedback exactly as you type
  if (emailInput) {
    emailInput.addEventListener('input', () => {
      const email = emailInput.value.trim();
      if (email === '') {
          showFieldError(emailInput, 'Please enter a valid email address.');
      } else if (!email.includes('@')) {
          showFieldError(emailInput, 'Email must contain an @ symbol.');
      } else if (!validateEmail(email)) {
          showFieldError(emailInput, 'Please enter a valid email address.');
      } else {
          clearFieldError(emailInput);
      }
    });
  }

  if (contactInput) {
    contactInput.addEventListener('input', () => {
      const phone = contactInput.value.trim();
      if (phone === '') {
          showFieldError(contactInput, 'Please enter a 10-digit number starting with 9.');
      } else if (!validatePhone(phone)) {
          showFieldError(contactInput, 'Phone number must be 10 digits and start with 9.');
      } else {
          clearFieldError(contactInput);
      }
    });
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', () => {
      const password = passwordInput.value;
      const confirmPassword = confirmPasswordInput.value;
      if (confirmPassword !== '' && password !== confirmPassword) {
          showFieldError(confirmPasswordInput, 'Passwords do not match.');
      } else {
          clearFieldError(confirmPasswordInput);
      }
    });
  }

  function showFieldError(field, message) {
    let errorElement = field.parentElement.querySelector('.validation_error') || field.parentElement.querySelector('.client_error');
    if (!errorElement) {
      errorElement = document.createElement('span');
      errorElement.className = 'validation_error';
      field.parentElement.appendChild(errorElement);
    }
    errorElement.style.display = 'block';
    errorElement.textContent = message;
    field.style.borderColor = '#d32f2f';
  }

  function clearFieldError(field) {
    const errorElement = field.parentElement.querySelector('.validation_error') || field.parentElement.querySelector('.client_error');
    if (errorElement) {
      errorElement.style.display = 'none';
      errorElement.textContent = '';
    }
    field.style.borderColor = '';
  }
});