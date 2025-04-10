/**
 * Agro Vision Form Validation
 * Client-side form validation utilities
 */

// IIFE to avoid polluting global scope
(function() {
    'use strict';
    
    /**
     * Form Validation Module
     */
    const FormValidator = {
        /**
         * Initialize form validation
         * @param {string} formSelector - CSS selector for the form
         */
        init: function(formSelector) {
            const form = document.querySelector(formSelector);
            
            if (!form) {
                console.error('Form not found:', formSelector);
                return;
            }
            
            form.addEventListener('submit', function(event) {
                if (!FormValidator.validateForm(form)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            });
            
            // Add input event listeners for real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    FormValidator.validateField(input);
                });
                
                input.addEventListener('blur', function() {
                    FormValidator.validateField(input);
                });
            });
        },
        
        /**
         * Validate the entire form
         * @param {HTMLFormElement} form - The form element
         * @returns {boolean} True if valid, false otherwise
         */
        validateForm: function(form) {
            let isValid = true;
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                if (!FormValidator.validateField(input)) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        /**
         * Validate a single field
         * @param {HTMLElement} field - The input field
         * @returns {boolean} True if valid, false otherwise
         */
        validateField: function(field) {
            let isValid = true;
            const errorDisplay = field.nextElementSibling?.classList.contains('invalid-feedback') 
                ? field.nextElementSibling 
                : null;
                
            // Reset validation state
            field.classList.remove('is-invalid');
            field.classList.remove('is-valid');
            
            // Required validation
            if (field.hasAttribute('required') && !field.value.trim()) {
                isValid = false;
                this.showError(field, errorDisplay, 'This field is required');
            }
            
            // Email validation
            if (field.type === 'email' && field.value.trim() && !this.isValidEmail(field.value)) {
                isValid = false;
                this.showError(field, errorDisplay, 'Please enter a valid email address');
            }
            
            // Number validation
            if (field.type === 'number') {
                const min = field.getAttribute('min');
                const max = field.getAttribute('max');
                
                if (field.value && min !== null && parseFloat(field.value) < parseFloat(min)) {
                    isValid = false;
                    this.showError(field, errorDisplay, `Value must be at least ${min}`);
                }
                
                if (field.value && max !== null && parseFloat(field.value) > parseFloat(max)) {
                    isValid = false;
                    this.showError(field, errorDisplay, `Value must not exceed ${max}`);
                }
            }
            
            // Pattern validation (regex)
            if (field.hasAttribute('pattern') && field.value.trim()) {
                const pattern = new RegExp(field.getAttribute('pattern'));
                if (!pattern.test(field.value)) {
                    isValid = false;
                    this.showError(field, errorDisplay, field.dataset.validationMessage || 'Please match the requested format');
                }
            }
            
            // Minlength validation
            if (field.hasAttribute('minlength') && field.value.trim()) {
                const minLength = parseInt(field.getAttribute('minlength'));
                if (field.value.length < minLength) {
                    isValid = false;
                    this.showError(field, errorDisplay, `Must be at least ${minLength} characters`);
                }
            }
            
            // Maxlength validation (client-side check as backup)
            if (field.hasAttribute('maxlength') && field.value.trim()) {
                const maxLength = parseInt(field.getAttribute('maxlength'));
                if (field.value.length > maxLength) {
                    isValid = false;
                    this.showError(field, errorDisplay, `Must not exceed ${maxLength} characters`);
                }
            }
            
            // Password strength validation (if specified)
            if (field.dataset.validatePassword === 'true' && field.value.trim()) {
                if (!this.isStrongPassword(field.value)) {
                    isValid = false;
                    this.showError(field, errorDisplay, 'Password must include uppercase, lowercase, number, and special character');
                }
            }
            
            // Password match validation
            if (field.dataset.matchWith && field.value.trim()) {
                const matchField = document.getElementById(field.dataset.matchWith);
                if (matchField && field.value !== matchField.value) {
                    isValid = false;
                    this.showError(field, errorDisplay, 'Passwords do not match');
                }
            }
            
            // Date validation
            if (field.type === 'date' && field.value.trim()) {
                const date = new Date(field.value);
                if (isNaN(date.getTime())) {
                    isValid = false;
                    this.showError(field, errorDisplay, 'Please enter a valid date');
                }
                
                // Check min/max dates if specified
                if (field.min && new Date(field.value) < new Date(field.min)) {
                    isValid = false;
                    this.showError(field, errorDisplay, `Date must be on or after ${field.min}`);
                }
                
                if (field.max && new Date(field.value) > new Date(field.max)) {
                    isValid = false;
                    this.showError(field, errorDisplay, `Date must be on or before ${field.max}`);
                }
            }
            
            // File validation
            if (field.type === 'file' && field.files.length > 0) {
                // Check file size
                if (field.dataset.maxSize) {
                    const maxSize = parseInt(field.dataset.maxSize) * 1024 * 1024; // Convert MB to bytes
                    if (field.files[0].size > maxSize) {
                        isValid = false;
                        this.showError(field, errorDisplay, `File size must not exceed ${field.dataset.maxSize}MB`);
                    }
                }
                
                // Check file type
                if (field.accept && field.accept.trim() !== '') {
                    const acceptedTypes = field.accept.split(',').map(type => type.trim());
                    const fileType = field.files[0].type;
                    const fileName = field.files[0].name;
                    const fileExt = fileName.split('.').pop().toLowerCase();
                    
                    let isAccepted = false;
                    for (const type of acceptedTypes) {
                        if (type.startsWith('.')) {
                            // Extension check
                            if (`.${fileExt}` === type) {
                                isAccepted = true;
                                break;
                            }
                        } else if (type.includes('*')) {
                            // MIME type with wildcard
                            const typeRegex = new RegExp(type.replace('*', '.*'));
                            if (typeRegex.test(fileType)) {
                                isAccepted = true;
                                break;
                            }
                        } else if (type === fileType) {
                            // Exact MIME type match
                            isAccepted = true;
                            break;
                        }
                    }
                    
                    if (!isAccepted) {
                        isValid = false;
                        this.showError(field, errorDisplay, 'File type not supported');
                    }
                }
            }
            
            // If field is valid, add valid class
            if (isValid && field.value.trim()) {
                field.classList.add('is-valid');
            }
            
            return isValid;
        },
        
        /**
         * Show validation error
         * @param {HTMLElement} field - The input field
         * @param {HTMLElement} errorDisplay - Element to display error in
         * @param {string} message - Error message
         */
        showError: function(field, errorDisplay, message) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            
            if (errorDisplay) {
                errorDisplay.textContent = message;
            } else {
                // Create error display if it doesn't exist
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                
                if (field.nextElementSibling) {
                    field.parentNode.insertBefore(feedback, field.nextElementSibling);
                } else {
                    field.parentNode.appendChild(feedback);
                }
            }
        },
        
        /**
         * Check if email is valid
         * @param {string} email - Email to validate
         * @returns {boolean} True if valid
         */
        isValidEmail: function(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        },
        
        /**
         * Check if password is strong
         * @param {string} password - Password to check
         * @returns {boolean} True if strong
         */
        isStrongPassword: function(password) {
            return password.length >= 8 && 
                   /[A-Z]/.test(password) && 
                   /[a-z]/.test(password) && 
                   /[0-9]/.test(password) && 
                   /[^A-Za-z0-9]/.test(password);
        }
    };
    
    // Make FormValidator available globally
    window.FormValidator = FormValidator;
})(); 