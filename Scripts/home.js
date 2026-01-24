// Enhanced JavaScript for interactive dashboard
// Refactored to use shared utilities for better maintainability

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

// Define missing functions to prevent errors
function initializeUserProfile() {
    // Initialize user profile functionality
    const userProfileElement = document.querySelector('.user-profile');
    if (userProfileElement) {
        // Add user profile interactions if element exists
        userProfileElement.addEventListener('click', function(e) {
            e.stopPropagation();
            showNotification('User profile menu opened', 'info', 2000);
        });
    }
}

function initializeNavigation() {
    // Initialize navigation functionality
    document.querySelectorAll('nav a, .navigation-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const target = this.getAttribute('href');
            if (target && target.startsWith('#')) {
                e.preventDefault();
                // Handle anchor navigation
                const targetElement = document.querySelector(target);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
}

function initializeWidgets() {
    // Initialize dashboard widgets
    const widgets = document.querySelectorAll('.widget');
    widgets.forEach(widget => {
        // Add widget interactions
        const header = widget.querySelector('.widget-header');
        if (header) {
            header.addEventListener('click', function() {
                widget.classList.toggle('collapsed');
            });
        }
    });
}

function initializeNotificationSystem() {
    // Initialize notification system
    showNotification('Dashboard initialized successfully', 'success', 2000);
}

// Initialize user data
function initializeUserData() {
    // Load user preferences
    const userPreferences = JSON.parse(localStorage.getItem('userPreferences') || '{}');
    
    // Set default preferences if not set
    if (!userPreferences.hasOwnProperty('notificationsEnabled')) {
        userPreferences.notificationsEnabled = true;
    }
    
    if (!userPreferences.hasOwnProperty('autoRefresh')) {
        userPreferences.autoRefresh = true;
    }
    
    localStorage.setItem('userPreferences', JSON.stringify(userPreferences));
}

// Setup global error handling
function setupErrorHandling() {
    window.addEventListener('error', function(event) {
        console.error('Global error caught:', event.error);
        if (localStorage.getItem('userPreferences') && 
            JSON.parse(localStorage.getItem('userPreferences')).notificationsEnabled) {
            showNotification('An error occurred: ' + event.error.message, 'error', 5000);
        }
    });
    
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        if (localStorage.getItem('userPreferences') && 
            JSON.parse(localStorage.getItem('userPreferences')).notificationsEnabled) {
            showNotification('An async error occurred: ' + event.reason, 'error', 5000);
        }
    });
}

// Dashboard Initialization
function initializeDashboard(apiHandler) {
    // Initialize widgets with proper cleanup
    if (window.Utils && window.Utils.WidgetManager) {
        const widgetManager = new Utils.WidgetManager();
        
        // Override widget update method for dashboard-specific logic
        if (widgetManager.updateWidgetData) {
            const originalUpdateWidgetData = widgetManager.updateWidgetData;
            widgetManager.updateWidgetData = function(widget) {
                const widgetType = widget.querySelector('.widget-title')?.textContent;
                
                // Simulate data update with API call
                if (apiHandler && apiHandler.get) {
                    apiHandler.get(`/dashboard/widgets/${widgetType.toLowerCase().replace(/\s+/g, '-')}`)
                        .then(data => {
                            updateWidgetDisplay(widget, data);
                        })
                        .catch(() => {
                            // Fallback to simulated data
                            updateWidgetDisplay(widget, generateMockWidgetData(widgetType));
                        });
                } else {
                    // Fallback to simulated data
                    updateWidgetDisplay(widget, generateMockWidgetData(widgetType));
                }
            };
        }
        
        if (widgetManager.openWidgetDetails) {
            widgetManager.openWidgetDetails = function(widgetType) {
                if (Utils.UIHelper && Utils.UIHelper.showToast) {
                    Utils.UIHelper.showToast(`Opening details for ${widgetType}`, 'info');
                }
                // Navigate to detailed view
                setTimeout(() => {
                    window.location.href = `/dashboard/widgets/${widgetType.toLowerCase().replace(/\s+/g, '-')}`;
                }, 1000);
            };
        }
    }
}

// Update widget display with new data
function updateWidgetDisplay(widget, data) {
    const valueElement = widget.querySelector('.widget-value');
    const changeElement = widget.querySelector('.widget-change');
    
    if (valueElement && data.value) {
        valueElement.textContent = data.value;
    }
    
    if (changeElement && data.change) {
        changeElement.textContent = data.change;
        changeElement.className = `widget-change ${data.trend || 'neutral'}`;
    }
}

// Generate mock widget data for fallback
function generateMockWidgetData(widgetType) {
    const mockData = {
        'Active Campaigns': { value: '12', change: '+2 this week', trend: 'positive' },
        'Pending Approvals': { value: '5', change: '-3 from yesterday', trend: 'positive' },
        'Total Reach': { value: '45.2K', change: '+12% this month', trend: 'positive' },
        'Engagement Rate': { value: '68%', change: '+5% this week', trend: 'positive' }
    };
    
    return mockData[widgetType] || { value: 'N/A', change: 'No data', trend: 'neutral' };
}

// Enhanced Search Functionality with debouncing
function initializeSearch() {
    let searchTimeout;
    const searchInput = document.querySelector('.search-box input');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length === 0) {
                hideSearchResults();
                return;
            }
            
            if (query.length < 2) {
                return; // Wait for more characters
            }
            
            // Debounce search by 300ms
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });
        
        // Also handle Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                performSearch(this.value.trim());
            }
        });
    }
}

// Validate search query
function validateSearchQuery(query) {
    if (typeof query !== 'string') {
        return false;
    }
    
    // Check for potentially dangerous patterns
    const dangerousPatterns = [/<script/i, /javascript:/i, /vbscript:/i, /on\w+=/i, /<iframe/i, /<object/i, /<embed/i];
    
    for (const pattern of dangerousPatterns) {
        if (pattern.test(query)) {
            return false;
        }
    }
    
    return true;
}

function displaySearchResults(results) {
    // Create or update search results dropdown
    let resultsContainer = document.querySelector('.search-results');
    if (!resultsContainer) {
        resultsContainer = document.createElement('div');
        resultsContainer.className = 'search-results';
        const searchBox = document.querySelector('.search-box');
        if (searchBox) {
            searchBox.appendChild(resultsContainer);
        }
    }
    
    if (results.length === 0) {
        resultsContainer.innerHTML = '<div class="no-results">No results found</div>';
    } else {
        // Sanitize results to prevent XSS
        const sanitizedResults = results.map(result => {
            return {
                id: result.id ? result.id : '',
                title: result.title || '',
                type: result.type || ''
            };
        });
        
        resultsContainer.innerHTML = sanitizedResults.map(result => `
            <div class="search-result-item" onclick="navigateToResult('${result.type}', '${result.id}')">
                <i class="fas fa-${getResultIcon(result.type)}"></i>
                <div class="result-content">
                    <div class="result-title">${result.title}</div>
                    <div class="result-type">${result.type}</div>
                </div>
            </div>
        `).join('');
    }
    
    resultsContainer.style.display = 'block';
}

function getResultIcon(type) {
    const icons = {
        'campaign': 'calendar-alt',
        'content': 'file-alt',
        'user': 'user',
        'report': 'chart-bar'
    };
    return icons[type] || 'file';
}

function getMockSearchResults(query) {
    return [
        { id: 1, title: `Campaign related to ${query}`, type: 'campaign' },
        { id: 2, title: `Content about ${query}`, type: 'content' },
        { id: 3, title: `Report mentioning ${query}`, type: 'report' }
    ];
}

function navigateToResult(type, id) {
    if (Utils.UIHelper && Utils.UIHelper.showToast) {
        Utils.UIHelper.showToast(`Opening ${type} #${id}`, 'info');
    }
    const resultsContainer = document.querySelector('.search-results');
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.value = '';
    }
}

// 5. Notification System
function initializeNotifications() {
    // The notifications are already handled by the separate notification system
    // This function can remain for backward compatibility if needed
}

// 6. Theme Toggle functionality removed as per requirement

// 7. Data Export
function initializeExport() {
    // Create export button if it doesn't exist
    let exportBtn = document.getElementById('exportData');
    if (!exportBtn) {
        exportBtn = document.createElement('button');
        exportBtn.id = 'exportData';
        exportBtn.className = 'export-btn';
        exportBtn.innerHTML = '<i class="fas fa-download"></i> Export Report';

        const headerActions = document.querySelector('.header-actions');
        if (headerActions) {
            headerActions.insertBefore(exportBtn, headerActions.querySelector('.user-profile'));
        }
    }

    exportBtn.addEventListener('click', function() {
        const exportType = prompt('Select export format: 1) PDF 2) CSV 3) Excel', '1');

        switch(exportType) {
            case '1':
                exportToPDF();
                break;
            case '2':
                exportToCSV();
                break;
            case '3':
                exportToExcel();
                break;
            default:
                alert('Export cancelled');
        }
    });
}

// 8. Quick Actions with Confirmation
function initializeQuickActions() {
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const actionText = this.querySelector('.action-text');
            if (actionText && !confirm(`Proceed with "${actionText.textContent}"?`)) {
                e.preventDefault();
            }
        });
    });
}

// 9. Real-time Activity Feed
function initializeActivityFeed() {
    // Simulate real-time updates
    setInterval(() => {
        addNewActivity();
    }, 60000); // Add new activity every minute

    // Make activity items clickable
    document.querySelectorAll('.activity-item').forEach(item => {
        item.style.cursor = 'pointer';
        item.addEventListener('click', function() {
            const activityText = this.querySelector('.activity-text');
            if (activityText) {
                openActivityDetails(activityText.textContent);
            }
        });
    });
}

// 10. Real-time Data Updates
function initializeRealTimeUpdates() {
    // Update widget values periodically
    setInterval(updateRealTimeData, 10000);

    // WebSocket simulation for real-time incidents
    simulateRealTimeIncidents();
}

// Helper Functions
function trackNavigation(moduleName) {
    console.log(`Module accessed: ${moduleName} at ${new Date().toISOString()}`);
}

function openWidgetDetails(widgetType) {
    alert(`Opening detailed view for: ${widgetType}`);
}

function updateWidgetData(widget) {
    const valueElement = widget.querySelector('.widget-value');
    const currentValue = parseInt(valueElement.textContent);

    // Simulate small random changes
    const change = Math.floor(Math.random() * 5) - 2; // -2 to +2
    const newValue = Math.max(0, currentValue + change);

    valueElement.textContent = newValue;
    valueElement.style.color = change > 0 ? 'var(--success)' :
                               change < 0 ? 'var(--danger)' :
                               'var(--white)';

    // Add animation
    valueElement.style.transform = 'scale(1.1)';
    setTimeout(() => {
        valueElement.style.transform = 'scale(1)';
    }, 300);
}

function performAdvancedSearch(query) {
    alert(`Advanced search for: "${query}"\nOpening search results page...`);
}

function hideSearchResults() {
    const container = document.querySelector('.search-results');
    if (container) container.style.display = 'none';
}

function exportToPDF() {
    alert('Generating PDF report...\nThis would generate a comprehensive report in a real application.');
}

function exportToCSV() {
    const csvContent = "data:text/csv;charset=utf-8," +
        "Metric,Value,Trend\n" +
        "Active Incidents,42,Down 12%\n" +
        "Active Campaigns,18,Up 3\n" +
        "Response Time,8.2m,Improved 1.5m\n" +
        "Satisfaction,92%,Up 4%";

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "dashboard_data.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportToExcel() {
    alert('Excel export requires additional libraries. CSV has been downloaded instead.');
    exportToCSV();
}

function addNewActivity() {
    const activities = [
        'New safety protocol implemented',
        'Emergency drill completed successfully',
        'System maintenance completed',
        'New team member onboarded',
        'Quarterly review meeting scheduled'
    ];

    const randomActivity = activities[Math.floor(Math.random() * activities.length)];
    const activityList = document.querySelector('.activity-list');
    if (!activityList) return;

    const newItem = document.createElement('li');
    newItem.className = 'activity-item new-item';
    newItem.innerHTML = `
        <div class="activity-icon icon-system">
            <i class="fas fa-sync-alt"></i>
        </div>
        <div class="activity-content">
            <div class="activity-text">${randomActivity}</div>
            <div class="activity-time">Just now</div>
        </div>
    `;

    // Add to top of list
    activityList.insertBefore(newItem, activityList.firstChild);

    // Remove oldest if more than 10 items
    if (activityList.children.length > 10) {
        activityList.removeChild(activityList.lastChild);
    }

    // Highlight new item
    setTimeout(() => {
        newItem.classList.remove('new-item');
    }, 3000);
}

function openActivityDetails(text) {
    alert(`Activity Details:

${text}

Would open detailed view in a real application.`);
}

function updateRealTimeData() {
    // Update random widget
    const widgets = document.querySelectorAll('.widget-value');
    if (widgets.length === 0) return;

    const randomWidget = widgets[Math.floor(Math.random() * widgets.length)];
    const change = Math.floor(Math.random() * 3) - 1;
    const currentValue = parseInt(randomWidget.textContent);
    const newValue = Math.max(0, currentValue + change);

    randomWidget.textContent = newValue;
}

function simulateRealTimeIncidents() {
    setInterval(() => {
        if (Math.random() > 0.7) { // 30% chance
            const incidents = Math.floor(Math.random() * 3) + 1;
            showIncidentAlert(incidents);
        }
    }, 15000);
}

function showIncidentAlert(count) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'incident-alert';
    alertDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i>
        <span>${count} new incident${count > 1 ? 's' : ''} reported</span>
        <button class="dismiss-alert">×</button>
    `;

    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.prepend(alertDiv);

        setTimeout(() => {
            alertDiv.classList.add('show');
        }, 100);

        // Auto-dismiss after 10 seconds
        setTimeout(() => {
            dismissAlert(alertDiv);
        }, 10000);

        // Manual dismiss
        alertDiv.querySelector('.dismiss-alert').addEventListener('click', () => {
            dismissAlert(alertDiv);
        });
    }
}

function dismissAlert(alertDiv) {
    alertDiv.classList.remove('show');
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 300);
}

// Debug function to check chart loading
function checkChartLoading() {
    console.log('Checking chart elements...');

    const incidentsChart = document.getElementById('incidentsChart');
    const campaignChart = document.getElementById('campaignChart');

    if (incidentsChart) {
        console.log('✓ Incidents chart element found:', incidentsChart);
        console.log('  - Width:', incidentsChart.width, 'Height:', incidentsChart.height);
    } else {
        console.log('✗ Incidents chart element NOT found');
    }

    if (campaignChart) {
        console.log('✓ Campaign chart element found:', campaignChart);
        console.log('  - Width:', campaignChart.width, 'Height:', campaignChart.height);
    } else {
        console.log('✗ Campaign chart element NOT found');
    }

    // Check if Chart.js is loaded
    if (typeof Chart !== 'undefined') {
        console.log('✓ Chart.js is loaded');
    } else {
        console.log('✗ Chart.js is NOT loaded');
    }
}

// Interactive Chart Functions
function initializeInteractiveCharts() {
    console.log('Initializing interactive charts...');

    // Initialize heat map
    generateHeatMap();

    // Add click events to incident cards
    const incidentCards = document.querySelectorAll('.incident-type-card');
    console.log(`Found ${incidentCards.length} incident cards`);
    
    incidentCards.forEach((incidentCard, index) => {
        // Remove existing listeners to prevent duplicates
        const newCard = incidentCard.cloneNode(true);
        incidentCard.parentNode.replaceChild(newCard, incidentCard);
        
        newCard.addEventListener('click', function() {
            const type = this.dataset.type;
            const count = this.dataset.count;
            console.log(`Incident card clicked: ${type} (${count})`);
            openIncidentDashboard(type, count);
        });
        
        console.log(`Added listener to card ${index + 1}: ${newCard.dataset.type}`);
    });

    // Add click events to campaign cards
    const campaignCards = document.querySelectorAll('.campaign-card');
    console.log(`Found ${campaignCards.length} campaign cards`);
    
    campaignCards.forEach((campaignCard, index) => {
        // Remove existing listeners to prevent duplicates
        const newCard = campaignCard.cloneNode(true);
        campaignCard.parentNode.replaceChild(newCard, campaignCard);
        
        newCard.addEventListener('click', function(e) {
            // Only trigger if not clicking on action buttons
            if (!e.target.closest('.campaign-actions')) {
                const campaignId = this.dataset.id;
                console.log(`Campaign card clicked: ${campaignId}`);
                viewCampaignDetails(campaignId);
            }
        });
        
        console.log(`Added listener to campaign card ${index + 1}: ${newCard.dataset.id}`);
    });

    // Initialize time filter
    const timeFilter = document.getElementById('timeFilter');
    if (timeFilter) {
        // Remove existing listener
        const newTimeFilter = timeFilter.cloneNode(true);
        timeFilter.parentNode.replaceChild(newTimeFilter, timeFilter);
        
        newTimeFilter.addEventListener('change', function() {
            console.log(`Time filter changed to: ${this.value}`);
            filterIncidentsByTime(this.value);
        });
        console.log('✓ Time filter initialized');
    } else {
        console.log('✗ Time filter element not found');
    }
    
    console.log('✓ Interactive charts initialized');
}

// Generate Heat Map
function generateHeatMap() {
    console.log('generateHeatMap() function called');
    const grid = document.querySelector('.heat-map-grid') || document.getElementById('heatMapGrid');
    console.log('Looking for heat map grid element...');
    console.log('Found .heat-map-grid:', document.querySelector('.heat-map-grid'));
    console.log('Found #heatMapGrid:', document.getElementById('heatMapGrid'));
    if (!grid) {
        console.log('✗ Heat map grid element not found (.heat-map-grid or #heatMapGrid)');
        return;
    }

    console.log('✓ Generating heat map');

    // Clear the grid to remove any existing cells
    grid.innerHTML = '';

    // Generate 28 cells (4 weeks x 7 days)
    for (let i = 0; i < 28; i++) {
        const cell = document.createElement('div');

        // Random intensity for demo
        const intensity = Math.floor(Math.random() * 20);

        if (intensity <= 5) {
            cell.className = 'heat-map-cell low';
            cell.title = `${intensity} incidents`;
        } else if (intensity <= 15) {
            cell.className = 'heat-map-cell medium';
            cell.title = `${intensity} incidents`;
        } else {
            cell.className = 'heat-map-cell high';
            cell.title = `${intensity} incidents`;
        }

        // Store week and day data in the element for easier access
        const week = Math.floor(i / 7) + 1;
        const day = (i % 7) + 1;
        
        // Add click event
        cell.addEventListener('click', function() {
            console.log(`Heat map cell clicked: Week ${week}, Day ${day} (${intensity} incidents)`);
            showDayDetails(week, day, intensity);
        });

        grid.appendChild(cell);
    }
    
    console.log('✓ Heat map generated with 28 cells');
}

// Incident Type Functions
function viewIncidentDetails(type) {
    alert(`Opening detailed view for ${type} incidents\n\nThis would show:
    • Recent ${type} incidents
    • Response teams assigned
    • Resolution status
    • Time analytics`);

    console.log(`Viewing ${type} incident details`);
}

function assignTeam(type) {
    const teamName = prompt(`Assign team to ${type} incidents:`);
    if (teamName) {
        showNotification(`Team "${teamName}" assigned to ${type} incidents`, 'success');
    }
}

function openIncidentDashboard(type, count) {
    console.log(`Opening dashboard for ${type} (${count} incidents)`);

    // Highlight selected card
    document.querySelectorAll('.incident-type-card').forEach(dashboardCard => {
        dashboardCard.style.transform = 'none';
        dashboardCard.style.boxShadow = 'none';
    });

    const selectedCard = document.querySelector(`.incident-type-card[data-type="${type}"]`);
    if (selectedCard) {
        selectedCard.style.transform = 'scale(1.02)';
        selectedCard.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.3)';
        console.log(`✓ Highlighted ${type} card`);
    } else {
        console.log(`✗ Could not find card for type: ${type}`);
    }

    showNotification(`Showing ${type} incidents (${count} total)`, 'info');
}

// Time Filter Function
function filterIncidentsByTime(timeRange) {
    const ranges = {
        'today': 'Last 24 hours',
        'week': 'Last 7 days',
        'month': 'Last 30 days',
        'quarter': 'Last 90 days'
    };

    console.log(`Filtering incidents for: ${ranges[timeRange]}`);

    // Simulate loading
    showNotification(`Loading incidents for ${ranges[timeRange].toLowerCase()}...`, 'info');

    // Simulate API call
    setTimeout(() => {
        // Update incident counts (random for demo)
        const incidents = {
            emergency: Math.floor(Math.random() * 30) + 10,
            health: Math.floor(Math.random() * 25) + 5,
            safety: Math.floor(Math.random() * 40) + 10,
            fire: Math.floor(Math.random() * 20) + 5,
            police: Math.floor(Math.random() * 25) + 8
        };

        // Update UI
        Object.keys(incidents).forEach(type => {
            const card = document.querySelector(`.incident-type-card[data-type="${type}"]`);
            if (card) {
                const countElement = card.querySelector('.incident-count');
                if (countElement) {
                    countElement.textContent = incidents[type];
                }
                card.dataset.count = incidents[type];

                // Update trend randomly
                const trends = ['up', 'down', 'neutral'];
                const trend = trends[Math.floor(Math.random() * 3)];
                const change = Math.floor(Math.random() * 20);

                const trendElement = card.querySelector('.incident-trend');
                if (trendElement) {
                    trendElement.textContent = trend === 'up' ? `↑ ${change}%` :
                                            trend === 'down' ? `↓ ${change}%` : '↔ 0%';
                    trendElement.className = `incident-trend ${trend}`;
                }

                console.log(`✓ Updated ${type} card: ${incidents[type]} incidents`);
            } else {
                console.log(`✗ Could not find card for type: ${type}`);
            }
        });

        // Regenerate heat map with new data
        generateHeatMap();

        showNotification(`Incidents filtered for ${ranges[timeRange].toLowerCase()}`, 'success');
    }, 800);
}

// Day Details Function
function showDayDetails(week, day, incidents) {
    const modalContent = `
        <div class="modal-header">
            <h3>Week ${week}, Day ${day}</h3>
        </div>
        <div class="modal-body">
            <div class="incident-breakdown">
                <h4>Incident Breakdown</h4>
                <div class="breakdown-item">
                    <span>Emergency:</span>
                    <span>${Math.floor(incidents * 0.3)}</span>
                </div>
                <div class="breakdown-item">
                    <span>Health:</span>
                    <span>${Math.floor(incidents * 0.2)}</span>
                </div>
                <div class="breakdown-item">
                    <span>Safety:</span>
                    <span>${Math.floor(incidents * 0.25)}</span>
                </div>
                <div class="breakdown-item">
                    <span>Fire:</span>
                    <span>${Math.floor(incidents * 0.1)}</span>
                </div>
                <div class="breakdown-item">
                    <span>Police:</span>
                    <span>${Math.floor(incidents * 0.15)}</span>
                </div>
            </div>
            <div class="response-info">
                <h4>Response Information</h4>
                <p>Average response time: ${(Math.random() * 15 + 5).toFixed(1)} minutes</p>
                <p>Resolution rate: ${(Math.random() * 30 + 70).toFixed(0)}%</p>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="closeModal()" class="action-btn-small" style="background: var(--dark-gray);">Close</button>
            <button onclick="exportDayReport(${week}, ${day})" class="action-btn-small">Export Report</button>
        </div>
    `;

    showModal('Day Details', modalContent);
}

// Enhanced Campaign Functions
function addNewCampaign() {
    showNotification('Opening new campaign creation wizard...', 'info', 2000);
    
    // Simulate API call to create new campaign
    const newCampaignData = {
        id: Date.now(), // Unique ID
        name: '',
        status: 'draft',
        createdDate: new Date().toISOString(),
        progress: 0
    };
    
    // Store in local storage temporarily
    try {
        let campaigns = JSON.parse(localStorage.getItem('campaigns')) || [];
        campaigns.push(newCampaignData);
        localStorage.setItem('campaigns', JSON.stringify(campaigns));
        
        // Show success notification
        showNotification('New campaign created successfully!', 'success', 3000);
        
        // Redirect to campaign planning module
        window.location.href = '../Modules/Module-1.php';
    } catch (error) {
        console.error('Error creating campaign:', error);
        showNotification('Failed to create campaign. Please try again.', 'error', 4000);
    }
}

// Open campaign creation modal
function openCampaignCreationModal(campaignId) {
    const modalContent = `
        <div class="campaign-wizard">
            <h3>Create New Campaign</h3>
            <div class="wizard-steps">
                <div class="step active" data-step="1">Basic Info</div>
                <div class="step" data-step="2">Audience</div>
                <div class="step" data-step="3">Timeline</div>
                <div class="step" data-step="4">Review</div>
            </div>
            <div class="wizard-content">
                <div class="step-form active" data-step="1">
                    <div class="form-group">
                        <label for="campaignName">Campaign Name</label>
                        <input type="text" id="campaignName" placeholder="Enter campaign name">
                    </div>
                    <div class="form-group">
                        <label for="campaignGoal">Goal</label>
                        <textarea id="campaignGoal" placeholder="Describe the campaign goal"></textarea>
                    </div>
                </div>
            </div>
            <div class="wizard-actions">
                <button class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn-primary" onclick="nextWizardStep(2)">Next</button>
            </div>
        </div>
    `;
    
    showModal('New Campaign', modalContent);
}

// Navigate wizard steps
function nextWizardStep(step) {
    // Update UI to show next step
    document.querySelectorAll('.step').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step-form').forEach(el => el.classList.remove('active'));
    
    const stepElement = document.querySelector(`.step[data-step="${step}"]`);
    const stepFormElement = document.querySelector(`.step-form[data-step="${step}"]`);
    
    if (stepElement) stepElement.classList.add('active');
    if (stepFormElement) stepFormElement.classList.add('active');
    
    // Update button actions based on step
    const actionsDiv = document.querySelector('.wizard-actions');
    if (actionsDiv) {
        if (step < 4) {
            actionsDiv.innerHTML = `
                <button class="btn-secondary" onclick="prevWizardStep(${step - 1})">Back</button>
                <button class="btn-primary" onclick="nextWizardStep(${step + 1})">Next</button>
            `;
        } else {
            actionsDiv.innerHTML = `
                <button class="btn-secondary" onclick="prevWizardStep(${step - 1})">Back</button>
                <button class="btn-primary" onclick="finishCampaignCreation()">Finish</button>
            `;
        }
    }
}

function prevWizardStep(step) {
    if (step > 0) {
        nextWizardStep(step);
    }
}

function finishCampaignCreation() {
    showNotification('Campaign created successfully!', 'success', 3000);
    closeModal();
    
    // Redirect to campaign planning module after a short delay
    setTimeout(() => {
        window.location.href = '../Modules/Module-1.php';
    }, 1500);
}

function viewCampaign(id) {
    const campaigns = {
        1: { name: 'Summer Safety', status: 'active', progress: 75, reach: 7500, engagement: 92, costPerReach: 0.42, completion: 78 },
        2: { name: 'School Zone Safety', status: 'active', progress: 60, reach: 5200, engagement: 88, costPerReach: 0.38, completion: 78 },
        3: { name: 'Home Safety Week', status: 'planned', progress: 10, reach: 10000, startDate: 'Oct 15', daysLeft: 5 },
        4: { name: 'Road Safety Month', status: 'completed', progress: 100, reach: 12500, engagement: 95, costPerReach: 0.35, completion: 100 }
    };

    const campaign = campaigns[id];
    if (campaign) {
        let extraInfo = '';
        if (campaign.status === 'planned') {
            extraInfo = `<div class="detail-item">
                <strong>Starts:</strong> ${campaign.startDate}<br>
                <strong>Days Left:</strong> ${campaign.daysLeft}
            </div>`;
        }
        
        showModal(
            `Campaign: ${campaign.name}`,
            `<div class="campaign-details">
                <div class="detail-item">
                    <strong>Status:</strong> ${campaign.status.toUpperCase()}
                </div>
                <div class="detail-item">
                    <strong>Progress:</strong> ${campaign.progress}% Complete
                </div>
                <div class="detail-item">
                    <strong>Reach:</strong> ${campaign.reach.toLocaleString()}
                </div>
                <div class="detail-item">
                    <strong>Engagement:</strong> ${campaign.engagement}%
                </div>
                ${extraInfo}
                ${campaign.status !== 'planned' ? `
                <div class="detail-item">
                    <strong>Cost per Reach:</strong> $${campaign.costPerReach.toFixed(2)}
                </div>
                <div class="detail-item">
                    <strong>Avg Completion:</strong> ${campaign.completion}%
                </div>` : ''}
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button onclick="openAnalytics(${id})" class="action-btn-small">View Analytics</button>
                    <button onclick="editCampaign(${id})" class="action-btn-small" style="background: var(--dark-gray);">Edit</button>
                </div>
            </div>`
        );
    }
}

function remindMe(campaign) {
    const time = prompt('Set reminder for when? (e.g., "1 hour", "tomorrow 9am")');
    if (time) {
        showNotification(`Reminder set for ${campaign} campaign in ${time}`, 'success');
    }
}

function viewLiveStats(campaign) {
    showModal(
        'Live Campaign Stats',
        `<div class="live-stats">
            <h3>${campaign} Campaign</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <div class="stat">
                    <div class="stat-value">${Math.floor(Math.random() * 1000) + 7000}</div>
                    <div class="stat-label">Live Reach</div>
                </div>
                <div class="stat">
                    <div class="stat-value">${(Math.random() * 8 + 2).toFixed(1)}%</div>
                    <div class="stat-label">Engagement</div>
                </div>
                <div class="stat">
                    <div class="stat-value">${Math.floor(Math.random() * 500) + 200}</div>
                    <div class="stat-label">Shares</div>
                </div>
                <div class="stat">
                    <div class="stat-value">${Math.floor(Math.random() * 1000) + 500}</div>
                    <div class="stat-label">Comments</div>
                </div>
            </div>
            <div style="font-size: 12px; color: var(--text-gray); margin-top: 20px;">
                <p>Last updated: Just now</p>
                <p>Next update in: 30 seconds</p>
            </div>
        </div>`,
        true // Auto-refresh
    );

    // Auto-refresh stats every 5 seconds
    if (typeof window.statsInterval !== 'undefined') {
        clearInterval(window.statsInterval);
    }

    window.statsInterval = setInterval(() => {
        console.log('Refreshing live stats...');
        // In a real app, this would fetch new data
    }, 5000);
}

function editCampaign(id) {
    // Redirect to the campaign planning module to edit
    window.location.href = '../Modules/Module-1.php?campaignId=' + id + '&action=edit';
    console.log('Editing campaign: ' + id);
}

function viewReport(id) {
    // Redirect to the campaign analytics reports
    window.location.href = '../Modules/CampaignAnalyticsReports.php?campaignId=' + id + '&view=report';
    console.log('Viewing report for campaign: ' + id);
}

function viewAllCampaigns() {
    // Redirect to the campaign analytics reports module
    window.location.href = '../Modules/CampaignAnalyticsReports.php';
    console.log('Viewing all campaigns');
}

function openAnalytics(id) {
    // Redirect to the analytics module for the specific campaign
    window.location.href = '../Modules/CampaignAnalyticsReports.php?campaignId=' + id;
    console.log('Opening analytics for campaign: ' + id);
}

function viewCampaignDetails(id) {
    // Redirect to the campaign analytics for detailed view
    window.location.href = '../Modules/CampaignAnalyticsReports.php?campaignId=' + id;
    console.log('Viewing details for campaign: ' + id);
}

// Modal Functions
function showModal(title, content, autoRefresh = false) {
    // Remove existing modal if present
    const existingModal = document.getElementById('customModal');
    if (existingModal) {
        existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'customModal';
    modal.className = 'custom-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2>${title}</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    // Store auto-refresh flag
    modal.dataset.autoRefresh = autoRefresh;
}

function closeModal() {
    const modal = document.getElementById('customModal');
    if (modal) {
        // Clear auto-refresh interval
        if (modal.dataset.autoRefresh === 'true' && window.statsInterval) {
            clearInterval(window.statsInterval);
        }
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Enhanced Notification Function
function showNotification(message, type = 'info', duration = 3000) {
    // Remove any existing notifications of the same type to prevent stacking
    document.querySelectorAll(`.custom-notification.${type}`).forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `custom-notification ${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    // Add to notification container or body if no container exists
    const notificationContainer = document.getElementById('notification-container') || document.body;
    notificationContainer.appendChild(notification);
    
    // Trigger reflow to enable animation
    void notification.offsetWidth;
    
    // Add show class for animation
    notification.classList.add('show');

    // Auto-remove after specified duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.add('fade-out');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }
    }, duration);
    
    // Add click to dismiss
    notification.addEventListener('click', function(e) {
        if (e.target !== this.querySelector('.notification-close')) {
            this.classList.add('fade-out');
            setTimeout(() => {
                if (this.parentNode) {
                    this.remove();
                }
            }, 300);
        }
    });
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Export Day Report
function exportDayReport(week, day) {
    alert(`Exporting report for Week ${week}, Day ${day}...\n\nReport would include:
    • Incident breakdown
    • Response times
    • Team assignments
    • Resolution rates
    • Recommendations`);
}

// Add custom styles for new components
function addCustomStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .custom-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            background: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            background: var(--secondary-black);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 10001;
            border: 1px solid var(--medium-gray);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--dark-gray);
        }

        .modal-header h2 {
            font-size: 18px;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
        }

        .modal-close:hover {
            background: var(--dark-gray);
            color: var(--white);
        }

        .modal-body {
            padding: 20px;
        }

        .btn-primary, .btn-secondary {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: #3a7bc8;
        }

        .btn-secondary {
            background: var(--dark-gray);
            color: var(--white);
        }

        .btn-secondary:hover {
            background: var(--medium-gray);
        }
        
        /* Enhanced notification styles */
        .custom-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--secondary-black);
            border: 1px solid var(--medium-gray);
            border-left: 4px solid var(--accent);
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s ease;
        }

        .custom-notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .custom-notification.fade-out {
            opacity: 0;
            transform: translateX(100%);
        }

        .custom-notification.success {
            border-left-color: var(--success);
        }

        .custom-notification.error {
            border-left-color: var(--danger);
        }

        .custom-notification.warning {
            border-left-color: var(--warning);
        }

        .custom-notification.info {
            border-left-color: var(--accent);
        }

        .custom-notification i {
            font-size: 16px;
        }

        .custom-notification.success i {
            color: var(--success);
        }

        .custom-notification.error i {
            color: var(--danger);
        }

        .custom-notification.warning i {
            color: var(--warning);
        }
        
        .custom-notification.info i {
            color: var(--accent);
        }

        .notification-close {
            background: none;
            border: none;
            color: var(--text-gray);
            font-size: 18px;
            cursor: pointer;
            padding: 0;
        }
    `;
    document.head.appendChild(style);
}

// Initialize everything
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Check if utils are loaded
        if (!window.Utils) {
            console.error('Utils not loaded. Please include utils.js before home.js');
            // Still render the basic page even if JS fails
        } else {
            // Initialize session management
            if (Utils.SessionManager && typeof Utils.SessionManager.init === 'function') {
                Utils.SessionManager.init();
            }
        }

        // Original initialization
        initializeNavigation();
        initializeWidgets();
        initializeSearch();
        initializeNotifications();
        initializeExport();
        initializeQuickActions();
        initializeActivityFeed();
        initializeRealTimeUpdates();

        // NEW: Initialize interactive charts
        initializeInteractiveCharts();
        
        // Initialize notification system
        initializeNotificationSystem();
        
        // Initialize user data
        initializeUserData();
        
        // Add CSS for modals and notifications
        addCustomStyles();
        
        // Check chart loading after a delay
        setTimeout(checkChartLoading, 1000);
        
        // Ensure heat map is generated
        setTimeout(generateHeatMap, 500);
    } catch (error) {
        console.error('Error initializing dashboard:', error);
        // Page will still render even if JS initialization fails
    }
});

// Export modal functions
function openExportModal() {
    const exportModal = document.getElementById('exportModal');
    if (exportModal) {
        exportModal.classList.add('active');
    }
}

function closeExportModal() {
    const exportModal = document.getElementById('exportModal');
    if (exportModal) {
        exportModal.classList.remove('active');
    }
}

function exportReport() {
    alert('Report exported successfully!');
    closeExportModal();
}

// Additional helper functions
function markAllAsRead() {
    const notifications = document.querySelectorAll('.notification-item.unread');
    notifications.forEach(notification => {
        notification.classList.remove('unread');
    });
    showNotification('All notifications marked as read', 'success');
}