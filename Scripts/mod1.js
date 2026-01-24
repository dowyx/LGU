// Campaign Planning JavaScript with enhanced API integration
let campaignsData = [];
let calendarEvents = [];
let currentCalendarDate = new Date();
const API_BASE_URL = ''; // Define your API base URL - using local storage instead of API
let moduleApiHandler = null;

// Mock data generators
function generateMockCampaignsData() {
    return [
        {
            id: 1,
            name: "Summer Safety Awareness",
            description: "Promote summer safety practices including water safety, heat protection, and outdoor activities",
            startDate: "2026-01-01",
            endDate: "2026-01-31",
            status: "active",
            type: "safety",
            budget: 150000,
            targetAudience: "General Public"
        },
        {
            id: 2,
            name: "Winter Health Campaign",
            description: "Winter health awareness and flu vaccination drive",
            startDate: "2026-01-15",
            endDate: "2026-02-15",
            status: "active",
            type: "health",
            budget: 200000,
            targetAudience: "Senior Citizens"
        },
        {
            id: 3,
            name: "Emergency Preparedness",
            description: "Community emergency response training",
            startDate: "2026-02-01",
            endDate: "2026-02-28",
            status: "planned",
            type: "emergency",
            budget: 100000,
            targetAudience: "Local Residents"
        }
    ];
}

function generateMockCalendarEvents() {
    return [
        {
            id: 1,
            title: "Safety Workshop",
            date: "2026-01-10",
            description: "Community safety workshop"
        },
        {
            id: 2,
            title: "Health Fair",
            date: "2026-01-15",
            description: "Annual community health fair"
        },
        {
            id: 3,
            title: "Emergency Drill",
            date: "2026-01-20",
            description: "Community emergency response drill"
        }
    ];
}

// Initialize the page with proper error handling
document.addEventListener('DOMContentLoaded', async function () {
    // Check if utils are loaded
    if (!window.Utils) {
        console.warn('Utils not loaded. Using standalone mode.');
        // Initialize without Utils if not available
        await initializeStandalone();
        return;
    }

    // Initialize session management
    Utils.SessionManager.init();
    
    // Initialize shared components
    const navigationManager = new Utils.NavigationManager();
    const apiHandler = new Utils.APIHandler();
    
    // Initialize module-specific components
    await initializeModule(apiHandler);
    
    // Initialize the Campaign Manager
    initializeCampaignManager();
});

async function initializeStandalone() {
    try {
        // Setup UI components without Utils
        setupEventListeners(null);
        
        // Use mock data
        campaignsData = generateMockCampaignsData();
        calendarEvents = generateMockCalendarEvents();
        
        renderCampaignsTable();
        renderCalendar();
        updateCampaignStats();
        
        // Initialize the Campaign Manager
        initializeCampaignManager();
        
        console.log('Initialized in standalone mode');
    } catch (error) {
        console.error('Failed to initialize in standalone mode:', error);
    }
}

async function initializeModule(apiHandler) {
    try {
        // Show loading state
        if (window.Utils) {
            Utils.UIHelper.showLoading('.campaigns-table', 'Loading campaigns...');
            // Skip calendar loading since we're rendering it directly
        }

        moduleApiHandler = apiHandler || null;
        
        // Load data - use local storage instead of API since API endpoints don't exist
        // Load from local storage if available, otherwise use mock data
        campaignsData = JSON.parse(localStorage.getItem('campaigns') || '[]');
        if (campaignsData.length === 0) {
            campaignsData = generateMockCampaignsData();
        }
        
        calendarEvents = JSON.parse(localStorage.getItem('calendarEvents') || '[]');
        if (calendarEvents.length === 0) {
            calendarEvents = generateMockCalendarEvents();
        }
        
        // Setup UI components
        setupEventListeners(apiHandler);
        renderCampaignsTable();
        renderCalendar();
        updateCampaignStats();
        
        // Hide loading states
        if (window.Utils) {
            Utils.UIHelper.hideLoading('.campaigns-table');
        }
    } catch (error) {
        console.error('Failed to initialize module:', error);
        if (window.Utils) {
            Utils.UIHelper.showToast('Failed to load campaign data. Please refresh the page.', 'error');
        }
    }
}

// Load campaigns data with enhanced error handling
async function loadCampaignsData(apiHandler) {
    try {
        const handler = apiHandler || moduleApiHandler;
        if (!handler) {
            campaignsData = generateMockCampaignsData();
            return;
        }
        const campaigns = await handler.get('/campaigns');
        campaignsData = campaigns || [];
        console.log('Campaigns loaded successfully:', campaignsData.length);
    } catch (error) {
        console.error('Error loading campaigns:', error);

        campaignsData = generateMockCampaignsData();
        if (window.Utils) {
            Utils.UIHelper.showToast('Using demo data - API unavailable', 'warning');
        }
    }
}

// Load calendar events with enhanced error handling
async function loadCalendarEvents(apiHandler) {
    try {
        const handler = apiHandler || moduleApiHandler;
        if (!handler) {
            calendarEvents = generateMockCalendarEvents();
            return;
        }
        const events = await handler.get('/calendar/events');
        calendarEvents = events || [];
        console.log('Calendar events loaded successfully:', calendarEvents.length);
    } catch (error) {
        console.error('Error loading calendar events:', error);

        calendarEvents = generateMockCalendarEvents();
        if (window.Utils) {
            Utils.UIHelper.showToast('Using demo calendar data - API unavailable', 'warning');
        }
    }
}

// Setup event listeners
function setupEventListeners(apiHandler) {
    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function (e) {
            if (e.key === 'Enter') {
                searchCampaigns(this.value);
            }
        });
    }

    // View Calendar button
    const viewCalendarBtn = document.querySelector('.calendar-view .btn');
    if (viewCalendarBtn) {
        viewCalendarBtn.addEventListener('click', openCalendarModal);
    }

    // Template buttons
    const templateButtons = document.querySelectorAll('.module-card:nth-child(6) .btn');
    if (templateButtons && templateButtons.length > 0) {
        templateButtons.forEach(button => {
            button.addEventListener('click', function () {
                const templateType = this.textContent.trim();
                createCampaignFromTemplate(templateType);
            });
        });
    }

    // Quick action buttons
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.querySelector('span')?.textContent;
            switch (action) {
                case 'New Campaign':
                    createNewCampaign();
                    break;
                case 'View Calendar':
                    openCalendarModal();
                    break;
                case 'Templates':
                    openTemplatesModal();
                    break;
                case 'Analytics':
                    openAnalyticsModal();
                    break;
            }
        });
    });
}

// Enhanced functions
function createNewCampaign() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Campaign</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="campaignForm">
                    <div class="form-group">
                        <label for="campaignName">Campaign Name</label>
                        <input type="text" id="campaignName" required>
                    </div>
                    <div class="form-group">
                        <label for="campaignType">Campaign Type</label>
                        <select id="campaignType">
                            <option value="awareness">Awareness</option>
                            <option value="education">Education</option>
                            <option value="emergency">Emergency</option>
                            <option value="health">Health</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate" required>
                    </div>
                    <div class="form-group">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate" required>
                    </div>
                    <div class="form-group">
                        <label for="targetAudience">Target Audience</label>
                        <input type="text" id="targetAudience" placeholder="e.g., General Public, Youth, Seniors">
                    </div>
                    <div class="form-group">
                        <label for="campaignDescription">Description</label>
                        <textarea id="campaignDescription" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="campaignBudget">Budget</label>
                        <input type="number" id="campaignBudget" placeholder="Enter amount">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn">Create Campaign</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const form = modal.querySelector('#campaignForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveCampaign();
        modal.remove();
    });

    setupModalClose(modal);
}

function openCalendarModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Campaign Calendar</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="calendar-container">
                    <div class="calendar-header">
                        <button class="btn btn-secondary" onclick="window.campaignCalendar?.previousMonth()">Previous</button>
                        <h3 id="currentMonth">January 2024</h3>
                        <button class="btn btn-secondary" onclick="window.campaignCalendar?.nextMonth()">Next</button>
                    </div>
                    <div class="calendar-grid">
                        <div class="calendar-weekdays">
                            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
                            <div>Thu</div><div>Fri</div><div>Sat</div>
                        </div>
                        <div class="calendar-days" id="calendarDays">
                            <!-- Calendar days will be generated here -->
                        </div>
                    </div>
                </div>
                <div class="calendar-events">
                    <h4>Upcoming Events</h4>
                    <div id="eventsList">
                        <!-- Events will be listed here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    generateCalendar();
    renderEventsList();
    setupModalClose(modal);
}

function openTemplatesModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Campaign Templates</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="templates-grid">
                    <div class="template-card" onclick="createCampaignFromTemplate('Emergency Alert')">
                        <div class="template-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4>Emergency Alert</h4>
                        <p>Quick emergency response campaigns</p>
                    </div>
                    <div class="template-card" onclick="createCampaignFromTemplate('Health Awareness')">
                        <div class="template-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h4>Health Awareness</h4>
                        <p>Public health education campaigns</p>
                    </div>
                    <div class="template-card" onclick="createCampaignFromTemplate('Community Outreach')">
                        <div class="template-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Community Outreach</h4>
                        <p>Community engagement programs</p>
                    </div>
                    <div class="template-card" onclick="createCampaignFromTemplate('Safety Campaign')">
                        <div class="template-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Safety Campaign</h4>
                        <p>Safety awareness and prevention</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    setupModalClose(modal);
}

function openAnalyticsModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Campaign Analytics</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="analytics-dashboard">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h4>Total Campaigns</h4>
                            <div class="stat-value">${getCampaignsSource().length}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Active Campaigns</h4>
                            <div class="stat-value">${getCampaignsSource().filter(c => c.status === 'active').length}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Completed</h4>
                            <div class="stat-value">${getCampaignsSource().filter(c => c.status === 'completed').length}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Total Budget</h4>
                            <div class="stat-value">₱${getCampaignsSource().reduce((sum, c) => sum + (parseFloat(c.budget) || 0), 0).toLocaleString()}</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    setupModalClose(modal);
}

// CRUD Operations
async function saveCampaign() {
    // Get form values
    const name = document.querySelector('#campaignName').value;
    const type = document.querySelector('#campaignType').value;
    const startDate = document.querySelector('#startDate').value;
    const endDate = document.querySelector('#endDate').value;
    const targetAudience = document.querySelector('#targetAudience').value;
    const description = document.querySelector('#campaignDescription').value;
    const budget = document.querySelector('#campaignBudget').value;
    
    // Validate input data
    if (!validateCampaignData(name, type, startDate, endDate, description)) {
        showNotification('Invalid campaign data provided', 'error');
        return;
    }
    
    const campaignData = {
        name: sanitizeInput(name),
        type: sanitizeInput(type),
        startDate: sanitizeInput(startDate),
        endDate: sanitizeInput(endDate),
        targetAudience: sanitizeInput(targetAudience),
        description: sanitizeInput(description),
        budget: parseFloat(budget) || 0,
        status: 'draft'
    };

    // For standalone mode, just save to local storage
    if (window.campaignManager) {
        window.campaignManager.createCampaign(campaignData);
    } else {
        showNotification('Campaign manager not available', 'error');
    }
}

// Validate campaign data
function validateCampaignData(name, type, startDate, endDate, description) {
    if (!name || name.trim().length === 0) {
        showNotification('Campaign name is required', 'error');
        return false;
    }
    
    if (name.length > 200) {
        showNotification('Campaign name is too long', 'error');
        return false;
    }
    
    if (!type) {
        showNotification('Campaign type is required', 'error');
        return false;
    }
    
    if (!startDate || !endDate) {
        showNotification('Both start and end dates are required', 'error');
        return false;
    }
    
    // Validate dates
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (isNaN(start.getTime()) || isNaN(end.getTime())) {
        showNotification('Invalid date format', 'error');
        return false;
    }
    
    if (start > end) {
        showNotification('End date must be after start date', 'error');
        return false;
    }
    
    if (description.length > 1000) {
        showNotification('Description is too long', 'error');
        return false;
    }
    
    // Check for potentially dangerous content
    const dangerousPatterns = [/<script/i, /javascript:/i, /vbscript:/i, /on\w+=/i];
    for (const pattern of dangerousPatterns) {
        if (pattern.test(name) || pattern.test(description)) {
            showNotification('Invalid content detected', 'error');
            return false;
        }
    }
    
    return true;
}

function createCampaignFromTemplate(templateType) {
    const templates = {
        'Emergency Alert': {
            type: 'emergency',
            description: 'Emergency response campaign for immediate public notification',
            targetAudience: 'General Public',
            budget: '5000'
        },
        'Health Awareness': {
            type: 'health',
            description: 'Public health education and awareness campaign',
            targetAudience: 'General Public',
            budget: '10000'
        },
        'Community Outreach': {
            type: 'awareness',
            description: 'Community engagement and outreach program',
            targetAudience: 'Local Community',
            budget: '7500'
        },
        'Safety Campaign': {
            type: 'safety',
            description: 'Safety awareness and prevention campaign',
            targetAudience: 'General Public',
            budget: '8000'
        }
    };

    const template = templates[templateType];
    if (template) {
        createNewCampaign();

        // Pre-fill form with template data
        setTimeout(() => {
            const typeSelect = document.querySelector('#campaignType');
            const descField = document.querySelector('#campaignDescription');
            const audienceField = document.querySelector('#targetAudience');
            const budgetField = document.querySelector('#campaignBudget');
            
            if (typeSelect) typeSelect.value = template.type;
            if (descField) descField.value = template.description;
            if (audienceField) audienceField.value = template.targetAudience;
            if (budgetField) budgetField.value = template.budget;
        }, 100);
    }
}

// Rendering functions
function renderCampaignsTable(filteredData = getCampaignsSource()) {
    const tableBody = document.querySelector('.campaigns-table tbody');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    filteredData.forEach(campaign => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="campaign-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">${escapeHtml(campaign.name)}</div>
                        <div style="font-size: 12px; color: #666;">${campaign.type || 'N/A'}</div>
                    </div>
                </div>
            </td>
            <td>${campaign.startDate}</td>
            <td>${campaign.endDate}</td>
            <td><span class="status-badge status-${campaign.status}">${capitalizeFirst(campaign.status)}</span></td>
            <td>${campaign.targetAudience}</td>
            <td>
                <div class="action-icons">
                    <i class="fas fa-eye" title="View" onclick="viewCampaign(${campaign.id})"></i>
                    <i class="fas fa-edit" title="Edit" onclick="editCampaign(${campaign.id})"></i>
                    <i class="fas fa-trash" title="Delete" onclick="deleteCampaign(${campaign.id})"></i>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function renderCalendar() {
    // Update the existing calendar in Module-1.html instead of replacing it
    const calendarDays = document.querySelector('.calendar-days');
    if (!calendarDays) return;

    const today = new Date();
    
    // Update the calendar days
    calendarDays.innerHTML = generateCalendarDays(today);
    
    // Update the month/year display
    const monthDisplay = document.querySelector('.calendar-header h3');
    if (monthDisplay) {
        monthDisplay.textContent = today.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }
    
    // Add event listeners to the navigation buttons
    const prevButton = document.querySelector('.prev-month');
    const nextButton = document.querySelector('.next-month');
    
    if (prevButton) {
        prevButton.onclick = function() {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
            renderCalendar();
            if (window.campaignManager) {   
                window.campaignManager.renderCalendar();
            }
        };
    }
    
    if (nextButton) {
        nextButton.onclick = function() {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
            renderCalendar();
            if (window.campaignManager) {
                window.campaignManager.renderCalendar();
            }
        };
    }
}

function updateCampaignStats() {
    const campaigns = getCampaignsSource();
    const totalCampaigns = campaigns.length;
    const activeCampaigns = campaigns.filter(c => c.status === 'active').length;
    const completedCampaigns = campaigns.filter(c => c.status === 'completed').length;
    const totalBudget = campaigns.reduce((sum, c) => sum + (parseFloat(c.budget) || 0), 0);

    // Update stats in dashboard
    const statValues = document.querySelectorAll('.stat-value');
    if (statValues.length >= 4) {
        statValues[0].textContent = totalCampaigns;
        statValues[1].textContent = activeCampaigns;
        statValues[2].textContent = completedCampaigns;
        statValues[3].textContent = `₱${totalBudget.toLocaleString()}`;
    }
}

// Search and filter functions
async function searchCampaigns(query) {
    if (!query.trim()) {
        renderCampaignsTable();
        return;
    }
    
    // Sanitize the query
    const sanitizedQuery = sanitizeInput(query);
    
    // Validate the query
    if (!validateSearchQuery(sanitizedQuery)) {
        showNotification('Invalid search query', 'error');
        return;
    }

    // Use client-side search
    const filtered = getCampaignsSource().filter(c => 
        c.name.toLowerCase().includes(sanitizedQuery.toLowerCase())
    );
    renderCampaignsTable(filtered);
}

// Validate search query
function validateSearchQuery(query) {
    if (typeof query !== 'string') {
        return false;
    }
    
    // Check for potentially dangerous patterns
    const dangerousPatterns = [/<script/i, /javascript:/i, /vbscript:/i, /on\w+=/i];
    
    for (const pattern of dangerousPatterns) {
        if (pattern.test(query)) {
            return false;
        }
    }
    
    return true;
}

// Helper functions
function getCampaignsSource() {
    return window.campaignManager?.campaigns || campaignsData;
}

function sanitizeInput(value) {
    if (window.Utils?.UIHelper?.sanitizeHTML) {
        return Utils.UIHelper.sanitizeHTML(value);
    }
    return escapeHtml(value);
}

function generateCalendarDays(date) {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let daysHTML = '';

    // Empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        daysHTML += '<div class="calendar-day empty"></div>';
    }

    // Days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);
        const isToday = currentDate.toDateString() === new Date().toDateString();
        const isPast = currentDate < new Date() && !isToday;
        const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() === 6;

        // Check for events on this date
        const dayEvents = calendarEvents.filter(event => {
            const eventDate = new Date(event.date);
            return eventDate.toDateString() === currentDate.toDateString();
        });

        const hasEvent = dayEvents.length > 0;

        // Build CSS classes for the day
        let dayClasses = ['calendar-day'];
        if (isToday) dayClasses.push('today');
        if (isPast) dayClasses.push('past');
        if (isWeekend) dayClasses.push('weekend');
        if (hasEvent) dayClasses.push('has-event');

        // Create day element
        let dayContent = `<span class="day-number">${day}</span>`;

        if (hasEvent) {
            dayContent += `<div class="event-indicators">${dayEvents.length}</div>`;
        }

        daysHTML += `<div class="${dayClasses.join(' ')}" data-date="${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}">${dayContent}</div>`;
    }

    return daysHTML;
}

// This function is not needed for Module-1.html since it uses a different calendar structure
function generateCalendar() {
    // Not applicable for this module - uses renderCalendar() instead
}

// This function is not needed for Module-1.html since it doesn't have an #eventsList element
function renderEventsList() {
    // Not applicable for this module
}

function viewCampaign(id) {
    const campaign = getCampaignsSource().find(c => c.id === id);
    if (!campaign) return;

    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Campaign Details</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="campaign-details">
                    <h4>${escapeHtml(campaign.name)}</h4>
                    <p><strong>Type:</strong> ${campaign.type || 'N/A'}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${campaign.status}">${capitalizeFirst(campaign.status)}</span></p>
                    <p><strong>Start Date:</strong> ${campaign.startDate}</p>
                    <p><strong>End Date:</strong> ${campaign.endDate}</p>
                    <p><strong>Target Audience:</strong> ${campaign.targetAudience}</p>
                    <p><strong>Budget:</strong> ₱${parseFloat(campaign.budget || 0).toLocaleString()}</p>
                    <p><strong>Description:</strong></p>
                    <p>${escapeHtml(campaign.description || 'No description')}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    setupModalClose(modal);
}

function editCampaign(id) {
    console.log('Edit campaign:', id);
    // Use the CampaignManager if available
    if (window.campaignManager) {
        window.campaignManager.openModal('edit', id);
    }
}

async function deleteCampaign(id) {
    // Validate the ID
    if (!validateId(id)) {
        showNotification('Invalid campaign ID', 'error');
        return;
    }
    
    if (!confirm('Are you sure you want to delete this campaign?')) {
        return;
    }

    // Use the CampaignManager if available
    if (window.campaignManager) {
        window.campaignManager.deleteCampaign(id);
        return;
    }

    // Fallback to direct removal if needed
    try {
        // Get existing campaigns
        let campaigns = [];
        const savedCampaigns = localStorage.getItem('campaigns');
        if (savedCampaigns) {
            campaigns = JSON.parse(savedCampaigns);
        }
        
        // Remove the campaign
        const updatedCampaigns = campaigns.filter(campaign => campaign.id !== id);
        localStorage.setItem('campaigns', JSON.stringify(updatedCampaigns));
        
        showNotification('Campaign deleted successfully!', 'success');
        await loadCampaignsData(moduleApiHandler);
        renderCampaignsTable();
        updateCampaignStats();
    } catch (error) {
        console.error('Delete campaign error:', error);
        showNotification('Error deleting campaign', 'error');
    }
}

// Validate ID
function validateId(id) {
    // Check if ID is a positive integer
    if (typeof id !== 'number' && typeof id !== 'string') {
        return false;
    }
    
    const numId = Number(id);
    return Number.isInteger(numId) && numId > 0;
}

// Utility functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalizeFirst(string) {
    if (!string) return '';
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function setupModalClose(modal) {
    modal.querySelectorAll('.close-modal').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => modal.remove());
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

function showNotification(message, type = 'info') {
    // Remove existing notification
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) existingNotification.remove();

    const iconMap = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${iconMap[type] || 'fa-info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">&times;</button>
    `;

    document.body.appendChild(notification);

    setTimeout(() => notification.classList.add('show'), 10);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 5000);

    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    });
}

// Campaign Manager Class
class CampaignManager {
    constructor() {
        this.campaigns = this.loadCampaigns();
        this.editingCampaignId = null;
        this.init();
    }

    init() {
        console.log('CampaignManager initialized with', this.campaigns.length, 'campaigns');

        this.setDefaultDates();
        this.renderCalendar();
        this.renderCampaigns();
        this.renderStatistics();
        this.renderMilestones();
        this.renderResources();
        this.setupEventListeners();
        this.setupModalEvents();

        setTimeout(() => {
            if (this.campaigns.length === 0) {
                this.showNotification('Welcome! Click "New Campaign" to create your first campaign.', 'info');
            }
        }, 1000);
    }

    setDefaultDates() {
        const today = new Date();
        const nextWeek = new Date(today);
        nextWeek.setDate(today.getDate() + 7);

        const formatDate = (date) => date.toISOString().split('T')[0];

        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');

        if (startDateInput) startDateInput.min = formatDate(today);
        if (endDateInput) endDateInput.min = formatDate(today);
    }

    loadCampaigns() {
        try {
            const savedCampaigns = localStorage.getItem('campaigns');
            return savedCampaigns ? JSON.parse(savedCampaigns) : this.getDefaultCampaigns();
        } catch (e) {
            console.error('Error loading campaigns:', e);
            return this.getDefaultCampaigns();
        }
    }

    getDefaultCampaigns() {
        return [
            {
                id: 1,
                name: "Summer Safety Awareness",
                description: "Promote summer safety practices including water safety, heat protection, and outdoor activities",
                startDate: "2026-01-01",
                endDate: "2026-01-31",
                status: "active",
                type: "safety",
                budget: 150000,
                budgetAllocated: 120000,
                budgetUsed: 98000,
                personnel: 15,
                equipment: 8,
                milestones: [
                    { id: 1, name: "Phase 1 Launch", date: "2026-01-10", completed: true },
                    { id: 2, name: "Phase 2 Launch", date: "2026-01-20", completed: false }
                ]
            },
            {
                id: 2,
                name: "Winter Health Campaign",
                description: "Winter health awareness and flu vaccination drive",
                startDate: "2026-01-15",
                endDate: "2026-02-15",
                status: "active",
                type: "health",
                budget: 200000,
                budgetAllocated: 180000,
                budgetUsed: 150000,
                personnel: 20,
                equipment: 10,
                milestones: [
                    { id: 1, name: "Vaccination Drive Start", date: "2026-01-16", completed: false },
                    { id: 2, name: "Mid-campaign Review", date: "2026-01-30", completed: false }
                ]
            }
        ];
    }

    saveCampaigns() {
        try {
            localStorage.setItem('campaigns', JSON.stringify(this.campaigns));
            console.log('Campaigns saved:', this.campaigns.length);
        } catch (e) {
            console.error('Error saving campaigns:', e);
            this.showNotification('Error saving campaigns to storage', 'error');
        }
    }

    generateId() {
        return this.campaigns.length > 0 ? Math.max(...this.campaigns.map(c => c.id)) + 1 : 1;
    }

    createCampaign(campaignData) {
        const newCampaign = {
            id: this.generateId(),
            name: campaignData.name,
            description: campaignData.description,
            startDate: campaignData.startDate,
            endDate: campaignData.endDate,
            status: campaignData.status || 'draft',
            type: campaignData.type,
            budget: parseFloat(campaignData.budget) || 0,
            budgetAllocated: parseFloat(campaignData.budgetAllocated) || 0,
            budgetUsed: parseFloat(campaignData.budgetUsed) || 0,
            personnel: parseInt(campaignData.personnel) || 0,
            equipment: parseInt(campaignData.equipment) || 0,
            milestones: this.parseMilestones(campaignData.milestones)
        };

        this.campaigns.push(newCampaign);
        this.saveCampaigns();
        this.refreshAllViews();
        this.showNotification(`Campaign "${newCampaign.name}" created successfully!`, 'success');
        return newCampaign;
    }

    updateCampaign(id, campaignData) {
        const index = this.campaigns.findIndex(c => c.id === id);
        if (index === -1) {
            this.showNotification('Campaign not found!', 'error');
            return false;
        }

        const oldCampaign = this.campaigns[index];
        this.campaigns[index] = {
            ...oldCampaign,
            name: campaignData.name,
            description: campaignData.description,
            startDate: campaignData.startDate,
            endDate: campaignData.endDate,
            status: campaignData.status,
            type: campaignData.type,
            budget: parseFloat(campaignData.budget) || oldCampaign.budget,
            budgetAllocated: parseFloat(campaignData.budgetAllocated) || oldCampaign.budgetAllocated,
            personnel: parseInt(campaignData.personnel) || oldCampaign.personnel,
            equipment: parseInt(campaignData.equipment) || oldCampaign.equipment,
            milestones: this.parseMilestones(campaignData.milestones) || oldCampaign.milestones
        };

        this.saveCampaigns();
        this.refreshAllViews();
        this.showNotification(`Campaign "${campaignData.name}" updated successfully!`, 'success');
        return true;
    }

    deleteCampaign(id) {
        const index = this.campaigns.findIndex(c => c.id === id);
        if (index === -1) {
            this.showNotification('Campaign not found!', 'error');
            return false;
        }

        const campaignName = this.campaigns[index].name;
        this.campaigns.splice(index, 1);
        this.saveCampaigns();
        this.refreshAllViews();
        this.showNotification(`Campaign "${campaignName}" deleted successfully!`, 'success');
        return true;
    }

    getCampaign(id) {
        return this.campaigns.find(c => c.id === id);
    }

    parseMilestones(milestonesText) {
        if (!milestonesText || !milestonesText.trim()) return [];

        const milestones = [];
        const lines = milestonesText.split(',');
        let milestoneId = 1;

        lines.forEach(line => {
            const parts = line.trim().split(':');
            if (parts.length === 2) {
                milestones.push({
                    id: milestoneId++,
                    name: parts[0].trim(),
                    date: parts[1].trim(),
                    completed: false
                });
            }
        });

        return milestones;
    }

    formatMilestones(milestones) {
        if (!milestones || milestones.length === 0) return '';
        return milestones.map(m => `${m.name}:${m.date}`).join(', ');
    }

    openModal(mode = 'create', campaignId = null) {
        const modal = document.getElementById('campaignModal');
        const title = document.getElementById('modalTitle');
        const deleteBtn = document.getElementById('deleteCampaignBtn');

        if (!modal || !title) {
            console.error('Modal elements not found');
            return;
        }

        if (mode === 'edit' && campaignId) {
            const campaign = this.getCampaign(campaignId);
            if (campaign) {
                this.editingCampaignId = campaignId;
                title.textContent = 'Edit Campaign';
                if (deleteBtn) deleteBtn.style.display = 'block';
                this.populateForm(campaign);
            }
        } else {
            this.editingCampaignId = null;
            title.textContent = 'New Campaign';
            if (deleteBtn) deleteBtn.style.display = 'none';
            this.clearForm();
        }

        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
            const nameField = document.getElementById('campaignName');
            if (nameField) nameField.focus();
        }, 10);
    }

    closeModal() {
        const modal = document.getElementById('campaignModal');
        if (!modal) return;

        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            this.clearForm();
        }, 300);
    }

    confirmDelete(campaignId) {
        const modal = document.getElementById('confirmModal');
        if (!modal) return;

        modal.style.display = 'flex';
        setTimeout(() => modal.classList.add('show'), 10);
        modal.dataset.campaignId = campaignId;
    }

    closeConfirmModal() {
        const modal = document.getElementById('confirmModal');
        if (!modal) return;

        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            delete modal.dataset.campaignId;
        }, 300);
    }

    handleDeleteConfirmation() {
        const modal = document.getElementById('confirmModal');
        if (!modal) return;

        const campaignId = parseInt(modal.dataset.campaignId);

        if (campaignId) {
            this.deleteCampaign(campaignId);
        }

        this.closeConfirmModal();
        this.closeModal();
    }

    renderCalendar() {
        const displayDate = currentCalendarDate || new Date();
        const year = displayDate.getFullYear();
        const month = displayDate.getMonth();

        const januaryCampaigns = this.campaigns.filter(campaign => {
            const start = new Date(campaign.startDate);
            const end = new Date(campaign.endDate);
            const janStart = new Date(year, month, 1);
            const janEnd = new Date(year, month + 1, 0);
            return start <= janEnd && end >= janStart && campaign.status === 'active';
        });

        document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
            const dayNumber = parseInt(day.textContent);
            if (!dayNumber) return;

            const date = new Date(year, month, dayNumber);
            const hasCampaign = januaryCampaigns.some(campaign => {
                const start = new Date(campaign.startDate);
                const end = new Date(campaign.endDate);
                return date >= start && date <= end;
            });

            if (hasCampaign) {
                day.classList.add('has-campaign');
                day.title = 'Click to view campaigns on this day';
            } else {
                day.classList.remove('has-campaign');
                day.title = '';
            }
        });
    }

    renderCampaigns() {
        const campaignList = document.querySelector('.campaign-list');
        if (!campaignList) return;

        campaignList.innerHTML = '';

        const displayCampaigns = this.campaigns
            .filter(c => c.status === 'active')
            .slice(0, 4);

        if (displayCampaigns.length === 0) {
            campaignList.innerHTML = `
                <li class="campaign-item no-campaigns">
                    <div class="campaign-details">
                        <div class="campaign-name">No Active Campaigns</div>
                        <div class="campaign-date">Click "New Campaign" to start</div>
                    </div>
                </li>
            `;
            return;
        }

        displayCampaigns.forEach(campaign => {
            const campaignItem = document.createElement('li');
            campaignItem.className = 'campaign-item';
            campaignItem.dataset.id = campaign.id;
            campaignItem.innerHTML = `
                <div class="campaign-status status-${campaign.status}"></div>
                <div class="campaign-details">
                    <div class="campaign-name">${escapeHtml(campaign.name)}</div>
                    <div class="campaign-date">
                        ${this.formatDate(campaign.startDate)} - ${this.formatDate(campaign.endDate)}
                    </div>
                    <div class="campaign-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${this.getCampaignProgress(campaign)}%"></div>
                        </div>
                    </div>
                </div>
                <div class="campaign-actions">
                    <button class="action-btn edit-btn" title="Edit" data-id="${campaign.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete-btn" title="Delete" data-id="${campaign.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            campaignList.appendChild(campaignItem);
        });

        campaignList.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const campaignId = parseInt(btn.dataset.id);
                this.openModal('edit', campaignId);
            });
        });

        campaignList.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const campaignId = parseInt(btn.dataset.id);
                this.confirmDelete(campaignId);
            });
        });

        campaignList.querySelectorAll('.campaign-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (!e.target.closest('.campaign-actions')) {
                    const campaignId = parseInt(item.dataset.id);
                    this.viewCampaignDetails(campaignId);
                }
            });
        });
    }

    renderStatistics() {
        const totalCampaigns = this.campaigns.length;
        const activeCampaigns = this.campaigns.filter(c => c.status === 'active').length;
        const totalBudget = this.campaigns.reduce((sum, c) => sum + (c.budget || 0), 0);
        const usedBudget = this.campaigns.reduce((sum, c) => sum + (c.budgetUsed || 0), 0);
        const totalPersonnel = this.campaigns.reduce((sum, c) => sum + (c.personnel || 0), 0);

        const today = new Date();
        const onSchedule = this.campaigns.filter(campaign => {
            const endDate = new Date(campaign.endDate);
            return endDate >= today || campaign.status === 'completed';
        }).length;

        const onSchedulePercent = totalCampaigns > 0 ?
            Math.round((onSchedule / totalCampaigns) * 100) : 100;

        const updateElement = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        updateElement('totalCampaigns', totalCampaigns);
        updateElement('onSchedulePercent', `${onSchedulePercent}%`);
        updateElement('budgetUtilized', `₱${(usedBudget / 1000).toFixed(1)}K`);
        updateElement('teamsInvolved', Math.ceil(totalPersonnel / 5));
    }

    renderMilestones() {
        const timeline = document.getElementById('milestonesTimeline');
        if (!timeline) return;

        let allMilestones = [];
        const today = new Date();
        const nextMonth = new Date();
        nextMonth.setDate(today.getDate() + 30);

        this.campaigns.forEach(campaign => {
            (campaign.milestones || []).forEach(milestone => {
                const milestoneDate = new Date(milestone.date);
                if (!milestone.completed && milestoneDate >= today && milestoneDate <= nextMonth) {
                    allMilestones.push({
                        ...milestone,
                        campaignName: campaign.name,
                        campaignType: campaign.type
                    });
                }
            });
        });

        allMilestones.sort((a, b) => new Date(a.date) - new Date(b.date));
        const upcomingMilestones = allMilestones.slice(0, 3);

        timeline.innerHTML = '';

        if (upcomingMilestones.length === 0) {
            timeline.innerHTML = `
                <div class="timeline-item">
                    <div class="timeline-content">
                        <strong>No upcoming milestones</strong>
                        <p>All milestones are completed or scheduled for later dates</p>
                    </div>
                </div>
            `;
            return;
        }

        upcomingMilestones.forEach(milestone => {
            const daysUntil = Math.ceil((new Date(milestone.date) - today) / (1000 * 60 * 60 * 24));
            const timelineItem = document.createElement('div');
            timelineItem.className = 'timeline-item';

            let iconClass = 'fas fa-flag';
            if (milestone.campaignType === 'health') iconClass = 'fas fa-heartbeat';
            if (milestone.campaignType === 'safety') iconClass = 'fas fa-shield-alt';
            if (milestone.campaignType === 'emergency') iconClass = 'fas fa-exclamation-triangle';

            timelineItem.innerHTML = `
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong>${escapeHtml(milestone.name)}</strong>
                        <span class="milestone-badge">${daysUntil === 0 ? 'Today' : `In ${daysUntil} day${daysUntil !== 1 ? 's' : ''}`}</span>
                    </div>
                    <p style="margin-top: 5px;">
                        <i class="${iconClass}"></i>
                        ${escapeHtml(milestone.campaignName)} • ${this.formatDate(milestone.date)}
                    </p>
                </div>
            `;
            timeline.appendChild(timelineItem);
        });
    }

    renderResources() {
        const resourceContainer = document.getElementById('resourceAllocation');
        if (!resourceContainer) return;

        const totalPersonnel = this.campaigns.reduce((sum, c) => sum + (c.personnel || 0), 0);
        const allocatedPersonnel = this.campaigns
            .filter(c => c.status !== 'completed')
            .reduce((sum, c) => sum + (c.personnel || 0), 0);

        const totalBudget = this.campaigns.reduce((sum, c) => sum + (c.budgetAllocated || 0), 0);
        const usedBudget = this.campaigns.reduce((sum, c) => sum + (c.budgetUsed || 0), 0);

        const totalEquipment = this.campaigns.reduce((sum, c) => sum + (c.equipment || 0), 0);
        const allocatedEquipment = this.campaigns
            .filter(c => c.status !== 'completed')
            .reduce((sum, c) => sum + (c.equipment || 0), 0);

        const personnelPercent = totalPersonnel > 0 ?
            Math.round((allocatedPersonnel / totalPersonnel) * 100) : 0;
        const budgetPercent = totalBudget > 0 ?
            Math.round((usedBudget / totalBudget) * 100) : 0;
        const equipmentPercent = totalEquipment > 0 ?
            Math.round((allocatedEquipment / totalEquipment) * 100) : 0;

        resourceContainer.innerHTML = `
            <div class="resource-item">
                <div class="resource-label">
                    <span><i class="fas fa-users"></i> Personnel</span>
                    <span>${allocatedPersonnel}/${totalPersonnel} (${personnelPercent}%)</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${personnelPercent}%;"></div>
                </div>
            </div>
            <div class="resource-item">
                <div class="resource-label">
                    <span><i class="fas fa-money-bill-wave"></i> Budget</span>
                    <span>₱${(usedBudget / 1000).toFixed(1)}K/₱${(totalBudget / 1000).toFixed(1)}K (${budgetPercent}%)</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${budgetPercent}%;"></div>
                </div>
            </div>
            <div class="resource-item">
                <div class="resource-label">
                    <span><i class="fas fa-tools"></i> Equipment</span>
                    <span>${allocatedEquipment}/${totalEquipment} (${equipmentPercent}%)</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: ${equipmentPercent}%;"></div>
                </div>
            </div>
        `;
    }

    refreshAllViews() {
        this.renderCalendar();
        this.renderCampaigns();
        this.renderStatistics();
        this.renderMilestones();
        this.renderResources();
    }

    setupEventListeners() {
        const newCampaignBtn = document.getElementById('createNewCampaignBtn');
        if (newCampaignBtn) {
            newCampaignBtn.addEventListener('click', () => {
                this.openModal('create');
            });
        }

        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchCampaigns(e.target.value);
            });
        }

        document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
            day.addEventListener('click', () => {
                const dayNumber = parseInt(day.textContent);
                if (dayNumber) {
                    this.showCampaignsForDay(dayNumber);
                }
            });
        });

        document.querySelectorAll('.template-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const template = e.target.closest('.template-btn').dataset.template;
                this.useTemplate(template);
            });
        });
    }

    clearForm() {
        const form = document.getElementById('campaignForm');
        if (form) {
            form.reset();
            
            // Clear any validation errors
            const formInputs = form.querySelectorAll('.form-input');
            formInputs.forEach(input => {
                input.style.borderColor = '';
                input.style.boxShadow = '';
            });
            
            // Clear hidden ID field
            const idField = document.getElementById('campaignId');
            if (idField) idField.value = '';
        }
    }
    
    populateForm(campaign) {
        const setFieldValue = (id, value) => {
            const field = document.getElementById(id);
            if (field) field.value = value;
        };
        
        setFieldValue('campaignId', campaign.id);
        setFieldValue('campaignName', campaign.name);
        setFieldValue('campaignDescription', campaign.description);
        setFieldValue('startDate', campaign.startDate);
        setFieldValue('endDate', campaign.endDate);
        setFieldValue('campaignType', campaign.type);
        setFieldValue('campaignStatus', campaign.status);
        setFieldValue('campaignBudget', campaign.budget);
        setFieldValue('budgetAllocated', campaign.budgetAllocated);
        setFieldValue('personnelCount', campaign.personnel);
        setFieldValue('equipmentCount', campaign.equipment);
        
        if (campaign.milestones) {
            setFieldValue('milestones', this.formatMilestones(campaign.milestones));
        }
    }
    
    handleFormSubmit(e) {
        e.preventDefault();
        
        const formData = {
            id: parseInt(document.getElementById('campaignId').value) || null,
            name: document.getElementById('campaignName').value,
            description: document.getElementById('campaignDescription').value,
            startDate: document.getElementById('startDate').value,
            endDate: document.getElementById('endDate').value,
            type: document.getElementById('campaignType').value,
            status: document.getElementById('campaignStatus').value,
            budget: document.getElementById('campaignBudget').value,
            budgetAllocated: document.getElementById('budgetAllocated').value,
            personnel: document.getElementById('personnelCount').value,
            equipment: document.getElementById('equipmentCount').value,
            milestones: document.getElementById('milestones').value
        };
        
        // Validate form data
        if (!this.validateFormData(formData)) {
            return;
        }
        
        if (this.editingCampaignId) {
            this.updateCampaign(this.editingCampaignId, formData);
        } else {
            this.createCampaign(formData);
        }
        
        this.closeModal();
    }
    
    validateFormData(data) {
        if (!data.name || data.name.trim().length === 0) {
            this.showNotification('Campaign name is required', 'error');
            return false;
        }
        
        if (!data.startDate || !data.endDate) {
            this.showNotification('Start and end dates are required', 'error');
            return false;
        }
        
        if (new Date(data.startDate) > new Date(data.endDate)) {
            this.showNotification('End date must be after start date', 'error');
            return false;
        }
        
        return true;
    }
    
    setupModalEvents() {
        const campaignForm = document.getElementById('campaignForm');
        if (campaignForm) {
            campaignForm.addEventListener('submit', (e) => {
                this.handleFormSubmit(e);
            });
        }

        const closeModalBtn = document.getElementById('closeModalBtn');
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => this.closeModal());
        }

        const cancelModalBtn = document.getElementById('cancelModalBtn');
        if (cancelModalBtn) {
            cancelModalBtn.addEventListener('click', () => this.closeModal());
        }

        const deleteCampaignBtn = document.getElementById('deleteCampaignBtn');
        if (deleteCampaignBtn) {
            deleteCampaignBtn.addEventListener('click', () => {
                if (this.editingCampaignId) {
                    this.confirmDelete(parseInt(this.editingCampaignId));
                }
            });
        }

        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                this.handleDeleteConfirmation();
            });
        }

        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        if (cancelDeleteBtn) {
            cancelDeleteBtn.addEventListener('click', () => this.closeConfirmModal());
        }

        const closeConfirmModalBtn = document.getElementById('closeConfirmModalBtn');
        if (closeConfirmModalBtn) {
            closeConfirmModalBtn.addEventListener('click', () => this.closeConfirmModal());
        }

        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    if (modal.id === 'campaignModal') this.closeModal();
                    if (modal.id === 'confirmModal') this.closeConfirmModal();
                }
            });
        });

        const formInputs = document.querySelectorAll('#campaignForm .form-input');
        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                input.style.borderColor = '';
                input.style.boxShadow = '';
            });
        });
    }

    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        } catch (e) {
            return dateString;
        }
    }

    getCampaignProgress(campaign) {
        try {
            const start = new Date(campaign.startDate);
            const end = new Date(campaign.endDate);
            const today = new Date();

            if (today < start) return 0;
            if (today > end) return 100;

            const totalDuration = end - start;
            const elapsed = today - start;
            return Math.round((elapsed / totalDuration) * 100);
        } catch (e) {
            return 0;
        }
    }

    showCampaignsForDay(day) {
        const displayDate = currentCalendarDate || new Date();
        const date = new Date(displayDate.getFullYear(), displayDate.getMonth(), day);
        const campaignsOnDay = this.campaigns.filter(campaign => {
            const start = new Date(campaign.startDate);
            const end = new Date(campaign.endDate);
            return date >= start && date <= end;
        });

        if (campaignsOnDay.length > 0) {
            let message = `Campaigns on ${this.formatDate(date.toISOString())}:\n\n`;
            campaignsOnDay.forEach(campaign => {
                message += `• ${campaign.name} (${campaign.type})\n`;
            });
            this.showNotification(message, 'info');
        } else {
            this.showNotification('No campaigns scheduled for this day', 'info');
        }
    }

    searchCampaigns(query) {
        const campaignItems = document.querySelectorAll('.campaign-item');
        const searchTerm = query.toLowerCase().trim();

        campaignItems.forEach(item => {
            const campaignName = item.querySelector('.campaign-name')?.textContent.toLowerCase() || '';
            const shouldShow = searchTerm === '' || campaignName.includes(searchTerm);
            item.style.display = shouldShow ? 'flex' : 'none';
        });
    }

    useTemplate(template) {
        const templateData = {
            vaccination: {
                name: "Vaccination Campaign",
                description: "Community vaccination drive for seasonal flu",
                type: "health",
                budget: 50000,
                budgetAllocated: 30000,
                personnel: 10,
                equipment: 5
            },
            safety: {
                name: "Safety Awareness Campaign",
                description: "Workplace safety and hazard prevention program",
                type: "safety",
                budget: 75000,
                budgetAllocated: 50000,
                personnel: 15,
                equipment: 8
            },
            emergency: {
                name: "Emergency Preparedness Campaign",
                description: "Community emergency response training",
                type: "emergency",
                budget: 100000,
                budgetAllocated: 60000,
                personnel: 20,
                equipment: 12
            }
        };

        const templateInfo = templateData[template];
        if (!templateInfo) return;

        this.openModal('create');

        const today = new Date();
        const nextMonth = new Date();
        nextMonth.setMonth(today.getMonth() + 1);

        const formatDate = (date) => date.toISOString().split('T')[0];

        setTimeout(() => {
            const setFieldValue = (id, value) => {
                const field = document.getElementById(id);
                if (field) field.value = value;
            };

            setFieldValue('campaignName', templateInfo.name);
            setFieldValue('campaignDescription', templateInfo.description);
            setFieldValue('startDate', formatDate(today));
            setFieldValue('endDate', formatDate(nextMonth));
            setFieldValue('campaignType', templateInfo.type);
            setFieldValue('campaignStatus', 'active');
            setFieldValue('campaignBudget', templateInfo.budget);
            setFieldValue('budgetAllocated', templateInfo.budgetAllocated);
            setFieldValue('personnelCount', templateInfo.personnel);
            setFieldValue('equipmentCount', templateInfo.equipment);

            this.showNotification(`Template "${templateInfo.name}" loaded. Fill in remaining details and save.`, 'info');
        }, 50);
    }

    viewCampaignDetails(campaignId) {
        const campaign = this.getCampaign(campaignId);
        if (!campaign) return;

        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.style.display = 'flex';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Campaign Details</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="campaign-detail-card">
                        <div class="campaign-detail-header">
                            <h3>${escapeHtml(campaign.name)}</h3>
                            <span class="status-badge status-${campaign.status}">${capitalizeFirst(campaign.status)}</span>
                        </div>
                        
                        <div class="detail-section">
                            <h4><i class="fas fa-info-circle"></i> Description</h4>
                            <p>${escapeHtml(campaign.description || 'No description provided')}</p>
                        </div>
                        
                        <div class="detail-grid">
                            <div class="detail-item">
                                <h4><i class="fas fa-calendar-alt"></i> Timeline</h4>
                                <p><strong>Start:</strong> ${this.formatDate(campaign.startDate)}</p>
                                <p><strong>End:</strong> ${this.formatDate(campaign.endDate)}</p>
                                <p><strong>Progress:</strong> ${this.getCampaignProgress(campaign)}%</p>
                            </div>
                            
                            <div class="detail-item">
                                <h4><i class="fas fa-money-bill-wave"></i> Budget</h4>
                                <p><strong>Total:</strong> ₱${campaign.budget.toLocaleString()}</p>
                                <p><strong>Allocated:</strong> ₱${campaign.budgetAllocated.toLocaleString()}</p>
                                <p><strong>Used:</strong> ₱${campaign.budgetUsed.toLocaleString()}</p>
                                <p><strong>Remaining:</strong> ₱${(campaign.budget - campaign.budgetUsed).toLocaleString()}</p>
                            </div>
                            
                            <div class="detail-item">
                                <h4><i class="fas fa-users"></i> Resources</h4>
                                <p><strong>Personnel:</strong> ${campaign.personnel}</p>
                                <p><strong>Equipment:</strong> ${campaign.equipment}</p>
                                <p><strong>Type:</strong> ${capitalizeFirst(campaign.type)}</p>
                            </div>
                        </div>
                        
                        ${campaign.milestones && campaign.milestones.length > 0 ? `
                        <div class="detail-section">
                            <h4><i class="fas fa-flag"></i> Milestones</h4>
                            <div class="milestones-list">
                                ${campaign.milestones.map(milestone => `
                                    <div class="milestone-item ${milestone.completed ? 'completed' : 'pending'}">
                                        <div class="milestone-checkbox">
                                            <i class="fas fa-${milestone.completed ? 'check' : 'circle'}"></i>
                                        </div>
                                        <div class="milestone-details">
                                            <strong>${escapeHtml(milestone.name)}</strong>
                                            <p>${this.formatDate(milestone.date)}</p>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="detail-actions">
                            <button class="btn btn-secondary" id="editDetailBtn">
                                <i class="fas fa-edit"></i> Edit Campaign
                            </button>
                            <button class="btn btn-secondary" onclick="window.campaignManager.closeModalDetail()">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Add event listeners
        modal.querySelector('.close-modal').addEventListener('click', () => this.closeModalDetail());
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModalDetail();
            }
        });

        const editBtn = modal.querySelector('#editDetailBtn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                this.closeModalDetail();
                this.openModal('edit', campaignId);
            });
        }

        setTimeout(() => modal.classList.add('show'), 10);
    }

    closeModalDetail() {
        const modal = document.querySelector('.modal-overlay:last-child');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">&times;</button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => notification.classList.add('show'), 10);

        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}

// Initialize the Campaign Manager
function initializeCampaignManager() {
    window.campaignManager = new CampaignManager();
    campaignsData = window.campaignManager.campaigns;
    window.campaignCalendar = {
        previousMonth: () => {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
            generateCalendar();
            window.campaignManager.renderCalendar();
        },
        nextMonth: () => {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
            generateCalendar();
            window.campaignManager.renderCalendar();
        }
    };
};

// Export for global access
window.CampaignManager = CampaignManager;
window.CampaignCalendar = window.campaignCalendar;

console.log('Campaign Planning Module loaded successfully');