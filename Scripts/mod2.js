// Content Repository JavaScript with Safety Promotions

// Safety promotional messages
const SAFETY_PROMOTIONS = [
    "Stay Safe, Stay Informed - Your community's safety is our priority",
    "Prevention is Better Than Cure - Access emergency preparedness guides",
    "Together We Build Safer Communities - Share safety resources today",
    "Knowledge Saves Lives - Download our life-saving safety materials",
    "Be Ready, Be Safe - Emergency response training materials available"
];

// Safety content categories with promotional descriptions
const SAFETY_CATEGORIES = {
    'emergency': { 
        name: 'Emergency Response', 
        icon: 'fa-exclamation-triangle', 
        description: 'Critical emergency procedures and response protocols'
    },
    'fire': { 
        name: 'Fire Safety', 
        icon: 'fa-fire', 
        description: 'Fire prevention, detection, and response materials'
    },
    'health': { 
        name: 'Public Health', 
        icon: 'fa-heartbeat', 
        description: 'Health awareness and disease prevention resources'
    },
    'disaster': { 
        name: 'Disaster Preparedness', 
        icon: 'fa-cloud-showers-heavy', 
        description: 'Natural disaster preparedness and recovery guides'
    },
    'traffic': { 
        name: 'Traffic Safety', 
        icon: 'fa-car-crash', 
        description: 'Road safety awareness and accident prevention'
    },
    'cyber': { 
        name: 'Cyber Security', 
        icon: 'fa-shield-alt', 
        description: 'Digital safety and cybersecurity awareness'
    }
};

function uploadNewContent() {
    showSafetyPromotion();
    alert('Opening file upload dialog...');
    // In a real application, this would open a file upload modal
}

function advancedSearch() {
    showSafetyPromotion();
    alert('Opening advanced search panel...');
}

function manageTags() {
    showSafetyPromotion();
    alert('Opening tag management interface...');
}

function bulkExport() {
    showSafetyPromotion();
    alert('Preparing bulk export...');
}

// Show random safety promotion
function showSafetyPromotion() {
    const randomIndex = Math.floor(Math.random() * SAFETY_PROMOTIONS.length);
    const promotion = SAFETY_PROMOTIONS[randomIndex];
    
    // Create promotion banner
    const banner = document.createElement('div');
    banner.className = 'safety-promotion-banner';
    banner.innerHTML = `
        <div style="
            background: linear-gradient(135deg, var(--accent), #ff6b35);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
            animation: fadeIn 0.5s ease-in;
        ">
            <i class="fas fa-shield-alt" style="margin-right: 10px;"></i>
            ${promotion}
            <button onclick="this.parentElement.parentElement.remove()" 
                    style="
                        float: right;
                        background: transparent;
                        border: none;
                        color: white;
                        font-size: 18px;
                        cursor: pointer;
                        margin-left: 10px;
                    ">×</button>
        </div>
    `;
    
    const header = document.querySelector('.header');
    if (header) {
        header.parentNode.insertBefore(banner, header.nextSibling);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (banner.parentNode) {
                banner.remove();
            }
        }, 5000);
    }
}

// Generate mock safety content data - reduced quantity
function generateMockSafetyContent() {
    const categories = Object.keys(SAFETY_CATEGORIES);
    const statuses = ['approved', 'pending', 'draft'];
    const extensions = ['pdf', 'docx', 'png', 'mp4', 'mp3'];
    
    const mockContent = [];
    
    // Generate only 12 items instead of 50
    for (let i = 1; i <= 12; i++) {
        const category = categories[Math.floor(Math.random() * categories.length)];
        const extension = extensions[Math.floor(Math.random() * extensions.length)];
        const status = statuses[Math.floor(Math.random() * statuses.length)];
        
        const sizes = ['1.2 MB', '2.4 MB', '850 KB', '4.1 MB', '56 KB', '12.3 MB'];
        const size = sizes[Math.floor(Math.random() * sizes.length)];
        
        const names = [
            'Emergency Response Guidelines',
            'Fire Safety Procedures',
            'Health Awareness Campaign',
            'Disaster Preparedness Manual',
            'Traffic Safety Tips',
            'Cyber Security Best Practices',
            'First Aid Training Materials',
            'Community Safety Protocols',
            'Emergency Contact Lists',
            'Safety Inspection Checklists',
            'Crisis Communication Guide',
            'Public Health Advisory'
        ];
        
        const name = names[Math.floor(Math.random() * names.length)];
        
        mockContent.push({
            id: i,
            name: `${name}.${extension}`,
            category: SAFETY_CATEGORIES[category].name,
            size: size,
            modified: new Date(Date.now() - Math.random() * 30 * 24 * 60 * 60 * 1000).toLocaleDateString(),
            status: status,
            version: `1.${Math.floor(Math.random() * 5) + 1}`,
            tags: [`#${category}`, '#safety', '#public'],
            description: `Essential ${SAFETY_CATEGORIES[category].description.toLowerCase()} resource.`
        });
    }
    
    return mockContent;
}

// Set active navigation
document.addEventListener('DOMContentLoaded', function () {
    const currentPage = 'Content-Repository.html';
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
    searchInput.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                showSafetyPromotion();
                searchContent(query);
            }
        }
    });
    
    // Add safety tips to search placeholder
    if (searchInput) {
        searchInput.placeholder = 'Search safety content, emergency procedures, fire safety...';
    }

    // Filter functionality
    const filterSelects = document.querySelectorAll('.search-filter select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function () {
            console.log(`Filter changed: ${this.value}`);
        });
    });

    // Action icons functionality
    const actionIcons = document.querySelectorAll('.action-icons i');
    actionIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const action = this.getAttribute('title');
            alert(`${action} action triggered`);
        });
    });

    // Quick action buttons
    const quickActions = document.querySelectorAll('.quick-actions-grid .action-btn');
    quickActions.forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.querySelector('span').textContent;
            console.log(`Quick action: ${action}`);
            showSafetyPromotion();
        });
    });
    
    // Add safety category filters
    const categoryFilter = document.querySelector('.search-filter select:nth-child(2)');
    if (categoryFilter) {
        categoryFilter.innerHTML = `
            <option value="">All Safety Categories</option>
            <option value="emergency">Emergency Response</option>
            <option value="fire">Fire Safety</option>
            <option value="health">Public Health</option>
            <option value="disaster">Disaster Preparedness</option>
            <option value="traffic">Traffic Safety</option>
            <option value="cyber">Cyber Security</option>
        `;
    }
    
    // Add auto-refresh safety tip
    setInterval(showRandomSafetyTip, 30000); // Every 30 seconds
});


// Content Repository JavaScript with full CRUD functionality and Safety Features
let contentData = [];
let currentSafetyTipInterval;

const API_BASE_URL = ''; // Disable API calls for local development

// Show random safety tip in sidebar
function showRandomSafetyTip() {
    const tips = [
        "Test your smoke detectors monthly",
        "Create a family emergency plan",
        "Keep emergency contacts easily accessible",
        "Store important documents in waterproof containers",
        "Learn basic first aid and CPR",
        "Check expiration dates on emergency supplies",
        "Know your local emergency alert systems",
        "Practice evacuation routes regularly"
    ];
    
    const randomTip = tips[Math.floor(Math.random() * tips.length)];
    
    // Try to find sidebar for tip placement
    const sidebar = document.querySelector('.sidebar');
    if (sidebar && !document.querySelector('.safety-tip-sidebar')) {
        const tipElement = document.createElement('div');
        tipElement.className = 'safety-tip-sidebar';
        tipElement.innerHTML = `
            <div style="
                margin: 20px 15px;
                padding: 15px;
                background: linear-gradient(135deg, #2c3e50, #34495e);
                border-radius: 8px;
                color: white;
                font-size: 14px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            ">
                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                    <i class="fas fa-lightbulb" style="margin-right: 10px; color: #f1c40f;"></i>
                    <strong>Safety Tip</strong>
                </div>
                <div>${randomTip}</div>
            </div>
        `;
        sidebar.appendChild(tipElement);
        
        // Remove after 10 seconds
        setTimeout(() => {
            if (tipElement.parentNode) {
                tipElement.remove();
            }
        }, 10000);
    }
}

// Initialize the page with safety features
document.addEventListener('DOMContentLoaded', async function () {
    initializeSafetyFeatures();
    setActiveNavigation();
    await loadContentData();
    setupEventListeners();
    renderContentTable();
    updateDashboardStats();
    
    // Show welcome safety message
    setTimeout(() => {
        const welcomeBanner = document.createElement('div');
        welcomeBanner.innerHTML = `
            <div style="
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: center;
                animation: fadeIn 1s ease-in;
                box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
            ">
                <h3 style="margin: 0 0 15px 0;">
                    <i class="fas fa-hands-helping" style="margin-right: 10px;"></i>
                    Welcome to the Public Safety Content Repository
                </h3>
                <p style="margin: 0; font-size: 16px; opacity: 0.9;">
                    Manage, share, and distribute life-saving safety materials for your community
                </p>
                <div style="margin-top: 15px; font-size: 14px; opacity: 0.8;">
                    <i class="fas fa-lightbulb" style="margin-right: 5px;"></i>
                    Tip: Use tags like #emergency, #fire, #health to organize your safety content
                </div>
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="
                            margin-top: 15px;
                            background: white;
                            color: #3498db;
                            border: none;
                            padding: 8px 20px;
                            border-radius: 20px;
                            cursor: pointer;
                            font-weight: 600;
                        ">
                    Get Started
                </button>
            </div>
        `;
        
        const header = document.querySelector('.header');
        if (header) {
            header.parentNode.insertBefore(welcomeBanner, header.nextSibling);
        }
    }, 1000);
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

// Load content data from API or generate mock data
async function loadContentData() {
    try {
        // Try API first
        if (API_BASE_URL) {
            const response = await fetch(`${API_BASE_URL}/content`);
            if (response.ok) {
                contentData = await response.json();
                return;
            }
        }
        
        // Fallback to mock safety content
        console.warn('API not available, using mock safety content');
        contentData = generateMockSafetyContent();
        
    } catch (error) {
        console.error('Error loading content:', error);
        contentData = generateMockSafetyContent(); // Ensure we always have data
    }
}

// Setup event listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    searchInput.addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            searchContent(this.value);
        }
    });

    // Filter functionality
    const filterSelects = document.querySelectorAll('.search-filter select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function () {
            applyFilters();
        });
    });

    // Quick action buttons
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.querySelector('span').textContent;
            switch (action) {
                case 'Upload New':
                    openUploadModal();
                    break;
                case 'Advanced Search':
                    openAdvancedSearch();
                    break;
                case 'Manage Tags':
                    manageTags();
                    break;
                case 'Bulk Export':
                    bulkExport();
                    break;
            }
        });
    });

    // Upload button
    document.querySelector('.module-header .btn').addEventListener('click', openUploadModal);

    // Review Queue button
    document.querySelector('.btn-secondary[style*="width: 100%"]')?.addEventListener('click', openReviewQueue);

    // Set Reminders button
    document.querySelectorAll('.btn-secondary').forEach(btn => {
        if (btn.textContent.includes('Set Reminders')) {
            btn.addEventListener('click', setReminders);
        }
    });
}

// Render content to the display area - simplified version
function renderContentTable(filteredData = contentData) {
    const displayArea = document.getElementById('content-display-area');
    if (!displayArea) return;

    // Clear existing content
    displayArea.innerHTML = '';

    if (filteredData.length === 0) {
        // Show empty state
        displayArea.innerHTML = `
            <div style="text-align: center; padding: 40px; color: var(--text-gray);">
                <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                <p>No content found matching your criteria</p>
                <p style="font-size: 14px;">Try adjusting your search or filter settings</p>
            </div>
        `;
        return;
    }

    // Limit to 6 most recent items
    const displayItems = filteredData.slice(0, 6);
    
    // Show count info
    if (filteredData.length > 6) {
        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = `
            padding: 15px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            color: #3498db;
            border: 1px solid #3498db;
        `;
        infoDiv.innerHTML = `
            <i class="fas fa-info-circle"></i> Showing ${displayItems.length} of ${filteredData.length} items. 
            Use search or filters to find specific content.
        `;
        displayArea.appendChild(infoDiv);
    }

    // Create simplified grid layout
    const gridContainer = document.createElement('div');
    gridContainer.style.cssText = `
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 15px;
        padding: 15px;
    `;

    displayItems.forEach(item => {
        const card = document.createElement('div');
        card.className = 'content-card';
        card.style.cssText = `
            background: var(--dark-gray);
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        `;
        
        card.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div style="
                    width: 40px;
                    height: 40px;
                    background: linear-gradient(135deg, var(--accent), #ff6b35);
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 16px;
                    flex-shrink: 0;
                ">
                    ${getFileIcon(item.category)}
                </div>
                <div style="flex-grow: 1; overflow: hidden;">
                    <div style="font-weight: 600; font-size: 15px; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" 
                         title="${escapeHtml(item.name)}">
                        ${escapeHtml(item.name)}
                    </div>
                    <div style="font-size: 12px; color: var(--text-gray);">
                        v${item.version || '1.0'} • ${item.size}
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; font-size: 13px;">
                <div>
                    <div style="color: var(--text-gray);">Category</div>
                    <div style="color: white; font-weight: 500;">${escapeHtml(item.category)}</div>
                </div>
                <div>
                    <div style="color: var(--text-gray);">Status</div>
                    <div>
                        <span class="status-badge status-${item.status}" style="
                            padding: 3px 10px;
                            border-radius: 15px;
                            font-size: 11px;
                            font-weight: 600;
                            text-transform: uppercase;
                        ">
                            ${capitalizeFirst(item.status)}
                        </span>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button onclick="previewContent(${item.id})" 
                        style="flex: 1; min-width: 70px; background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.3s ease;">
                    <i class="fas fa-eye"></i> View
                </button>
                <button onclick="downloadContent(${item.id})" 
                        style="flex: 1; min-width: 70px; background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: all 0.3s ease;">
                    <i class="fas fa-download"></i> Get
                </button>
            </div>
            
            <!-- Approval buttons for pending content -->
            ${item.status === 'pending' ? `
            <div style="display: flex; gap: 5px; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                <button onclick="approveContent(${item.id})" 
                        style="flex: 1; background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button onclick="rejectContent(${item.id})" 
                        style="flex: 1; background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.3s ease;">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
            ` : ''}
        `;
        
        // Add hover effect
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.3)';
            this.style.borderColor = 'var(--accent)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
            this.style.borderColor = 'transparent';
        });
        
        gridContainer.appendChild(card);
    });
    
    displayArea.appendChild(gridContainer);
}

// Search content with safety enhancements
async function searchContent(query) {
    if (!query.trim()) {
        renderContentTable();
        return;
    }

    try {
        // Client-side search for mock data
        const searchTerm = query.toLowerCase();
        const results = contentData.filter(item => 
            item.name.toLowerCase().includes(searchTerm) ||
            item.category.toLowerCase().includes(searchTerm) ||
            item.description.toLowerCase().includes(searchTerm) ||
            (item.tags && item.tags.some(tag => tag.toLowerCase().includes(searchTerm)))
        );
        
        renderContentTable(results);
        
        // Show search results message
        const message = document.createElement('div');
        message.innerHTML = `
            <div style="
                background: var(--dark-gray);
                padding: 15px;
                border-radius: 8px;
                margin: 15px 0;
                text-align: center;
                border-left: 4px solid var(--accent);
            ">
                Found ${results.length} safety resources matching "${query}"
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="margin-left: 15px; background: var(--accent); border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                    Clear
                </button>
            </div>
        `;
        
        const table = document.querySelector('.content-table');
        if (table && table.parentNode) {
            table.parentNode.insertBefore(message, table);
        }
        
    } catch (error) {
        console.error('Search error:', error);
        alert('Search failed. Please try again.');
    }
}

// Apply filters with safety categorization
function applyFilters() {
    const categoryFilter = document.querySelector('.search-filter select:nth-child(2)').value;
    const statusFilter = document.querySelector('.search-filter select:nth-child(3)').value;

    let filtered = [...contentData];

    if (categoryFilter) {
        filtered = filtered.filter(item => 
            item.category.toLowerCase().includes(categoryFilter.toLowerCase()) ||
            (item.tags && item.tags.some(tag => tag.toLowerCase().includes(categoryFilter.toLowerCase())))
        );
    }

    if (statusFilter) {
        filtered = filtered.filter(item => item.status.toLowerCase() === statusFilter.toLowerCase());
    }

    renderContentTable(filtered);
    
    // Show filter results
    if (categoryFilter || statusFilter) {
        const filterMessage = document.createElement('div');
        filterMessage.innerHTML = `
            <div style="
                background: linear-gradient(135deg, #3498db, #2980b9);
                color: white;
                padding: 12px;
                border-radius: 6px;
                margin: 10px 0;
                text-align: center;
                font-size: 14px;
            ">
                <i class="fas fa-filter"></i> Showing ${filtered.length} filtered safety resources
                <button onclick="this.parentElement.parentElement.remove(); renderContentTable()" 
                        style="margin-left: 10px; background: white; color: #3498db; border: none; padding: 3px 8px; border-radius: 3px; cursor: pointer; font-size: 12px;">
                    Clear Filters
                </button>
            </div>
        `;
        
        const table = document.querySelector('.content-table');
        if (table && table.parentNode) {
            table.parentNode.insertBefore(filterMessage, table);
        }
    }
}

// Open upload modal with safety guidance
function openUploadModal() {
    showSafetyPromotion();
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--accent), #ff6b35); color: white;">
                <h3><i class="fas fa-upload"></i> Upload Safety Content</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div style="padding: 20px; background: #f8f9fa; border-radius: 0 0 8px 8px;">
                <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                    <strong><i class="fas fa-info-circle"></i> Safety Content Guidelines:</strong>
                    <ul style="margin: 10px 0 0 20px; font-size: 14px;">
                        <li>Ensure all content is accurate and up-to-date</li>
                        <li>Include proper citations for medical/technical information</li>
                        <li>Verify accessibility compliance (alt text, readable fonts)</li>
                        <li>Consider multilingual audiences when appropriate</li>
                    </ul>
                </div>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="fileInput"><i class="fas fa-file-upload"></i> Select Safety Files</label>
                        <input type="file" id="fileInput" multiple accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.mp4,.mp3" required>
                        <div id="fileList" class="file-list"></div>
                        <small style="color: #666; display: block; margin-top: 5px;">Supported: PDF, DOC, Images, Videos, Audio</small>
                    </div>
                    <div class="form-group">
                        <label for="contentName">Content Name</label>
                        <input type="text" id="contentName" placeholder="Enter content name">
                    </div>
                    <div class="form-group">
                        <label for="contentCategory"><i class="fas fa-folder"></i> Safety Category</label>
                        <select id="contentCategory" required>
                            <option value="">Select Safety Category</option>
                            <option value="emergency">Emergency Response</option>
                            <option value="fire">Fire Safety</option>
                            <option value="health">Public Health</option>
                            <option value="disaster">Disaster Preparedness</option>
                            <option value="traffic">Traffic Safety</option>
                            <option value="cyber">Cyber Security</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="contentTags"><i class="fas fa-tags"></i> Safety Tags (comma separated)</label>
                        <input type="text" id="contentTags" placeholder="emergency, safety, training, prevention, awareness">
                        <small style="color: #666; display: block; margin-top: 5px;">Recommended: emergency, safety, prevention, awareness</small>
                    </div>
                    <div class="form-group">
                        <label for="contentDescription"><i class="fas fa-align-left"></i> Safety Description</label>
                        <textarea id="contentDescription" rows="4" placeholder="Describe the safety content, its purpose, and target audience..."></textarea>
                        <small style="color: #666; display: block; margin-top: 5px;">Include key safety information and intended use</small>
                    </div>
                    <div class="modal-footer" style="background: #f1f3f4; padding: 15px; border-radius: 0 0 8px 8px;">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn" style="background: linear-gradient(135deg, var(--accent), #ff6b35);">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Safety Content
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Handle file selection
    const fileInput = modal.querySelector('#fileInput');
    const fileList = modal.querySelector('#fileList');

    fileInput.addEventListener('change', function () {
        fileList.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.textContent = `${file.name} (${formatBytes(file.size)})`;
            fileList.appendChild(fileItem);
        });

        // Auto-fill content name if only one file
        if (this.files.length === 1) {
            modal.querySelector('#contentName').value = this.files[0].name.replace(/\.[^/.]+$/, "");
        }
    });

    // Handle form submission
    const form = modal.querySelector('#uploadForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await handleUpload();
        modal.remove();
    });

    // Close modal handlers
    modal.querySelectorAll('.close-modal').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => modal.remove());
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Additional safety utility functions

// Open advanced search modal
function openAdvancedSearch() {
    showSafetyPromotion();
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white;">
                <h3><i class="fas fa-search"></i> Advanced Safety Search</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="advancedSearchForm">
                    <div class="form-group">
                        <label>Search by:</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                            <div>
                                <input type="radio" id="searchName" name="searchType" value="name" checked>
                                <label for="searchName">File Name</label>
                            </div>
                            <div>
                                <input type="radio" id="searchTags" name="searchType" value="tags">
                                <label for="searchTags">Tags</label>
                            </div>
                            <div>
                                <input type="radio" id="searchCategory" name="searchType" value="category">
                                <label for="searchCategory">Category</label>
                            </div>
                            <div>
                                <input type="radio" id="searchDescription" name="searchType" value="description">
                                <label for="searchDescription">Description</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="searchQuery"><i class="fas fa-keyboard"></i> Search Query</label>
                        <input type="text" id="searchQuery" placeholder="Enter search terms..." required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-filter"></i> Filter by Status</label>
                        <div style="display: flex; gap: 15px; margin-top: 10px; flex-wrap: wrap;">
                            <div>
                                <input type="checkbox" id="statusApproved" value="approved">
                                <label for="statusApproved">Approved</label>
                            </div>
                            <div>
                                <input type="checkbox" id="statusPending" value="pending">
                                <label for="statusPending">Pending</label>
                            </div>
                            <div>
                                <input type="checkbox" id="statusDraft" value="draft">
                                <label for="statusDraft">Draft</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: #f8f9fa; padding: 15px; border-radius: 0 0 8px 8px;">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                            <i class="fas fa-search"></i> Search Safety Content
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const form = modal.querySelector('#advancedSearchForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const searchType = form.querySelector('input[name="searchType"]:checked').value;
        const query = form.querySelector('#searchQuery').value;
        const statusChecks = form.querySelectorAll('input[type="checkbox"]:checked');
        const statuses = Array.from(statusChecks).map(cb => cb.value);
        
        // Perform advanced search
        let results = [...contentData];
        
        // Apply status filter
        if (statuses.length > 0) {
            results = results.filter(item => statuses.includes(item.status));
        }
        
        // Apply search filter
        const searchTerm = query.toLowerCase();
        results = results.filter(item => {
            switch(searchType) {
                case 'name':
                    return item.name.toLowerCase().includes(searchTerm);
                case 'tags':
                    return item.tags && item.tags.some(tag => tag.toLowerCase().includes(searchTerm));
                case 'category':
                    return item.category.toLowerCase().includes(searchTerm);
                case 'description':
                    return item.description && item.description.toLowerCase().includes(searchTerm);
                default:
                    return true;
            }
        });
        
        renderContentTable(results);
        modal.remove();
        
        // Show results message
        const resultMsg = document.createElement('div');
        resultMsg.innerHTML = `
            <div style="
                background: linear-gradient(135deg, #9b59b6, #8e44ad);
                color: white;
                padding: 15px;
                border-radius: 8px;
                margin: 15px 0;
                text-align: center;
            ">
                Advanced search found ${results.length} safety resources
                <button onclick="this.parentElement.parentElement.remove(); renderContentTable()" 
                        style="margin-left: 15px; background: white; color: #9b59b6; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                    Clear Results
                </button>
            </div>
        `;
        
        const table = document.querySelector('.content-table');
        if (table && table.parentNode) {
            table.parentNode.insertBefore(resultMsg, table);
        }
    });
    
    setupModalClose(modal);
}

// Open review queue
function openReviewQueue() {
    showSafetyPromotion();
    
    const pendingItems = contentData.filter(item => item.status === 'pending');
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
                <h3><i class="fas fa-tasks"></i> Safety Content Review Queue (${pendingItems.length})</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                ${pendingItems.length > 0 ? `
                <div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <strong><i class="fas fa-exclamation-circle"></i> Review Required:</strong>
                    <div style="margin-top: 10px; font-size: 14px;">
                        ${pendingItems.length} safety resources need approval before publication.
                    </div>
                </div>
                
                <div style="max-height: 400px; overflow-y: auto;">
                    ${pendingItems.map(item => `
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            padding: 15px;
                            margin-bottom: 10px;
                            background: var(--dark-gray);
                            border-radius: 8px;
                        ">
                            <div>
                                <div style="font-weight: 600; margin-bottom: 5px;">${escapeHtml(item.name)}</div>
                                <div style="font-size: 14px; color: var(--text-gray);">
                                    ${item.category} • ${item.size} • Added ${item.modified}
                                </div>
                            </div>
                            <div>
                                <button onclick="approveItem(${item.id})" 
                                        style="background: #27ae60; color: white; border: none; padding: 8px 15px; border-radius: 4px; margin-right: 10px; cursor: pointer;">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button onclick="rejectItem(${item.id})" 
                                        style="background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
                ` : `
                <div style="
                    text-align: center;
                    padding: 40px;
                    background: #d4edda;
                    border-radius: 8px;
                    color: #155724;
                ">
                    <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h3>No Pending Reviews</h3>
                    <p>All safety content is currently approved and ready for use.</p>
                </div>
                `}
            </div>
            <div class="modal-footer" style="background: #f8f9fa; padding: 15px; border-radius: 0 0 8px 8px;">
                <button class="btn btn-secondary close-modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    setupModalClose(modal);
}

// Approve content item
function approveItem(id) {
    const item = contentData.find(i => i.id === id);
    if (item) {
        item.status = 'approved';
        item.modified = new Date().toLocaleDateString();
        updateDashboardStats();
        
        // Remove from modal and show confirmation
        const itemElement = event.target.closest('[style*="justify-content: space-between"]');
        if (itemElement) {
            itemElement.style.background = '#d4edda';
            itemElement.innerHTML = `
                <div style="padding: 15px; text-align: center; width: 100%;">
                    <i class="fas fa-check-circle" style="color: #27ae60; font-size: 24px; margin-bottom: 10px;"></i>
                    <div><strong>${escapeHtml(item.name)}</strong> approved successfully!</div>
                </div>
            `;
        }
        
        setTimeout(() => {
            openReviewQueue(); // Refresh queue
        }, 1500);
    }
}

// Reject content item
function rejectItem(id) {
    if (confirm('Are you sure you want to reject this safety content?')) {
        const item = contentData.find(i => i.id === id);
        if (item) {
            item.status = 'draft';
            item.modified = new Date().toLocaleDateString();
            updateDashboardStats();
            
            // Remove from modal and show confirmation
            const itemElement = event.target.closest('[style*="justify-content: space-between"]');
            if (itemElement) {
                itemElement.style.background = '#f8d7da';
                itemElement.innerHTML = `
                    <div style="padding: 15px; text-align: center; width: 100%;">
                        <i class="fas fa-times-circle" style="color: #e74c3c; font-size: 24px; margin-bottom: 10px;"></i>
                        <div><strong>${escapeHtml(item.name)}</strong> marked for revision</div>
                    </div>
                `;
            }
            
            setTimeout(() => {
                openReviewQueue(); // Refresh queue
            }, 1500);
        }
    }
}

// Set reminders for expiring content
function setReminders() {
    showSafetyPromotion();
    
    // Find expiring content (within 30 days)
    const expiringSoon = contentData.filter(item => {
        // Simulate expiration dates
        const expDate = new Date(item.modified);
        expDate.setMonth(expDate.getMonth() + 6); // 6 months from modification
        const daysUntilExp = Math.ceil((expDate - new Date()) / (1000 * 60 * 60 * 24));
        return daysUntilExp <= 30 && daysUntilExp > 0;
    });
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white;">
                <h3><i class="fas fa-bell"></i> Safety Content Reminders</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                ${expiringSoon.length > 0 ? `
                <div style="margin-bottom: 20px;">
                    <h4><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> Content Expiring Soon (${expiringSoon.length})</h4>
                    <div style="margin-top: 15px;">
                        ${expiringSoon.map(item => {
                            const expDate = new Date(item.modified);
                            expDate.setMonth(expDate.getMonth() + 6);
                            const daysLeft = Math.ceil((expDate - new Date()) / (1000 * 60 * 60 * 24));
                            
                            return `
                            <div style="
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                padding: 12px;
                                margin-bottom: 10px;
                                background: ${daysLeft <= 7 ? '#ffebee' : '#fff3e0'};
                                border-radius: 6px;
                                border-left: 4px solid ${daysLeft <= 7 ? '#f44336' : '#ff9800'};
                            ">
                                <div>
                                    <div style="font-weight: 600;">${escapeHtml(item.name)}</div>
                                    <div style="font-size: 14px; color: var(--text-gray);">
                                        ${item.category} • Expires in ${daysLeft} days
                                    </div>
                                </div>
                                <button onclick="setReminder(${item.id})" 
                                        style="background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                    Set Reminder
                                </button>
                            </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                ` : `
                <div style="
                    text-align: center;
                    padding: 40px;
                    background: #e8f5e8;
                    border-radius: 8px;
                    color: #2e7d32;
                ">
                    <i class="fas fa-check-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                    <h3>No Upcoming Expirations</h3>
                    <p>All safety content is current and doesn't require immediate attention.</p>
                </div>
                `}
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--dark-gray);">
                    <h4><i class="fas fa-cog" style="color: #3498db;"></i> Reminder Settings</h4>
                    <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <button onclick="setBulkReminders('weekly')" 
                                style="background: #9b59b6; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-calendar-week"></i> Weekly Digest
                        </button>
                        <button onclick="setBulkReminders('daily')" 
                                style="background: #e67e22; color: white; border: none; padding: 12px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-bell"></i> Daily Alerts
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8f9fa; padding: 15px; border-radius: 0 0 8px 8px;">
                <button class="btn btn-secondary close-modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    setupModalClose(modal);
}

// Set individual reminder
function setReminder(id) {
    const item = contentData.find(i => i.id === id);
    if (item) {
        alert(`Reminder set for: ${item.name}\n\nYou will receive notifications about this safety content's expiration.`);
        
        // Visual feedback
        const button = event.target;
        button.innerHTML = '<i class="fas fa-check"></i> Set';
        button.style.background = '#27ae60';
        button.disabled = true;
    }
}

// Set bulk reminders
function setBulkReminders(frequency) {
    const frequencyText = frequency === 'weekly' ? 'weekly digest emails' : 'daily alerts';
    alert(`Bulk reminders configured for ${frequencyText}.\n\nYou will receive updates about expiring safety content.`);
}

// Ensure Utils is available or provide fallback
if (typeof Utils === 'undefined') {
    console.warn('Utils not loaded. Using standalone mode.');
    
    // Provide minimal Utils fallback
    window.Utils = {
        SessionManager: {
            login: function(userData) {
                localStorage.setItem('userSession', JSON.stringify({
                    isLoggedIn: true,
                    userData: userData,
                    loginTime: new Date().toISOString(),
                    lastActivity: new Date().toISOString()
                }));
            },
            logout: function() {
                localStorage.removeItem('userSession');
            },
            getCurrentUser: function() {
                const session = localStorage.getItem('userSession');
                return session ? JSON.parse(session).userData : null;
            }
        }
    };
}

console.log('Safety-enhanced Content Repository module initialized with promotional features');



// Clean upload function - simplified for direct file upload
async function handleUpload() {
    console.log('Starting clean upload process...');
    
    const fileInput = document.querySelector('#fileInput');
    const contentName = document.querySelector('#contentName').value;
    const category = document.querySelector('#contentCategory').value;
    const tags = document.querySelector('#contentTags').value;
    const description = document.querySelector('#contentDescription').value;

    // Simple validation
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('Please select a file');
        return;
    }
    
    if (!category) {
        alert('Please select a category');
        return;
    }
    
    if (!description.trim()) {
        alert('Please enter a description');
        return;
    }

    try {
        // Add to content data
        const newContent = {
            id: Date.now(),
            name: contentName || fileInput.files[0].name,
            category: SAFETY_CATEGORIES[category]?.name || category,
            size: formatBytes(fileInput.files[0].size),
            modified: new Date().toLocaleDateString(),
            status: 'pending',
            version: '1.0',
            tags: tags.split(',').map(tag => tag.trim()).filter(tag => tag),
            description: description
        };
        
        contentData.unshift(newContent);
        console.log('Added content:', newContent);
        
        // Force render update
        renderContentTable(contentData);
        updateDashboardStats();
        
        // Close modal
        const modal = document.querySelector('.modal');
        if (modal) {
            modal.remove();
        }
        
        // Show success message
        alert('File uploaded successfully! It is now in the pending review queue.');
        
        // Scroll to top to show new content
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
    } catch (error) {
        console.error('Upload error:', error);
        alert('Upload failed: ' + error.message);
    }
}

// Approve pending content and move to main section
function approveContent(contentId) {
    console.log('Approving content ID:', contentId);
    
    const contentIndex = contentData.findIndex(item => item.id === contentId);
    if (contentIndex !== -1) {
        // Update status to approved
        contentData[contentIndex].status = 'approved';
        contentData[contentIndex].approvedDate = new Date().toLocaleDateString();
        
        console.log('Content approved:', contentData[contentIndex]);
        
        // Re-render to show status change
        renderContentTable(contentData);
        updateDashboardStats();
        
        // Show confirmation
        alert('Content approved and moved to main section!');
    }
}

// Reject pending content
function rejectContent(contentId) {
    console.log('Rejecting content ID:', contentId);
    
    const contentIndex = contentData.findIndex(item => item.id === contentId);
    if (contentIndex !== -1) {
        // Update status to rejected
        contentData[contentIndex].status = 'rejected';
        contentData[contentIndex].rejectedDate = new Date().toLocaleDateString();
        
        console.log('Content rejected:', contentData[contentIndex]);
        
        // Re-render to show status change
        renderContentTable(contentData);
        updateDashboardStats();
        
        // Show confirmation
        alert('Content rejected!');
    }
}

// Preview content with safety information
function previewContent(id) {
    const item = contentData.find(i => i.id === id);
    if (!item) return;

    // Create preview modal with proper color contrast
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--accent), #ff6b35); color: white;">
                <h3><i class="fas fa-eye"></i> Preview: ${escapeHtml(item.name)}</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" style="background: #2c3e50; color: white;">
                <div style="margin-bottom: 20px; padding: 15px; background: rgba(255, 255, 255, 0.1); border-radius: 8px; border-left: 4px solid var(--accent);">
                    <strong><i class="fas fa-shield-alt"></i> Safety Information:</strong>
                    <div style="margin-top: 10px; font-size: 14px;">
                        <div style="margin-bottom: 8px;"><strong style="color: #f1c40f;">Category:</strong> <span style="color: white;">${item.category}</span></div>
                        <div style="margin-bottom: 8px;"><strong style="color: #f1c40f;">Size:</strong> <span style="color: white;">${item.size}</span></div>
                        <div style="margin-bottom: 8px;"><strong style="color: #f1c40f;">Last Modified:</strong> <span style="color: white;">${item.modified}</span></div>
                        <div style="margin-bottom: 8px;"><strong style="color: #f1c40f;">Status:</strong> <span class="status-badge status-${item.status}" style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; background: ${item.status === 'approved' ? '#27ae60' : item.status === 'pending' ? '#f39c12' : '#95a5a6'}; color: ${item.status === 'draft' ? '#2c3e50' : 'white'};">${capitalizeFirst(item.status)}</span></div>
                        ${item.tags ? `<div><strong style="color: #f1c40f;">Tags:</strong> <span style="color: white;">${item.tags.join(', ')}</span></div>` : ''}
                    </div>
                </div>
                
                <div style="background: #34495e; padding: 20px; border-radius: 8px; min-height: 200px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; border: 1px solid #4a5f7a;">
                    ${getPreviewContent(item)}
                </div>
                
                ${item.description ? `
                <div style="margin-top: 20px; padding: 15px; background: rgba(255, 255, 255, 0.08); border-radius: 8px; border-left: 4px solid #3498db;">
                    <strong style="color: #3498db;"><i class="fas fa-info-circle"></i> Description:</strong>
                    <div style="margin-top: 10px; color: white; line-height: 1.5;">${escapeHtml(item.description)}</div>
                </div>` : ''}
                
                <div style="margin-top: 20px; padding: 15px; background: rgba(39, 174, 96, 0.15); border-radius: 8px; border-left: 4px solid #27ae60;">
                    <strong style="color: #27ae60;"><i class="fas fa-lightbulb"></i> Safety Tip:</strong>
                    <div style="margin-top: 10px; color: white; font-size: 14px; line-height: 1.5;">
                        Always verify safety information with official sources before implementation.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #34495e; padding: 15px; border-radius: 0 0 8px 8px; border-top: 1px solid #4a5f7a;">
                <button class="btn" onclick="downloadContent(${id})" style="background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; margin-right: 10px;">
                    <i class="fas fa-download"></i> Download Safety Resource
                </button>
                <button class="btn btn-secondary close-modal" style="background: #95a5a6; color: #2c3e50; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    setupModalClose(modal);
    showSafetyPromotion();
}

// Download content with safety confirmation
async function downloadContent(id) {
    const item = contentData.find(i => i.id === id);
    if (!item) return;
    
    // Show safety confirmation
    if (!confirm(`Download safety resource: ${item.name}\n\nRemember to verify information with official sources before use.`)) {
        return;
    }
    
    try {
        // Simulate download
        const downloadIndicator = document.createElement('div');
        downloadIndicator.innerHTML = `
            <div style="
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0,0,0,0.8);
                color: white;
                padding: 30px;
                border-radius: 10px;
                text-align: center;
                z-index: 10000;
            ">
                <i class="fas fa-download" style="font-size: 32px; margin-bottom: 15px;"></i>
                <div>Preparing safety resource download...</div>
                <div style="font-size: 14px; margin-top: 10px; opacity: 0.8;">${item.name}</div>
            </div>
        `;
        document.body.appendChild(downloadIndicator);
        
        // Simulate processing time
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        downloadIndicator.remove();
        
        // Create download link
        const blob = new Blob([`Safety Content: ${item.name}
Category: ${item.category}
Description: ${item.description || 'No description'}

This is a simulated safety resource for demonstration purposes.`], 
                             {type: 'text/plain'});
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = item.name.replace(/\.[^/.]+$/, '') + '_safety_resource.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        // Show success message
        alert(`Safety resource downloaded successfully!

File: ${a.download}

Remember to verify all safety information with official sources.`);
        
    } catch (error) {
        console.error('Download error:', error);
        if (document.querySelector('[style*="position: fixed"]')) {
            document.querySelector('[style*="position: fixed"]')?.remove();
        }
        alert('Download failed. Please try again.');
    }
}

// Edit content with safety validation
function editContent(id) {
    const item = contentData.find(i => i.id === id);
    if (!item) return;

    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
                <h3><i class="fas fa-edit"></i> Edit Safety Content</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div style="padding: 20px; background: #f8f9fa;">
                <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                    <strong><i class="fas fa-exclamation-triangle"></i> Important:</strong>
                    <div style="font-size: 14px; margin-top: 8px;">
                        Changes to safety content affect community safety. Please ensure all information is accurate and verified.
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <div class="form-group">
                        <label for="editName"><i class="fas fa-file-signature"></i> Content Name</label>
                        <input type="text" id="editName" value="${escapeHtml(item.name)}" required>
                    </div>
                    <div class="form-group">
                        <label for="editCategory"><i class="fas fa-folder"></i> Safety Category</label>
                        <select id="editCategory">
                            <option value="emergency" ${item.category.includes('Emergency') ? 'selected' : ''}>Emergency Response</option>
                            <option value="fire" ${item.category.includes('Fire') ? 'selected' : ''}>Fire Safety</option>
                            <option value="health" ${item.category.includes('Health') ? 'selected' : ''}>Public Health</option>
                            <option value="disaster" ${item.category.includes('Disaster') ? 'selected' : ''}>Disaster Preparedness</option>
                            <option value="traffic" ${item.category.includes('Traffic') ? 'selected' : ''}>Traffic Safety</option>
                            <option value="cyber" ${item.category.includes('Cyber') ? 'selected' : ''}>Cyber Security</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editStatus"><i class="fas fa-flag"></i> Status</label>
                        <select id="editStatus">
                            <option value="draft" ${item.status === 'draft' ? 'selected' : ''}>Draft</option>
                            <option value="pending" ${item.status === 'pending' ? 'selected' : ''}>Pending Review</option>
                            <option value="approved" ${item.status === 'approved' ? 'selected' : ''}>Approved</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editTags"><i class="fas fa-tags"></i> Safety Tags (comma separated)</label>
                        <input type="text" id="editTags" value="${item.tags ? item.tags.join(', ') : ''}">
                    </div>
                    <div class="form-group">
                        <label for="editDescription"><i class="fas fa-align-left"></i> Safety Description</label>
                        <textarea id="editDescription" rows="4">${escapeHtml(item.description || '')}</textarea>
                    </div>
                    <div class="modal-footer" style="background: #f8f9fa; padding: 15px; border-radius: 0 0 8px 8px;">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                            <i class="fas fa-save"></i> Save Safety Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const form = modal.querySelector('#editForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await updateContent(id);
        modal.remove();
    });

    setupModalClose(modal);
    showSafetyPromotion();
}

// Update content with safety logging
async function updateContent(id) {
    const formData = {
        name: document.querySelector('#editName').value,
        category: document.querySelector('#editCategory').value,
        status: document.querySelector('#editStatus').value,
        tags: document.querySelector('#editTags').value.split(',').map(tag => tag.trim()).filter(tag => tag),
        description: document.querySelector('#editDescription').value
    };

    try {
        // Validate safety content
        if (!formData.description.trim()) {
            alert('Safety description is required');
            return;
        }
        
        // Update in mock data
        const itemIndex = contentData.findIndex(i => i.id === id);
        if (itemIndex !== -1) {
            contentData[itemIndex] = {
                ...contentData[itemIndex],
                ...formData,
                category: SAFETY_CATEGORIES[formData.category]?.name || formData.category,
                modified: new Date().toLocaleDateString()
            };
        }
        
        alert('Safety content updated successfully!');
        await loadContentData();
        renderContentTable();
        updateDashboardStats();
        
        // Log safety update
        console.log(`Safety content updated: ${formData.name} (${formData.category})`);
        
    } catch (error) {
        console.error('Update error:', error);
        alert('Update failed. Please try again.');
    }
}

// Delete content with safety confirmation
async function deleteContent(id) {
    const item = contentData.find(i => i.id === id);
    if (!item) return;
    
    // Enhanced safety confirmation
    const confirmed = confirm(
        `⚠️ DELETE SAFETY RESOURCE ⚠️\n\n` +
        `Are you sure you want to permanently delete:\n` +
        `${item.name}\n\n` +
        `Category: ${item.category}\n` +
        `Status: ${capitalizeFirst(item.status)}\n\n` +
        `WARNING: This action cannot be undone and may affect community safety resources.`
    );
    
    if (!confirmed) {
        return;
    }
    
    // Double confirmation for approved content
    if (item.status === 'approved') {
        const doubleConfirm = confirm(
            `🚨 FINAL WARNING 🚨\n\n` +
            `This is APPROVED safety content that may be actively used.\n\n` +
            `Are you ABSOLUTELY SURE you want to delete this resource?`
        );
        
        if (!doubleConfirm) {
            return;
        }
    }

    try {
        // Remove from mock data
        contentData = contentData.filter(i => i.id !== id);
        
        alert('Safety content deleted successfully!');
        await loadContentData();
        renderContentTable();
        updateDashboardStats();
        
        // Show deletion confirmation
        const deletionNotice = document.createElement('div');
        deletionNotice.innerHTML = `
            <div style="
                background: linear-gradient(135deg, #e74c3c, #c0392b);
                color: white;
                padding: 15px;
                border-radius: 8px;
                margin: 15px 0;
                text-align: center;
                animation: shake 0.5s ease-in-out;
            ">
                <i class="fas fa-trash-alt" style="margin-right: 10px;"></i>
                Safety resource permanently deleted
                <button onclick="this.parentElement.parentElement.remove()" 
                        style="float: right; background: transparent; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
            </div>
        `;
        
        const header = document.querySelector('.header');
        if (header) {
            header.parentNode.insertBefore(deletionNotice, header.nextSibling);
        }
        
        // Log deletion
        console.log(`Safety content deleted: ${item.name} (${item.category})`);
        
    } catch (error) {
        console.error('Delete error:', error);
        alert('Delete failed. Please try again.');
    }
}

// Update dashboard statistics with safety metrics
function updateDashboardStats() {
    // Update asset library counts with safety categories
    const documentsCount = contentData.filter(item => item.category.includes('Document')).length;
    const imagesCount = contentData.filter(item => item.category.includes('Image')).length;
    const videosCount = contentData.filter(item => item.category.includes('Video')).length;
    const audioCount = contentData.filter(item => item.category.includes('Audio')).length;
    
    // Safety-specific counts
    const emergencyCount = contentData.filter(item => item.tags && item.tags.includes('#emergency')).length;
    const fireSafetyCount = contentData.filter(item => item.tags && item.tags.includes('#fire')).length;
    const healthCount = contentData.filter(item => item.tags && item.tags.includes('#health')).length;

    // Update counts in asset library
    const categoryCounts = document.querySelectorAll('.category-count');
    if (categoryCounts.length >= 4) {
        categoryCounts[0].textContent = `${documentsCount} safety docs`;
        categoryCounts[1].textContent = `${imagesCount} safety images`;
        categoryCounts[2].textContent = `${videosCount} training videos`;
        categoryCounts[3].textContent = `${audioCount} alert files`;
    }

    // Update approval workflow
    const pendingCount = contentData.filter(item => item.status === 'pending').length;
    const approvedCount = contentData.filter(item => item.status === 'approved').length;
    const draftCount = contentData.filter(item => item.status === 'draft').length;

    const approvalCounts = document.querySelectorAll('.approval-item .count');
    if (approvalCounts.length >= 3) {
        approvalCounts[0].textContent = pendingCount;
        approvalCounts[1].textContent = approvedCount;
        approvalCounts[2].textContent = draftCount;
    }

    // Update content analytics with safety metrics
    const totalFiles = contentData.length;
    const totalSizeMB = Math.round(totalFiles * 2.5); // Average file size estimate
    const activeUsers = 247 + Math.floor(totalFiles / 8);
    const reuseRate = Math.min(85 + Math.floor(totalFiles / 3), 98);
    const downloadsToday = 42 + Math.floor(totalFiles / 4);
    
    // Safety impact metrics
    const safetyTopics = [...new Set(contentData.flatMap(item => 
        item.tags ? item.tags.filter(tag => tag.startsWith('#')) : []
    ))].length;

    const statValues = document.querySelectorAll('.stat-value');
    if (statValues.length >= 4) {
        statValues[0].innerHTML = `${totalSizeMB}<span style="font-size: 14px; opacity: 0.8;"> MB</span>`;
        statValues[1].textContent = activeUsers;
        statValues[2].innerHTML = `${reuseRate}<span style="font-size: 14px; opacity: 0.8;">%</span>`;
        statValues[3].textContent = downloadsToday;
    }
    
    // Update recently added section with safety content
    updateRecentlyAdded();
    
    // Update popular tags with safety focus
    updatePopularTags();
}

// Update recently added section with dynamic safety content
function updateRecentlyAdded() {
    const recentContainer = document.querySelector('.recent-items');
    if (!recentContainer) return;
    
    // Get 4 most recent items
    const recentItems = [...contentData]
        .sort((a, b) => new Date(b.modified) - new Date(a.modified))
        .slice(0, 4);
    
    recentContainer.innerHTML = '';
    
    recentItems.forEach(item => {
        const recentItem = document.createElement('div');
        recentItem.className = 'recent-item';
        recentItem.innerHTML = `
            <div style="
                width: 40px;
                height: 40px;
                background: linear-gradient(135deg, var(--accent), #ff6b35);
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                margin-right: 15px;
                flex-shrink: 0;
            ">
                ${getFileIcon(item.category)}
            </div>
            <div>
                <div class="item-name" style="font-weight: 600; margin-bottom: 5px;">${escapeHtml(item.name)}</div>
                <div class="item-date" style="font-size: 13px; color: var(--text-gray);">
                    <i class="fas fa-shield-alt" style="margin-right: 5px; color: var(--accent);"></i>
                    ${item.category} • ${item.size}
                </div>
            </div>
        `;
        recentContainer.appendChild(recentItem);
    });
}

// Update popular tags with safety focus
function updatePopularTags() {
    const tagContainer = document.querySelector('[style*="flex-wrap: wrap"]');
    if (!tagContainer) return;
    
    // Generate popular safety tags
    const tagCounts = {};
    contentData.forEach(item => {
        if (item.tags) {
            item.tags.forEach(tag => {
                tagCounts[tag] = (tagCounts[tag] || 0) + 1;
            });
        }
    });
    
    // Sort by frequency and take top 8
    const sortedTags = Object.entries(tagCounts)
        .sort(([,a], [,b]) => b - a)
        .slice(0, 8)
        .map(([tag]) => tag);
    
    // Add some essential safety tags if not present
    const essentialTags = ['#emergency', '#safety', '#health', '#prevention', '#awareness'];
    essentialTags.forEach(tag => {
        if (!sortedTags.includes(tag)) {
            sortedTags.push(tag);
        }
    });
    
    tagContainer.innerHTML = '';
    
    sortedTags.slice(0, 8).forEach(tag => {
        const tagElement = document.createElement('span');
        tagElement.style.cssText = `
            padding: 8px 15px;
            background: linear-gradient(135deg, var(--dark-gray), #34495e);
            border-radius: 20px;
            font-size: 14px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        `;
        
        tagElement.innerHTML = `
            <i class="fas fa-tag" style="margin-right: 5px; color: var(--accent);"></i>
            ${tag}
        `;
        
        tagElement.addEventListener('mouseover', function() {
            this.style.background = 'linear-gradient(135deg, var(--accent), #ff6b35)';
            this.style.transform = 'scale(1.05)';
        });
        
        tagElement.addEventListener('mouseout', function() {
            this.style.background = 'linear-gradient(135deg, var(--dark-gray), #34495e)';
            this.style.transform = 'scale(1)';
        });
        
        tagElement.addEventListener('click', function() {
            searchContent(tag.replace('#', ''));
        });
        
        tagContainer.appendChild(tagElement);
    });
    
    // Add "Add New Tag" button
    const addButton = document.createElement('button');
    addButton.className = 'btn btn-secondary';
    addButton.style.cssText = 'width: 100%; margin-top: 20px;';
    addButton.innerHTML = '<i class="fas fa-plus"></i> Add New Safety Tag';
    addButton.addEventListener('click', function() {
        const newTag = prompt('Enter new safety tag (without #):');
        if (newTag && newTag.trim()) {
            alert(`Tag #${newTag.trim()} would be added to the system`);
        }
    });
    
    tagContainer.parentNode.appendChild(addButton);
}

// Add CSS animations for better UX
function addSafetyStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .safety-promotion-banner {
            animation: fadeIn 0.5s ease-in;
        }
        
        .recent-item:hover {
            transform: translateX(5px);
            transition: transform 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
            transition: all 0.3s ease;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-approved {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #f39c12, #f1c40f);
            color: white;
        }
        
        .status-draft {
            background: linear-gradient(135deg, #95a5a6, #bdc3c7);
            color: #2c3e50;
        }
        
        .file-type {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent), #ff6b35);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }
        
        .action-icons i {
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .action-icons i:hover {
            background: rgba(255, 107, 53, 0.2);
            transform: scale(1.2);
        }
        
        .modal-content {
            animation: slideInDown 0.3s ease-out;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .module-grid {
                grid-template-columns: 1fr;
            }
            
            .search-filter {
                flex-direction: column;
                gap: 10px;
            }
        }
    `;
    document.head.appendChild(style);
}

// Initialize safety features
function initializeSafetyFeatures() {
    addSafetyStyles();
    showRandomSafetyTip();
    
    // Add safety footer message
    const footer = document.createElement('div');
    footer.innerHTML = `
        <div style="
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            border-radius: 8px;
            font-size: 14px;
        ">
            <i class="fas fa-shield-alt" style="margin-right: 10px; color: #f1c40f;"></i>
            <strong>Public Safety Content Repository</strong> - Building safer communities through shared knowledge
            <div style="margin-top: 10px; font-size: 12px; opacity: 0.8;">
                All content is for educational purposes. Verify information with official sources.
            </div>
        </div>
    `;
    
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.appendChild(footer);
    }
}

// Enhanced file icon with safety theme
function getFileIcon(category) {
    const lowerCategory = category.toLowerCase();
    
    if (lowerCategory.includes('emergency')) return '<i class="fas fa-exclamation-triangle"></i>';
    if (lowerCategory.includes('fire')) return '<i class="fas fa-fire"></i>';
    if (lowerCategory.includes('health')) return '<i class="fas fa-heartbeat"></i>';
    if (lowerCategory.includes('disaster')) return '<i class="fas fa-cloud-showers-heavy"></i>';
    if (lowerCategory.includes('traffic')) return '<i class="fas fa-car-crash"></i>';
    if (lowerCategory.includes('cyber')) return '<i class="fas fa-shield-alt"></i>';
    if (lowerCategory.includes('image')) return '<i class="fas fa-image"></i>';
    if (lowerCategory.includes('video')) return '<i class="fas fa-video"></i>';
    if (lowerCategory.includes('audio')) return '<i class="fas fa-microphone"></i>';
    
    return '<i class="fas fa-file-alt"></i>';
}

function getPreviewContent(item) {
    const ext = item.name.split('.').pop().toLowerCase();
    
    // For image files
    if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
        return `<div style="text-align: center; color: white;">
            <i class="fas fa-file-image" style="font-size: 48px; color: var(--accent); margin-bottom: 15px;"></i>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">${escapeHtml(item.name)}</div>
            <div style="color: #bdc3c7; font-size: 14px;">Image file preview not available in demo mode</div>
        </div>`;
    
    // For PDF files
    } else if (['pdf'].includes(ext)) {
        return `<div style="text-align: center; color: white;">
            <i class="fas fa-file-pdf" style="font-size: 48px; color: #e74c3c; margin-bottom: 15px;"></i>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">${escapeHtml(item.name)}</div>
            <div style="color: #bdc3c7; font-size: 14px;">PDF preview not available in demo mode</div>
        </div>`;
    
    // For document files
    } else if (['doc', 'docx'].includes(ext)) {
        return `<div style="text-align: center; color: white;">
            <i class="fas fa-file-word" style="font-size: 48px; color: #3498db; margin-bottom: 15px;"></i>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">${escapeHtml(item.name)}</div>
            <div style="color: #bdc3c7; font-size: 14px;">Document preview not available in demo mode</div>
        </div>`;
    
    // For video files
    } else if (['mp4', 'avi', 'mov'].includes(ext)) {
        return `<div style="text-align: center; color: white;">
            <i class="fas fa-file-video" style="font-size: 48px; color: #9b59b6; margin-bottom: 15px;"></i>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">${escapeHtml(item.name)}</div>
            <div style="color: #bdc3c7; font-size: 14px;">Video preview not available in demo mode</div>
        </div>`;
    
    // For audio files
    } else if (['mp3', 'wav', 'ogg'].includes(ext)) {
        return `<div style="text-align: center; color: white;">
            <i class="fas fa-file-audio" style="font-size: 48px; color: #2ecc71; margin-bottom: 15px;"></i>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">${escapeHtml(item.name)}</div>
            <div style="color: #bdc3c7; font-size: 14px;">Audio preview not available in demo mode</div>
        </div>`;
    
    // For other file types
    } else {
        return `<div style="text-align: center; color: white;">
            <i class="fas fa-file" style="font-size: 48px; color: var(--accent); margin-bottom: 15px;"></i>
            <div style="font-size: 18px; font-weight: 600; margin-bottom: 10px;">${escapeHtml(item.name)}</div>
            <div style="color: #bdc3c7; font-size: 14px;">Preview not available for this file type</div>
        </div>`;
    }
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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