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
            budgetAllocated: 120000,
            budgetUsed: 98000,
            personnel: 15,
            equipment: 8,
            targetAudience: "General Public",
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
            targetAudience: "Senior Citizens",
            milestones: [
                { id: 1, name: "Vaccination Drive Start", date: "2026-01-16", completed: false },
                { id: 2, name: "Mid-campaign Review", date: "2026-01-30", completed: false }
            ]
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
            budgetAllocated: 80000,
            budgetUsed: 0,
            personnel: 12,
            equipment: 6,
            targetAudience: "Local Residents",
            milestones: []
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
        }

        moduleApiHandler = apiHandler || null;
        
        // Load data - use local storage instead of API since API endpoints don't exist
        campaignsData = JSON.parse(localStorage.getItem('campaigns') || '[]');
        if (campaignsData.length === 0) {
            campaignsData = generateMockCampaignsData();
            localStorage.setItem('campaigns', JSON.stringify(campaignsData));
        }
        
        calendarEvents = JSON.parse(localStorage.getItem('calendarEvents') || '[]');
        if (calendarEvents.length === 0) {
            calendarEvents = generateMockCalendarEvents();
            localStorage.setItem('calendarEvents', JSON.stringify(calendarEvents));
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

    // Add create campaign button listener if exists
    const createNewCampaignBtn = document.getElementById('createNewCampaignBtn');
    if (createNewCampaignBtn) {
        createNewCampaignBtn.addEventListener('click', createNewCampaign);
    }
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
                    <input type="hidden" id="campaignId" value="">
                    <div class="form-group">
                        <label for="campaignName">Campaign Name *</label>
                        <input type="text" id="campaignName" required>
                    </div>
                    <div class="form-group">
                        <label for="campaignType">Campaign Type *</label>
                        <select id="campaignType" required>
                            <option value="">Select Type</option>
                            <option value="awareness">Awareness</option>
                            <option value="education">Education</option>
                            <option value="emergency">Emergency</option>
                            <option value="health">Health</option>
                            <option value="safety">Safety</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="campaignStatus">Status *</label>
                        <select id="campaignStatus" required>
                            <option value="draft">Draft</option>
                            <option value="planned">Planned</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="startDate">Start Date *</label>
                        <input type="date" id="startDate" required>
                    </div>
                    <div class="form-group">
                        <label for="endDate">End Date *</label>
                        <input type="date" id="endDate" required>
                    </div>
                    <div class="form-group">
                        <label for="targetAudience">Target Audience</label>
                        <input type="text" id="targetAudience" placeholder="e.g., General Public, Youth, Seniors">
                    </div>
                    <div class="form-group">
                        <label for="campaignDescription">Description *</label>
                        <textarea id="campaignDescription" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="campaignBudget">Total Budget (₱)</label>
                        <input type="number" id="campaignBudget" min="0" placeholder="Enter amount">
                    </div>
                    <div class="form-group">
                        <label for="budgetAllocated">Budget Allocated (₱)</label>
                        <input type="number" id="budgetAllocated" min="0" placeholder="Enter allocated amount">
                    </div>
                    <div class="form-group">
                        <label for="personnelCount">Personnel Count</label>
                        <input type="number" id="personnelCount" min="0" placeholder="Enter personnel count">
                    </div>
                    <div class="form-group">
                        <label for="equipmentCount">Equipment Count</label>
                        <input type="number" id="equipmentCount" min="0" placeholder="Enter equipment count">
                    </div>
                    <div class="form-group">
                        <label for="milestones">Milestones (Format: Name:Date, Name:Date)</label>
                        <textarea id="milestones" rows="3" placeholder="e.g., Planning Phase:2026-01-10, Execution Phase:2026-01-20"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn">Save Campaign</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Set default dates
    const today = new Date();
    const nextMonth = new Date();
    nextMonth.setMonth(today.getMonth() + 1);
    
    const formatDate = (date) => date.toISOString().split('T')[0];
    
    document.getElementById('startDate').value = formatDate(today);
    document.getElementById('endDate').value = formatDate(nextMonth);

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
                        <h3 id="currentMonth">${currentCalendarDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</h3>
                        <button class="btn btn-secondary" id="nextMonthBtn">Next</button>
                    </div>
                    <div class="calendar-grid">
                        <div class="calendar-weekdays">
                            <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div>
                            <div>Thu</div><div>Fri</div><div>Sat</div>
                        </div>
                        <div class="calendar-days" id="calendarDays">
                            ${generateCalendarDays(currentCalendarDate)}
                        </div>
                    </div>
                </div>
                <div class="calendar-events">
                    <h4>Upcoming Events</h4>
                    <div id="eventsList">
                        ${calendarEvents.slice(0, 5).map(event => `
                            <div class="event-item">
                                <div class="event-date">${formatDisplayDate(event.date)}</div>
                                <div class="event-title">${escapeHtml(event.title)}</div>
                                <div class="event-description">${escapeHtml(event.description)}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    
    // Add event listeners for calendar navigation
    document.getElementById('prevMonthBtn').addEventListener('click', () => {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
        document.getElementById('currentMonth').textContent = currentCalendarDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        document.getElementById('calendarDays').innerHTML = generateCalendarDays(currentCalendarDate);
        if (window.campaignManager) {
            window.campaignManager.renderCalendar();
        }
    });
    
    document.getElementById('nextMonthBtn').addEventListener('click', () => {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
        document.getElementById('currentMonth').textContent = currentCalendarDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        document.getElementById('calendarDays').innerHTML = generateCalendarDays(currentCalendarDate);
        if (window.campaignManager) {
            window.campaignManager.renderCalendar();
        }
    });
    
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
    const campaigns = getCampaignsSource();
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
                            <div class="stat-value">${campaigns.length}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Active Campaigns</h4>
                            <div class="stat-value">${campaigns.filter(c => c.status === 'active').length}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Completed</h4>
                            <div class="stat-value">${campaigns.filter(c => c.status === 'completed').length}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Total Budget</h4>
                            <div class="stat-value">₱${campaigns.reduce((sum, c) => sum + (parseFloat(c.budget) || 0), 0).toLocaleString()}</div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <h4>Campaigns by Type</h4>
                        <div id="typeChart">
                            ${renderTypeChart(campaigns)}
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

function renderTypeChart(campaigns) {
    const typeCounts = {};
    campaigns.forEach(campaign => {
        typeCounts[campaign.type] = (typeCounts[campaign.type] || 0) + 1;
    });
    
    const colors = {
        'awareness': '#4CAF50',
        'education': '#2196F3',
        'emergency': '#F44336',
        'health': '#9C27B0',
        'safety': '#FF9800'
    };
    
    return Object.entries(typeCounts).map(([type, count]) => `
        <div class="chart-bar">
            <div class="bar-label">${capitalizeFirst(type)}</div>
            <div class="bar-container">
                <div class="bar-fill" style="width: ${(count / campaigns.length) * 100}%; background-color: ${colors[type] || '#666';}"></div>
                <div class="bar-value">${count}</div>
            </div>
        </div>
    `).join('');
}

// CRUD Operations
async function saveCampaign() {
    const idField = document.getElementById('campaignId');
    const id = idField ? parseInt(idField.value) : null;
    const isEdit = !!id;
    
    // Get form values
    const campaignData = {
        id: id,
        name: document.getElementById('campaignName').value,
        type: document.getElementById('campaignType').value,
        status: document.getElementById('campaignStatus').value,
        startDate: document.getElementById('startDate').value,
        endDate: document.getElementById('endDate').value,
        targetAudience: document.getElementById('targetAudience').value,
        description: document.getElementById('campaignDescription').value,
        budget: parseFloat(document.getElementById('campaignBudget').value) || 0,
        budgetAllocated: parseFloat(document.getElementById('budgetAllocated').value) || 0,
        personnel: parseInt(document.getElementById('personnelCount').value) || 0,
        equipment: parseInt(document.getElementById('equipmentCount').value) || 0,
        milestones: document.getElementById('milestones').value
    };

    // Validate input data
    if (!validateCampaignData(campaignData)) {
        return;
    }

    // Use CampaignManager for CRUD operations
    if (window.campaignManager) {
        if (isEdit) {
            window.campaignManager.updateCampaign(id, campaignData);
        } else {
            window.campaignManager.createCampaign(campaignData);
        }
        
        // Refresh the UI
        renderCampaignsTable();
        updateCampaignStats();
        showNotification(`Campaign ${isEdit ? 'updated' : 'created'} successfully!`, 'success');
    } else {
        // Fallback if CampaignManager is not available
        if (isEdit) {
            // Update existing campaign
            const index = campaignsData.findIndex(c => c.id === id);
            if (index !== -1) {
                campaignsData[index] = { ...campaignsData[index], ...campaignData };
            }
        } else {
            // Create new campaign
            const newId = campaignsData.length > 0 ? Math.max(...campaignsData.map(c => c.id)) + 1 : 1;
            campaignData.id = newId;
            campaignData.budgetUsed = 0;
            campaignData.milestones = parseMilestones(campaignData.milestones);
            campaignsData.push(campaignData);
        }
        
        // Save to localStorage
        localStorage.setItem('campaigns', JSON.stringify(campaignsData));
        
        showNotification(`Campaign ${isEdit ? 'updated' : 'created'} successfully!`, 'success');
        
        // Refresh the UI
        renderCampaignsTable();
        updateCampaignStats();
    }
}

function parseMilestones(milestonesText) {
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

// Validate campaign data
function validateCampaignData(data) {
    if (!data.name || data.name.trim().length === 0) {
        showNotification('Campaign name is required', 'error');
        return false;
    }
    
    if (data.name.length > 200) {
        showNotification('Campaign name is too long (max 200 characters)', 'error');
        return false;
    }
    
    if (!data.type) {
        showNotification('Campaign type is required', 'error');
        return false;
    }
    
    if (!data.status) {
        showNotification('Campaign status is required', 'error');
        return false;
    }
    
    if (!data.startDate || !data.endDate) {
        showNotification('Both start and end dates are required', 'error');
        return false;
    }
    
    // Validate dates
    const start = new Date(data.startDate);
    const end = new Date(data.endDate);
    
    if (isNaN(start.getTime()) || isNaN(end.getTime())) {
        showNotification('Invalid date format', 'error');
        return false;
    }
    
    if (start > end) {
        showNotification('End date must be after start date', 'error');
        return false;
    }
    
    if (data.description && data.description.length > 1000) {
        showNotification('Description is too long (max 1000 characters)', 'error');
        return false;
    }
    
    // Check for potentially dangerous content
    const dangerousPatterns = [/<script/i, /javascript:/i, /vbscript:/i, /on\w+=/i];
    for (const pattern of dangerousPatterns) {
        if (pattern.test(data.name) || pattern.test(data.description)) {
            showNotification('Invalid content detected', 'error');
            return false;
        }
    }
    
    // Budget validation
    if (data.budget < 0) {
        showNotification('Budget cannot be negative', 'error');
        return false;
    }
    
    if (data.budgetAllocated > data.budget) {
        showNotification('Allocated budget cannot exceed total budget', 'error');
        return false;
    }
    
    return true;
}

function createCampaignFromTemplate(templateType) {
    const templates = {
        'Emergency Alert': {
            name: 'Emergency Alert Campaign',
            type: 'emergency',
            status: 'active',
            description: 'Emergency response campaign for immediate public notification and safety measures',
            targetAudience: 'General Public',
            budget: 50000,
            budgetAllocated: 30000,
            personnel: 10,
            equipment: 8
        },
        'Health Awareness': {
            name: 'Health Awareness Campaign',
            type: 'health',
            status: 'active',
            description: 'Public health education and awareness campaign focusing on preventive healthcare',
            targetAudience: 'General Public',
            budget: 100000,
            budgetAllocated: 60000,
            personnel: 15,
            equipment: 12
        },
        'Community Outreach': {
            name: 'Community Outreach Program',
            type: 'awareness',
            status: 'planned',
            description: 'Community engagement and outreach program to build stronger community relationships',
            targetAudience: 'Local Community',
            budget: 75000,
            budgetAllocated: 40000,
            personnel: 8,
            equipment: 5
        },
        'Safety Campaign': {
            name: 'Safety Awareness Campaign',
            type: 'safety',
            status: 'active',
            description: 'Safety awareness and prevention campaign for workplace and public safety',
            targetAudience: 'General Public',
            budget: 80000,
            budgetAllocated: 50000,
            personnel: 12,
            equipment: 10
        }
    };

    const template = templates[templateType];
    if (template) {
        createNewCampaign();

        // Pre-fill form with template data
        setTimeout(() => {
            const today = new Date();
            const nextMonth = new Date();
            nextMonth.setMonth(today.getMonth() + 1);
            
            const formatDate = (date) => date.toISOString().split('T')[0];
            
            document.getElementById('campaignName').value = template.name;
            document.getElementById('campaignType').value = template.type;
            document.getElementById('campaignStatus').value = template.status;
            document.getElementById('startDate').value = formatDate(today);
            document.getElementById('endDate').value = formatDate(nextMonth);
            document.getElementById('targetAudience').value = template.targetAudience;
            document.getElementById('campaignDescription').value = template.description;
            document.getElementById('campaignBudget').value = template.budget;
            document.getElementById('budgetAllocated').value = template.budgetAllocated;
            document.getElementById('personnelCount').value = template.personnel;
            document.getElementById('equipmentCount').value = template.equipment;
            
            showNotification(`Template "${templateType}" loaded. Please review and customize the details.`, 'info');
        }, 100);
    }
}

// Rendering functions
function renderCampaignsTable(filteredData = getCampaignsSource()) {
    const tableBody = document.querySelector('.campaigns-table tbody');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    if (filteredData.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <div style="color: #666; font-size: 16px;">
                        <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                        <p>No campaigns found</p>
                        <button class="btn" onclick="createNewCampaign()" style="margin-top: 16px;">
                            <i class="fas fa-plus"></i> Create Your First Campaign
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    filteredData.forEach(campaign => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="campaign-icon">
                        <i class="fas ${getCampaignIcon(campaign.type)}"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">${escapeHtml(campaign.name)}</div>
                        <div style="font-size: 12px; color: #666;">${capitalizeFirst(campaign.type || 'N/A')}</div>
                    </div>
                </div>
            </td>
            <td>${formatDisplayDate(campaign.startDate)}</td>
            <td>${formatDisplayDate(campaign.endDate)}</td>
            <td><span class="status-badge status-${campaign.status}">${capitalizeFirst(campaign.status)}</span></td>
            <td>${campaign.targetAudience || 'N/A'}</td>
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

function getCampaignIcon(type) {
    const icons = {
        'awareness': 'fa-bullhorn',
        'education': 'fa-graduation-cap',
        'emergency': 'fa-exclamation-triangle',
        'health': 'fa-heartbeat',
        'safety': 'fa-shield-alt'
    };
    return icons[type] || 'fa-flag';
}

function renderCalendar() {
    const calendarDays = document.querySelector('.calendar-days');
    if (!calendarDays) return;

    calendarDays.innerHTML = generateCalendarDays(currentCalendarDate);
    
    // Update the month/year display
    const monthDisplay = document.querySelector('.calendar-header h3');
    if (monthDisplay) {
        monthDisplay.textContent = currentCalendarDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
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
        c.name.toLowerCase().includes(sanitizedQuery.toLowerCase()) ||
        c.description.toLowerCase().includes(sanitizedQuery.toLowerCase()) ||
        c.type.toLowerCase().includes(sanitizedQuery.toLowerCase()) ||
        c.targetAudience.toLowerCase().includes(sanitizedQuery.toLowerCase())
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

        // Check for campaigns on this date
        const dayCampaigns = getCampaignsSource().filter(campaign => {
            const start = new Date(campaign.startDate);
            const end = new Date(campaign.endDate);
            return currentDate >= start && currentDate <= end;
        });

        const hasEvent = dayEvents.length > 0;
        const hasCampaign = dayCampaigns.length > 0;

        // Build CSS classes for the day
        let dayClasses = ['calendar-day'];
        if (isToday) dayClasses.push('today');
        if (isPast) dayClasses.push('past');
        if (isWeekend) dayClasses.push('weekend');
        if (hasEvent) dayClasses.push('has-event');
        if (hasCampaign) dayClasses.push('has-campaign');

        // Create day element
        let dayContent = `<span class="day-number">${day}</span>`;

        if (hasEvent || hasCampaign) {
            const indicatorCount = (hasEvent ? dayEvents.length : 0) + (hasCampaign ? dayCampaigns.length : 0);
            dayContent += `<div class="event-indicators">${indicatorCount}</div>`;
        }

        const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        daysHTML += `<div class="${dayClasses.join(' ')}" data-date="${dateString}" onclick="viewDayCampaigns('${dateString}')">${dayContent}</div>`;
    }

    return daysHTML;
}

function viewDayCampaigns(dateString) {
    const campaigns = getCampaignsSource().filter(campaign => {
        const start = new Date(campaign.startDate);
        const end = new Date(campaign.endDate);
        const date = new Date(dateString);
        return date >= start && date <= end;
    });
    
    if (campaigns.length > 0) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Campaigns on ${formatDisplayDate(dateString)}</h3>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="campaigns-list">
                        ${campaigns.map(campaign => `
                            <div class="campaign-item" onclick="viewCampaign(${campaign.id})">
                                <div class="campaign-icon">
                                    <i class="fas ${getCampaignIcon(campaign.type)}"></i>
                                </div>
                                <div class="campaign-details">
                                    <h4>${escapeHtml(campaign.name)}</h4>
                                    <p>${campaign.type} • ${campaign.status}</p>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        setupModalClose(modal);
    } else {
        showNotification('No campaigns scheduled for this date', 'info');
    }
}

function formatDisplayDate(dateString) {
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
                    <div class="campaign-header">
                        <div class="campaign-icon-large">
                            <i class="fas ${getCampaignIcon(campaign.type)}"></i>
                        </div>
                        <div>
                            <h4>${escapeHtml(campaign.name)}</h4>
                            <p class="campaign-type">${capitalizeFirst(campaign.type)} Campaign</p>
                        </div>
                        <span class="status-badge status-${campaign.status}">${capitalizeFirst(campaign.status)}</span>
                    </div>
                    
                    <div class="detail-section">
                        <h5><i class="fas fa-info-circle"></i> Description</h5>
                        <p>${escapeHtml(campaign.description || 'No description provided')}</p>
                    </div>
                    
                    <div class="detail-grid">
                        <div class="detail-item">
                            <h5><i class="fas fa-calendar-alt"></i> Timeline</h5>
                            <p><strong>Start Date:</strong> ${formatDisplayDate(campaign.startDate)}</p>
                            <p><strong>End Date:</strong> ${formatDisplayDate(campaign.endDate)}</p>
                            <p><strong>Target Audience:</strong> ${campaign.targetAudience || 'N/A'}</p>
                        </div>
                        
                        <div class="detail-item">
                            <h5><i class="fas fa-money-bill-wave"></i> Budget</h5>
                            <p><strong>Total Budget:</strong> ₱${parseFloat(campaign.budget || 0).toLocaleString()}</p>
                            <p><strong>Allocated:</strong> ₱${parseFloat(campaign.budgetAllocated || 0).toLocaleString()}</p>
                            <p><strong>Used:</strong> ₱${parseFloat(campaign.budgetUsed || 0).toLocaleString()}</p>
                        </div>
                        
                        <div class="detail-item">
                            <h5><i class="fas fa-users"></i> Resources</h5>
                            <p><strong>Personnel:</strong> ${campaign.personnel || 0}</p>
                            <p><strong>Equipment:</strong> ${campaign.equipment || 0}</p>
                        </div>
                    </div>
                    
                    ${campaign.milestones && campaign.milestones.length > 0 ? `
                    <div class="detail-section">
                        <h5><i class="fas fa-flag"></i> Milestones</h5>
                        <div class="milestones-list">
                            ${campaign.milestones.map(milestone => `
                                <div class="milestone-item ${milestone.completed ? 'completed' : 'pending'}">
                                    <i class="fas fa-${milestone.completed ? 'check-circle' : 'circle'}"></i>
                                    <div class="milestone-details">
                                        <strong>${escapeHtml(milestone.name)}</strong>
                                        <p>${formatDisplayDate(milestone.date)}</p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="editCampaign(${campaign.id})">
                        <i class="fas fa-edit"></i> Edit Campaign
                    </button>
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    setupModalClose(modal);
}

function editCampaign(id) {
    const campaign = getCampaignsSource().find(c => c.id === id);
    if (!campaign) {
        showNotification('Campaign not found', 'error');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Campaign</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="campaignForm">
                    <input type="hidden" id="campaignId" value="${campaign.id}">
                    <div class="form-group">
                        <label for="campaignName">Campaign Name *</label>
                        <input type="text" id="campaignName" value="${escapeHtml(campaign.name)}" required>
                    </div>
                    <div class="form-group">
                        <label for="campaignType">Campaign Type *</label>
                        <select id="campaignType" required>
                            <option value="">Select Type</option>
                            <option value="awareness" ${campaign.type === 'awareness' ? 'selected' : ''}>Awareness</option>
                            <option value="education" ${campaign.type === 'education' ? 'selected' : ''}>Education</option>
                            <option value="emergency" ${campaign.type === 'emergency' ? 'selected' : ''}>Emergency</option>
                            <option value="health" ${campaign.type === 'health' ? 'selected' : ''}>Health</option>
                            <option value="safety" ${campaign.type === 'safety' ? 'selected' : ''}>Safety</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="campaignStatus">Status *</label>
                        <select id="campaignStatus" required>
                            <option value="draft" ${campaign.status === 'draft' ? 'selected' : ''}>Draft</option>
                            <option value="planned" ${campaign.status === 'planned' ? 'selected' : ''}>Planned</option>
                            <option value="active" ${campaign.status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="completed" ${campaign.status === 'completed' ? 'selected' : ''}>Completed</option>
                            <option value="cancelled" ${campaign.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="startDate">Start Date *</label>
                        <input type="date" id="startDate" value="${campaign.startDate}" required>
                    </div>
                    <div class="form-group">
                        <label for="endDate">End Date *</label>
                        <input type="date" id="endDate" value="${campaign.endDate}" required>
                    </div>
                    <div class="form-group">
                        <label for="targetAudience">Target Audience</label>
                        <input type="text" id="targetAudience" value="${escapeHtml(campaign.targetAudience || '')}" placeholder="e.g., General Public, Youth, Seniors">
                    </div>
                    <div class="form-group">
                        <label for="campaignDescription">Description *</label>
                        <textarea id="campaignDescription" rows="4" required>${escapeHtml(campaign.description || '')}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="campaignBudget">Total Budget (₱)</label>
                        <input type="number" id="campaignBudget" value="${campaign.budget || 0}" min="0">
                    </div>
                    <div class="form-group">
                        <label for="budgetAllocated">Budget Allocated (₱)</label>
                        <input type="number" id="budgetAllocated" value="${campaign.budgetAllocated || 0}" min="0">
                    </div>
                    <div class="form-group">
                        <label for="budgetUsed">Budget Used (₱)</label>
                        <input type="number" id="budgetUsed" value="${campaign.budgetUsed || 0}" min="0">
                    </div>
                    <div class="form-group">
                        <label for="personnelCount">Personnel Count</label>
                        <input type="number" id="personnelCount" value="${campaign.personnel || 0}" min="0">
                    </div>
                    <div class="form-group">
                        <label for="equipmentCount">Equipment Count</label>
                        <input type="number" id="equipmentCount" value="${campaign.equipment || 0}" min="0">
                    </div>
                    <div class="form-group">
                        <label for="milestones">Milestones (Format: Name:Date, Name:Date)</label>
                        <textarea id="milestones" rows="3">${campaign.milestones ? campaign.milestones.map(m => `${escapeHtml(m.name)}:${m.date}`).join(', ') : ''}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" onclick="deleteCampaign(${campaign.id})">
                            <i class="fas fa-trash"></i> Delete Campaign
                        </button>
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn">Update Campaign</button>
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

async function deleteCampaign(id) {
    if (!confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
        return;
    }

    // Use the CampaignManager if available
    if (window.campaignManager) {
        window.campaignManager.deleteCampaign(id);
        return;
    }

    // Fallback to direct removal if needed
    try {
        // Remove from campaignsData
        const index = campaignsData.findIndex(c => c.id === id);
        if (index !== -1) {
            const campaignName = campaignsData[index].name;
            campaignsData.splice(index, 1);
            
            // Save to localStorage
            localStorage.setItem('campaigns', JSON.stringify(campaignsData));
            
            showNotification(`Campaign "${campaignName}" deleted successfully!`, 'success');
            
            // Refresh the UI
            renderCampaignsTable();
            updateCampaignStats();
        }
    } catch (error) {
        console.error('Delete campaign error:', error);
        showNotification('Error deleting campaign', 'error');
    }
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

// Campaign Manager Class (keeping it for backward compatibility)
class CampaignManager {
    constructor() {
        this.campaigns = this.loadCampaigns();
        this.editingCampaignId = null;
        this.init();
    }

    init() {
        console.log('CampaignManager initialized with', this.campaigns.length, 'campaigns');
        this.refreshAllViews();
    }

    loadCampaigns() {
        try {
            const savedCampaigns = localStorage.getItem('campaigns');
            return savedCampaigns ? JSON.parse(savedCampaigns) : generateMockCampaignsData();
        } catch (e) {
            console.error('Error loading campaigns:', e);
            return generateMockCampaignsData();
        }
    }

    saveCampaigns() {
        try {
            localStorage.setItem('campaigns', JSON.stringify(this.campaigns));
            console.log('Campaigns saved:', this.campaigns.length);
        } catch (e) {
            console.error('Error saving campaigns:', e);
            showNotification('Error saving campaigns to storage', 'error');
        }
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
            targetAudience: campaignData.targetAudience || '',
            milestones: this.parseMilestones(campaignData.milestones)
        };

        this.campaigns.push(newCampaign);
        this.saveCampaigns();
        this.refreshAllViews();
        return newCampaign;
    }

    updateCampaign(id, campaignData) {
        const index = this.campaigns.findIndex(c => c.id === id);
        if (index === -1) {
            showNotification('Campaign not found!', 'error');
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
            budgetUsed: parseFloat(campaignData.budgetUsed) || oldCampaign.budgetUsed,
            personnel: parseInt(campaignData.personnel) || oldCampaign.personnel,
            equipment: parseInt(campaignData.equipment) || oldCampaign.equipment,
            targetAudience: campaignData.targetAudience || oldCampaign.targetAudience,
            milestones: this.parseMilestones(campaignData.milestones) || oldCampaign.milestones
        };

        this.saveCampaigns();
        this.refreshAllViews();
        return true;
    }

    deleteCampaign(id) {
        const index = this.campaigns.findIndex(c => c.id === id);
        if (index === -1) {
            showNotification('Campaign not found!', 'error');
            return false;
        }

        this.campaigns.splice(index, 1);
        this.saveCampaigns();
        this.refreshAllViews();
        return true;
    }

    getCampaign(id) {
        return this.campaigns.find(c => c.id === id);
    }

    generateId() {
        return this.campaigns.length > 0 ? Math.max(...this.campaigns.map(c => c.id)) + 1 : 1;
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

    refreshAllViews() {
        // Update campaignsData to keep in sync
        campaignsData = this.campaigns;
        
        // Refresh UI components
        renderCampaignsTable();
        renderCalendar();
        updateCampaignStats();
    }
}

// Initialize the Campaign Manager
function initializeCampaignManager() {
    window.campaignManager = new CampaignManager();
    campaignsData = window.campaignManager.campaigns;
    
    // Initialize calendar if needed
    if (!window.campaignCalendar) {
        window.campaignCalendar = {
            previousMonth: () => {
                currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
                renderCalendar();
                window.campaignManager.refreshAllViews();
            },
            nextMonth: () => {
                currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
                renderCalendar();
                window.campaignManager.refreshAllViews();
            }
        };
    }
}

// Export for global access
window.CampaignManager = CampaignManager;
window.CampaignCalendar = window.campaignCalendar;

console.log('Campaign Planning Module loaded successfully');