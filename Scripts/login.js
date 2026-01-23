// Enhanced Login JavaScript with security improvements
// Check if utils are loaded before proceeding
document.addEventListener('DOMContentLoaded', function() {
    if (!window.Utils) {
        console.error('Utils not loaded. Please include utils.js before login.js');
        return;
    }

    initializeLogin();
});

// Initialize variables
let isProcessing = false;
let loginAttempts = 0;
const MAX_LOGIN_ATTEMPTS = 3;
const LOCKOUT_DURATION = 15 * 60 * 1000; // 15 minutes

// DOM Elements
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const loginButton = document.getElementById('loginButton');
const togglePasswordBtn = document.getElementById('togglePassword');
const loadingOverlay = document.getElementById('loadingOverlay');
const emailError = document.getElementById('email-error');
const passwordError = document.getElementById('password-error');
const logo = document.getElementById('logo');

function initializeLogin() {
    // Check for existing session
    const currentUser = Utils.SessionManager.getCurrentUser();
    if (currentUser) {
        redirectToDashboard();
        return;
    }

    // Check for account lockout
    checkAccountLockout();
    
    // Setup event listeners
    setupEventListeners();
    
    // Setup form validation
    setupFormValidation();
    
    // Setup security features
    setupSecurityFeatures();
}

function setupEventListeners() {
    // Login form submission
    const loginForm = document.querySelector('.login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Input field validation
    if (emailInput) {
        emailInput.addEventListener('blur', () => validateEmail());
        emailInput.addEventListener('input', () => {
            Utils.ValidationUtils.clearError('email');
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('blur', () => validatePassword());
        passwordInput.addEventListener('input', () => {
            Utils.ValidationUtils.clearError('password');
        });
    }

    // Password visibility toggle
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', togglePasswordVisibility);
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !isProcessing) {
            handleLogin(e);
        }
    });
}

function setupFormValidation() {
    // Real-time validation
    if (emailInput) {
        emailInput.addEventListener('input', Utils.debounce(() => {
            if (emailInput.value.length > 0) {
                validateEmail();
            }
        }, 500));
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', Utils.debounce(() => {
            if (passwordInput.value.length > 0) {
                validatePassword();
            }
        }, 500));
    }
}

function setupSecurityFeatures() {
    // Prevent autocomplete on sensitive fields
    if (passwordInput) {
        passwordInput.setAttribute('autocomplete', 'new-password');
    }

    // Add CSRF protection (if form exists)
    const loginForm = document.querySelector('.login-form');
    if (loginForm) {
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = 'csrf_token';
        csrfToken.value = generateCSRFToken();
        loginForm.appendChild(csrfToken);
    }

    // Setup rate limiting
    setupRateLimiting();
}

function validateEmail() {
    const email = emailInput.value.trim();
    if (!email) {
        showError(emailInput, emailError, 'Please enter your email address');
    } else if (!isValidEmail(email)) {
        showError(emailInput, emailError, 'Please enter a valid email address');
    } else {
        showSuccess(emailInput);
    }
}

function validatePassword() {
    const password = passwordInput.value.trim();
    if (!password) {
        showError(passwordInput, passwordError, 'Please enter your password');
    } else if (password.length < 4) {
        showError(passwordInput, passwordError, 'Password must be at least 4 characters');
    } else {
        showSuccess(passwordInput);
    }
}

function showError(input, errorElement, message) {
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('active');
    }
    input.style.borderColor = 'var(--danger)';
    input.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';

    // Shake animation for error
    input.style.animation = 'shake 0.5s cubic-bezier(.36,.07,.19,.97) both';
    setTimeout(() => {
        input.style.animation = '';
    }, 500);
}

function showSuccess(input) {
    input.style.borderColor = 'var(--success, #10b981)';
    input.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Handle login with enhanced UI feedback
function handleLogin() {
    if (isProcessing) return;

    // Validate form
    if (!validateForm()) {
        return;
    }

    isProcessing = true;

    // Disable button and show loading state
    if (loginButton) {
        loginButton.disabled = true;
        loginButton.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 10px;"></i>Logging in...';
    }

    // Show loading overlay with delay for smooth transition
    setTimeout(() => {
        if (loadingOverlay) loadingOverlay.classList.add('active');
    }, 300);

    // Get form values
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    
    // Validate inputs
    if (!validateLoginInputs(email, password)) {
        handleLoginFailure('Invalid input provided');
        return;
    }

    // Simulate API call delay with progress indicator
    const startTime = Date.now();
    const checkInterval = setInterval(() => {
        const elapsed = Date.now() - startTime;
        const progress = Math.min(elapsed / 1500, 1);
        if (loadingOverlay) {
            const h3 = loadingOverlay.querySelector('h3');
            if (h3) h3.textContent = `Logging in... ${Math.round(progress * 100)}%`;
        }
    }, 100);

    setTimeout(() => {
        clearInterval(checkInterval);

        // Check credentials
        const isValidCredentials = checkCredentials(email, password);

        if (isValidCredentials) {
            // Save user session using SessionManager
            const userData = {
                name: "User",
                email: Utils.UIHelper.sanitizeHTML(email),
                role: "Safety Manager",
                avatar: "U",
                notifications: 3
            };
            Utils.SessionManager.login(userData);

            // Show success animation
            if (loadingOverlay) {
                loadingOverlay.innerHTML = `
                    <div class="success-animation">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Login Successful!</h3>
                    <p>Welcome back, ${Utils.UIHelper.sanitizeHTML(email.split('@')[0])}</p>
                    <p style="color: var(--text-gray); font-size: 14px; margin-top: 10px;">
                        Redirecting to dashboard...
                    </p>
                `;
            }

            // Redirect to dashboard after 2 seconds
            setTimeout(() => {
                window.location.href = 'home.html';
            }, 2000);
        } else {
            // Show error message
            handleLoginFailure('Invalid credentials. Please try again.\n\nUse:\nEmail: john.doe@safety.gov\nPassword: demo123');
        }
    }, 1500);
}

// Validate login inputs
function validateLoginInputs(email, password) {
    // Validate email
    if (!Utils.ValidationUtils.email(email)) {
        return false;
    }
    
    // Validate email length
    if (email.length > 254) {
        return false;
    }
    
    // Validate password
    if (!password || password.length < 4 || password.length > 128) {
        return false;
    }
    
    // Check for potentially dangerous patterns in email
    const dangerousPatterns = [/<script/i, /javascript:/i, /vbscript:/i, /on\w+=/i];
    for (const pattern of dangerousPatterns) {
        if (pattern.test(email)) {
            return false;
        }
    }
    
    return true;
}

// Handle login failure
function handleLoginFailure(errorMessage) {
    // Show error message
    if (loadingOverlay) loadingOverlay.classList.remove('active');

    if (loginButton) {
        loginButton.disabled = false;
        loginButton.innerHTML = '<i class="fas fa-sign-in-alt" style="margin-right: 10px;"></i>Login to Dashboard';
    }

    // Show animated error
    const loginCard = document.querySelector('.login-card');
    if (loginCard) {
        loginCard.style.animation = 'shake 0.5s cubic-bezier(.36,.07,.19,.97) both';
        setTimeout(() => {
            loginCard.style.animation = 'float 6s ease-in-out infinite';
        }, 500);
    }

    alert(errorMessage);
    isProcessing = false;

    // Focus on email field
    emailInput.focus();
    
    // Increment login attempts
    loginAttempts++;
    if (loginAttempts >= MAX_LOGIN_ATTEMPTS) {
        // Lock account
        localStorage.setItem('lockoutStartTime', Date.now());
        alert('Too many failed login attempts. Account locked for 15 minutes.');
    }
}

// Validate the login form
function validateForm() {
    const email = emailInput.value.trim();
    const password = passwordInput.value;

    let isValid = true;

    // Email validation
    if (!email) {
        showError(emailInput, emailError, 'Email is required');
        isValid = false;
    } else if (!Utils.ValidationUtils.email(email)) {
        showError(emailInput, emailError, 'Please enter a valid email address');
        isValid = false;
    } else if (email.length > 254) {
        showError(emailInput, emailError, 'Email address is too long');
        isValid = false;
    } else {
        showSuccess(emailInput);
        Utils.ValidationUtils.clearError('email');
    }

    // Password validation
    if (!password) {
        showError(passwordInput, passwordError, 'Password is required');
        isValid = false;
    } else if (password.length < 4) {
        showError(passwordInput, passwordError, 'Password must be at least 4 characters');
        isValid = false;
    } else if (password.length > 128) {
        showError(passwordInput, passwordError, 'Password is too long');
        isValid = false;
    } else {
        showSuccess(passwordInput);
        Utils.ValidationUtils.clearError('password');
    }

    return isValid;
}

function checkCredentials(email, password) {
    // Demo credentials check
    const demoEmail = 'john.doe@safety.gov';
    const demoPassword = 'demo123';

    return email === demoEmail && password === demoPassword;
}

// Add event listeners
if (loginButton) {
    loginButton.addEventListener('click', handleLogin);
}

// Allow Enter key to submit form
if (emailInput) {
    emailInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleLogin();
        }
    });
}

if (passwordInput) {
    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            handleLogin();
        }
    });
}

// Clear errors on input with visual feedback
if (emailInput) {
    emailInput.addEventListener('input', function() {
        if (emailError) emailError.classList.remove('active');
        this.style.borderColor = '';
        this.style.boxShadow = '';
    });
}

if (passwordInput) {
    passwordInput.addEventListener('input', function() {
        if (passwordError) passwordError.classList.remove('active');
        this.style.borderColor = '';
        this.style.boxShadow = '';
    });
}

// Add focus/blur effects
if (emailInput) {
    emailInput.addEventListener('focus', function() {
        const icon = this.parentElement.querySelector('.input-icon');
        if (icon) icon.style.color = 'var(--primary)';
    });

    emailInput.addEventListener('blur', function() {
        if (!this.value) {
            const icon = this.parentElement.querySelector('.input-icon');
            if (icon) icon.style.color = 'var(--text-gray)';
        }
    });
}

if (passwordInput) {
    passwordInput.addEventListener('focus', function() {
        const icon = this.parentElement.querySelector('.input-icon');
        if (icon) icon.style.color = 'var(--primary)';
    });

    passwordInput.addEventListener('blur', function() {
        if (!this.value) {
            const icon = this.parentElement.querySelector('.input-icon');
            if (icon) icon.style.color = 'var(--text-gray)';
        }
    });
}

function showLoginLoading() {
    const loadingScreen = document.getElementById('loginLoading');
    if (loadingScreen) {
        loadingScreen.classList.add('active');
        document.body.classList.add('login-loading-active');
    }
}

function hideLoginLoading() {
    const loadingScreen = document.getElementById('loginLoading');
    if (loadingScreen) {
        loadingScreen.classList.remove('active');
        document.body.classList.remove('login-loading-active');
    }
}

