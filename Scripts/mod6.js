// Campaign Analytics & Reports JavaScript
function generateNewReport() {
    alert('Opening report generation wizard...');
    // In a real application, this would open a report generation modal
}

function generatePerformanceReport() {
    alert('Generating performance summary report...');
}

function generateFinancialReport() {
    alert('Generating financial analysis report...');
}

function generateAudienceReport() {
    alert('Generating audience insights report...');
}

function generateComparativeReport() {
    alert('Generating comparative analysis report...');
}

// Campaign Analytics & Reports - Enhanced with Full CRUD Functionality

// Data Models
let campaigns = [
    {
        id: 1,
        name: "Summer Safety Awareness",
        reach: 45231,
        engagement: 18,
        roi: 3.2,
        progress: 92,
        performance: "high",
        lastUpdated: "2024-10-15"
    },
    {
        id: 2,
        name: "COVID-19 Vaccination Drive",
        reach: 78452,
        engagement: 22,
        roi: 4.1,
        progress: 88,
        performance: "high",
        lastUpdated: "2024-10-14"
    },
    {
        id: 3,
        name: "Flood Preparedness",
        reach: 23456,
        engagement: 12,
        roi: 1.8,
        progress: 65,
        performance: "medium",
        lastUpdated: "2024-10-13"
    },
    {
        id: 4,
        name: "Cybersecurity Awareness",
        reach: 15782,
        engagement: 8,
        roi: 1.2,
        progress: 42,
        performance: "low",
        lastUpdated: "2024-10-12"
    }
];

let reports = [
    {
        id: 1,
        name: "Q3 Campaign Performance Report",
        description: "Comprehensive analysis of all Q3 campaigns",
        type: "performance",
        period: "Jul - Sep 2024",
        generated: "Oct 1, 2024",
        status: "published",
        fileUrl: "#"
    },
    {
        id: 2,
        name: "Summer Safety Campaign Analysis",
        description: "Detailed performance metrics and insights",
        type: "comprehensive",
        period: "Jun - Aug 2024",
        generated: "Sep 15, 2024",
        status: "published",
        fileUrl: "#"
    },
    {
        id: 3,
        name: "Financial ROI Analysis Q3",
        description: "Budget utilization and return on investment",
        type: "financial",
        period: "Jul - Sep 2024",
        generated: "Oct 5, 2024",
        status: "scheduled",
        fileUrl: "#"
    },
    {
        id: 4,
        name: "Audience Engagement Insights",
        description: "Demographic and behavioral analysis",
        type: "audience",
        period: "Jan - Sep 2024",
        generated: "Oct 10, 2024",
        status: "draft",
        fileUrl: "#"
    },
    {
        id: 5,
        name: "Channel Performance Comparison",
        description: "Multi-channel effectiveness analysis",
        type: "performance",
        period: "Last 90 Days",
        generated: "Oct 12, 2024",
        status: "published",
        fileUrl: "#"
    }
];

let channels = [
    { id: 1, name: "Email Campaigns", openRate: 24, ctr: 8.5, roi: 4.2, icon: "envelope" },
    { id: 2, name: "SMS Alerts", openRate: 98, response: 42, roi: 3.8, icon: "mobile-alt" },
    { id: 3, name: "Social Media", engagement: 12, reach: 45000, roi: 2.4, icon: "hashtag" },
    { id: 4, name: "Traditional Media", reach: 28000, awareness: 65, roi: 1.8, icon: "newspaper" }
];

// Current user session
let currentUser = {
    name: "Analytics Manager",
    role: "User",
    permissions: ["read", "write", "delete", "export"]
};

let currentFilter = 'all';
let selectedCampaignId = null;

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    // Original event listeners
    const currentPage = 'Campaign-Analytics-Reports.html';
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                alert(`Searching reports for: "${this.value}"`);
            }
        });
    }

    // Filter items functionality
    const filterItems = document.querySelectorAll('.filter-item');
    filterItems.forEach(item => {
        item.addEventListener('click', function() {
            filterItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            console.log(`Filter applied: ${this.textContent}`);
        });
    });

    // Report item clicks
    const reportItems = document.querySelectorAll('.report-item');
    reportItems.forEach(item => {
        item.addEventListener('click', function() {
            const reportType = this.querySelector('div').textContent;
            console.log(`Generating ${reportType} report`);
        });
    });

    // Export buttons
    const exportButtons = document.querySelectorAll('.export-btn');
    exportButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const format = this.textContent.trim();
            alert(`Exporting report in ${format} format...`);
        });
    });

    // Action icons functionality
    const actionIcons = document.querySelectorAll('.report-actions i');
    actionIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const action = this.getAttribute('title');
            alert(`${action} action triggered`);
        });
    });

    // Run forecast button
    const forecastBtn = document.querySelector('.module-card:nth-child(7) .btn');
    if (forecastBtn) {
        forecastBtn.addEventListener('click', function() {
            alert('Running predictive forecast analysis...');
        });
    }

    // Custom report builder buttons
    const previewBtn = document.querySelector('.module-card:last-child .btn:nth-child(1)');
    const generateBtn = document.querySelector('.module-card:last-child .btn-success');

    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            alert('Previewing custom report...');
        });
    }

    if (generateBtn) {
        generateBtn.addEventListener('click', function() {
            const reportType = document.querySelector('.module-card:last-child select:nth-child(1)')?.value;
            const timePeriod = document.querySelector('.module-card:last-child select:nth-child(2)')?.value;
            alert(`Generating ${reportType} report for ${timePeriod}...`);
        });
    }

    // Campaign performance item clicks
    const campaignItems = document.querySelectorAll('.campaign-item');
    campaignItems.forEach(item => {
        item.addEventListener('click', function() {
            const campaignName = this.querySelector('.campaign-name').textContent;
            console.log(`Viewing analytics for: ${campaignName}`);
        });
    });

    // New enhanced functionality
    initializeApplication();
    setupEventListeners();
    loadDashboardData();
});

function initializeApplication() {
    // Set current page active
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') && link.getAttribute('href').includes('Campaign-Analytics-Reports')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // Set user info
    const usernameEl = document.getElementById('username');
    const userroleEl = document.getElementById('userrole');
    if (usernameEl) usernameEl.textContent = currentUser.name;
    if (userroleEl) userroleEl.textContent = currentUser.role;

    // Load dynamic content
    renderCampaigns();
    renderReportsTable();
    renderChannels();
    updateKPIDashboard();
    populateCampaignSelect();
}

// CRUD Operations for Campaigns
function openCampaignModal(action, campaignId = null) {
    const modal = document.getElementById('campaignModal');
    const title = document.getElementById('campaignModalTitle');
    const deleteBtn = document.getElementById('deleteCampaignBtn');

    if (!modal || !title) return;

    if (action === 'create') {
        title.textContent = 'Add New Campaign';
        const form = document.getElementById('campaignForm');
        if (form) form.reset();
        const campaignIdInput = document.getElementById('campaignId');
        if (campaignIdInput) campaignIdInput.value = '';
        if (deleteBtn) deleteBtn.style.display = 'none';
    } else if (action === 'edit') {
        const campaign = campaigns.find(c => c.id === campaignId);
        if (campaign) {
            title.textContent = 'Edit Campaign';
            document.getElementById('campaignName').value = campaign.name;
            document.getElementById('campaignReach').value = campaign.reach;
            document.getElementById('campaignEngagement').value = campaign.engagement;
            document.getElementById('campaignROI').value = campaign.roi;
            document.getElementById('campaignProgress').value = campaign.progress;
            document.getElementById('campaignPerformance').value = campaign.performance;
            document.getElementById('campaignId').value = campaign.id;
            if (deleteBtn) deleteBtn.style.display = 'block';
        }
    }

    selectedCampaignId = campaignId;
    modal.style.display = 'block';
}

function saveCampaign(event) {
    event.preventDefault();

    const campaignData = {
        id: document.getElementById('campaignId').value || Date.now(),
        name: document.getElementById('campaignName').value,
        reach: parseInt(document.getElementById('campaignReach').value) || 0,
        engagement: parseFloat(document.getElementById('campaignEngagement').value) || 0,
        roi: parseFloat(document.getElementById('campaignROI').value) || 0,
        progress: parseInt(document.getElementById('campaignProgress').value) || 0,
        performance: document.getElementById('campaignPerformance').value,
        lastUpdated: new Date().toISOString().split('T')[0]
    };

    if (document.getElementById('campaignId').value) {
        // Update existing campaign
        const index = campaigns.findIndex(c => c.id == campaignData.id);
        if (index !== -1) {
            campaigns[index] = campaignData;
            showNotification('Campaign updated successfully!');
        }
    } else {
        // Create new campaign
        campaignData.id = Date.now();
        campaigns.push(campaignData);
        showNotification('Campaign created successfully!');
    }

    closeModal('campaignModal');
    renderCampaigns();
    updateKPIDashboard();
    populateCampaignSelect();
}

function deleteCampaign() {
    if (!selectedCampaignId) return;

    if (confirm('Are you sure you want to delete this campaign?')) {
        campaigns = campaigns.filter(c => c.id != selectedCampaignId);
        showNotification('Campaign deleted successfully!');
        closeModal('campaignModal');
        renderCampaigns();
        updateKPIDashboard();
        populateCampaignSelect();
    }
}

function viewCampaignDetails(campaignId) {
    const campaign = campaigns.find(c => c.id === campaignId);
    if (campaign) {
        alert(`Campaign Details:\n\nName: ${campaign.name}\nReach: ${campaign.reach.toLocaleString()}\nEngagement: ${campaign.engagement}%\nROI: ${campaign.roi}x\nProgress: ${campaign.progress}%\nLast Updated: ${campaign.lastUpdated}`);
    }
}

// CRUD Operations for Reports
function openReportManager() {
    const modal = document.getElementById('reportModal');
    const content = document.getElementById('reportModalContent');

    if (!modal || !content) return;

    let html = `
        <div style="margin-bottom: 20px;">
            <button class="btn btn-success" onclick="createNewReport()" style="width: 100%;">
                <i class="fas fa-plus"></i> Create New Report
            </button>
        </div>
        <div style="max-height: 400px; overflow-y: auto;">
    `;

    reports.forEach(report => {
        html += `
            <div class="report-manager-item" style="padding: 15px; background-color: var(--dark-gray); border-radius: 8px; margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600;">${report.name}</div>
                        <div style="font-size: 12px; color: var(--text-gray);">${report.description}</div>
                    </div>
                    <div>
                        <button class="btn-sm" onclick="editReport(${report.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-sm" onclick="deleteReport(${report.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn-sm" onclick="duplicateReport(${report.id})" title="Duplicate">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    content.innerHTML = html;
    modal.style.display = 'block';
}

function createNewReport() {
    const reportName = prompt('Enter report name:');
    if (reportName) {
        const newReport = {
            id: Date.now(),
            name: reportName,
            description: "New custom report",
            type: "performance",
            period: "Custom Range",
            generated: new Date().toLocaleDateString(),
            status: "draft",
            fileUrl: "#"
        };
        reports.push(newReport);
        showNotification('New report created!');
        renderReportsTable();
        closeModal('reportModal');
    }
}

function editReport(reportId) {
    const report = reports.find(r => r.id === reportId);
    if (report) {
        const newName = prompt('Edit report name:', report.name);
        if (newName) {
            report.name = newName;
            showNotification('Report updated!');
            renderReportsTable();
            closeModal('reportModal');
        }
    }
}

function deleteReport(reportId) {
    if (confirm('Are you sure you want to delete this report?')) {
        reports = reports.filter(r => r.id !== reportId);
        showNotification('Report deleted!');
        renderReportsTable();
    }
}

function duplicateReport(reportId) {
    const report = reports.find(r => r.id === reportId);
    if (report) {
        const duplicate = {
            ...report,
            id: Date.now(),
            name: `${report.name} (Copy)`,
            generated: new Date().toLocaleDateString(),
            status: "draft"
        };
        reports.push(duplicate);
        showNotification('Report duplicated!');
        renderReportsTable();
        closeModal('reportModal');
    }
}

// Channel Management
function openChannelManager() {
    const modal = document.getElementById('channelModal');
    const content = document.getElementById('channelModalContent');

    if (!modal || !content) return;

    let html = `
        <div style="margin-bottom: 20px;">
            <button class="btn btn-success" onclick="addNewChannel()" style="width: 100%;">
                <i class="fas fa-plus"></i> Add New Channel
            </button>
        </div>
        <div style="max-height: 400px; overflow-y: auto;">
    `;

    channels.forEach(channel => {
        html += `
            <div class="channel-manager-item" style="padding: 15px; background-color: var(--dark-gray); border-radius: 8px; margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-${channel.icon}"></i>
                        <div>
                            <div style="font-weight: 600;">${channel.name}</div>
                            <div style="font-size: 12px; color: var(--text-gray);">
                                ${channel.roi ? `ROI: ${channel.roi}x` : ''}
                            </div>
                        </div>
                    </div>
                    <div>
                        <button class="btn-sm" onclick="editChannel(${channel.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-sm" onclick="deleteChannel(${channel.id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    html += `</div>`;
    content.innerHTML = html;
    modal.style.display = 'block';
}

function addNewChannel() {
    const channelName = prompt('Enter channel name:');
    if (channelName) {
        const newChannel = {
            id: Date.now(),
            name: channelName,
            roi: 0,
            icon: "broadcast-tower"
        };
        channels.push(newChannel);
        showNotification('New channel added!');
        renderChannels();
        closeModal('channelModal');
    }
}

function editChannel(channelId) {
    const channel = channels.find(c => c.id === channelId);
    if (channel) {
        const newName = prompt('Edit channel name:', channel.name);
        if (newName) {
            channel.name = newName;
            showNotification('Channel updated!');
            renderChannels();
            closeModal('channelModal');
        }
    }
}

function deleteChannel(channelId) {
    if (confirm('Are you sure you want to delete this channel?')) {
        channels = channels.filter(c => c.id !== channelId);
        showNotification('Channel deleted!');
        renderChannels();
    }
}

// Report Generation Functions
function openReportWizard() {
    alert('Opening report generation wizard...\nThis would open a step-by-step wizard for creating custom reports.');
}

function generateReport(type) {
    const reportTypes = {
        performance: 'Performance Summary',
        financial: 'Financial Analysis',
        audience: 'Audience Insights',
        comparative: 'Comparative Analysis'
    };

    showNotification(`Generating ${reportTypes[type]} report...`);

    // Simulate report generation
    setTimeout(() => {
        const newReport = {
            id: Date.now(),
            name: `${reportTypes[type]} - ${new Date().toLocaleDateString()}`,
            description: `Automatically generated ${reportTypes[type].toLowerCase()} report`,
            type: type,
            period: "Last 30 Days",
            generated: new Date().toLocaleDateString(),
            status: "published",
            fileUrl: "#"
        };
        reports.push(newReport);
        renderReportsTable();
        showNotification(`${reportTypes[type]} report generated successfully!`);
    }, 1500);
}

function exportReport(format) {
    const formats = {
        pdf: 'PDF',
        excel: 'Excel',
        ppt: 'PowerPoint'
    };
    showNotification(`Exporting report in ${formats[format]} format...`);

    // Simulate export process
    setTimeout(() => {
        showNotification(`Report exported successfully as ${formats[format]}!`);
    }, 1000);
}

function shareReport() {
    const email = prompt('Enter email address to share report:');
    if (email && validateEmail(email)) {
        showNotification(`Report shared with ${email}`);
    } else if (email) {
        alert('Please enter a valid email address');
    }
}

// Custom Report Builder Functions
function previewCustomReport() {
    const reportType = document.getElementById('reportType')?.value;
    const timePeriod = document.getElementById('timePeriod')?.value;
    const selectedCampaigns = document.getElementById('selectedCampaigns')?.value;
    const metrics = document.getElementById('selectedMetrics')?.value;

    alert(`Previewing Custom Report:\n\nType: ${reportType}\nPeriod: ${timePeriod}\nCampaigns: ${selectedCampaigns}\nMetrics: ${metrics}`);
}

function generateCustomReport() {
    const reportType = document.getElementById('reportType')?.value;
    const timePeriod = document.getElementById('timePeriod')?.value;

    showNotification(`Generating custom ${reportType} report for ${timePeriod}...`);

    // Simulate generation
    setTimeout(() => {
        const newReport = {
            id: Date.now(),
            name: `Custom ${reportType} Report`,
            description: `Custom report generated for ${timePeriod}`,
            type: 'comprehensive',
            period: timePeriod,
            generated: new Date().toLocaleDateString(),
            status: "published",
            fileUrl: "#"
        };
        reports.push(newReport);
        renderReportsTable();
        showNotification('Custom report generated successfully!');
    }, 2000);
}

// Analytics Functions
function runForecast() {
    showNotification('Running predictive forecast analysis...');

    // Simulate forecast calculation
    setTimeout(() => {
        const performanceScoreEl = document.getElementById('performanceScore');
        if (performanceScoreEl) {
            performanceScoreEl.textContent = Math.floor(Math.random() * 20) + 85;
        }
        updatePerformanceRating();
        showNotification('Forecast analysis completed!');
    }, 2000);
}

function configureForecast() {
    alert('Opening forecast configuration panel...\nHere you can adjust prediction models and parameters.');
}

function recalculateScores() {
    showNotification('Recalculating performance scores...');

    // Simulate recalculation
    setTimeout(() => {
        const newEngagementScore = Math.floor(Math.random() * 10) + 85;
        const newROIScore = Math.floor(Math.random() * 10) + 90;
        const newSatisfactionScore = Math.floor(Math.random() * 10) + 80;

        const engagementScoreEl = document.getElementById('engagementScore');
        const roiScoreEl = document.getElementById('roiScore');
        const satisfactionScoreEl = document.getElementById('satisfactionScore');
        const performanceScoreEl = document.getElementById('performanceScore');

        if (engagementScoreEl) engagementScoreEl.textContent = newEngagementScore;
        if (roiScoreEl) roiScoreEl.textContent = newROIScore;
        if (satisfactionScoreEl) satisfactionScoreEl.textContent = newSatisfactionScore;

        if (engagementScoreEl && roiScoreEl && satisfactionScoreEl) {
            const avgScore = Math.round((newEngagementScore + newROIScore + newSatisfactionScore) / 3);
            if (performanceScoreEl) performanceScoreEl.textContent = avgScore;
        }

        updatePerformanceRating();
        showNotification('Scores recalculated successfully!');
    }, 1500);
}

function updatePerformanceRating() {
    const performanceScoreEl = document.getElementById('performanceScore');
    const performanceRatingEl = document.getElementById('performanceRating');
    const performancePercentileEl = document.getElementById('performancePercentile');

    if (!performanceScoreEl || !performanceRatingEl || !performancePercentileEl) return;

    const score = parseInt(performanceScoreEl.textContent);
    let rating, percentile;

    if (score >= 90) {
        rating = "Excellent Performance";
        percentile = "Top 10% of all campaigns";
    } else if (score >= 75) {
        rating = "Good Performance";
        percentile = "Top 30% of all campaigns";
    } else if (score >= 60) {
        rating = "Average Performance";
        percentile = "Middle 50% of campaigns";
    } else {
        rating = "Needs Improvement";
        percentile = "Bottom 20% of campaigns";
    }

    performanceRatingEl.textContent = rating;
    performancePercentileEl.textContent = percentile;
}

// Utility Functions
function applyFilter(filterType) {
    currentFilter = filterType;
    const filterItems = document.querySelectorAll('.filter-item');
    filterItems.forEach(item => item.classList.remove('active'));
    
    // Using event parameter to get the clicked element
    if (event && event.target) {
        event.target.classList.add('active');
        showNotification(`Filter applied: ${event.target.textContent}`);
    }

    renderCampaigns();
}

function showUserMenu() {
    const menuItems = [
        { label: 'Profile Settings', action: 'editProfile' },
        { label: 'Notification Preferences', action: 'notifications' },
        { label: 'Logout', action: 'logout' }
    ];

    let menuHtml = '<div class="context-menu">';
    menuItems.forEach(item => {
        menuHtml += `<div class="context-menu-item" onclick="${item.action}()">${item.label}</div>`;
    });
    menuHtml += '</div>';

    // Create and show context menu
    const menu = document.createElement('div');
    menu.innerHTML = menuHtml;
    menu.style.position = 'absolute';
    menu.style.top = '50px';
    menu.style.right = '20px';
    menu.id = 'userContextMenu';
    document.body.appendChild(menu);

    // Close menu when clicking elsewhere
    setTimeout(() => {
        function closeMenu(e) {
            if (!menu.contains(e.target) && !e.target.classList.contains('user-profile')) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        }
        document.addEventListener('click', closeMenu);
    }, 0);
}

function editProfile() {
    const newName = prompt('Enter your name:', currentUser.name);
    if (newName) {
        currentUser.name = newName;
        const usernameEl = document.getElementById('username');
        if (usernameEl) usernameEl.textContent = newName;
        showNotification('Profile updated!');
    }
}

function notifications() {
    alert('Notification preferences would open here.');
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        showNotification('Logging out...');
        setTimeout(() => {
            // Update this to match your actual login page path
            window.location.href = '/LGU4/login.php';
        }, 1000);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    const messageEl = document.getElementById('notificationMessage');

    if (!notification || !messageEl) return;

    notification.style.backgroundColor = type === 'success' ? 'var(--success)' :
                                      type === 'error' ? 'var(--danger)' :
                                      type === 'warning' ? 'var(--warning)' : 'var(--accent)';

    messageEl.textContent = message;
    notification.style.display = 'block';

    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}

// Rendering Functions
function renderCampaigns() {
    const container = document.getElementById('campaignList');
    if (!container) return;

    let html = '';

    let filteredCampaigns = campaigns;

    // Apply filters
    switch(currentFilter) {
        case 'high':
            filteredCampaigns = campaigns.filter(c => c.performance === 'high');
            break;
        case 'attention':
            filteredCampaigns = campaigns.filter(c => c.performance === 'low' || c.progress < 50);
            break;
        case '7days':
            // Simulate time-based filtering
            filteredCampaigns = campaigns.slice(0, 2);
            break;
        case '30days':
            filteredCampaigns = campaigns;
            break;
    }

    filteredCampaigns.forEach(campaign => {
        const performanceClass = `${campaign.performance}-performance`;

        html += `
            <div class="campaign-item ${performanceClass}" onclick="viewCampaignDetails(${campaign.id})"
                 ondblclick="openCampaignModal('edit', ${campaign.id})">
                <div class="campaign-name">${campaign.name}</div>
                <div class="campaign-metrics">
                    <span>Reach: ${campaign.reach.toLocaleString()}</span>
                    <span>Engagement: ${campaign.engagement}%</span>
                    <span>ROI: ${campaign.roi}x</span>
                </div>
                <div class="campaign-progress">
                    <div class="progress-container">
                        <div class="progress-bar" style="width: ${campaign.progress}%"></div>
                    </div>
                    <span style="font-size: 14px; color: ${getProgressColor(campaign.progress)};">${campaign.progress}%</span>
                </div>
                <div style="margin-top: 10px; display: flex; gap: 10px;">
                    <button class="btn-sm" onclick="event.stopPropagation(); openCampaignModal('edit', ${campaign.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-sm" onclick="event.stopPropagation(); deleteCampaignFromList(${campaign.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function deleteCampaignFromList(campaignId) {
    if (confirm('Delete this campaign?')) {
        campaigns = campaigns.filter(c => c.id !== campaignId);
        renderCampaigns();
        updateKPIDashboard();
        showNotification('Campaign deleted!');
    }
}

function renderReportsTable() {
    const tbody = document.getElementById('reportsTableBody');
    if (!tbody) return;

    let html = '';

    reports.forEach(report => {
        const typeClass = `type-${report.type}`;
        const statusClass = `status-${report.status}`;

        html += `
            <tr>
                <td>
                    <div style="font-weight: 600;">${report.name}</div>
                    <div style="font-size: 12px; color: var(--text-gray);">${report.description}</div>
                </td>
                <td><span class="report-type ${typeClass}">${capitalizeFirst(report.type)}</span></td>
                <td>${report.period}</td>
                <td>${report.generated}</td>
                <td><span class="report-status ${statusClass}">${capitalizeFirst(report.status)}</span></td>
                <td>
                    <div class="report-actions">
                        <i class="fas fa-eye" title="View" onclick="viewReport(${report.id})"></i>
                        <i class="fas fa-download" title="Download" onclick="downloadReport(${report.id})"></i>
                        <i class="fas fa-edit" title="Edit" onclick="editReport(${report.id})"></i>
                        <i class="fas fa-trash" title="Delete" onclick="deleteReport(${report.id})"></i>
                        <i class="fas fa-share" title="Share" onclick="shareSpecificReport(${report.id})"></i>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderChannels() {
    const container = document.getElementById('channelList');
    if (!container) return;

    let html = '';

    channels.forEach(channel => {
        html += `
            <div class="channel-item" onclick="viewChannelAnalytics(${channel.id})">
                <div class="channel-icon">
                    <i class="fas fa-${channel.icon}"></i>
                </div>
                <div class="channel-details">
                    <div class="channel-name">${channel.name}</div>
                    <div class="channel-stats">
                        ${channel.openRate ? `<span>Open Rate: ${channel.openRate}%</span>` : ''}
                        ${channel.ctr ? `<span>CTR: ${channel.ctr}%</span>` : ''}
                        ${channel.response ? `<span>Response: ${channel.response}%</span>` : ''}
                        ${channel.engagement ? `<span>Engagement: ${channel.engagement}%</span>` : ''}
                        ${channel.reach ? `<span>Reach: ${channel.reach.toLocaleString()}</span>` : ''}
                        ${channel.roi ? `<span>ROI: ${channel.roi}x</span>` : ''}
                        ${channel.awareness ? `<span>Awareness: ${channel.awareness}%</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function viewChannelAnalytics(channelId) {
    const channel = channels.find(c => c.id === channelId);
    if (channel) {
        alert(`Channel Analytics: ${channel.name}\n\n${JSON.stringify(channel, null, 2)}`);
    }
}

function updateKPIDashboard() {
    // Calculate aggregated KPIs from campaigns
    const totalReach = campaigns.reduce((sum, c) => sum + c.reach, 0);
    const avgEngagement = campaigns.reduce((sum, c) => sum + c.engagement, 0) / campaigns.length;
    const avgROI = campaigns.reduce((sum, c) => sum + c.roi, 0) / campaigns.length;

    // Update DOM elements
    const totalReachEl = document.getElementById('totalReach');
    const avgEngagementEl = document.getElementById('avgEngagement');
    const avgROIEl = document.getElementById('avgROI');

    if (totalReachEl) totalReachEl.textContent = `${(totalReach / 1000).toFixed(0)}K`;
    if (avgEngagementEl) avgEngagementEl.textContent = `${avgEngagement.toFixed(1)}%`;
    if (avgROIEl) avgROIEl.textContent = `${avgROI.toFixed(1)}x`;

    // Update ROI analysis
    const totalInvestment = 2400000;
    const totalValue = totalInvestment * (avgROI + 1);
    const netROI = ((totalValue - totalInvestment) / totalInvestment * 100).toFixed(0);

    const totalInvestmentEl = document.getElementById('totalInvestment');
    const totalValueEl = document.getElementById('totalValue');
    const netROIEl = document.getElementById('netROI');

    if (totalInvestmentEl) totalInvestmentEl.textContent = `$${(totalInvestment / 1000000).toFixed(1)}M`;
    if (totalValueEl) totalValueEl.textContent = `$${(totalValue / 1000000).toFixed(1)}M`;
    if (netROIEl) netROIEl.textContent = `${netROI}%`;
}

function populateCampaignSelect() {
    const select = document.getElementById('selectedCampaigns');
    if (!select) return;

    // Clear existing options except the first one
    while (select.options.length > 1) {
        select.remove(1);
    }

    // Add campaign options
    campaigns.forEach(campaign => {
        const option = document.createElement('option');
        option.value = campaign.id;
        option.textContent = campaign.name;
        select.appendChild(option);
    });
}

// Setup Event Listeners
function setupEventListeners() {
    // Campaign form submission
    const campaignForm = document.getElementById('campaignForm');
    if (campaignForm) {
        campaignForm.addEventListener('submit', saveCampaign);
    }

    // Global search
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.toLowerCase();
                if (searchTerm) {
                    const filtered = reports.filter(r =>
                        r.name.toLowerCase().includes(searchTerm) ||
                        r.description.toLowerCase().includes(searchTerm)
                    );
                    alert(`Found ${filtered.length} reports matching "${searchTerm}"`);
                }
            }
        });
    }

    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+N for new report
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            openReportWizard();
        }
        // Ctrl+F for search focus
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) searchInput.focus();
        }
        // Escape to close modals
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Initialize editable fields
    initializeEditableFields();
}

function initializeEditableFields() {
    // Make KPI values editable on double-click
    const kpiValues = document.querySelectorAll('.kpi-value');
    kpiValues.forEach(value => {
        value.addEventListener('dblclick', function() {
            const currentValue = this.textContent;
            const newValue = prompt('Enter new value:', currentValue);
            if (newValue) {
                this.textContent = newValue;
                showNotification('KPI value updated!');
            }
        });
    });
}

function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.style.display = 'none';
    });
}

// Helper Functions
function getProgressColor(progress) {
    if (progress >= 80) return 'var(--success)';
    if (progress >= 60) return 'var(--warning)';
    return 'var(--danger)';
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function loadDashboardData() {
    // Simulate loading data
    showNotification('Loading dashboard data...');

    setTimeout(() => {
        updateKPIDashboard();
        updatePerformanceRating();
        showNotification('Dashboard data loaded successfully!');
    }, 1000);
}

// Additional interactive functions
function viewReport(reportId) {
    const report = reports.find(r => r.id === reportId);
    if (report) {
        alert(`Viewing Report: ${report.name}\n\nType: ${report.type}\nPeriod: ${report.period}\nStatus: ${report.status}\nGenerated: ${report.generated}\n\n${report.description}`);
    }
}

function downloadReport(reportId) {
    const report = reports.find(r => r.id === reportId);
    if (report) {
        showNotification(`Downloading ${report.name}...`);
        // Simulate download
        setTimeout(() => {
            showNotification(`${report.name} downloaded successfully!`);
        }, 1000);
    }
}

function shareSpecificReport(reportId) {
    const report = reports.find(r => r.id === reportId);
    if (report) {
        const email = prompt(`Share "${report.name}" with:`);
        if (email && validateEmail(email)) {
            showNotification(`Report shared with ${email}`);
        }
    }
}

function editROI(type) {
    let elementId;
    let label;
    
    if (type === 'investment') {
        elementId = 'totalInvestment';
        label = 'investment';
    } else if (type === 'value') {
        elementId = 'totalValue';
        label = 'value generated';
    } else {
        elementId = 'costPerEngagement';
        label = 'cost per engagement';
    }
    
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const currentValue = element.textContent;
    const newValue = prompt(`Enter new ${label}:`, currentValue);
    if (newValue) {
        element.textContent = newValue;
        showNotification('ROI metric updated!');
    }
}

function editDemo(ageGroup) {
    const element = document.getElementById(ageGroup);
    if (!element) return;
    
    const currentValue = element.textContent;
    const newValue = prompt(`Enter new percentage for ${ageGroup}:`, currentValue);
    if (newValue) {
        element.textContent = newValue;
        showNotification('Demographic data updated!');
    }
}

function openChartEditor(chartType) {
    alert(`Opening chart editor for ${chartType === 'kpi' ? 'KPI Trends' : 'ROI Distribution'}\nHere you can customize chart settings, colors, and data points.`);
}