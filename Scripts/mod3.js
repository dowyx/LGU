// Target Group Segmentation JavaScript with full functionality
let segmentsData = [];
let demographicsData = [];

const API_BASE_URL = 'http://localhost:3000/api';

// Initialize the page
document.addEventListener('DOMContentLoaded', async function () {
    setActiveNavigation();
    await loadSegmentsData();
    await loadDemographicsData();
    setupEventListeners();
    renderSegmentsTable();
    renderDemographicsChart();
    updateSegmentStats();
});

// Set active navigation
function setActiveNavigation() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref.includes(currentPage)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Load segments data from API
async function loadSegmentsData() {
    try {
        const response = await fetch(`${API_BASE_URL}/segments`);
        if (response.ok) {
            segmentsData = await response.json();
        } else {
            console.error('Failed to load segments data');
            segmentsData = [];
        }
    } catch (error) {
        console.error('Error loading segments:', error);
        segmentsData = [];
    }
}

// Load demographics data
async function loadDemographicsData() {
    try {
        const response = await fetch(`${API_BASE_URL}/demographics`);
        if (response.ok) {
            demographicsData = await response.json();
        } else {
            console.error('Failed to load demographics data');
            demographicsData = [];
        }
    } catch (error) {
        console.error('Error loading demographics:', error);
        demographicsData = [];
    }
}

// Setup event listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function (e) {
            if (e.key === 'Enter') {
                searchSegments(this.value);
            }
        });
    }

    // Filter items functionality
    const filterItems = document.querySelectorAll('.filter-item');
    filterItems.forEach(item => {
        item.addEventListener('click', function () {
            filterItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            applyFilter(this.textContent.trim());
        });
    });

    // Quick action buttons
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.querySelector('span').textContent;
            switch (action) {
                case 'New Segment':
                    createNewSegment();
                    break;
                case 'Import Data':
                    openImportModal();
                    break;
                case 'Analytics':
                    openAnalyticsModal();
                    break;
                case 'Export':
                    openExportModal();
                    break;
            }
        });
    });
}

// Enhanced functions
function createNewSegment() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Segment</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="segmentForm">
                    <div class="form-group">
                        <label for="segmentName">Segment Name</label>
                        <input type="text" id="segmentName" required>
                    </div>
                    <div class="form-group">
                        <label for="segmentType">Segment Type</label>
                        <select id="segmentType">
                            <option value="demographic">Demographic</option>
                            <option value="geographic">Geographic</option>
                            <option value="behavioral">Behavioral</option>
                            <option value="psychographic">Psychographic</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="segmentDescription">Description</label>
                        <textarea id="segmentDescription" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Target Criteria</label>
                        <div class="criteria-grid">
                            <div class="criteria-item">
                                <label for="ageRange">Age Range</label>
                                <input type="text" id="ageRange" placeholder="e.g., 18-35">
                            </div>
                            <div class="criteria-item">
                                <label for="location">Location</label>
                                <input type="text" id="location" placeholder="e.g., Manila, Cebu">
                            </div>
                            <div class="criteria-item">
                                <label for="interests">Interests</label>
                                <input type="text" id="interests" placeholder="e.g., Technology, Sports">
                            </div>
                            <div class="criteria-item">
                                <label for="income">Income Level</label>
                                <select id="income">
                                    <option value="">Select Income Level</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="estimatedSize">Estimated Size</label>
                        <input type="number" id="estimatedSize" placeholder="Number of people">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn">Create Segment</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const form = modal.querySelector('#segmentForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await saveSegment();
        modal.remove();
    });

    setupModalClose(modal);
}

function openImportModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Import Segment Data</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="importForm">
                    <div class="form-group">
                        <label for="importFile">Select File</label>
                        <input type="file" id="importFile" accept=".csv,.xlsx,.json" required>
                    </div>
                    <div class="form-group">
                        <label for="importType">Import Type</label>
                        <select id="importType">
                            <option value="csv">CSV File</option>
                            <option value="excel">Excel File</option>
                            <option value="json">JSON File</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="overwriteData"> Overwrite existing data
                        </label>
                    </div>
                    <div class="import-preview" id="importPreview">
                        <h4>Preview</h4>
                        <div id="previewContent">
                            <!-- File preview will appear here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn">Import Data</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const fileInput = modal.querySelector('#importFile');
    const previewContent = modal.querySelector('#previewContent');

    fileInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            previewContent.innerHTML = `
                <p><strong>File:</strong> ${file.name}</p>
                <p><strong>Size:</strong> ${formatBytes(file.size)}</p>
                <p><strong>Type:</strong> ${file.type}</p>
            `;
        }
    });

    const form = modal.querySelector('#importForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await importData();
        modal.remove();
    });

    setupModalClose(modal);
}

function openAnalyticsModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Segment Analytics</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="analytics-dashboard">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <h4>Total Segments</h4>
                            <div class="stat-value">${segmentsData.length}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Active Segments</h4>
                            <div class="stat-value">${segmentsData.filter(s => s.status === 'active').length}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Total Reach</h4>
                            <div class="stat-value">${segmentsData.reduce((sum, s) => sum + (s.estimatedSize || 0), 0)}</div>
                        </div>
                        <div class="stat-card">
                            <h4>Avg. Segment Size</h4>
                            <div class="stat-value">${Math.round(segmentsData.reduce((sum, s) => sum + (s.estimatedSize || 0), 0) / segmentsData.length)}</div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <h4>Segment Distribution</h4>
                        <canvas id="segmentChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h4>Demographics Breakdown</h4>
                        <canvas id="demographicsChart"></canvas>
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

function openExportModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Export Segment Data</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="form-group">
                        <label>Export Format</label>
                        <div class="radio-group">
                            <label><input type="radio" name="exportFormat" value="csv" checked> CSV</label>
                            <label><input type="radio" name="exportFormat" value="excel"> Excel</label>
                            <label><input type="radio" name="exportFormat" value="json"> JSON</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Export Options</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="includeDemographics" checked> Include Demographics</label>
                            <label><input type="checkbox" name="includeAnalytics" checked> Include Analytics</label>
                            <label><input type="checkbox" name="includeCriteria" checked> Include Criteria</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="segmentFilter">Filter by Segment Type</label>
                        <select id="segmentFilter">
                            <option value="">All Segments</option>
                            <option value="demographic">Demographic</option>
                            <option value="geographic">Geographic</option>
                            <option value="behavioral">Behavioral</option>
                            <option value="psychographic">Psychographic</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn">Export Data</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const form = modal.querySelector('#exportForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await exportData();
        modal.remove();
    });

    setupModalClose(modal);
}

// CRUD Operations
async function saveSegment() {
    const segmentData = {
        name: document.querySelector('#segmentName').value,
        type: document.querySelector('#segmentType').value,
        description: document.querySelector('#segmentDescription').value,
        criteria: {
            ageRange: document.querySelector('#ageRange').value,
            location: document.querySelector('#location').value,
            interests: document.querySelector('#interests').value,
            income: document.querySelector('#income').value
        },
        estimatedSize: document.querySelector('#estimatedSize').value,
        status: 'active'
    };

    try {
        const response = await fetch(`${API_BASE_URL}/segments`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(segmentData)
        });

        if (response.ok) {
            showNotification('Segment created successfully!', 'success');
            await loadSegmentsData();
            renderSegmentsTable();
            updateSegmentStats();
        } else {
            showNotification('Failed to create segment', 'error');
        }
    } catch (error) {
        console.error('Create segment error:', error);
        showNotification('Error creating segment', 'error');
    }
}

async function importData() {
    const fileInput = document.querySelector('#importFile');
    const file = fileInput.files[0];

    if (!file) {
        showNotification('Please select a file to import', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', document.querySelector('#importType').value);
    formData.append('overwrite', document.querySelector('#overwriteData').checked);

    try {
        const response = await fetch(`${API_BASE_URL}/segments/import`, {
            method: 'POST',
            body: formData
        });

        if (response.ok) {
            const result = await response.json();
            showNotification(`Successfully imported ${result.count} segments!`, 'success');
            await loadSegmentsData();
            renderSegmentsTable();
            updateSegmentStats();
        } else {
            showNotification('Failed to import data', 'error');
        }
    } catch (error) {
        console.error('Import error:', error);
        showNotification('Error importing data', 'error');
    }
}

async function exportData() {
    const format = document.querySelector('input[name="exportFormat"]:checked').value;
    const includeDemographics = document.querySelector('input[name="includeDemographics"]').checked;
    const includeAnalytics = document.querySelector('input[name="includeAnalytics"]').checked;
    const includeCriteria = document.querySelector('input[name="includeCriteria"]').checked;
    const segmentFilter = document.querySelector('#segmentFilter').value;

    const exportOptions = {
        format,
        includeDemographics,
        includeAnalytics,
        includeCriteria,
        segmentFilter
    };

    try {
        const response = await fetch(`${API_BASE_URL}/segments/export`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(exportOptions)
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `segments_export.${format}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            showNotification('Data exported successfully!', 'success');
        } else {
            showNotification('Failed to export data', 'error');
        }
    } catch (error) {
        console.error('Export error:', error);
        showNotification('Error exporting data', 'error');
    }
}

// Rendering functions
function renderSegmentsTable(filteredData = segmentsData) {
    const tableBody = document.querySelector('.segments-table tbody');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    filteredData.forEach(segment => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="segment-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">${escapeHtml(segment.name)}</div>
                        <div style="font-size: 12px; color: var(--text-gray);">${segment.type}</div>
                    </div>
                </div>
            </td>
            <td>${segment.estimatedSize || 0}</td>
            <td><span class="status-badge status-${segment.status}">${capitalizeFirst(segment.status)}</span></td>
            <td>${segment.criteria?.location || 'N/A'}</td>
            <td>${segment.criteria?.ageRange || 'N/A'}</td>
            <td>
                <div class="action-icons">
                    <i class="fas fa-eye" title="View" onclick="viewSegment(${segment.id})"></i>
                    <i class="fas fa-edit" title="Edit" onclick="editSegment(${segment.id})"></i>
                    <i class="fas fa-trash" title="Delete" onclick="deleteSegment(${segment.id})"></i>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

function renderDemographicsChart() {
    const chartContainer = document.querySelector('.demographics-chart');
    if (!chartContainer) return;

    // Simple chart rendering
    const demographics = {
        '18-24': 25,
        '25-34': 35,
        '35-44': 20,
        '45-54': 15,
        '55+': 5
    };

    chartContainer.innerHTML = `
        <div class="chart-bars">
            ${Object.entries(demographics).map(([age, percentage]) => `
                <div class="chart-bar">
                    <div class="bar-fill" style="height: ${percentage * 2}px;"></div>
                    <div class="bar-label">${age}</div>
                    <div class="bar-value">${percentage}%</div>
                </div>
            `).join('')}
        </div>
    `;
}

function updateSegmentStats() {
    const totalSegments = segmentsData.length;
    const activeSegments = segmentsData.filter(s => s.status === 'active').length;
    const totalReach = segmentsData.reduce((sum, s) => sum + (s.estimatedSize || 0), 0);
    const avgSize = totalSegments > 0 ? Math.round(totalReach / totalSegments) : 0;

    // Update stats in dashboard
    const statValues = document.querySelectorAll('.stat-value');
    if (statValues.length >= 4) {
        statValues[0].textContent = totalSegments;
        statValues[1].textContent = activeSegments;
        statValues[2].textContent = totalReach.toLocaleString();
        statValues[3].textContent = avgSize.toLocaleString();
    }
}

// Search and filter functions
async function searchSegments(query) {
    if (!query.trim()) {
        renderSegmentsTable();
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/segments/search/${encodeURIComponent(query)}`);
        if (response.ok) {
            const results = await response.json();
            renderSegmentsTable(results);
        }
    } catch (error) {
        console.error('Search error:', error);
    }
}

function applyFilter(filterType) {
    let filtered = [...segmentsData];

    if (filterType !== 'All') {
        filtered = filtered.filter(segment => segment.type === filterType.toLowerCase());
    }

    renderSegmentsTable(filtered);
}

// Helper functions
function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalizeFirst(string) {
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
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <i class="fas fa-times close-notification"></i>
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);

    // Close on click
    notification.querySelector('.close-notification').addEventListener('click', () => {
        notification.remove();
    });
}

// Additional CRUD functions
function viewSegment(id) {
    const segment = segmentsData.find(s => s.id === id);
    if (!segment) return;

    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Segment Details</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="segment-details">
                    <h4>${escapeHtml(segment.name)}</h4>
                    <p><strong>Type:</strong> ${capitalizeFirst(segment.type)}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${segment.status}">${capitalizeFirst(segment.status)}</span></p>
                    <p><strong>Estimated Size:</strong> ${(segment.estimatedSize || 0).toLocaleString()}</p>
                    <p><strong>Description:</strong></p>
                    <p>${escapeHtml(segment.description)}</p>
                    <p><strong>Target Criteria:</strong></p>
                    <ul>
                        <li><strong>Age Range:</strong> ${segment.criteria?.ageRange || 'N/A'}</li>
                        <li><strong>Location:</strong> ${segment.criteria?.location || 'N/A'}</li>
                        <li><strong>Interests:</strong> ${segment.criteria?.interests || 'N/A'}</li>
                        <li><strong>Income Level:</strong> ${capitalizeFirst(segment.criteria?.income || 'N/A')}</li>
                    </ul>
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

function editSegment(id) {
    const segment = segmentsData.find(s => s.id === id);
    if (!segment) return;

    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Segment</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editSegmentForm">
                    <div class="form-group">
                        <label for="editSegmentName">Segment Name</label>
                        <input type="text" id="editSegmentName" value="${escapeHtml(segment.name)}" required>
                    </div>
                    <div class="form-group">
                        <label for="editSegmentType">Segment Type</label>
                        <select id="editSegmentType">
                            <option value="demographic" ${segment.type === 'demographic' ? 'selected' : ''}>Demographic</option>
                            <option value="geographic" ${segment.type === 'geographic' ? 'selected' : ''}>Geographic</option>
                            <option value="behavioral" ${segment.type === 'behavioral' ? 'selected' : ''}>Behavioral</option>
                            <option value="psychographic" ${segment.type === 'psychographic' ? 'selected' : ''}>Psychographic</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editSegmentDescription">Description</label>
                        <textarea id="editSegmentDescription" rows="4" required>${escapeHtml(segment.description)}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Target Criteria</label>
                        <div class="criteria-grid">
                            <div class="criteria-item">
                                <label for="editAgeRange">Age Range</label>
                                <input type="text" id="editAgeRange" value="${segment.criteria?.ageRange || ''}" placeholder="e.g., 18-35">
                            </div>
                            <div class="criteria-item">
                                <label for="editLocation">Location</label>
                                <input type="text" id="editLocation" value="${segment.criteria?.location || ''}" placeholder="e.g., Manila, Cebu">
                            </div>
                            <div class="criteria-item">
                                <label for="editInterests">Interests</label>
                                <input type="text" id="editInterests" value="${segment.criteria?.interests || ''}" placeholder="e.g., Technology, Sports">
                            </div>
                            <div class="criteria-item">
                                <label for="editIncome">Income Level</label>
                                <select id="editIncome">
                                    <option value="">Select Income Level</option>
                                    <option value="low" ${segment.criteria?.income === 'low' ? 'selected' : ''}>Low</option>
                                    <option value="medium" ${segment.criteria?.income === 'medium' ? 'selected' : ''}>Medium</option>
                                    <option value="high" ${segment.criteria?.income === 'high' ? 'selected' : ''}>High</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editEstimatedSize">Estimated Size</label>
                        <input type="number" id="editEstimatedSize" value="${segment.estimatedSize || 0}" placeholder="Number of people">
                    </div>
                    <div class="form-group">
                        <label for="editSegmentStatus">Status</label>
                        <select id="editSegmentStatus">
                            <option value="active" ${segment.status === 'active' ? 'selected' : ''}>Active</option>
                            <option value="inactive" ${segment.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                            <option value="archived" ${segment.status === 'archived' ? 'selected' : ''}>Archived</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const form = modal.querySelector('#editSegmentForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await updateSegment(id);
        modal.remove();
    });

    setupModalClose(modal);
}

async function updateSegment(id) {
    const segmentData = {
        name: document.querySelector('#editSegmentName').value,
        type: document.querySelector('#editSegmentType').value,
        description: document.querySelector('#editSegmentDescription').value,
        criteria: {
            ageRange: document.querySelector('#editAgeRange').value,
            location: document.querySelector('#editLocation').value,
            interests: document.querySelector('#editInterests').value,
            income: document.querySelector('#editIncome').value
        },
        estimatedSize: document.querySelector('#editEstimatedSize').value,
        status: document.querySelector('#editSegmentStatus').value
    };

    try {
        const response = await fetch(`${API_BASE_URL}/segments/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(segmentData)
        });

        if (response.ok) {
            showNotification('Segment updated successfully!', 'success');
            await loadSegmentsData();
            renderSegmentsTable();
            updateSegmentStats();
        } else {
            showNotification('Failed to update segment', 'error');
        }
    } catch (error) {
        console.error('Update segment error:', error);
        showNotification('Error updating segment', 'error');
    }
}

async function deleteSegment(id) {
    if (!confirm('Are you sure you want to delete this segment?')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/segments/${id}`, {
            method: 'DELETE'
        });

        if (response.ok) {
            showNotification('Segment deleted successfully!', 'success');
            await loadSegmentsData();
            renderSegmentsTable();
            updateSegmentStats();
        } else {
            showNotification('Failed to delete segment', 'error');
        }
    } catch (error) {
        console.error('Delete segment error:', error);
        showNotification('Error deleting segment', 'error');
    }
}

// Legacy code removed to prevent automatic popup
// Old implementation with alerts was causing unintended behavior


// Target Group Segmentation JavaScript with CRUD Operations
let segments = [
    {
        id: 1,
        name: "High-Risk Population",
        description: "Chronic conditions, elderly",
        type: "demographic",
        size: 12847,
        engagementRate: 92,
        lastUpdated: "Jul 15, 2024",
        status: "active",
        tags: ["Age 65+", "Chronic Conditions", "High Priority"],
        color: "danger"
    },
    {
        id: 2,
        name: "College Students",
        description: "University campuses",
        type: "demographic",
        size: 15782,
        engagementRate: 78,
        lastUpdated: "Jul 14, 2024",
        status: "active",
        tags: ["Education", "Campus", "Young Adults"],
        color: "success"
    },
    {
        id: 3,
        name: "Past Campaign Responders",
        description: "High engagement history",
        type: "behavioral",
        size: 8452,
        engagementRate: 95,
        lastUpdated: "Jul 13, 2024",
        status: "active",
        tags: ["High Engagement", "Responders"],
        color: "accent"
    }
];

// Segment creation modal HTML
const segmentModalHTML = `
<div class="modal-overlay" id="segmentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Create New Segment</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="segmentForm">
                <div class="form-group">
                    <label for="segmentName">Segment Name</label>
                    <input type="text" id="segmentName" required placeholder="Enter segment name">
                </div>
                <div class="form-group">
                    <label for="segmentDescription">Description</label>
                    <textarea id="segmentDescription" rows="3" placeholder="Enter segment description"></textarea>
                </div>
                <div class="form-group">
                    <label>Segment Type</label>
                    <div class="radio-group">
                        <label><input type="radio" name="segmentType" value="demographic" checked> Demographic</label>
                        <label><input type="radio" name="segmentType" value="behavioral"> Behavioral</label>
                        <label><input type="radio" name="segmentType" value="geographic"> Geographic</label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="segmentSize">Segment Size</label>
                    <input type="number" id="segmentSize" min="1" value="1000">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="segmentStatus">
                        <option value="active">Active</option>
                        <option value="draft">Draft</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="segmentTags">Tags (comma separated)</label>
                    <input type="text" id="segmentTags" placeholder="tag1, tag2, tag3">
                </div>
                <div class="criteria-section">
                    <h4>Select Criteria</h4>
                    <div class="criteria-grid" id="criteriaContainer">
                        <!-- Criteria checkboxes will be populated here -->
                    </div>
                </div>
                <input type="hidden" id="segmentId">
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn" id="submitSegmentBtn" onclick="saveSegment()">Save Segment</button>
        </div>
    </div>
</div>
`;

// Confirmation modal HTML
const confirmModalHTML = `
<div class="modal-overlay" id="confirmModal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 id="confirmTitle">Confirm Action</h3>
            <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmActionBtn">Confirm</button>
        </div>
    </div>
</div>
`;

// Criteria options
const criteriaOptions = {
    demographic: [
        "Age Range",
        "Location (City/District)",
        "Language Preference",
        "Education Level",
        "Occupation",
        "Income Level",
        "Marital Status"
    ],
    behavioral: [
        "Past Campaign Engagement",
        "Response History",
        "Preferred Communication Channels",
        "Service Usage Patterns",
        "Website Visits",
        "App Usage"
    ],
    geographic: [
        "City",
        "State/Region",
        "Country",
        "Urban/Rural",
        "Proximity to Facilities"
    ]
};

// Initialize application
document.addEventListener('DOMContentLoaded', function () {
    // Add modals to DOM
    document.body.insertAdjacentHTML('beforeend', segmentModalHTML);
    document.body.insertAdjacentHTML('beforeend', confirmModalHTML);

    // Add modal styles
    addModalStyles();
    
    // Add additional styles for new features
    addEnhancedStyles();

    // Initialize page
    setActiveNavigation();
    setupEventListeners();
    updateSegmentLibrary();
    updateSegmentTable();
    initializeFilters();
    
    // Initialize advanced features
    initializeAdvancedFeatures();
});

function addEnhancedStyles() {
    const style = document.createElement('style');
    style.textContent = `
        /* Bulk Actions */
        .bulk-actions {
            display: none;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .bulk-actions .btn {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .bulk-actions .btn i {
            margin-right: 5px;
        }
        
        /* Segment Comparison */
        .comparison-table {
            overflow-x: auto;
        }
        
        .segment-comparison {
            width: 100%;
            border-collapse: collapse;
        }
        
        .segment-comparison th, .segment-comparison td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .segment-comparison th {
            background-color: var(--dark-gray);
            font-weight: 600;
        }
        
        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .metric-card {
            background-color: var(--dark-gray);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .metric-desc {
            font-size: 12px;
            color: var(--text-gray);
        }
        
        /* Analytics Section */
        .analytics-section {
            margin-bottom: 20px;
        }
        
        .analytics-section h4 {
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid var(--dark-gray);
        }
        
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        /* Recommendations */
        .recommendations-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .recommendation-item {
            padding: 10px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .recommendation-item.high {
            background-color: rgba(244, 67, 54, 0.15);
            border-left: 4px solid var(--danger);
        }
        
        .recommendation-item.medium {
            background-color: rgba(255, 152, 0, 0.15);
            border-left: 4px solid var(--warning);
        }
        
        .recommendation-item.low {
            background-color: rgba(76, 175, 80, 0.15);
            border-left: 4px solid var(--success);
        }
        
        .recommendation-item i {
            font-size: 16px;
        }
        
        /* Bulk Update Form */
        .bulk-update-form {
            margin: 15px 0;
        }
        
        .bulk-update-form .form-group {
            margin-bottom: 15px;
        }
        
        .bulk-update-form label {
            display: block;
            margin-bottom: 5px;
        }
        
        .bulk-update-form input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .bulk-update-form select {
            margin-left: 10px;
            padding: 5px;
            background-color: var(--dark-gray);
            border: 1px solid var(--medium-gray);
            color: var(--white);
            border-radius: 4px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .segment-comparison {
                font-size: 12px;
            }
            
            .segment-comparison th, .segment-comparison td {
                padding: 8px 5px;
            }
        }
    `;
    document.head.appendChild(style);
}

function addModalStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-content {
            background-color: var(--secondary-black);
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--dark-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--white);
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
            border-radius: 50%;
        }

        .modal-close:hover {
            background-color: var(--dark-gray);
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--dark-gray);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--white);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            background-color: var(--dark-gray);
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            color: var(--white);
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .criteria-section {
            margin-top: 25px;
        }

        .criteria-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .criteria-grid {
                grid-template-columns: 1fr;
            }
        }

        .criteria-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background-color: var(--dark-gray);
            border-radius: 6px;
            cursor: pointer;
        }

        .criteria-checkbox:hover {
            background-color: var(--medium-gray);
        }

        .criteria-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
        }
    `;
    document.head.appendChild(style);
}

function setActiveNavigation() {
    const currentPage = 'Target-Group-Segmentation.html';
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

function setupEventListeners() {
    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    searchInput.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            searchSegments(this.value);
        }
    });

    // Create segment button
    const createBtn = document.querySelector('.module-header .btn');
    createBtn.onclick = function () {
        openCreateModal();
    };

    // Build segment button
    const buildBtn = document.querySelector('.segment-builder .btn-success');
    buildBtn.addEventListener('click', function () {
        const selectedCriteria = getSelectedCriteria();
        if (selectedCriteria.length > 0) {
            buildSegmentFromCriteria(selectedCriteria);
        } else {
            alert('Please select at least one criteria');
        }
    });

    // Quick segmentation buttons
    const quickSegButtons = document.querySelectorAll('.module-card:last-child .btn');
    quickSegButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const segmentType = this.textContent.trim();
            createQuickSegment(segmentType);
        });
    });

    // Optimize channels button
    const optimizeBtn = document.querySelector('.btn-secondary[style*="Optimize"]');
    if (optimizeBtn) {
        optimizeBtn.addEventListener('click', optimizeChannels);
    }
    
    // Add bulk actions container if it doesn't exist
    const tableContainer = document.querySelector('.segment-table-container');
    if (tableContainer && !document.querySelector('.bulk-actions')) {
        const bulkActions = document.createElement('div');
        bulkActions.className = 'bulk-actions';
        bulkActions.style.display = 'none';
        tableContainer.insertBefore(bulkActions, tableContainer.firstChild);
    }
    
    // Add checkbox header to table if it doesn't exist
    const tableHeader = document.querySelector('.segment-table thead tr');
    if (tableHeader && !tableHeader.querySelector('input[type="checkbox"]')) {
        const checkboxHeader = document.createElement('th');
        checkboxHeader.innerHTML = '<input type="checkbox" title="Select all segments">';
        tableHeader.insertBefore(checkboxHeader, tableHeader.firstChild);
    }
}

function openCreateModal(segmentId = null) {
    const modal = document.getElementById('segmentModal');
    const form = document.getElementById('segmentForm');
    const title = document.getElementById('modalTitle');
    const submitBtn = document.getElementById('submitSegmentBtn');

    if (segmentId) {
        // Edit mode
        const segment = segments.find(s => s.id === segmentId);
        if (segment) {
            title.textContent = 'Edit Segment';
            submitBtn.textContent = 'Update Segment';
            document.getElementById('segmentId').value = segment.id;
            document.getElementById('segmentName').value = segment.name;
            document.getElementById('segmentDescription').value = segment.description || '';
            document.querySelector(`input[name="segmentType"][value="${segment.type}"]`).checked = true;
            document.getElementById('segmentSize').value = segment.size;
            document.getElementById('segmentStatus').value = segment.status;
            document.getElementById('segmentTags').value = segment.tags?.join(', ') || '';

            // Update criteria based on type
            updateCriteriaOptions(segment.type);
        }
    } else {
        // Create mode
        title.textContent = 'Create New Segment';
        submitBtn.textContent = 'Save Segment';
        form.reset();
        document.getElementById('segmentId').value = '';
        updateCriteriaOptions('demographic');
    }

    modal.style.display = 'flex';
    document.getElementById('segmentName').focus();
}

function updateCriteriaOptions(type) {
    const container = document.getElementById('criteriaContainer');
    const options = criteriaOptions[type] || [];

    container.innerHTML = '';
    options.forEach(criteria => {
        const checkbox = document.createElement('div');
        checkbox.className = 'criteria-checkbox';
        checkbox.innerHTML = `
            <input type="checkbox" id="crit_${criteria.replace(/\s+/g, '_')}">
            <label for="crit_${criteria.replace(/\s+/g, '_')}">${criteria}</label>
        `;
        container.appendChild(checkbox);
    });
}

function closeModal() {
    document.getElementById('segmentModal').style.display = 'none';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function saveSegment() {
    const form = document.getElementById('segmentForm');
    const segmentId = document.getElementById('segmentId').value;
    const name = document.getElementById('segmentName').value.trim();

    if (!name) {
        alert('Segment name is required');
        return;
    }

    const segmentData = {
        id: segmentId ? parseInt(segmentId) : Date.now(),
        name: name,
        description: document.getElementById('segmentDescription').value.trim(),
        type: document.querySelector('input[name="segmentType"]:checked').value,
        size: parseInt(document.getElementById('segmentSize').value) || 1000,
        engagementRate: Math.floor(Math.random() * 30) + 70, // Random 70-100%
        lastUpdated: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
        status: document.getElementById('segmentStatus').value,
        tags: document.getElementById('segmentTags').value.split(',').map(tag => tag.trim()).filter(tag => tag),
        color: getRandomColor()
    };

    if (segmentId) {
        // Update existing segment
        const index = segments.findIndex(s => s.id === parseInt(segmentId));
        if (index !== -1) {
            segments[index] = { ...segments[index], ...segmentData };
            showNotification('Segment updated successfully!');
        }
    } else {
        // Add new segment
        segments.unshift(segmentData);
        showNotification('Segment created successfully!');
    }

    closeModal();
    updateSegmentLibrary();
    updateSegmentTable();
}

function deleteSegment(segmentId) {
    openConfirmModal(
        'Delete Segment',
        'Are you sure you want to delete this segment? This action cannot be undone.',
        function () {
            segments = segments.filter(s => s.id !== segmentId);
            showNotification('Segment deleted successfully!');
            updateSegmentLibrary();
            updateSegmentTable();
            closeConfirmModal();
        }
    );
}

function duplicateSegment(segmentId) {
    const segment = segments.find(s => s.id === segmentId);
    if (segment) {
        const newSegment = {
            ...segment,
            id: Date.now(),
            name: `${segment.name} (Copy)`,
            size: Math.floor(segment.size * 0.5),
            lastUpdated: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
            status: 'draft'
        };
        segments.unshift(newSegment);
        showNotification('Segment duplicated successfully!');
        updateSegmentLibrary();
        updateSegmentTable();
    }
}

function getSelectedCriteria() {
    const criteria = [];
    document.querySelectorAll('#criteriaContainer input[type="checkbox"]:checked').forEach(cb => {
        criteria.push(cb.parentElement.querySelector('label').textContent);
    });
    return criteria;
}

function buildSegmentFromCriteria(criteria) {
    const segmentName = prompt('Enter a name for the new segment:', `Segment based on ${criteria[0]}`);
    if (segmentName) {
        const newSegment = {
            id: Date.now(),
            name: segmentName,
            description: `Created from criteria: ${criteria.join(', ')}`,
            type: determineSegmentType(criteria),
            size: Math.floor(Math.random() * 50000) + 1000,
            engagementRate: Math.floor(Math.random() * 30) + 70,
            lastUpdated: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
            status: 'active',
            tags: criteria,
            color: getRandomColor()
        };
        segments.unshift(newSegment);
        showNotification('Segment built successfully!');
        updateSegmentLibrary();
        updateSegmentTable();
    }
}

function createQuickSegment(type) {
    const segmentTemplates = {
        'By Location': {
            name: `Geographic Segment - ${getRandomLocation()}`,
            type: 'geographic',
            tags: ['Location-based', 'Regional']
        },
        'By Age Group': {
            name: `Age Group: ${getRandomAgeGroup()}`,
            type: 'demographic',
            tags: ['Age-based', 'Demographic']
        },
        'By Engagement History': {
            name: 'High Engagement Segment',
            type: 'behavioral',
            tags: ['Behavioral', 'High Engagement']
        },
        'By Health Condition': {
            name: 'Specific Condition Group',
            type: 'demographic',
            tags: ['Health', 'Medical', 'Condition-based']
        }
    };

    const template = segmentTemplates[type] || {
        name: `Quick Segment - ${type}`,
        type: 'demographic',
        tags: ['Quick', 'Generated']
    };

    const newSegment = {
        id: Date.now(),
        name: template.name,
        description: `Quickly generated segment using ${type}`,
        type: template.type,
        size: Math.floor(Math.random() * 30000) + 1000,
        engagementRate: Math.floor(Math.random() * 30) + 70,
        lastUpdated: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
        status: 'draft',
        tags: template.tags,
        color: getRandomColor()
    };

    segments.unshift(newSegment);
    showNotification(`Segment created using ${type}!`);
    updateSegmentLibrary();
    updateSegmentTable();
}

function updateSegmentLibrary() {
    const segmentList = document.querySelector('.segment-list');
    if (!segmentList) return;

    const recentSegments = segments.slice(0, 4); // Show first 4 segments

    segmentList.innerHTML = recentSegments.map(segment => `
        <div class="segment-item ${segment.color || 'parents'}">
            <div class="segment-name">${segment.name}</div>
            <div class="segment-count">${segment.size.toLocaleString()} individuals</div>
            <div class="progress-container">
                <div class="progress-bar" style="width: ${segment.engagementRate}%; background-color: var(--${segment.color || 'accent'});"></div>
            </div>
            <div class="segment-tags">
                ${segment.tags?.slice(0, 3).map(tag => `<span class="segment-tag">${tag}</span>`).join('') || ''}
            </div>
            <div class="segment-actions" style="margin-top: 10px;">
                <i class="fas fa-edit" title="Edit" onclick="openCreateModal(${segment.id})"></i>
                <i class="fas fa-copy" title="Duplicate" onclick="duplicateSegment(${segment.id})"></i>
                <i class="fas fa-trash" title="Delete" onclick="deleteSegment(${segment.id})"></i>
            </div>
        </div>
    `).join('');
}

function updateSegmentTable() {
    const tableBody = document.querySelector('.segment-table tbody');
    if (!tableBody) return;

    tableBody.innerHTML = segments.map(segment => `
        <tr data-segment-id="${segment.id}">
            <td>
                <input type="checkbox" class="segment-checkbox" data-segment-id="${segment.id}" onchange="toggleSegmentSelection(${segment.id})">
                <div style="font-weight: 600;">${segment.name}</div>
                <div style="font-size: 12px; color: var(--text-gray);">${segment.description || ''}</div>
            </td>
            <td><span class="segment-type type-${segment.type}">${segment.type.charAt(0).toUpperCase() + segment.type.slice(1)}</span></td>
            <td>${segment.size.toLocaleString()}</td>
            <td>
                <div>${segment.engagementRate}%</div>
                <div style="height: 4px; background-color: var(--dark-gray); border-radius: 2px; margin-top: 5px;">
                    <div style="width: ${segment.engagementRate}%; height: 100%; background-color: var(--${getEngagementColor(segment.engagementRate)}); border-radius: 2px;"></div>
                </div>
            </td>
            <td>${segment.lastUpdated}</td>
            <td><span style="color: var(--${getStatusColor(segment.status)});">${segment.status.charAt(0).toUpperCase() + segment.status.slice(1)}</span></td>
            <td>
                <div class="segment-actions">
                    <i class="fas fa-edit" title="Edit" onclick="openCreateModal(${segment.id})"></i>
                    <i class="fas fa-chart-line" title="Analytics" onclick="showAnalytics(${segment.id})"></i>
                    <i class="fas fa-copy" title="Duplicate" onclick="duplicateSegment(${segment.id})"></i>
                    <i class="fas fa-trash" title="Delete" onclick="deleteSegment(${segment.id})"></i>
                </div>
            </td>
        </tr>
    `).join('');
    
    // Add event listener for header checkbox to select all
    const headerCheckbox = document.querySelector('.segment-table th input[type="checkbox"]');
    if (headerCheckbox) {
        headerCheckbox.onchange = toggleSelectAll;
    }
}

// Selected segments tracking
let selectedSegments = [];

function toggleSegmentSelection(segmentId) {
    const index = selectedSegments.indexOf(segmentId);
    if (index > -1) {
        selectedSegments.splice(index, 1);
    } else {
        selectedSegments.push(segmentId);
    }
    
    // Update bulk action buttons visibility
    updateBulkActionButtons();
}

function toggleSelectAll(event) {
    const isChecked = event.target.checked;
    const checkboxes = document.querySelectorAll('.segment-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
        const segmentId = parseInt(checkbox.dataset.segmentId);
        
        if (isChecked && !selectedSegments.includes(segmentId)) {
            selectedSegments.push(segmentId);
        } else if (!isChecked && selectedSegments.includes(segmentId)) {
            const index = selectedSegments.indexOf(segmentId);
            if (index > -1) selectedSegments.splice(index, 1);
        }
    });
    
    updateBulkActionButtons();
}

function updateBulkActionButtons() {
    const bulkActions = document.querySelector('.bulk-actions');
    if (bulkActions) {
        if (selectedSegments.length > 0) {
            bulkActions.style.display = 'flex';
            bulkActions.innerHTML = `
                <button class="btn btn-sm" onclick="compareSelectedSegments()" title="Compare selected segments">
                    <i class="fas fa-balance-scale"></i> Compare (${selectedSegments.length})
                </button>
                <button class="btn btn-sm btn-warning" onclick="bulkUpdateDialog()" title="Update selected segments">
                    <i class="fas fa-edit"></i> Update
                </button>
                <button class="btn btn-sm btn-danger" onclick="bulkDeleteDialog()" title="Delete selected segments">
                    <i class="fas fa-trash"></i> Delete
                </button>
            `;
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

function compareSelectedSegments() {
    if (selectedSegments.length < 2) {
        showNotification('Please select at least 2 segments to compare', 'error');
        return;
    }
    compareSegments(selectedSegments);
}

function bulkUpdateDialog() {
    if (selectedSegments.length === 0) {
        showNotification('Please select at least one segment to update', 'error');
        return;
    }
    
    openConfirmModal(
        'Bulk Update Segments',
        `Update ${selectedSegments.length} selected segments? Select fields to update:`,
        function () {
            // Create update form
            const updateForm = `
                <div class="bulk-update-form">
                    <div class="form-group">
                        <label><input type="checkbox" id="updateStatus"> Update Status</label>
                        <select id="newStatus" disabled>
                            <option value="active">Active</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                            <option value="paused">Paused</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" id="updateType"> Update Type</label>
                        <select id="newType" disabled>
                            <option value="demographic">Demographic</option>
                            <option value="behavioral">Behavioral</option>
                            <option value="geographic">Geographic</option>
                        </select>
                    </div>
                </div>
            `;
            
            // Temporarily replace confirm modal content
            document.getElementById('confirmMessage').innerHTML = updateForm;
            
            // Enable/disable selects based on checkbox
            document.getElementById('updateStatus').onchange = function() {
                document.getElementById('newStatus').disabled = !this.checked;
            };
            document.getElementById('updateType').onchange = function() {
                document.getElementById('newType').disabled = !this.checked;
            };
            
            // Change confirm button to process update
            document.getElementById('confirmActionBtn').textContent = 'Apply Updates';
            document.getElementById('confirmActionBtn').onclick = function() {
                const updates = {};
                if (document.getElementById('updateStatus').checked) {
                    updates.status = document.getElementById('newStatus').value;
                }
                if (document.getElementById('updateType').checked) {
                    updates.type = document.getElementById('newType').value;
                }
                
                if (Object.keys(updates).length > 0) {
                    bulkUpdateSegments(selectedSegments, updates);
                    closeConfirmModal();
                    clearSelection();
                } else {
                    showNotification('Please select at least one field to update', 'error');
                }
            };
        }
    );
}

function bulkDeleteDialog() {
    if (selectedSegments.length === 0) {
        showNotification('Please select at least one segment to delete', 'error');
        return;
    }
    
    openConfirmModal(
        'Bulk Delete Segments',
        `Are you sure you want to delete ${selectedSegments.length} selected segments? This action cannot be undone.`,
        function () {
            bulkDeleteSegments(selectedSegments);
            closeConfirmModal();
            clearSelection();
        }
    );
}

function clearSelection() {
    selectedSegments = [];
    const checkboxes = document.querySelectorAll('.segment-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    
    const headerCheckbox = document.querySelector('.segment-table th input[type="checkbox"]');
    if (headerCheckbox) headerCheckbox.checked = false;
    
    updateBulkActionButtons();
}

function showAnalytics(segmentId) {
    const segment = segments.find(s => s.id === segmentId);
    if (segment) {
        // Create analytics modal
        const analyticsModal = document.createElement('div');
        analyticsModal.className = 'modal-overlay';
        analyticsModal.id = 'analyticsModal';
        analyticsModal.innerHTML = `
            <div class="modal-content" style="max-width: 90%; width: 800px;">
                <div class="modal-header">
                    <h3>Analytics for "${segment.name}"</h3>
                    <button class="modal-close" onclick="closeAnalyticsModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="analytics-grid">
                        <div class="metric-card">
                            <h4>Segment Size</h4>
                            <div class="metric-value">${segment.size.toLocaleString()}</div>
                            <div class="metric-desc">Individuals in this segment</div>
                        </div>
                        <div class="metric-card">
                            <h4>Engagement Rate</h4>
                            <div class="metric-value" style="color: var(--${getEngagementColor(segment.engagementRate)});">${segment.engagementRate}%</div>
                            <div class="metric-desc">Average engagement level</div>
                        </div>
                        <div class="metric-card">
                            <h4>Segment Type</h4>
                            <div class="metric-value">${segment.type}</div>
                            <div class="metric-desc">Classification</div>
                        </div>
                        <div class="metric-card">
                            <h4>Status</h4>
                            <div class="metric-value" style="color: var(--${getStatusColor(segment.status)}); text-transform: uppercase;">${segment.status}</div>
                            <div class="metric-desc">Current state</div>
                        </div>
                    </div>
                    
                    <div class="analytics-section">
                        <h4>Tags</h4>
                        <div class="tags-container">
                            ${segment.tags?.map(tag => `<span class="segment-tag">${tag}</span>`).join('') || 'No tags assigned'}
                        </div>
                    </div>
                    
                    <div class="analytics-section">
                        <h4>Recommendations</h4>
                        <div class="recommendations-list">
                            ${getRecommendationsForSegment(segment).map(rec => `
                                <div class="recommendation-item ${rec.priority}">
                                    <i class="fas fa-lightbulb"></i>
                                    <span>${rec.message}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeAnalyticsModal()">Close</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(analyticsModal);
        analyticsModal.style.display = 'flex';
    }
}

function getRecommendationsForSegment(segment) {
    const recommendations = [];
    
    if (segment.engagementRate < 60) {
        recommendations.push({
            message: `This segment has low engagement. Consider optimizing your messaging or targeting criteria.`,
            priority: 'high'
        });
    } else if (segment.engagementRate > 90) {
        recommendations.push({
            message: `This segment has excellent engagement! Consider expanding similar targeting approaches.`,
            priority: 'low'
        });
    }
    
    if (segment.size < 1000) {
        recommendations.push({
            message: `This segment is relatively small. Consider broadening criteria to increase reach.`,
            priority: 'medium'
        });
    } else if (segment.size > 50000) {
        recommendations.push({
            message: `This segment is quite large. Consider refining criteria for more targeted messaging.`,
            priority: 'medium'
        });
    }
    
    return recommendations;
}

function closeAnalyticsModal() {
    const modal = document.getElementById('analyticsModal');
    if (modal) modal.remove();
}

function initializeFilters() {
    const filterItems = document.querySelectorAll('.filter-item');
    filterItems.forEach(item => {
        item.addEventListener('click', function () {
            filterItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            filterSegments(this.textContent.trim());
        });
    });
}

function filterSegments(filter) {
    let filteredSegments = [...segments];

    switch (filter) {
        case 'High Priority':
            filteredSegments = segments.filter(s => s.tags?.includes('High Priority'));
            break;
        case 'Demographic':
            filteredSegments = segments.filter(s => s.type === 'demographic');
            break;
        case 'Behavioral':
            filteredSegments = segments.filter(s => s.type === 'behavioral');
            break;
        case 'Geographic':
            filteredSegments = segments.filter(s => s.type === 'geographic');
            break;
        case 'Active Campaigns':
            filteredSegments = segments.filter(s => s.status === 'active');
            break;
        // "All Segments" shows everything
    }

    updateTableWithFilter(filteredSegments);
}

function updateTableWithFilter(filteredSegments) {
    const tableBody = document.querySelector('.segment-table tbody');
    if (!tableBody) return;

    if (filteredSegments.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-gray);">
                    No segments found matching the selected filter.
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = filteredSegments.map(segment => `
        <tr>
            <td>
                <div style="font-weight: 600;">${segment.name}</div>
                <div style="font-size: 12px; color: var(--text-gray);">${segment.description || ''}</div>
            </td>
            <td><span class="segment-type type-${segment.type}">${segment.type.charAt(0).toUpperCase() + segment.type.slice(1)}</span></td>
            <td>${segment.size.toLocaleString()}</td>
            <td>
                <div>${segment.engagementRate}%</div>
                <div style="height: 4px; background-color: var(--dark-gray); border-radius: 2px; margin-top: 5px;">
                    <div style="width: ${segment.engagementRate}%; height: 100%; background-color: var(--${getEngagementColor(segment.engagementRate)}); border-radius: 2px;"></div>
                </div>
            </td>
            <td>${segment.lastUpdated}</td>
            <td><span style="color: var(--${getStatusColor(segment.status)});">${segment.status.charAt(0).toUpperCase() + segment.status.slice(1)}</span></td>
            <td>
                <div class="segment-actions">
                    <i class="fas fa-edit" title="Edit" onclick="openCreateModal(${segment.id})"></i>
                    <i class="fas fa-chart-line" title="Analytics" onclick="showAnalytics(${segment.id})"></i>
                    <i class="fas fa-copy" title="Duplicate" onclick="duplicateSegment(${segment.id})"></i>
                    <i class="fas fa-trash" title="Delete" onclick="deleteSegment(${segment.id})"></i>
                </div>
            </td>
        </tr>
    `).join('');
}

function searchSegments(query) {
    if (!query.trim()) {
        updateSegmentTable();
        return;
    }

    const searchTerm = query.toLowerCase();
    const filteredSegments = segments.filter(segment =>
        segment.name.toLowerCase().includes(searchTerm) ||
        segment.description.toLowerCase().includes(searchTerm) ||
        segment.tags?.some(tag => tag.toLowerCase().includes(searchTerm))
    );

    updateTableWithFilter(filteredSegments);
}

function showAnalytics(segmentId) {
    const segment = segments.find(s => s.id === segmentId);
    if (segment) {
        alert(`Analytics for "${segment.name}":\n\n` +
            `Size: ${segment.size.toLocaleString()} individuals\n` +
            `Engagement Rate: ${segment.engagementRate}%\n` +
            `Type: ${segment.type}\n` +
            `Status: ${segment.status}\n` +
            `Last Updated: ${segment.lastUpdated}`);
    }
}

function optimizeChannels() {
    alert('Analyzing channel performance...\nOptimizing communication strategy...\n\n' +
        'Recommendations:\n' +
        '1. Increase Email frequency for High-Risk group\n' +
        '2. Add SMS for Senior Citizens\n' +
        '3. Use Social Media for College Students\n' +
        '4. Maintain Traditional Media for Parents');
}

function openConfirmModal(title, message, confirmCallback) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmModal').style.display = 'flex';

    const confirmBtn = document.getElementById('confirmActionBtn');
    confirmBtn.onclick = confirmCallback;
}

function showNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        </div>
    `;

    // Add styles
    if (!document.querySelector('.notification-styles')) {
        const style = document.createElement('style');
        style.className = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: var(--success);
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 1001;
                animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s;
                animation-fill-mode: forwards;
            }

            .notification-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }

            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 3000);
}

// Enhanced helper functions
function getRandomColor() {
    const colors = ['danger', 'warning', 'accent', 'success', 'primary', 'secondary'];
    return colors[Math.floor(Math.random() * colors.length)];
}

function determineSegmentType(criteria) {
    if (criteria.some(c => ['Age', 'Location', 'Language', 'Education', 'Occupation', 'Income', 'Gender', 'Marital Status'].some(term => c.includes(term)))) {
        return 'demographic';
    } else if (criteria.some(c => ['Engagement', 'Response', 'Channels', 'Usage', 'History', 'Website Visits', 'App Usage'].some(term => c.includes(term)))) {
        return 'behavioral';
    } else if (criteria.some(c => ['City', 'State', 'Country', 'Urban/Rural', 'Proximity to Facilities'].some(term => c.includes(term)))) {
        return 'geographic';
    }
    return 'demographic';
}

function getRandomLocation() {
    const locations = ['Downtown', 'Suburban', 'Rural', 'Urban Core', 'Coastal', 'Mountain', 'Metropolitan', 'Residential Area', 'Business District'];
    return locations[Math.floor(Math.random() * locations.length)];
}

function getRandomAgeGroup() {
    const ageGroups = ['18-24', '25-34', '35-44', '45-54', '55-64', '65+', 'Under 18', '18-30', '30-50', '50+'];
    return ageGroups[Math.floor(Math.random() * ageGroups.length)];
}

function getEngagementColor(rate) {
    if (rate >= 90) return 'success';
    if (rate >= 75) return 'accent';
    if (rate >= 60) return 'warning';
    return 'danger';
}

function getStatusColor(status) {
    switch (status) {
        case 'active': return 'success';
        case 'draft': return 'text-gray';
        case 'archived': return 'warning';
        case 'paused': return 'danger';
        default: return 'text-gray';
    }
}

// Enhanced Export/Import functionality
function exportSegments() {
    const dataStr = JSON.stringify(segments, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'segments-export-' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
    URL.revokeObjectURL(url);
    showNotification('Segments exported successfully!');
}

function importSegments(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        try {
            const imported = JSON.parse(e.target.result);
            if (Array.isArray(imported)) {
                // Merge imported segments with existing ones, avoiding duplicates
                const existingIds = new Set(segments.map(s => s.id));
                const newSegments = imported.filter(seg => !existingIds.has(seg.id));
                
                segments = [...newSegments, ...segments];
                updateSegmentLibrary();
                updateSegmentTable();
                showNotification(`Imported ${newSegments.length} new segments successfully!`);
            }
        } catch (error) {
            showNotification('Error importing segments: Invalid file format', 'error');
        }
    };
    reader.readAsText(file);
}

// Advanced Analytics Functions
function calculateSegmentOverlap(segment1, segment2) {
    // Calculate potential overlap between two segments
    // This is a simplified calculation - in a real app, this would use actual user data
    const baseOverlap = Math.min(segment1.size, segment2.size) / Math.max(segment1.size, segment2.size);
    const typeMatch = segment1.type === segment2.type ? 0.5 : 0.1;
    return Math.min(1, (baseOverlap * 0.7) + (typeMatch * 0.3));
}

function getSegmentRecommendations() {
    // Analyze segments and provide recommendations
    const recommendations = [];
    
    // Check for low engagement segments
    segments.forEach(segment => {
        if (segment.engagementRate < 60) {
            recommendations.push({
                type: 'low_engagement',
                message: `Segment "${segment.name}" has low engagement (${segment.engagementRate}%). Consider optimizing messaging or targeting.`,
                priority: 'high'
            });
        }
    });
    
    // Check for large segments that might be too broad
    const avgSize = segments.reduce((sum, seg) => sum + seg.size, 0) / segments.length;
    segments.forEach(segment => {
        if (segment.size > avgSize * 1.5) {
            recommendations.push({
                type: 'broad_targeting',
                message: `Segment "${segment.name}" is significantly larger than average. Consider narrowing the criteria for better targeting.`,
                priority: 'medium'
            });
        }
    });
    
    // Check for small segments that might not be cost-effective
    segments.forEach(segment => {
        if (segment.size < 1000 && segment.status === 'active') {
            recommendations.push({
                type: 'small_segment',
                message: `Segment "${segment.name}" is quite small. Consider combining with similar segments for better efficiency.`,
                priority: 'low'
            });
        }
    });
    
    return recommendations;
}

// Visualization Functions
function renderSegmentDistributionChart() {
    const canvas = document.getElementById('segmentDistributionChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Count segments by type
    const typeCounts = {};
    segments.forEach(segment => {
        typeCounts[segment.type] = (typeCounts[segment.type] || 0) + 1;
    });
    
    // Draw a simple bar chart
    const types = Object.keys(typeCounts);
    const counts = Object.values(typeCounts);
    const maxValue = Math.max(...counts, 1);
    
    const barWidth = canvas.width / (types.length * 2);
    const maxHeight = canvas.height - 40;
    
    ctx.fillStyle = '#4A90E2';
    for (let i = 0; i < types.length; i++) {
        const barHeight = (counts[i] / maxValue) * maxHeight;
        const x = i * barWidth * 2 + 20;
        const y = canvas.height - barHeight - 20;
        
        ctx.fillRect(x, y, barWidth, barHeight);
        
        // Draw label
        ctx.fillStyle = 'white';
        ctx.font = '10px Arial';
        ctx.fillText(types[i], x, canvas.height - 5);
        ctx.fillStyle = '#4A90E2';
    }
}

function renderEngagementTrendChart() {
    const canvas = document.getElementById('engagementTrendChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Draw a simple line chart
    const engagements = segments.slice(0, 10).map(s => s.engagementRate); // Last 10 segments
    if (engagements.length === 0) return;
    
    const maxValue = Math.max(...engagements, 100);
    const minValue = Math.min(...engagements, 0);
    
    const pointSpacing = canvas.width / (engagements.length - 1);
    
    ctx.beginPath();
    ctx.moveTo(0, canvas.height - (engagements[0] / maxValue) * canvas.height);
    
    for (let i = 1; i < engagements.length; i++) {
        const x = i * pointSpacing;
        const y = canvas.height - (engagements[i] / maxValue) * canvas.height;
        ctx.lineTo(x, y);
    }
    
    ctx.strokeStyle = '#4CAF50';
    ctx.lineWidth = 2;
    ctx.stroke();
    
    // Draw data points
    ctx.fillStyle = '#4CAF50';
    for (let i = 0; i < engagements.length; i++) {
        const x = i * pointSpacing;
        const y = canvas.height - (engagements[i] / maxValue) * canvas.height;
        ctx.beginPath();
        ctx.arc(x, y, 4, 0, 2 * Math.PI);
        ctx.fill();
    }
}

// Advanced Search and Filtering
function advancedSearch(query, filters = {}) {
    return segments.filter(segment => {
        // Basic text search
        const matchesText = !query || 
            segment.name.toLowerCase().includes(query.toLowerCase()) ||
            segment.description.toLowerCase().includes(query.toLowerCase()) ||
            segment.tags.some(tag => tag.toLowerCase().includes(query.toLowerCase()));
        
        // Apply additional filters
        const matchesType = !filters.type || segment.type === filters.type;
        const matchesStatus = !filters.status || segment.status === filters.status;
        const matchesMinSize = !filters.minSize || segment.size >= filters.minSize;
        const matchesMaxSize = !filters.maxSize || segment.size <= filters.maxSize;
        const matchesMinEngagement = !filters.minEngagement || segment.engagementRate >= filters.minEngagement;
        
        return matchesText && matchesType && matchesStatus && 
               matchesMinSize && matchesMaxSize && matchesMinEngagement;
    });
}

// Bulk Operations
function bulkUpdateSegments(segmentIds, updates) {
    let updatedCount = 0;
    
    segments = segments.map(segment => {
        if (segmentIds.includes(segment.id)) {
            segments = { ...segment, ...updates, lastUpdated: new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) };
            updatedCount++;
        }
        return segment;
    });
    
    updateSegmentLibrary();
    updateSegmentTable();
    showNotification(`Updated ${updatedCount} segments successfully!`);
}

function bulkDeleteSegments(segmentIds) {
    openConfirmModal(
        'Bulk Delete Segments',
        `Are you sure you want to delete ${segmentIds.length} segments? This action cannot be undone.`,
        function () {
            segments = segments.filter(s => !segmentIds.includes(s.id));
            updateSegmentLibrary();
            updateSegmentTable();
            showNotification(`Deleted ${segmentIds.length} segments successfully!`);
            closeConfirmModal();
        }
    );
}

// Segment Comparison Tool
function compareSegments(segmentIds) {
    if (segmentIds.length < 2) {
        showNotification('Please select at least 2 segments to compare', 'error');
        return;
    }
    
    const selectedSegments = segments.filter(s => segmentIds.includes(s.id));
    
    // Create comparison modal
    const comparisonModal = document.createElement('div');
    comparisonModal.className = 'modal-overlay';
    comparisonModal.id = 'comparisonModal';
    comparisonModal.innerHTML = `
        <div class="modal-content" style="max-width: 90%; width: 900px;">
            <div class="modal-header">
                <h3>Segment Comparison</h3>
                <button class="modal-close" onclick="closeComparisonModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="comparison-table">
                    <table class="segment-comparison">
                        <thead>
                            <tr>
                                <th>Attribute</th>
                                ${selectedSegments.map(seg => `<th>${seg.name}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Type</strong></td>
                                ${selectedSegments.map(seg => `<td>${seg.type}</td>`).join('')}
                            </tr>
                            <tr>
                                <td><strong>Size</strong></td>
                                ${selectedSegments.map(seg => `<td>${seg.size.toLocaleString()}</td>`).join('')}
                            </tr>
                            <tr>
                                <td><strong>Engagement Rate</strong></td>
                                ${selectedSegments.map(seg => `<td>${seg.engagementRate}%</td>`).join('')}
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                ${selectedSegments.map(seg => `<td>${seg.status}</td>`).join('')}
                            </tr>
                            <tr>
                                <td><strong>Tags</strong></td>
                                ${selectedSegments.map(seg => `<td>${seg.tags.join(', ')}</td>`).join('')}
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeComparisonModal()">Close</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(comparisonModal);
    comparisonModal.style.display = 'flex';
}

function closeComparisonModal() {
    const modal = document.getElementById('comparisonModal');
    if (modal) modal.remove();
}

// Initialize advanced features when DOM is loaded
function initializeAdvancedFeatures() {
    // Render charts if elements exist
    setTimeout(() => {
        renderSegmentDistributionChart();
        renderEngagementTrendChart();
    }, 100);
}

// Add to the DOMContentLoaded event
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAdvancedFeatures);
} else {
    initializeAdvancedFeatures();
}