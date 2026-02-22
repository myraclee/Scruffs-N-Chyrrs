document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.change_password_form');
  const newPasswordInput = document.getElementById('new_password');
  const confirmPasswordInput = document.getElementById('new_password_confirmation');

  if (!form) return;

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
  newPasswordInput.addEventListener('input', () => {
    const password = newPasswordInput.value;
    const errors = validatePassword(password);

    // Find or create hints container
    let hintsContainer = newPasswordInput.parentElement.querySelector('.password_hints');
    if (!hintsContainer) {
      hintsContainer = document.createElement('div');
      hintsContainer.className = 'password_hints';
      newPasswordInput.parentElement.appendChild(hintsContainer);
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

  // Form submission validation
  form.addEventListener('submit', (e) => {
    const currentPassword = form.querySelector('#current_password').value;
    const newPassword = newPasswordInput.value;
    const confirmPassword = confirmPasswordInput.value;

    let isValid = true;
    const newPasswordErrors = validatePassword(newPassword);

    if (!currentPassword) {
      isValid = false;
      showFieldError(form.querySelector('#current_password'), 'Current password is required');
    }

    if (newPasswordErrors.length > 0) {
      isValid = false;
      const errorMessage = 'Password must have: ' + newPasswordErrors.join(', ');
      showFieldError(newPasswordInput, errorMessage);
    }

    if (newPassword !== confirmPassword) {
      isValid = false;
      showFieldError(confirmPasswordInput, 'Passwords do not match');
    }

    if (!isValid) {
      e.preventDefault();
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
});
