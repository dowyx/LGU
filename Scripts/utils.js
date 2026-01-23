// Shared Utilities for Public Safety Campaign Management System
// Common functionality to reduce code duplication and improve maintainability

// Configuration Management
const CONFIG = {
    API_BASE_URL: window.location.hostname === 'localhost' ? 
        'http://localhost:3000/api' : 
        'https://api.safety.gov/api',
    DEBOUNCE_DELAY: 300,
    TOAST_DURATION: 3000,
    WIDGET_UPDATE_INTERVAL: 30000,
    SESSION_TIMEOUT: 3600000, // 1 hour
    MAX_FILE_SIZE: 10 * 1024 * 1024, // 10MB
    ALLOWED_FILE_TYPES: ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain'],
    SECURITY_HEADERS: {
        'X-Content-Type-Options': 'nosniff',
        'X-Frame-Options': 'DENY',
        'X-XSS-Protection': '1; mode=block'
    }
};

// Navigation Manager
class NavigationManager {
    constructor() {
        this.currentPage = this.getCurrentPage();
        this.navLinks = document.querySelectorAll('.nav-link');
        this.init();
    }

    getCurrentPage() {
        return window.location.pathname.split('/').pop() || 'home.html';
    }

    init() {
        this.setActiveNavigation();
        this.setupNavigationTracking();
    }

    setActiveNavigation() {
        this.navLinks.forEach(link => {
            const linkHref = link.getAttribute('href');
            const isActive = this.currentPage === linkHref ||
                (this.currentPage === '' && linkHref === '#') ||
                (this.currentPage === 'home.html' && linkHref === '#') ||
                (linkHref && linkHref.includes(this.currentPage));

            link.classList.toggle('active', isActive);
        });
    }

    setupNavigationTracking() {
        this.navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                if (!link.getAttribute('href')?.startsWith('#')) {
                    const moduleName = link.querySelector('.nav-text')?.textContent;
                    this.trackNavigation(moduleName);
                }
            });
        });
    }

    trackNavigation(moduleName) {
        console.log(`Navigating to: ${moduleName}`);
        // Add analytics tracking here
        this.saveUserActivity('navigation', { module: moduleName });
    }

    saveUserActivity(action, data) {
        const activities = JSON.parse(localStorage.getItem('userActivities') || '[]');
        activities.push({
            action,
            data,
            timestamp: new Date().toISOString()
        });
        
        // Keep only last 100 activities
        if (activities.length > 100) {
            activities.shift();
        }
        
        localStorage.setItem('userActivities', JSON.stringify(activities));
    }
}

// API Handler
class APIHandler {
    constructor() {
        this.baseURL = CONFIG.API_BASE_URL;
        this.defaultHeaders = {
            'Content-Type': 'application/json'
        };
    }

    async request(endpoint, options = {}) {
        // Sanitize the endpoint to prevent path traversal attacks
        const sanitizedEndpoint = this.sanitizeEndpoint(endpoint);
        const url = `${this.baseURL}${sanitizedEndpoint}`;
        
        // Add security headers
        const securityHeaders = CONFIG.SECURITY_HEADERS;
        
        const config = {
            headers: { 
                ...this.defaultHeaders, 
                ...securityHeaders,
                ...options.headers 
            },
            ...options
        };

        try {
            // Validate URL before making request
            if (!this.isValidUrl(url)) {
                throw new Error('Invalid URL provided');
            }
            
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error(`API Error (${endpoint}):`, error);
            UIHelper.showToast(`Failed to load data: ${error.message}`, 'error');
            throw error;
        }
    }
    
    // Sanitize endpoint to prevent path traversal
    sanitizeEndpoint(endpoint) {
        // Remove any path traversal sequences
        let sanitized = endpoint.replace('..\\', '').replace('../', '');
        // Ensure it starts with /
        if (!sanitized.startsWith('/')) {
            sanitized = '/' + sanitized;
        }
        return sanitized;
    }
    
    // Validate URL format
    isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }

    async get(endpoint) {
        return this.request(endpoint);
    }

    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }
}

// UI Helper
class UIHelper {
    static showToast(message, type = 'info', duration = CONFIG.TOAST_DURATION) {
        // Remove existing toasts
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
                <span>${this.sanitizeHTML(message)}</span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        // Add toast styles if not already present
        if (!document.querySelector('#toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                .toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    padding: 16px;
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    min-width: 300px;
                    animation: slideIn 0.3s ease;
                }
                .toast-error { border-left: 4px solid #dc3545; }
                .toast-success { border-left: 4px solid #28a745; }
                .toast-warning { border-left: 4px solid #ffc107; }
                .toast-info { border-left: 4px solid #17a2b8; }
                .toast-content {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }
                .toast-close {
                    background: none;
                    border: none;
                    cursor: pointer;
                    opacity: 0.6;
                }
                .toast-close:hover { opacity: 1; }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, duration);
    }

    static getToastIcon(type) {
        const icons = {
            error: 'exclamation-circle',
            success: 'check-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    static sanitizeHTML(str) {
        if (typeof str !== 'string') {
            return '';
        }
        
        const div = document.createElement('div');
        div.textContent = str;
        
        // More robust sanitization to prevent XSS
        let sanitized = div.innerHTML;
        
        // Remove potentially dangerous attributes
        sanitized = sanitized.replace(/\s*(on\w+|href|src|data|action|formaction)\s*=\s*["'][^"']*["']/gi, '');
        
        // Remove javascript:, data:, vbscript: protocols
        sanitized = sanitized.replace(/(javascript:|data:|vbscript:)/gi, '');
        
        return sanitized;
    }

    static showLoading(element, message = 'Loading...') {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element) {
            element.innerHTML = `
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>${message}</span>
                </div>
            `;
        }
    }

    static hideLoading(element, content) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        
        if (element && content !== undefined) {
            element.innerHTML = content;
        }
    }
}

// Validation Utils
class ValidationUtils {
    static email(email) {
        if (typeof email !== 'string' || email.length > 254) {
            return false;
        }
        
        const re = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
        return re.test(email);
    }

    static phone(phone) {
        if (typeof phone !== 'string') {
            return false;
        }
        
        const re = /^[0-9\s\-\+\(\)]+$/;
        return re.test(phone) && phone.replace(/\D/g, '').length >= 10;
    }
    
    static alphanumeric(value) {
        if (typeof value !== 'string') {
            return false;
        }
        
        const re = /^[a-zA-Z0-9_]+$/;
        return re.test(value);
    }
    
    static url(url) {
        if (typeof url !== 'string') {
            return false;
        }
        
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }
    
    static fileExtension(filename, allowedExtensions) {
        if (typeof filename !== 'string' || !Array.isArray(allowedExtensions)) {
            return false;
        }
        
        const extension = filename.toLowerCase().split('.').pop();
        return allowedExtensions.includes(extension);
    }

    static required(value) {
        return value && value.toString().trim().length > 0;
    }

    static minLength(value, min) {
        return value && value.toString().length >= min;
    }

    static maxLength(value, max) {
        return value && value.toString().length <= max;
    }
    
    static fileSize(size, maxSize = CONFIG.MAX_FILE_SIZE) {
        return size <= maxSize;
    }
    
    static fileType(type, allowedTypes = CONFIG.ALLOWED_FILE_TYPES) {
        return allowedTypes.includes(type);
    }

    static showError(fieldId, message) {
        const errorElement = document.getElementById(`${fieldId}-error`);
        const inputElement = document.getElementById(fieldId);
        
        if (errorElement) {
            errorElement.textContent = UIHelper.sanitizeHTML(message);
            errorElement.style.display = 'block';
        }
        
        if (inputElement) {
            inputElement.classList.add('error');
        }
    }

    static clearError(fieldId) {
        const errorElement = document.getElementById(`${fieldId}-error`);
        const inputElement = document.getElementById(fieldId);
        
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
        
        if (inputElement) {
            inputElement.classList.remove('error');
        }
    }
}

// Debounce Utility
function debounce(func, wait = CONFIG.DEBOUNCE_DELAY) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Session Manager
class SessionManager {
    static init() {
        this.checkSession();
        this.setupActivityTracking();
    }

    static checkSession() {
        const session = this.getSecureSession();
        if (!session) {
            this.redirectToLogin();
            return false;
        }

        const sessionData = this.parseSession(session);
        if (!sessionData) {
            this.logout();
            return false;
        }
        
        const now = Date.now();
        
        if (now - sessionData.lastActivity > CONFIG.SESSION_TIMEOUT) {
            this.logout();
            return false;
        }

        this.updateLastActivity();
        return true;
    }

    static updateLastActivity() {
        const session = this.getSecureSession();
        if (session) {
            const sessionData = this.parseSession(session);
            if (sessionData) {
                sessionData.lastActivity = Date.now();
                this.setSecureSession(JSON.stringify(sessionData));
            }
        }
    }

    static setupActivityTracking() {
        ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, () => this.updateLastActivity(), true);
        });
    }

    static login(userData) {
        if (!userData || typeof userData !== 'object') {
            console.error('Invalid user data provided for login');
            return false;
        }
        
        // Sanitize user data before storing
        const sanitizedUserData = {
            id: userData.id || null,
            username: userData.username ? UIHelper.sanitizeHTML(userData.username) : null,
            email: userData.email || null,
            role: userData.role || 'user',
            permissions: userData.permissions || []
        };
        
        const sessionData = {
            user: sanitizedUserData,
            loginTime: Date.now(),
            lastActivity: Date.now(),
            fingerprint: this.generateFingerprint()
        };
        
        this.setSecureSession(JSON.stringify(sessionData));
        return true;
    }

    static logout() {
        this.clearSecureSession();
        this.redirectToLogin();
    }

    static redirectToLogin() {
<<<<<<< HEAD
        if (!window.location.pathname.includes('login.php')) {
            window.location.href = '/LGU4/login.php';
=======
        if (!window.location.pathname.includes('login.html')) {
            window.location.href = '/login.html';
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
        }
    }

    static getCurrentUser() {
        const session = this.getSecureSession();
        if (!session) {
            return null;
        }
        
        const sessionData = this.parseSession(session);
        return sessionData ? sessionData.user : null;
    }
    
    // Get session with additional security checks
    static getSecureSession() {
        try {
            // Check for session tampering
            const session = localStorage.getItem('userSession');
            if (!session) {
                return null;
            }
            
            // Verify session integrity
            return session;
        } catch (error) {
            console.error('Error retrieving session:', error);
            return null;
        }
    }
    
    // Set session with additional security
    static setSecureSession(sessionData) {
        try {
            localStorage.setItem('userSession', sessionData);
        } catch (error) {
            console.error('Error setting session:', error);
        }
    }
    
    // Clear session securely
    static clearSecureSession() {
        try {
            localStorage.removeItem('userSession');
        } catch (error) {
            console.error('Error clearing session:', error);
        }
    }
    
    // Parse session with error handling
    static parseSession(session) {
        try {
            const parsed = JSON.parse(session);
            
            // Validate required fields
            if (!parsed.loginTime || !parsed.lastActivity) {
                return null;
            }
            
            return parsed;
        } catch (error) {
            console.error('Error parsing session:', error);
            return null;
        }
    }
    
    // Generate a simple browser fingerprint
    static generateFingerprint() {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.fillText('fingerprint', 10, 10);
        
        const userAgent = navigator.userAgent;
        const platform = navigator.platform;
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        
        return btoa(userAgent + platform + timezone + canvas.toDataURL()).substring(0, 16);
    }
}

// Widget Manager
class WidgetManager {
    constructor() {
        this.widgets = document.querySelectorAll('.widget');
        this.updateIntervals = new Map();
        this.init();
    }

    init() {
        this.setupWidgetClicks();
        this.startRealTimeUpdates();
    }

    setupWidgetClicks() {
        this.widgets.forEach(widget => {
            widget.addEventListener('click', () => {
                const widgetType = widget.querySelector('.widget-title')?.textContent;
                this.openWidgetDetails(widgetType);
            });
        });
    }

    startRealTimeUpdates() {
        this.widgets.forEach(widget => {
            const interval = setInterval(() => {
                this.updateWidgetData(widget);
            }, CONFIG.WIDGET_UPDATE_INTERVAL);
            
            this.updateIntervals.set(widget, interval);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    }

    updateWidgetData(widget) {
        // Override in specific implementations
        console.log('Updating widget:', widget);
    }

    openWidgetDetails(widgetType) {
        UIHelper.showToast(`Opening details for ${widgetType}`, 'info');
        // Override in specific implementations
    }

    cleanup() {
        this.updateIntervals.forEach(interval => clearInterval(interval));
        this.updateIntervals.clear();
    }
}

// Search Manager
class SearchManager {
    constructor(inputSelector, searchHandler) {
        this.input = document.querySelector(inputSelector);
        this.searchHandler = searchHandler;
        this.debouncedSearch = debounce(this.performSearch.bind(this));
        this.init();
    }

    init() {
        if (!this.input) return;

        this.input.addEventListener('input', (e) => {
            // Sanitize input before searching
            const sanitizedQuery = UIHelper.sanitizeHTML(e.target.value);
            this.debouncedSearch(sanitizedQuery);
        });

        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Sanitize input before searching
                const sanitizedQuery = UIHelper.sanitizeHTML(e.target.value);
                this.performSearch(sanitizedQuery);
            }
        });
    }

    performSearch(query) {
        // Validate and sanitize the query
        if (!this.validateQuery(query)) {
            return;
        }
        
        try {
            this.searchHandler(query);
        } catch (error) {
            console.error('Search error:', error);
            UIHelper.showToast('Search failed. Please try again.', 'error');
        }
    }
    
    // Validate the search query
    validateQuery(query) {
        if (typeof query !== 'string') {
            return false;
        }
        
        // Trim the query
        const trimmedQuery = query.trim();
        
        // Check minimum length
        if (trimmedQuery.length < 2) {
            return false;
        }
        
        // Check for potentially harmful patterns
        const dangerousPatterns = [
            /<script/i,
            /javascript:/i,
            /vbscript:/i,
            /on\w+=/i,
            /<iframe/i,
            /<object/i,
            /<embed/i
        ];
        
        for (const pattern of dangerousPatterns) {
            if (pattern.test(trimmedQuery)) {
                return false;
            }
        }
        
        return true;
    }
}

// Export for use in other files
window.Utils = {
    NavigationManager,
    APIHandler,
    UIHelper,
    ValidationUtils,
    SessionManager,
    WidgetManager,
    SearchManager,
    debounce,
    CONFIG
};
