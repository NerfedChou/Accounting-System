/**
 * Validation Module - Form and data validation
 * Accounting System
 */

/**
 * Validate required field
 * @param {any} value - Value to validate
 * @returns {Object} Validation result
 */
export function validateRequired(value) {
  const isValid = value !== null && value !== undefined && value !== '';
  return {
    isValid,
    message: isValid ? '' : 'This field is required'
  };
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @returns {Object} Validation result
 */
export function validateEmail(email) {
  const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const isValid = regex.test(email);
  return {
    isValid,
    message: isValid ? '' : 'Please enter a valid email address'
  };
}

/**
 * Validate number
 * @param {any} value - Value to validate
 * @param {Object} options - Min and max values
 * @returns {Object} Validation result
 */
export function validateNumber(value, options = {}) {
  const num = parseFloat(value);

  if (isNaN(num)) {
    return {
      isValid: false,
      message: 'Please enter a valid number'
    };
  }

  if (options.min !== undefined && num < options.min) {
    return {
      isValid: false,
      message: `Value must be at least ${options.min}`
    };
  }

  if (options.max !== undefined && num > options.max) {
    return {
      isValid: false,
      message: `Value must not exceed ${options.max}`
    };
  }

  return {
    isValid: true,
    message: ''
  };
}

/**
 * Validate amount (positive number)
 * @param {any} amount - Amount to validate
 * @returns {Object} Validation result
 */
export function validateAmount(amount) {
  const num = parseFloat(amount);

  if (isNaN(num)) {
    return {
      isValid: false,
      message: 'Please enter a valid amount'
    };
  }

  if (num <= 0) {
    return {
      isValid: false,
      message: 'Amount must be greater than zero'
    };
  }

  return {
    isValid: true,
    message: ''
  };
}

/**
 * Validate date
 * @param {string} date - Date to validate
 * @param {Object} options - Validation options
 * @returns {Object} Validation result
 */
export function validateDate(date, options = {}) {
  const dateObj = new Date(date);

  if (isNaN(dateObj.getTime())) {
    return {
      isValid: false,
      message: 'Please enter a valid date'
    };
  }

  if (options.notFuture && dateObj > new Date()) {
    return {
      isValid: false,
      message: 'Date cannot be in the future'
    };
  }

  if (options.notPast && dateObj < new Date()) {
    return {
      isValid: false,
      message: 'Date cannot be in the past'
    };
  }

  return {
    isValid: true,
    message: ''
  };
}

/**
 * Validate double-entry balance (CRITICAL FOR ACCOUNTING)
 * @param {Array} lines - Transaction lines with line_type and amount
 * @returns {Object} Validation result with detailed info
 */
export function validateDoubleEntry(lines) {
  if (!Array.isArray(lines) || lines.length === 0) {
    return {
      isValid: false,
      message: 'At least one transaction line is required',
      debits: 0,
      credits: 0,
      difference: 0
    };
  }

  let totalDebits = 0;
  let totalCredits = 0;

  lines.forEach(line => {
    const amount = parseFloat(line.amount) || 0;
    if (line.line_type === 'debit') {
      totalDebits += amount;
    } else if (line.line_type === 'credit') {
      totalCredits += amount;
    }
  });

  // Round to 2 decimal places to handle floating point precision
  totalDebits = Math.round(totalDebits * 100) / 100;
  totalCredits = Math.round(totalCredits * 100) / 100;
  const difference = Math.round((totalDebits - totalCredits) * 100) / 100;

  const isValid = difference === 0;

  return {
    isValid,
    message: isValid
      ? 'Transaction is balanced'
      : `Transaction is not balanced. Difference: $${Math.abs(difference).toFixed(2)}`,
    debits: totalDebits,
    credits: totalCredits,
    difference: difference
  };
}

/**
 * Validate transaction lines
 * @param {Array} lines - Transaction lines
 * @returns {Object} Validation result
 */
export function validateTransactionLines(lines) {
  if (!Array.isArray(lines) || lines.length < 2) {
    return {
      isValid: false,
      message: 'At least 2 transaction lines are required (minimum one debit and one credit)'
    };
  }

  // Check each line has required fields
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];

    if (!line.account_id) {
      return {
        isValid: false,
        message: `Line ${i + 1}: Please select an account`
      };
    }

    if (!line.line_type || !['debit', 'credit'].includes(line.line_type)) {
      return {
        isValid: false,
        message: `Line ${i + 1}: Please select debit or credit`
      };
    }

    const amountValidation = validateAmount(line.amount);
    if (!amountValidation.isValid) {
      return {
        isValid: false,
        message: `Line ${i + 1}: ${amountValidation.message}`
      };
    }
  }

  // Validate double-entry balance
  return validateDoubleEntry(lines);
}

/**
 * Validate password strength
 * @param {string} password - Password to validate
 * @param {Object} options - Validation options
 * @returns {Object} Validation result
 */
export function validatePassword(password, options = {}) {
  const minLength = options.minLength || 6;

  if (!password || password.length < minLength) {
    return {
      isValid: false,
      message: `Password must be at least ${minLength} characters long`
    };
  }

  if (options.requireNumber && !/\d/.test(password)) {
    return {
      isValid: false,
      message: 'Password must contain at least one number'
    };
  }

  if (options.requireSpecial && !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
    return {
      isValid: false,
      message: 'Password must contain at least one special character'
    };
  }

  return {
    isValid: true,
    message: ''
  };
}

/**
 * Validate form fields
 * @param {Object} formData - Form data object
 * @param {Object} rules - Validation rules
 * @returns {Object} Validation results
 */
export function validateForm(formData, rules) {
  const errors = {};
  let isValid = true;

  for (const field in rules) {
    const value = formData[field];
    const fieldRules = rules[field];

    for (const rule of fieldRules) {
      let result;

      switch (rule.type) {
        case 'required':
          result = validateRequired(value);
          break;
        case 'email':
          result = validateEmail(value);
          break;
        case 'number':
          result = validateNumber(value, rule.options);
          break;
        case 'amount':
          result = validateAmount(value);
          break;
        case 'date':
          result = validateDate(value, rule.options);
          break;
        case 'password':
          result = validatePassword(value, rule.options);
          break;
        case 'custom':
          result = rule.validator(value);
          break;
        default:
          result = { isValid: true, message: '' };
      }

      if (!result.isValid) {
        errors[field] = result.message;
        isValid = false;
        break; // Stop at first error for this field
      }
    }
  }

  return {
    isValid,
    errors
  };
}

/**
 * Display validation errors in form
 * @param {Object} errors - Errors object {fieldName: errorMessage}
 */
export function displayFormErrors(errors) {
  // Clear existing errors
  document.querySelectorAll('.form__error').forEach(el => el.remove());
  document.querySelectorAll('.form__input--error, .form__select--error').forEach(el => {
    el.classList.remove('form__input--error', 'form__select--error');
  });

  // Display new errors
  for (const field in errors) {
    const input = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
    if (input) {
      // Add error class
      input.classList.add(input.tagName === 'SELECT' ? 'form__select--error' : 'form__input--error');

      // Create error message
      const errorEl = document.createElement('span');
      errorEl.className = 'form__error';
      errorEl.textContent = errors[field];

      // Insert after input
      input.parentNode.appendChild(errorEl);
    }
  }
}

/**
 * Clear form validation errors
 */
export function clearFormErrors() {
  document.querySelectorAll('.form__error').forEach(el => el.remove());
  document.querySelectorAll('.form__input--error, .form__select--error').forEach(el => {
    el.classList.remove('form__input--error', 'form__select--error');
  });
}

// Export all as default object as well
export default {
  validateRequired,
  validateEmail,
  validateNumber,
  validateAmount,
  validateDate,
  validateDoubleEntry,
  validateTransactionLines,
  validatePassword,
  validateForm,
  displayFormErrors,
  clearFormErrors
};

