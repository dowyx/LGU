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
                        <button class="btn btn-secondary" id="prevMonthBtn">Previous</button>
                        <h3 id="currentMonth">January 2024</h3>
                        <button class="btn btn-secondary" id="nextMonthBtn">Next</button>
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
    
    // Generate calendar for modal
    generateCalendarForModal();
    
    // Setup month navigation buttons
    const prevBtn = modal.querySelector('#prevMonthBtn');
    const nextBtn = modal.querySelector('#nextMonthBtn');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
            generateCalendarForModal();
            if (window.campaignManager) {   
                window.campaignManager.renderCalendar();
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
            generateCalendarForModal();
            if (window.campaignManager) {
                window.campaignManager.renderCalendar();
            }
        });
    }
    
    renderEventsListForModal(modal);
    setupModalClose(modal);
}

// Generate calendar for modal (fixed version)
function generateCalendarForModal() {
    const calendarDays = document.querySelector('#calendarDays');
    const currentMonth = document.querySelector('#currentMonth');
    
    if (!calendarDays || !currentMonth) return;
    
    const year = currentCalendarDate.getFullYear();
    const month = currentCalendarDate.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // Update month display
    currentMonth.textContent = currentCalendarDate.toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
    });
    
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
    
    calendarDays.innerHTML = daysHTML;
}

// Render events list for modal
function renderEventsListForModal(modal) {
    const eventsList = modal.querySelector('#eventsList');
    if (!eventsList) return;
    
    // Get upcoming events (next 7 days)
    const today = new Date();
    const nextWeek = new Date();
    nextWeek.setDate(today.getDate() + 7);
    
    const upcomingEvents = calendarEvents.filter(event => {
        const eventDate = new Date(event.date);
        return eventDate >= today && eventDate <= nextWeek;
    }).sort((a, b) => new Date(a.date) - new Date(b.date));
    
    if (upcomingEvents.length === 0) {
        eventsList.innerHTML = '<p>No upcoming events in the next 7 days.</p>';
        return;
    }
    
    let eventsHTML = '';
    upcomingEvents.forEach(event => {
        const eventDate = new Date(event.date);
        const daysUntil = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));
        
        eventsHTML += `
            <div class="event-item">
                <div class="event-date">
                    <div class="event-day">${eventDate.getDate()}</div>
                    <div class="event-month">${eventDate.toLocaleDateString('en-US', { month: 'short' })}</div>
                </div>
                <div class="event-details">
                    <h5>${escapeHtml(event.title)}</h5>
                    <p>${escapeHtml(event.description)}</p>
                    <span class="event-days">${daysUntil === 0 ? 'Today' : `In ${daysUntil} day${daysUntil !== 1 ? 's' : ''}`}</span>
                </div>
            </div>
        `;
    });
    
    eventsList.innerHTML = eventsHTML;
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
                    <div class="template-card" data-template="Emergency Alert">
                        <div class="template-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h4>Emergency Alert</h4>
                        <p>Quick emergency response campaigns</p>
                    </div>
                    <div class="template-card" data-template="Health Awareness">
                        <div class="template-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h4>Health Awareness</h4>
                        <p>Public health education campaigns</p>
                    </div>
                    <div class="template-card" data-template="Community Outreach">
                        <div class="template-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4>Community Outreach</h4>
                        <p>Community engagement programs</p>
                    </div>
                    <div class="template-card" data-template="Safety Campaign">
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
    
    // Add event listeners to template cards
    modal.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', function() {
            const templateType = this.dataset.template;
            createCampaignFromTemplate(templateType);
            modal.remove();
        });
    });
    
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
    const name = document.querySelector('#campaignName')?.value;
    const type = document.querySelector('#campaignType')?.value;
    const startDate = document.querySelector('#startDate')?.value;
    const endDate = document.querySelector('#endDate')?.value;
    const targetAudience = document.querySelector('#targetAudience')?.value;
    const description = document.querySelector('#campaignDescription')?.value;
    const budget = document.querySelector('#campaignBudget')?.value;
    
    if (!name || !type || !startDate || !endDate || !description) {
        showNotification('Please fill in all required fields', 'error');
        return;
    }
    
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
        // Fallback to local storage
        let campaigns = JSON.parse(localStorage.getItem('campaigns') || '[]');
        campaignData.id = campaigns.length > 0 ? Math.max(...campaigns.map(c => c.id)) + 1 : 1;
        campaigns.push(campaignData);
        localStorage.setItem('campaigns', JSON.stringify(campaigns));
        showNotification('Campaign created successfully!', 'success');
        
        // Update display
        campaignsData = campaigns;
        renderCampaignsTable();
        updateCampaignStats();
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
    } else {
        showNotification('Campaign manager not available', 'error');
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

// Campaign Manager Class (remains the same as in your original code)
// ... [CampaignManager class remains unchanged from your original code] ...

// Initialize the Campaign Manager
function initializeCampaignManager() {
    window.campaignManager = new CampaignManager();
    campaignsData = window.campaignManager.campaigns;
    window.campaignCalendar = {
        previousMonth: () => {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
            generateCalendarForModal();
            window.campaignManager.renderCalendar();
        },
        nextMonth: () => {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
            generateCalendarForModal();
            window.campaignManager.renderCalendar();
        }
    };
};

// Export for global access
window.CampaignManager = CampaignManager;
window.CampaignCalendar = window.campaignCalendar;

// Make functions available globally
window.generateCalendar = generateCalendarForModal;
window.renderEventsList = renderEventsListForModal;

console.log('Campaign Planning Module loaded successfully');