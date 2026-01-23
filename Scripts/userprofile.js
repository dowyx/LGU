// User Profile Management
class UserProfile {
    constructor() {
        this.userData = {
            name: "User",
            email: "john.doe@safety.gov",
            role: "Safety Manager",
            department: "Public Safety Department",
            phone: "+1 (555) 123-4567",
            location: "Downtown District",
            lastLogin: new Date().toLocaleString(),
            notifications: 3,
            avatar: "U"
        };

        this.init();
    }

    init() {
        console.log('UserProfile initialized');

        // Check login status
        this.checkLoginStatus();

        // Setup profile
        this.setupProfile();

        // Load user data
        this.loadUserData();

        // Add click outside handler
        this.addClickOutsideHandler();
    }

    addClickOutsideHandler() {
        document.addEventListener('click', (e) => {
            const dropdown = document.querySelector('.profile-dropdown');
            const profileElement = document.querySelector('.user-profile');

            if (dropdown && profileElement &&
                !dropdown.contains(e.target) &&
                !profileElement.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    }

    checkLoginStatus() {
        const userSession = localStorage.getItem('userSession');
        console.log('Current user session:', userSession);

        // Check if we're on a protected page
        const protectedPages = ['home.html', 'index.html', 'dashboard.html'];
        const currentPage = window.location.pathname.split('/').pop();
        const isProtectedPage = protectedPages.some(page =>
            window.location.pathname.includes(page) ||
            currentPage === page ||
            currentPage === '' // root page
        );

        console.log('Current page:', currentPage, 'Is protected:', isProtectedPage);

        if (!userSession && isProtectedPage) {
            console.log('Not logged in, redirecting to login page');
            this.showLoginRequiredModal();
            return false;
        }

        return true;
    }

    showLoginRequiredModal() {
        console.log('Showing login required modal');

        // Create modal HTML
        const modalHTML = `
            <div class="custom-modal-overlay" id="loginRequiredModal">
                <div class="custom-modal">
                    <div class="modal-header">
                        <div class="modal-icon">
                            <i class="fas fa-user-lock"></i>
                        </div>
                        <h3 class="modal-title">Login Required</h3>
                    </div>

                    <div class="modal-body">
                        <p>You need to be logged in to access this page.</p>
                        <p class="modal-subtext">
                            Please log in to continue to the dashboard.<br>
                            Auto-redirecting in 5 seconds...
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button class="btn-secondary modal-btn" id="cancelRedirect">
                            <i class="fas fa-times"></i>
                            Stay Here
                        </button>
                        <button class="btn-primary modal-btn" id="proceedToLogin">
                            <i class="fas fa-sign-in-alt"></i>
                            Go to Login
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Add styles if not already present
        this.addModalStyles();

        // Add event listeners
        setTimeout(() => {
            const modal = document.getElementById('loginRequiredModal');
            const cancelBtn = document.getElementById('cancelRedirect');
            const loginBtn = document.getElementById('proceedToLogin');

            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    console.log('User chose to stay');
                    modal.remove();
                });
            }

            if (loginBtn) {
                loginBtn.addEventListener('click', () => {
                    console.log('Redirecting to login page');
                    modal.remove();
<<<<<<< HEAD
                    window.location.href = 'login.php';
=======
                    window.location.href = 'login.html';
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
                });
            }

            // Auto-redirect after 5 seconds
            setTimeout(() => {
                if (document.getElementById('loginRequiredModal')) {
                    console.log('Auto-redirecting to login page');
<<<<<<< HEAD
                    window.location.href = 'login.php';
=======
                    window.location.href = 'login.html';
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
                }
            }, 5000);
        }, 10);
    }

    loadUserData() {
        const savedData = localStorage.getItem('userProfile');
        if (savedData) {
            try {
                const parsedData = JSON.parse(savedData);
                // Validate and sanitize the parsed data
                if (this.isValidUserProfile(parsedData)) {
                    this.userData = { 
                        ...this.userData, 
                        ...this.sanitizeUserProfile(parsedData) 
                    };
                } else {
                    console.error('Invalid user profile data detected');
                    // Optionally reset to default profile
                    this.userData = this.getDefaultUserData();
                }
            } catch (error) {
                console.error('Error parsing user profile data:', error);
                // Reset to default profile on parsing error
                this.userData = this.getDefaultUserData();
            }
        }
        this.updateHeaderInfo();
    }
    
    // Validate user profile data
    isValidUserProfile(data) {
        if (!data || typeof data !== 'object') {
            return false;
        }
        
        // Check for required fields
        const requiredFields = ['name', 'email'];
        for (const field of requiredFields) {
            if (!(field in data) || typeof data[field] !== 'string') {
                return false;
            }
        }
        
        // Validate email format
        if (data.email && !Utils.ValidationUtils.email(data.email)) {
            return false;
        }
        
        // Check for potentially dangerous content
        const dangerousPatterns = [/<script/i, /javascript:/i, /vbscript:/i, /on\w+=/i];
        for (const key in data) {
            if (typeof data[key] === 'string') {
                for (const pattern of dangerousPatterns) {
                    if (pattern.test(data[key])) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    // Sanitize user profile data
    sanitizeUserProfile(data) {
        const sanitized = {};
        
        for (const key in data) {
            if (data.hasOwnProperty(key)) {
                if (typeof data[key] === 'string') {
                    sanitized[key] = Utils.UIHelper.sanitizeHTML(data[key]);
                } else {
                    sanitized[key] = data[key];
                }
            }
        }
        
        return sanitized;
    }
    
    // Get default user data
    getDefaultUserData() {
        return {
            name: "User",
            email: "john.doe@safety.gov",
            role: "Safety Manager",
            department: "Public Safety Department",
            phone: "+1 (555) 123-4567",
            location: "Downtown District",
            lastLogin: new Date().toLocaleString(),
            notifications: 3,
            avatar: "U"
        };
    }

    updateHeaderInfo() {
        const userNameElement = document.querySelector('.user-profile > div:nth-child(2) > div:nth-child(1)');
        const userRoleElement = document.querySelector('.user-profile > div:nth-child(2) > div:nth-child(2)');
        const userAvatarElement = document.querySelector('.user-avatar');

        if (userNameElement) {
            userNameElement.textContent = Utils.UIHelper.sanitizeHTML(this.userData.name);
        }
        if (userRoleElement) {
            userRoleElement.textContent = Utils.UIHelper.sanitizeHTML(this.userData.role);
        }
        if (userAvatarElement) {
            userAvatarElement.textContent = Utils.UIHelper.sanitizeHTML(this.userData.avatar);
        }
    }

    setupProfile() {
        const profileElement = document.querySelector('.user-profile');
        if (!profileElement) {
            console.log('Profile element not found');
            return;
        }

        // Add click handler to profile element
        profileElement.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleProfileDropdown();
        });

        // Create dropdown
        this.createProfileDropdown();
    }

    createProfileDropdown() {
        // Remove existing dropdown if any
        const existingDropdown = document.querySelector('.profile-dropdown');
        if (existingDropdown) {
            existingDropdown.remove();
        }

        const dropdownHTML = `
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    <div class="dropdown-avatar">${this.userData.avatar}</div>
                    <div class="dropdown-user-info">
                        <div class="dropdown-user-name">${this.userData.name}</div>
                        <div class="dropdown-user-role">${this.userData.role}</div>
                        <div class="dropdown-user-email">${this.userData.email}</div>
                    </div>
                </div>

                <div class="dropdown-divider"></div>

                <a href="profile.html" class="dropdown-item">
                    <i class="fas fa-user-circle"></i>
                    <span>My Profile</span>
                </a>

                <a href="settings.html" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>

                <a href="notifications.html" class="dropdown-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    ${this.userData.notifications > 0 ? `<span class="notification-badge">${this.userData.notifications}</span>` : ''}
                </a>

                <div class="dropdown-divider"></div>

                <a href="help.html" class="dropdown-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & Support</span>
                </a>

                <a href="feedback.html" class="dropdown-item">
                    <i class="fas fa-comment-alt"></i>
                    <span>Send Feedback</span>
                </a>

                <div class="dropdown-divider"></div>

                <button class="dropdown-item logout-btn" id="logoutButton">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        `;

        const headerActions = document.querySelector('.header-actions');
        if (headerActions) {
            headerActions.style.position = 'relative';
            headerActions.insertAdjacentHTML('beforeend', dropdownHTML);

            // Add event listener to logout button
            const logoutBtn = document.getElementById('logoutButton');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Logout button clicked');
                    this.showLogoutConfirmation();
                });
            }
        }
    }

    toggleProfileDropdown() {
        const dropdown = document.querySelector('.profile-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('active');
        }
    }

    showLogoutConfirmation() {
        console.log('Showing logout confirmation');

        // Close profile dropdown if open
        const dropdown = document.querySelector('.profile-dropdown');
        if (dropdown) {
            dropdown.classList.remove('active');
        }

        // Add blur effect to body and disable interactions
        document.body.classList.add('logout-modal-open');

        // Create modal HTML with correct CSS classes for centered position
        const modalHTML = `
            <div class="logout-modal active" id="logoutModal">
                <div class="logout-modal-content">
                    <div class="logout-modal-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>

                    <h3 class="logout-modal-title">Logout Confirmation</h3>

                    <p class="logout-modal-text">
                        Are you sure you want to logout?<br>
                        <span style="opacity: 0.8; font-size: 14px;">
                            You will need to log in again to access your account.
                        </span>
                    </p>

                    <div class="logout-modal-actions">
                        <button class="logout-modal-btn cancel" id="cancelLogout">
                            Cancel
                        </button>
                        <button class="logout-modal-btn confirm" id="confirmLogout">
                            <i class="fas fa-sign-out-alt"></i>
                            Yes, Logout
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('logoutModal');
        if (existingModal) {
            existingModal.remove();
            document.body.classList.remove('logout-modal-open');
        }

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Add event listeners
        setTimeout(() => {
            const cancelBtn = document.getElementById('cancelLogout');
            const confirmBtn = document.getElementById('confirmLogout');
            const modal = document.getElementById('logoutModal');

            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    console.log('Logout cancelled');
                    document.body.classList.remove('logout-modal-open');
                    modal.remove();
                });
            }

            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    console.log('Logout confirmed');
                    document.body.classList.remove('logout-modal-open');
                    this.performLogout();
                });
            }

            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        console.log('Clicked outside modal');
                        document.body.classList.remove('logout-modal-open');
                        modal.remove();
                    }
                });
            }

            // Also listen for Escape key
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    console.log('Escape pressed');
                    document.body.classList.remove('logout-modal-open');
                    modal.remove();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        }, 10);
    }

    addModalStyles() {
        // Add styles if not already present
        if (!document.getElementById('modal-styles')) {
            const styleHTML = `
                <style id="modal-styles">
                    /* Modal overlay - covers entire screen */
                    .custom-modal-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.7);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 99999;
                        backdrop-filter: blur(3px);
                        animation: fadeIn 0.3s ease-out;
                    }

                    /* Modal container - perfectly centered */
                    .custom-modal {
                        background: white;
                        border-radius: 12px;
                        padding: 30px;
                        width: 90%;
                        max-width: 400px;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        animation: slideUp 0.3s ease-out;
                        border: 1px solid rgba(255, 255, 255, 0.1);
                        margin: 20px; /* Adds spacing on small screens */
                    }

                    /* Ensure modal stays centered on all screen sizes */
                    @media (max-width: 480px) {
                        .custom-modal {
                            margin: 20px;
                            width: calc(100% - 40px);
                            max-width: none;
                        }
                    }

                    /* Modal content */
                    .modal-header {
                        text-align: center;
                        margin-bottom: 20px;
                    }

                    .modal-icon {
                        font-size: 48px;
                        color: #3498db;
                        margin-bottom: 15px;
                    }

                    .modal-icon.logout-icon {
                        color: #e74c3c;
                    }

                    .modal-title {
                        font-size: 22px;
                        font-weight: 600;
                        margin: 0 0 10px 0;
                        color: #2c3e50;
                    }

                    .modal-body {
                        margin-bottom: 25px;
                        text-align: center;
                    }

                    .modal-body p {
                        color: #34495e;
                        line-height: 1.6;
                        margin: 0 0 10px 0;
                    }

                    .modal-subtext {
                        color: #7f8c8d;
                        font-size: 14px;
                    }

                    .modal-footer {
                        display: flex;
                        gap: 12px;
                    }

                    /* Modal buttons */
                    .modal-btn {
                        flex: 1;
                        padding: 12px 20px;
                        border-radius: 8px;
                        border: none;
                        font-weight: 600;
                        cursor: pointer;
                        font-size: 14px;
                        transition: all 0.2s ease;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                    }

                    .modal-btn:hover {
                        transform: translateY(-1px);
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    }

                    .btn-secondary {
                        background: #ecf0f1;
                        color: #2c3e50;
                    }

                    .btn-secondary:hover {
                        background: #dfe6e9;
                    }

                    .btn-primary {
                        background: #3498db;
                        color: white;
                    }

                    .btn-primary:hover {
                        background: #2980b9;
                    }

                    .btn-danger {
                        background: #e74c3c;
                        color: white;
                    }

                    .btn-danger:hover {
                        background: #c0392b;
                    }

                    /* Profile dropdown styles */
                    .profile-dropdown {
                        position: absolute;
                        top: 100%;
                        right: 0;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                        width: 280px;
                        z-index: 1000;
                        margin-top: 10px;
                        opacity: 0;
                        visibility: hidden;
                        transform: translateY(-10px);
                        transition: all 0.3s ease;
                        border: 1px solid #e0e0e0;
                    }

                    .profile-dropdown.active {
                        opacity: 1;
                        visibility: visible;
                        transform: translateY(0);
                    }

                    .dropdown-header {
                        display: flex;
                        align-items: center;
                        padding: 20px;
                        background: #f8f9fa;
                        border-radius: 8px 8px 0 0;
                    }

                    .dropdown-avatar {
                        width: 50px;
                        height: 50px;
                        background: #3498db;
                        color: white;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-weight: bold;
                        font-size: 20px;
                        margin-right: 15px;
                    }

                    .dropdown-user-info {
                        flex: 1;
                    }

                    .dropdown-user-name {
                        font-weight: 600;
                        color: #2c3e50;
                        margin-bottom: 3px;
                    }

                    .dropdown-user-role {
                        color: #7f8c8d;
                        font-size: 13px;
                        margin-bottom: 5px;
                    }

                    .dropdown-user-email {
                        color: #3498db;
                        font-size: 12px;
                        word-break: break-all;
                    }

                    .dropdown-divider {
                        height: 1px;
                        background: #eee;
                        margin: 8px 0;
                    }

                    .dropdown-item {
                        display: flex;
                        align-items: center;
                        padding: 12px 20px;
                        color: #2c3e50;
                        text-decoration: none;
                        transition: background 0.2s ease;
                        position: relative;
                    }

                    .dropdown-item:hover {
                        background: #f8f9fa;
                    }

                    .dropdown-item i {
                        width: 20px;
                        margin-right: 12px;
                        color: #7f8c8d;
                    }

                    .dropdown-item .notification-badge {
                        position: absolute;
                        right: 20px;
                        background: #e74c3c;
                        color: white;
                        border-radius: 10px;
                        padding: 2px 8px;
                        font-size: 11px;
                        font-weight: 600;
                    }

                    .logout-btn {
                        color: #e74c3c;
                    }

                    .logout-btn i {
                        color: #e74c3c;
                    }

                    /* Animations */
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }

                    @keyframes slideUp {
                        from {
                            opacity: 0;
                            transform: translateY(20px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                </style>
            `;

            document.head.insertAdjacentHTML('beforeend', styleHTML);
        }
    }

    performLogout() {
        console.log('Performing logout...');

        // Update modal to show loading in the center
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.innerHTML = `
                <div class="logout-modal-content">
                    <div class="logout-modal-icon" style="color: var(--accent); animation: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>

                    <h3 class="logout-modal-title">Logging Out...</h3>

                    <p class="logout-modal-text">
                        Please wait while we log you out.
                    </p>

                    <div class="login-loading-progress" style="margin-top: 30px;">
                        <div class="login-loading-progress-bar"></div>
                    </div>
                </div>
            `;
        }

        // Clear all user data
        localStorage.clear();
        sessionStorage.clear();
        console.log('Local storage cleared');

        // Show success message briefly before redirect
        setTimeout(() => {
            if (modal) {
                modal.innerHTML = `
                    <div class="logout-modal-content">
                        <div class="logout-modal-icon" style="color: var(--success); animation: none;">
                            <i class="fas fa-check-circle"></i>
                        </div>

                        <h3 class="logout-modal-title">Logged Out Successfully</h3>

                        <p class="logout-modal-text">
                            You have been logged out.<br>
                            Redirecting to login page...
                        </p>
                    </div>
                `;
            }

            // Redirect after delay
            setTimeout(() => {
                console.log('Redirecting to login page...');
                document.body.classList.remove('logout-modal-open');
<<<<<<< HEAD
                window.location.href = 'login.php';
=======
                window.location.href = 'login.html';
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
            }, 1000);
        }, 1500);
    }
}

// Initialize user profile when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Don't initialize on login page
<<<<<<< HEAD
    if (window.location.pathname.includes('login.php')) {
=======
    if (window.location.pathname.includes('login.html')) {
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
        return;
    }

    // Initialize user profile for dashboard pages
    try {
        window.userProfile = new UserProfile();
        console.log('UserProfile initialized for dashboard');
    } catch (error) {
        console.error('Error initializing UserProfile:', error);

        // Show error modal if initialization fails
        const errorHTML = `
            <div class="custom-modal-overlay" id="profileErrorModal">
                <div class="custom-modal">
                    <div class="modal-header">
                        <div class="modal-icon" style="color: #f39c12;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="modal-title">Profile Error</h3>
                    </div>

                    <div class="modal-body">
                        <p>There was an error loading your profile.</p>
                        <p class="modal-subtext">Please try refreshing the page or contact support.</p>
                    </div>

                    <div class="modal-footer">
                        <button class="btn-secondary modal-btn" onclick="location.reload()">
                            <i class="fas fa-redo"></i>
                            Refresh Page
                        </button>
                        <button class="btn-primary modal-btn" onclick="window.location.href='help.html'">
                            <i class="fas fa-life-ring"></i>
                            Get Help
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', errorHTML);
    }
});

// Global logout function
window.forceLogout = function() {
    console.log('forceLogout called');

    // Use the UserProfile's logout method if available
    if (window.userProfile && typeof window.userProfile.showLogoutConfirmation === 'function') {
        window.userProfile.showLogoutConfirmation();
    } else {
        // Fallback to simple logout
        localStorage.clear();
        sessionStorage.clear();
<<<<<<< HEAD
        window.location.href = 'login.php';
=======
        window.location.href = 'login.html';
>>>>>>> a5ee48574ab959bafe1d5a07ba89c68909282e5a
    }
};

// Show success/error messages
window.showMessage = function(type, title, message, duration = 5000) {
    const messageHTML = `
        <div class="message-toast message-${type}">
            <div class="message-icon">
                ${type === 'success' ? '<i class="fas fa-check-circle"></i>' : ''}
                ${type === 'error' ? '<i class="fas fa-exclamation-circle"></i>' : ''}
                ${type === 'warning' ? '<i class="fas fa-exclamation-triangle"></i>' : ''}
                ${type === 'info' ? '<i class="fas fa-info-circle"></i>' : ''}
            </div>
            <div class="message-content">
                <h4 class="message-title">${title}</h4>
                <p class="message-text">${message}</p>
            </div>
            <button class="message-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    // Add message to body
    document.body.insertAdjacentHTML('beforeend', messageHTML);

    // Add message styles if not already present
    if (!document.getElementById('message-styles')) {
        const messageStyles = `
            <style id="message-styles">
                .message-toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                    padding: 15px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    min-width: 300px;
                    max-width: 400px;
                    z-index: 10000;
                    animation: slideInRight 0.3s ease-out;
                    border-left: 4px solid #3498db;
                }

                .message-toast.message-success {
                    border-left-color: #2ecc71;
                }

                .message-toast.message-error {
                    border-left-color: #e74c3c;
                }

                .message-toast.message-warning {
                    border-left-color: #f39c12;
                }

                .message-toast.message-info {
                    border-left-color: #3498db;
                }

                .message-icon {
                    font-size: 24px;
                }

                .message-toast.message-success .message-icon {
                    color: #2ecc71;
                }

                .message-toast.message-error .message-icon {
                    color: #e74c3c;
                }

                .message-toast.message-warning .message-icon {
                    color: #f39c12;
                }

                .message-toast.message-info .message-icon {
                    color: #3498db;
                }

                .message-content {
                    flex: 1;
                }

                .message-title {
                    margin: 0 0 5px 0;
                    font-size: 14px;
                    font-weight: 600;
                    color: #2c3e50;
                }

                .message-text {
                    margin: 0;
                    font-size: 13px;
                    color: #7f8c8d;
                    line-height: 1.4;
                }

                .message-close {
                    background: none;
                    border: none;
                    color: #95a5a6;
                    cursor: pointer;
                    padding: 0;
                    font-size: 14px;
                    transition: color 0.2s;
                }

                .message-close:hover {
                    color: #e74c3c;
                }

                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', messageStyles);
    }

    // Auto-remove after duration
    setTimeout(() => {
        const message = document.querySelector('.message-toast');
        if (message) {
            message.remove();
        }
    }, duration);
};

