// JavaScript functionality for the integration module
function configureNewIntegration() {
    alert('Opening integration configuration wizard...');
    // In a real application, this would open a modal or navigate to configuration page
}

function testAllConnections() {
    alert('Testing all system connections...');
    // In a real application, this would trigger API calls to test connections
}

function runHealthCheck() {
    alert('Running comprehensive system health check...');
    // In a real application, this would check all integration points
}

function viewErrorLogs() {
    alert('Opening error log viewer...');
    // In a real application, this would filter and display error logs
}

function generateComplianceReport() {
    alert('Generating compliance report...');
    // In a real application, this would create and download a report
}

// Set active navigation
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = 'Health-Police-Integration.html';
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
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            alert(`Searching integrations for: "${this.value}"`);
        }
    });

    // User profile click
    const userProfile = document.querySelector('.user-profile');
    userProfile.addEventListener('click', function() {
        alert('User profile menu would open here');
    });

    // Filter functionality
    const filterItems = document.querySelectorAll('.filter-item');
    filterItems.forEach(item => {
        item.addEventListener('click', function() {
            filterItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            // In a real application, this would filter the table and cards
            // based on the selected filter
            console.log(`Filter applied: ${this.textContent}`);
        });
    });

    // API Management button
    const manageAPIsBtn = document.querySelector('.module-card:nth-child(4) .btn');
    manageAPIsBtn.addEventListener('click', function() {
        alert('Opening API management interface...');
    });

    // Quick action buttons
    const quickActions = document.querySelectorAll('.quick-actions-grid .action-btn');
    quickActions.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.querySelector('span').textContent;
            console.log(`Quick action: ${action}`);
        });
    });

    // Download logs button
    const downloadLogsBtn = document.querySelector('.module-card:last-child .btn');
    downloadLogsBtn.addEventListener('click', function() {
        alert('Downloading system logs...');
    });

    // Integration action icons in table
    const syncButtons = document.querySelectorAll('.fa-sync');
    syncButtons.forEach(button => {
        button.addEventListener('click', function() {
            const integrationName = this.closest('tr').querySelector('[style*="font-weight: 600"]').textContent;
            alert(`Manual sync triggered for: ${integrationName}`);
        });
    });

    const configButtons = document.querySelectorAll('.fa-cog');
    configButtons.forEach(button => {
        button.addEventListener('click', function() {
            const integrationName = this.closest('tr').querySelector('[style*="font-weight: 600"]').textContent;
            alert(`Opening configuration for: ${integrationName}`);
        });
    });

    const monitorButtons = document.querySelectorAll('.fa-chart-line');
    monitorButtons.forEach(button => {
        button.addEventListener('click', function() {
            const integrationName = this.closest('tr').querySelector('[style*="font-weight: 600"]').textContent;
            alert(`Opening monitoring dashboard for: ${integrationName}`);
        });
    });

    const toolButtons = document.querySelectorAll('.fa-tools');
    toolButtons.forEach(button => {
        button.addEventListener('click', function() {
            const integrationName = this.closest('tr').querySelector('[style*="font-weight: 600"]').textContent;
            alert(`Opening maintenance panel for: ${integrationName}`);
        });
    });

    const historyButtons = document.querySelectorAll('.fa-history');
    historyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const integrationName = this.closest('tr').querySelector('[style*="font-weight: 600"]').textContent;
            alert(`Viewing logs for: ${integrationName}`);
        });
    });

    // Log item interactions
    const logItems = document.querySelectorAll('.log-item');
    logItems.forEach(item => {
        item.addEventListener('click', function() {
            console.log(`Log details: ${this.textContent.trim()}`);
        });
    });
});


// JavaScript functionality for the integration module with full CRUD operations

// Data Store for Integrations
let integrationsData = [
    {
        id: 1,
        name: "Public Health Registry Sync",
        type: "Health",
        system: "State Health Department",
        dataPoints: ["Immunizations", "Disease Reports", "Lab Results"],
        lastUpdated: "2 minutes ago",
        status: "active",
        description: "Real-time immunization and disease data"
    },
    {
        id: 2,
        name: "Police Incident Feed",
        type: "Police",
        system: "City Police Department",
        dataPoints: ["Incidents", "Dispatch", "Resources"],
        lastUpdated: "5 minutes ago",
        status: "active",
        description: "Live incident reports and dispatch data"
    },
    {
        id: 3,
        name: "Hospital Capacity Monitor",
        type: "Health",
        system: "Regional Hospital Network",
        dataPoints: ["Bed Status", "ED Capacity", "Specialists"],
        lastUpdated: "15 minutes ago",
        status: "maintenance",
        description: "Emergency department and bed availability"
    }
];

// Modal Management
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// CRUD Operations
function createIntegration() {
    const name = document.getElementById('integrationName').value;
    const type = document.getElementById('integrationType').value;
    const system = document.getElementById('connectedSystem').value;
    const description = document.getElementById('integrationDesc').value;

    const newIntegration = {
        id: integrationsData.length + 1,
        name: name,
        type: type,
        system: system,
        dataPoints: ["Default"],
        lastUpdated: "Just now",
        status: "active",
        description: description
    };

    integrationsData.push(newIntegration);
    renderIntegrationsTable();
    closeModal('createModal');

    // Add log entry
    addLogEntry(`Created new integration: ${name}`, 'success');
    showNotification(`Integration "${name}" created successfully!`, 'success');
}

function updateIntegration(id) {
    const integration = integrationsData.find(item => item.id === id);
    if (!integration) return;

    document.getElementById('editName').value = integration.name;
    document.getElementById('editType').value = integration.type.toLowerCase();
    document.getElementById('editSystem').value = integration.system;
    document.getElementById('editStatus').value = integration.status;
    document.getElementById('editDesc').value = integration.description;
    document.getElementById('editId').value = id;

    openModal('editModal');
}

function saveIntegrationChanges() {
    const id = parseInt(document.getElementById('editId').value);
    const integration = integrationsData.find(item => item.id === id);

    if (integration) {
        integration.name = document.getElementById('editName').value;
        integration.type = document.getElementById('editType').value;
        integration.system = document.getElementById('editSystem').value;
        integration.status = document.getElementById('editStatus').value;
        integration.description = document.getElementById('editDesc').value;
        integration.lastUpdated = "Just now";

        renderIntegrationsTable();
        closeModal('editModal');

        addLogEntry(`Updated integration: ${integration.name}`, 'info');
        showNotification(`Integration "${integration.name}" updated!`, 'success');
    }
}

function deleteIntegration(id) {
    const integration = integrationsData.find(item => item.id === id);
    if (!integration) return;

    if (confirm(`Are you sure you want to delete "${integration.name}"?`)) {
        integrationsData = integrationsData.filter(item => item.id !== id);
        renderIntegrationsTable();

        addLogEntry(`Deleted integration: ${integration.name}`, 'warning');
        showNotification(`Integration "${integration.name}" deleted!`, 'warning');
    }
}

function renderIntegrationsTable() {
    const tableBody = document.querySelector('.integration-table tbody');
    tableBody.innerHTML = '';

    integrationsData.forEach(integration => {
        const row = document.createElement('tr');

        // Status badge class
        const statusClass = integration.status === 'active' ? 'status-active' :
                           integration.status === 'disabled' ? 'status-disabled' :
                           integration.status === 'maintenance' ? 'status-maintenance' : 'status-error';

        // Type badge class
        const typeClass = integration.type === 'Health' ? 'type-health' :
                         integration.type === 'Police' ? 'type-police' :
                         integration.type === 'Emergency' ? 'type-emergency' : 'type-data';

        row.innerHTML = `
            <td>
                <div style="font-weight: 600;">${integration.name}</div>
                <div style="font-size: 12px; color: var(--text-gray);">${integration.description}</div>
            </td>
            <td><span class="integration-type ${typeClass}">${integration.type}</span></td>
            <td>${integration.system}</td>
            <td>
                ${integration.dataPoints.map(point => `<span class="badge">${point}</span>`).join('')}
            </td>
            <td>${integration.lastUpdated}</td>
            <td><span class="integration-status ${statusClass}">${integration.status.charAt(0).toUpperCase() + integration.status.slice(1)}</span></td>
            <td>
                <div class="integration-actions">
                    <i class="fas fa-sync" title="Sync Now" onclick="syncIntegration(${integration.id})"></i>
                    <i class="fas fa-cog" title="Configure" onclick="updateIntegration(${integration.id})"></i>
                    <i class="fas fa-chart-line" title="Monitor" onclick="monitorIntegration(${integration.id})"></i>
                    <i class="fas fa-trash" title="Delete" onclick="deleteIntegration(${integration.id})" style="color: var(--danger);"></i>
                </div>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

// System Status Management
function toggleSystemStatus(systemName) {
    const systemItems = document.querySelectorAll('.system-item');
    systemItems.forEach(item => {
        const nameElement = item.querySelector('.system-name');
        if (nameElement && nameElement.textContent.includes(systemName)) {
            const isOnline = item.classList.contains('online');
            const isOffline = item.classList.contains('offline');

            item.classList.remove('online', 'offline', 'maintenance');

            if (isOnline) {
                item.classList.add('maintenance');
                item.querySelector('.last-sync').textContent = 'Manual maintenance mode';
            } else if (isOffline) {
                item.classList.add('online');
                item.querySelector('.last-sync').textContent = 'Last sync: Just now';
            } else {
                item.classList.add('offline');
                item.querySelector('.last-sync').textContent = 'System offline';
            }

            addLogEntry(`Changed ${systemName} status`, 'warning');
            showNotification(`${systemName} status updated!`, 'info');
        }
    });
}

// Filter Functionality
function filterIntegrations(filterType) {
    const filterItems = document.querySelectorAll('.filter-item');
    filterItems.forEach(item => item.classList.remove('active'));
    event.target.classList.add('active');

    // Filter logic
    switch(filterType) {
        case 'Health Systems':
            // Filter health systems
            break;
        case 'Police Systems':
            // Filter police systems
            break;
        case 'Active':
            // Show only active
            break;
        case 'Needs Attention':
            // Show issues
            break;
    }

    showNotification(`Filter applied: ${filterType}`, 'info');
}

// Log Management
function addLogEntry(message, type) {
    const logsContainer = document.querySelector('.logs-container');
    const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit', second:'2-digit'});

    const logItem = document.createElement('div');
    logItem.className = 'log-item';
    logItem.onclick = () => viewLogDetails(message, timestamp);

    const typeIcon = type === 'success' ? '✓' :
                     type === 'error' ? '✗' :
                     type === 'warning' ? '⚠' : 'ℹ';

    logItem.innerHTML = `
        <span class="log-timestamp">${timestamp}</span>
        <span class="log-${type}">${typeIcon}</span>
        ${message}
    `;

    logsContainer.insertBefore(logItem, logsContainer.firstChild);

    // Keep only last 20 logs
    if (logsContainer.children.length > 20) {
        logsContainer.removeChild(logsContainer.lastChild);
    }
}

function downloadLogs() {
    const logs = document.querySelectorAll('.log-item');
    let logContent = "Integration Logs\n================\n\n";

    logs.forEach(log => {
        logContent += log.textContent.trim() + '\n';
    });

    const blob = new Blob([logContent], {type: 'text/plain'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `integration-logs-${new Date().toISOString().split('T')[0]}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    addLogEntry('Downloaded complete log file', 'success');
    showNotification('Logs downloaded successfully!', 'success');
}

// API Management
function manageAPIs() {
    const apis = document.querySelectorAll('.api-item');
    apis.forEach(api => {
        const apiName = api.querySelector('.api-name').textContent;
        api.onclick = () => openAPIDetails(apiName);
    });
    openModal('apiModal');
}

function testAPI(apiName) {
    // Simulate API test
    const success = Math.random() > 0.2; // 80% success rate

    if (success) {
        addLogEntry(`API test successful: ${apiName}`, 'success');
        showNotification(`${apiName} test passed!`, 'success');
    } else {
        addLogEntry(`API test failed: ${apiName}`, 'error');
        showNotification(`${apiName} test failed!`, 'error');
    }
}

// Notification System
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <i class="fas fa-times close-notification"></i>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);

    // Close button
    notification.querySelector('.close-notification').onclick = () => {
        notification.parentNode.removeChild(notification);
    };
}

// Additional Functions
function testAllConnections() {
    const systems = document.querySelectorAll('.system-item');
    systems.forEach(system => {
        const systemName = system.querySelector('.system-name').textContent.trim();
        setTimeout(() => {
            system.classList.add('testing');
            setTimeout(() => {
                system.classList.remove('testing');
                system.classList.add('online');
                addLogEntry(`Connection test passed: ${systemName}`, 'success');
            }, 1000);
        }, Math.random() * 2000);
    });

    showNotification('Testing all system connections...', 'info');
}

function runHealthCheck() {
    showNotification('Running comprehensive health check...', 'info');

    // Simulate health check
    setTimeout(() => {
        const issues = Math.random() > 0.7 ? 1 : 0; // 30% chance of issues
        if (issues) {
            addLogEntry('Health check completed with warnings', 'warning');
            showNotification('Health check completed with warnings', 'warning');
        } else {
            addLogEntry('Health check completed successfully', 'success');
            showNotification('Health check passed! All systems operational', 'success');
        }
    }, 3000);
}

function generateComplianceReport() {
    showNotification('Generating compliance report...', 'info');

    // Simulate report generation
    setTimeout(() => {
        const reportContent = `
            Compliance Report
            =================
            Date: ${new Date().toLocaleDateString()}

            HIPAA Compliance: ✓ Passed
            CJIS Certification: ✓ Passed
            GDPR Compliance: ✓ Passed
            Data Encryption: AES-256 ✓
            Security Audit: Pending

            Systems Status:
            - Public Health Database: Online
            - Police CAD System: Online
            - Hospital EHR: Maintenance
            - Emergency Services: Online
        `;

        const blob = new Blob([reportContent], {type: 'text/plain'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `compliance-report-${new Date().toISOString().split('T')[0]}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        addLogEntry('Compliance report generated and downloaded', 'success');
        showNotification('Compliance report downloaded!', 'success');
    }, 2000);
}

// Initialize on DOM Load
document.addEventListener('DOMContentLoaded', function() {
    // Set active navigation
    const currentPage = 'Health-Police-Integration.html';
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    // Initialize table
    renderIntegrationsTable();

    // Setup search functionality
    const searchInput = document.querySelector('.search-box input');
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter' || this.value.length >= 3) {
            searchIntegrations(this.value);
        }
    });

    // Setup filter items
    document.querySelectorAll('.filter-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.filter-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            filterIntegrations(this.textContent);
        });
    });

    // Setup system items for click events
    document.querySelectorAll('.system-item').forEach(item => {
        item.addEventListener('click', function() {
            const systemName = this.querySelector('.system-name').textContent.trim();
            toggleSystemStatus(systemName);
        });
    });

    // Setup API management
    document.querySelectorAll('.api-item').forEach(api => {
        api.addEventListener('click', function() {
            const apiName = this.querySelector('.api-name').textContent;
            openAPIDetails(apiName);
        });
    });

    // Setup quick actions
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.querySelector('span').textContent;
            // Actions are handled by onclick in HTML
        });
    });

    // Setup log items
    document.querySelectorAll('.log-item').forEach(item => {
        item.addEventListener('click', function() {
            const details = this.textContent.trim();
            openLogDetails(details);
        });
    });

    // Setup connection visual nodes
    document.querySelectorAll('.connection-node').forEach(node => {
        node.addEventListener('click', function() {
            const systemType = this.querySelector('i').className.includes('heartbeat') ? 'Health' :
                              this.querySelector('i').className.includes('shield-alt') ? 'Security' : 'Emergency';
            showNotification(`${systemType} system node clicked`, 'info');
        });
    });

    // User profile click
    document.querySelector('.user-profile').addEventListener('click', function() {
        openUserProfile();
    });
});


// Search functionality
function searchIntegrations(query) {
    if (query.length < 1) {
        renderIntegrationsTable();
        return;
    }

    const filtered = integrationsData.filter(integration =>
        integration.name.toLowerCase().includes(query.toLowerCase()) ||
        integration.system.toLowerCase().includes(query.toLowerCase()) ||
        integration.type.toLowerCase().includes(query.toLowerCase())
    );

    renderFilteredIntegrations(filtered);
    addLogEntry(`Searched for: "${query}"`, 'info');
}

function renderFilteredIntegrations(filteredData) {
    const tableBody = document.querySelector('.integration-table tbody');
    tableBody.innerHTML = '';

    filteredData.forEach(integration => {
        // Same rendering logic as renderIntegrationsTable
        const row = document.createElement('tr');
        // ... row creation code ...
        tableBody.appendChild(row);
    });
}

// Integration-specific actions
function syncIntegration(id) {
    const integration = integrationsData.find(item => item.id === id);
    if (integration) {
        integration.lastUpdated = "Just now";
        renderIntegrationsTable();
        addLogEntry(`Manual sync triggered for: ${integration.name}`, 'info');
        showNotification(`Syncing ${integration.name}...`, 'info');

        // Simulate sync delay
        setTimeout(() => {
            showNotification(`${integration.name} sync completed!`, 'success');
        }, 1500);
    }
}

function monitorIntegration(id) {
    const integration = integrationsData.find(item => item.id === id);
    if (integration) {
        openModal('monitorModal');
        // Load monitoring data for this integration
        showNotification(`Opening monitoring for ${integration.name}`, 'info');
    }
}

// View log details
function viewLogDetails(message, timestamp) {
    alert(`Log Details:

Time: ${timestamp}
Message: ${message}

Click OK to copy to clipboard.`);
    navigator.clipboard.writeText(`${timestamp} - ${message}`);
    showNotification('Log details copied to clipboard', 'info');
}

// Open API details
function openAPIDetails(apiName) {
    const modal = document.getElementById('apiModal');
    const apiDetails = modal.querySelector('.api-details-container');

    let details = '';
    switch(apiName) {
        case 'Health Data API':
            details = `
                <h4>Health Data API Details</h4>
                <p>Version: v2.1</p>
                <p>Endpoint: /api/v2/health/data</p>
                <p>Authentication: OAuth 2.0</p>
                <button class="api-test-btn" onclick="testAPI('${apiName}')">Test Connection</button>
            `;
            break;
        case 'Police Incident API':
            details = `
                <h4>Police Incident API Details</h4>
                <p>Version: v1.4</p>
                <p>Endpoint: /api/v1/police/incidents</p>
                <p>Authentication: API Key + IP Whitelist</p>
                <button class="api-test-btn" onclick="testAPI('${apiName}')">Test Connection</button>
            `;
            break;
        case 'Emergency Alert API':
            details = `
                <h4>Emergency Alert API Details</h4>
                <p>Version: v3.0</p>
                <p>Endpoint: /api/v3/emergency/alerts</p>
                <p>Authentication: Certificate-based</p>
                <button class="api-test-btn" onclick="testAPI('${apiName}')">Test Connection</button>
            `;
            break;
    }

    apiDetails.innerHTML = details;
    openModal('apiModal');
}

// User Profile
function openUserProfile() {
    const userMenu = document.createElement('div');
    userMenu.className = 'user-menu';
    userMenu.innerHTML = `
        <div class="user-menu-item" onclick="editProfile()">Edit Profile</div>
        <div class="user-menu-item" onclick="changePassword()">Change Password</div>
        <div class="user-menu-item" onclick="logout()">Logout</div>
    `;

    document.body.appendChild(userMenu);

    // Position near user profile
    const profile = document.querySelector('.user-profile');
    const rect = profile.getBoundingClientRect();
    userMenu.style.position = 'absolute';
    userMenu.style.top = (rect.bottom + 5) + 'px';
    userMenu.style.right = (window.innerWidth - rect.right) + 'px';

    // Remove on outside click
    setTimeout(() => {
        document.addEventListener('click', function removeMenu(e) {
            if (!userMenu.contains(e.target) && e.target !== profile) {
                userMenu.remove();
                document.removeEventListener('click', removeMenu);
            }
        });
    }, 100);
}

function editProfile() {
    showNotification('Profile editor would open here', 'info');
}

function changePassword() {
    showNotification('Password change dialog would open', 'info');
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        showNotification('Logging out...', 'info');
        setTimeout(() => {
            window.location.href = '/LGU4/login.php';
        }, 1000);
    }
}