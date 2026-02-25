// Enhanced JavaScript for interactive dashboard
// Refactored for better maintainability, performance, and error handling

// Global error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // Don't let errors break the page
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled Promise Rejection:', e.reason);
    // Don't let errors break the page
    e.preventDefault();
});

// Utils object - assuming this is loaded from utils.js; if not, define minimally
if (!window.Utils) {
    window.Utils = {
        SessionManager: {
            init: () => console.warn('SessionManager not implemented')
        },
        NavigationManager: class {},
        APIHandler: class {
            async get(url) {
                // Mock API for demo; replace with real fetch
                return new Promise(resolve => setTimeout(() => resolve({}), 500));
            }
        },
        UIHelper: {
            showToast: (msg, type) => console.log(`${type}: ${msg}`),
            sanitizeHTML: str => str.replace(/</g, '&lt;').replace(/>/g, '&gt;')
        },
        WidgetManager: class {
            updateWidgetData(widget) {
                console.log('Updating widget:', widget);
            }
            openWidgetDetails(type) {
                console.log('Opening details for:', type);
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        // Initialize session management
        Utils.SessionManager.init();

        // Initialize shared components
        const navigationManager = new Utils.NavigationManager();
        const apiHandler = new Utils.APIHandler();
        
        // Initialize dashboard components
        initializeDashboard(apiHandler);
        initializeSearch();
        initializeNotifications(); // Make sure this function exists
        initializeExport();
        initializeQuickActions();
        initializeActivityFeed();
        initializeUserProfile();
        
        // IMPORTANT: Initialize navigation for proper link handling
        initializeNavigation();

        // Setup global error handling (already set, but ensure)
        setupErrorHandling();
        
        // Initialize additional functionality
        initializeUserData();
        
        // Initialize interactive charts
        initializeInteractiveCharts();
        
        // Initialize notification system
        initializeNotificationSystem();
        
        // Add custom styles if needed (better to move to CSS)
        addCustomStyles();
    } catch (error) {
        console.error('Error initializing dashboard:', error);
        // Fallback: show basic notification if possible
        showNotification('Dashboard initialization failed. Some features may not work.', 'error');
    }
});

// Initialize user profile
function initializeUserProfile() {
    const userProfileElement = document.querySelector('.user-profile');
    if (userProfileElement) {
        userProfileElement.addEventListener('click', function(e) {
            e.stopPropagation();
            showNotification('User profile menu opened', 'info');
            // Add actual menu toggle logic here if needed
        });
    }
}

// FIXED: Initialize navigation - doesn't block normal navigation, just enhances it
function initializeNavigation() {
    // Log current location for debugging
    console.log('Current URL:', window.location.href);
    console.log('Current path:', window.location.pathname);
    
    // Handle smooth scrolling for anchor links only
    document.querySelectorAll('nav a[href^="#"], .navigation-link[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            const targetElement = document.querySelector(target);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // Add active class based on current URL
    const currentPath = window.location.pathname;
    const currentFile = currentPath.split('/').pop(); // Get filename
    
    document.querySelectorAll('.nav-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href) {
            // Extract filename from href
            const hrefFile = href.split('/').pop();
            
            // Log for debugging
            console.log('Link href:', href, '| File:', hrefFile, '| Current:', currentFile);
            
            // Check if this link matches current page
            if (hrefFile === currentFile) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
            
            // Also check if href ends with current path
            if (currentPath.endsWith(href) || currentPath.endsWith('/' + href)) {
                link.classList.add('active');
            }
        }
    });
}

// Initialize widgets
function initializeWidgets() {
    const widgets = document.querySelectorAll('.widget');
    widgets.forEach(widget => {
        const header = widget.querySelector('.widget-header');
        if (header) {
            header.addEventListener('click', function() {
                widget.classList.toggle('collapsed');
            });
        }
    });
}

// Initialize notification system
function initializeNotificationSystem() {
    showNotification('Dashboard initialized successfully', 'success');
}

// Initialize user data
function initializeUserData() {
    let userPreferences = JSON.parse(localStorage.getItem('userPreferences') || '{}');
    
    // Set default preferences
    userPreferences = {
        notificationsEnabled: true,
        autoRefresh: true,
        ...userPreferences
    };
    
    localStorage.setItem('userPreferences', JSON.stringify(userPreferences));
}

// Setup global error handling
function setupErrorHandling() {
    // Already set at top; add app-specific handling if needed
}

// Dashboard Initialization
function initializeDashboard(apiHandler) {
    const widgetManager = new Utils.WidgetManager();
    
    // Override update method
    widgetManager.updateWidgetData = function(widget) {
        const widgetType = widget.querySelector('.widget-title')?.textContent?.toLowerCase().replace(/\s+/g, '-') || '';
        
        if (!widgetType) return;
        
        apiHandler.get(`/dashboard/widgets/${widgetType}`)
            .then(data => updateWidgetDisplay(widget, data))
            .catch(() => updateWidgetDisplay(widget, generateMockWidgetData(widgetType)));
    };
    
    // Initialize all widgets
    document.querySelectorAll('.widget').forEach(widget => widgetManager.updateWidgetData(widget));
}

// Update widget display
function updateWidgetDisplay(widget, data) {
    const valueElement = widget.querySelector('.widget-value');
    const changeElement = widget.querySelector('.widget-change');
    
    if (valueElement && data.value) valueElement.textContent = data.value;
    if (changeElement && data.change) {
        changeElement.textContent = data.change;
        changeElement.className = `widget-change ${data.trend || 'neutral'}`;
    }
}

// Generate mock data
function generateMockWidgetData(widgetType) {
    const mockData = {
        'active-campaigns': { value: '12', change: '+2 this week', trend: 'positive' },
        'pending-approvals': { value: '5', change: '-3 from yesterday', trend: 'positive' },
        'total-reach': { value: '45.2K', change: '+12% this month', trend: 'positive' },
        'engagement-rate': { value: '68%', change: '+5% this week', trend: 'positive' }
    };
    
    return mockData[widgetType] || { value: 'N/A', change: 'No data', trend: 'neutral' };
}

// Enhanced Search with debouncing
function initializeSearch() {
    let searchTimeout;
    const searchInput = document.querySelector('.search-box input');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                if (query.length >= 2) performSearch(query);
                else hideSearchResults();
            }, 300);
        });
    }
}

// Perform search
function performSearch(query) {
    const sanitizedQuery = Utils.UIHelper.sanitizeHTML(query);
    Utils.UIHelper.showToast(`Searching for: "${sanitizedQuery}"`, 'info');
    
    const apiHandler = new Utils.APIHandler();
    apiHandler.get(`/search?q=${encodeURIComponent(sanitizedQuery)}`)
        .then(results => displaySearchResults(results))
        .catch(() => displaySearchResults(getMockSearchResults(sanitizedQuery)));
}

// Display search results (placeholder)
function displaySearchResults(results) {
    console.log('Search results:', results);
    // Implement actual display logic here
}

// Hide search results
function hideSearchResults() {
    console.log('Hiding search results');
    // Implement hiding logic
}

// Mock search results
function getMockSearchResults(query) {
    return [{ title: `Mock result for ${query}`, type: 'campaign' }];
}

// Validate search query
function validateSearchQuery(query) {
    // Basic validation: no script tags, etc.
    return !/<script>/i.test(query);
}

// Initialize interactive charts (placeholder)
function initializeInteractiveCharts() {
    console.log('Initializing charts');
    // Add chart library integration if needed (e.g., Chart.js)
}

// Initialize export (placeholder)
function initializeExport() {
    console.log('Initializing export');
}

// Initialize quick actions (placeholder)
function initializeQuickActions() {
    console.log('Initializing quick actions');
}

// Initialize activity feed (placeholder)
function initializeActivityFeed() {
    console.log('Initializing activity feed');
}

// ADDED: Initialize notifications dropdown
function initializeNotifications() {
    const notificationBtn = document.querySelector('.notifications-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('notificationsMenu');
            if (menu) {
                menu.classList.toggle('show');
            }
        });
        
        // Close notifications when clicking outside
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('notificationsMenu');
            const btn = document.querySelector('.notifications-btn');
            if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.remove('show');
            }
        });
    }
}

// Add custom styles dynamically (consider moving to CSS file)
function addCustomStyles() {
    const style = document.createElement('style');
    style.textContent = `
        /* Your custom styles here */
        .notification { position: fixed; bottom: 20px; right: 20px; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; }
        .notification.success { background: #4CAF50; color: white; }
        .notification.error { background: #f44336; color: white; }
        .notification.info { background: #2196F3; color: white; }
        
        /* Notifications dropdown styles */
        .notifications-dropdown { position: relative; }
        .notifications-menu { 
            display: none; 
            position: absolute; 
            right: 0; 
            top: 100%; 
            width: 300px; 
            background: var(--secondary-black); 
            border: 1px solid var(--medium-gray); 
            border-radius: 8px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.2); 
            z-index: 1000; 
        }
        .notifications-menu.show { display: block; }
        .notification-item.unread { background: rgba(74, 144, 226, 0.1); }
    `;
    document.head.appendChild(style);
}

// Show notification
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), duration);
}

// Export modal functions
function openExportModal() {
    showNotification('Export modal opened', 'info');
    // Implement actual modal
}

function closeExportModal() {
    showNotification('Export modal closed', 'info');
}

function exportReport() {
    showNotification('Report exported', 'success');
    closeExportModal();
}

// Mark notifications as read
function markAllAsRead() {
    showNotification('All notifications marked as read', 'success');
}

// Close modal (generic)
function closeModal(modalId) {
    document.getElementById(modalId)?.classList.remove('active');
}

// Placeholder view functions
function viewIncidentDetails(type) {
    showNotification(`Viewing ${type} incidents`, 'info');
}

function assignTeam(type) {
    showNotification(`Assigning team to ${type} incidents`, 'info');
}

function addNewCampaign() {
    showNotification('Adding new campaign', 'info');
}

function viewCampaign(id) {
    showNotification(`Viewing campaign ${id}`, 'info');
}

function editCampaign(id) {
    showNotification(`Editing campaign ${id}`, 'info');
}

function remindMe(campaign) {
    showNotification(`Reminder set for ${campaign}`, 'success');
}

function viewLiveStats(campaign) {
    showNotification(`Viewing stats for ${campaign}`, 'info');
}

function viewAllCampaigns() {
    showNotification('Viewing all campaigns', 'info');
}

// Make functions global if needed
window.viewIncidentDetails = viewIncidentDetails;
window.assignTeam = assignTeam;
window.addNewCampaign = addNewCampaign;
window.viewCampaign = viewCampaign;
window.editCampaign = editCampaign;
window.remindMe = remindMe;
window.viewLiveStats = viewLiveStats;
window.viewAllCampaigns = viewAllCampaigns;
window.openExportModal = openExportModal;
window.markAllAsRead = markAllAsRead;
window.closeModal = closeModal;
window.showNotification = showNotification;