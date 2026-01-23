// Event & Seminar Management JavaScript - Fixed Version
let eventsData = [];
let attendeesData = [];
let filteredEvents = [];

// Enhanced data management with validation
function validateEventData(event) {
    const requiredFields = ['title', 'type', 'date', 'location'];
    for (const field of requiredFields) {
        if (!event[field] || event[field].toString().trim() === '') {
            return { valid: false, message: `Field '${field}' is required` };
        }
    }
    
    if (event.capacity && isNaN(parseInt(event.capacity))) {
        return { valid: false, message: 'Capacity must be a number' };
    }
    
    return { valid: true };
}

// Initialize sample data
function initializeSampleData() {
    eventsData = [
        {
            id: 1,
            title: "Fire Safety Training",
            type: "training",
            date: "2026-02-15T10:00",
            location: "Community Center Hall A",
            description: "Essential fire safety protocols and emergency procedures",
            registrations: 35,
            capacity: 50,
            status: "upcoming",
            createdAt: new Date().toISOString()
        },
        {
            id: 2,
            title: "Health & Wellness Seminar",
            type: "seminar",
            date: "2026-02-20T14:00",
            location: "Barangay Health Center",
            description: "Learn about maintaining good health and wellness practices",
            registrations: 42,
            capacity: 60,
            status: "upcoming",
            createdAt: new Date().toISOString()
        },
        {
            id: 3,
            title: "Skills Development Workshop",
            type: "workshop",
            date: "2026-02-25T09:00",
            location: "Multi-Purpose Hall",
            description: "Hands-on workshop for developing practical skills",
            registrations: 28,
            capacity: 40,
            status: "upcoming",
            createdAt: new Date().toISOString()
        }
    ];

    attendeesData = [
        { id: 1, eventId: 1, name: "Juan Dela Cruz", email: "juan@example.com", phone: "09123456789", status: "confirmed", registrationDate: new Date().toISOString() },
        { id: 2, eventId: 1, name: "Maria Santos", email: "maria@example.com", phone: "09987654321", status: "confirmed", registrationDate: new Date().toISOString() },
        { id: 3, eventId: 2, name: "Pedro Reyes", email: "pedro@example.com", phone: "09123459876", status: "pending", registrationDate: new Date().toISOString() },
        { id: 4, eventId: 3, name: "Ana Lim", email: "ana@example.com", phone: "09876543210", status: "checked-in", registrationDate: new Date().toISOString() },
        { id: 5, eventId: 3, name: "Jose Garcia", email: "jose@example.com", phone: "09123456543", status: "checked-in", registrationDate: new Date().toISOString() }
    ];
}

// Update all views
function updateAllViews() {
    updateUpcomingEvents();
    updateEventsTable();
    updateStatistics();
    updateCalendarView();
}

// Update Upcoming Events List
function updateUpcomingEvents() {
    const eventList = document.querySelector('.event-list');
    if (!eventList) return;

    const upcomingEvents = eventsData.filter(e => e.status === 'upcoming').slice(0, 4);

    eventList.innerHTML = upcomingEvents.map(event => {
        const date = new Date(event.date);
        const percentage = Math.round((event.registrations / event.capacity) * 100);

        return `
            <div class="event-item" onclick="viewEventDetails(${event.id})">
                <div class="event-date">
                    <div class="date-day">${date.getDate()}</div>
                    <div class="date-month">${date.toLocaleString('en-US', { month: 'short' }).toUpperCase()}</div>
                </div>
                <div class="event-details">
                    <div class="event-title">${event.title}</div>
                    <div class="event-location">${event.location}</div>
                    <div class="event-attendees">${event.registrations} registered • Capacity: ${event.capacity}</div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: ${percentage}%"></div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Update Events Table
function updateEventsTable() {
    const tableBody = document.querySelector('.events-table tbody');
    if (!tableBody) return;

    const eventsToDisplay = filteredEvents && filteredEvents.length > 0 ? filteredEvents : eventsData;

    tableBody.innerHTML = eventsToDisplay.map(event => {
        const date = new Date(event.date);
        const formattedDate = date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        const formattedTime = date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        const percentage = event.capacity > 0 ? Math.round((event.registrations / event.capacity) * 100) : 0;

        const statusClass = `status-${event.status}`;
        const typeClass = `type-${event.type}`;

        return `
            <tr data-event-id="${event.id}">
                <td>
                    <div style="font-weight: 600; cursor: pointer;" onclick="viewEventDetails(${event.id})">${event.title}</div>
                    <div style="font-size: 12px; color: var(--text-gray);">${event.description}</div>
                </td>
                <td><span class="event-type ${typeClass}">${event.type.charAt(0).toUpperCase() + event.type.slice(1)}</span></td>
                <td>
                    <div>${formattedDate}</div>
                    <div style="font-size: 12px; color: var(--text-gray);">${formattedTime}</div>
                </td>
                <td>${event.location}</td>
                <td>
                    <div>${event.registrations}/${event.capacity}</div>
                    <div class="progress-container" style="height: 4px; margin-top: 5px;">
                        <div class="progress-bar" style="width: ${percentage}%; background-color: ${percentage >= 80 ? 'var(--success)' : percentage >= 60 ? 'var(--warning)' : 'var(--accent)'}"></div>
                    </div>
                </td>
                <td><span class="event-status ${statusClass}">${event.status.charAt(0).toUpperCase() + event.status.slice(1)}</span></td>
                <td>
                    <div class="event-actions">
                        <i class="fas fa-eye" title="View Details" onclick="viewEventDetails(${event.id})"></i>
                        <i class="fas fa-edit" title="Edit" onclick="editEvent(${event.id})"></i>
                        <i class="fas fa-users" title="Attendees" onclick="viewAttendees(${event.id})"></i>
                        <i class="fas fa-qrcode" title="QR Check-in" onclick="generateCheckInQR(${event.id})"></i>
                        <i class="fas fa-trash" title="Delete" onclick="deleteEvent(${event.id})" style="color: var(--danger);"></i>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    const tableFooter = document.querySelector('.events-table tfoot');
    if (tableFooter) {
        const eventCount = eventsToDisplay.length;
        const totalEvents = eventsData.length;
        tableFooter.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 10px; font-style: italic; color: var(--text-gray);">
                    Showing ${eventCount} of ${totalEvents} events
                </td>
            </tr>
        `;
    }
}

// Update Statistics
function updateStatistics() {
    const totalEvents = eventsData.length;
    const upcomingEvents = eventsData.filter(e => e.status === 'upcoming').length;
    const totalRegistrations = eventsData.reduce((sum, event) => sum + event.registrations, 0);
    const totalCapacity = eventsData.reduce((sum, event) => sum + event.capacity, 0);
    const avgAttendance = totalEvents > 0 ? Math.round((totalRegistrations / totalCapacity) * 100) : 0;
    const avgRegistrationsPerEvent = totalEvents > 0 ? Math.round(totalRegistrations / totalEvents) : 0;

    const statElements = document.querySelectorAll('.stat-item .stat-value');
    if (statElements.length >= 4) {
        statElements[0].textContent = upcomingEvents;
        statElements[1].textContent = totalRegistrations;
        statElements[2].textContent = avgAttendance + '%';
        statElements[3].textContent = avgRegistrationsPerEvent;
    }
}

// Update Calendar View
function updateCalendarView() {
    const calendarView = document.querySelector('.calendar-view');
    if (!calendarView) return;

    const upcomingEvents = eventsData
        .filter(e => e.status === 'upcoming')
        .sort((a, b) => new Date(a.date) - new Date(b.date))
        .slice(0, 3);

    const calendarItems = document.querySelector('.calendar-view .calendar-event-items') ||
        document.querySelector('.calendar-view');

    if (calendarItems) {
        calendarItems.innerHTML = upcomingEvents.map(event => {
            const date = new Date(event.date);
            const now = new Date();
            const isToday = date.toDateString() === now.toDateString();
            const percentage = Math.round((event.registrations / event.capacity) * 100);

            let timeText = isToday ? 'Today' : date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            timeText += ` • ${date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}`;

            let badgeClass = 'badge-success';
            if (percentage < 60) badgeClass = 'badge-info';
            else if (percentage < 80) badgeClass = 'badge-warning';

            let iconClass = 'calendar-event-icon ';
            if (event.type === 'workshop') iconClass += 'workshop';
            else if (event.type === 'seminar') iconClass += 'seminar';
            else if (event.type === 'training') iconClass += 'training';

            let icon = 'fa-calendar';
            if (event.type === 'workshop') icon = 'fa-user-md';
            else if (event.type === 'seminar') icon = 'fa-shield-alt';
            else if (event.type === 'training') icon = 'fa-exclamation-triangle';

            return `
                <div class="calendar-event-item" onclick="viewEventDetails(${event.id})">
                    <div class="${iconClass}">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="calendar-event-details">
                        <div class="calendar-event-title">${event.title}</div>
                        <div class="calendar-event-time">${timeText}</div>
                        <div class="calendar-event-location">${event.location}</div>
                    </div>
                    <span class="badge ${badgeClass}">${percentage}% Full</span>
                </div>
            `;
        }).join('');
    }
}

// Initialize filters
function initializeFilters() {
    const filterItems = document.querySelectorAll('.filter-item');
    filterItems.forEach(item => {
        item.addEventListener('click', function () {
            const filterType = this.textContent.trim();
            applyEventFilter(filterType);
            
            filterItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Apply event filter
function applyEventFilter(filterType) {
    switch (filterType) {
        case 'All Events':
            filteredEvents = [...eventsData];
            break;
        case 'Upcoming':
            filteredEvents = eventsData.filter(e => {
                const eventDate = new Date(e.date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return eventDate >= today && e.status !== 'cancelled';
            });
            break;
        case 'Completed':
            filteredEvents = eventsData.filter(e => {
                const eventDate = new Date(e.date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return eventDate < today || e.status === 'completed';
            });
            break;
        case 'Cancelled':
            filteredEvents = eventsData.filter(e => e.status === 'cancelled');
            break;
        case 'Seminars':
            filteredEvents = eventsData.filter(e => e.type === 'seminar');
            break;
        case 'Workshops':
            filteredEvents = eventsData.filter(e => e.type === 'workshop');
            break;
        case 'Trainings':
            filteredEvents = eventsData.filter(e => e.type === 'training');
            break;
        default:
            filteredEvents = [...eventsData];
    }
    
    updateEventsTable();
    showNotification(`Showing: ${filterType}`, 'info');
}

// Set active navigation
function setActiveNavigation() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref && linkHref.includes(currentPage)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// Debounce utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Set up event listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    if (searchInput) {
        const debouncedSearch = debounce((value) => {
            searchEvents(value);
        }, 300);
        
        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value);
        });
    }

    // Quick action buttons
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.querySelector('span')?.textContent;
            switch (action) {
                case 'New Event':
                    createNewEvent();
                    break;
                case 'Send Reminders':
                    sendReminders();
                    break;
                case 'Print Materials':
                    printMaterials();
                    break;
                case 'Export Attendees':
                    exportAttendees();
                    break;
                case 'Generate Reports':
                    generateReports();
                    break;
            }
        });
    });
}

// Search Events
function searchEvents(query) {
    if (query.length < 1) {
        filteredEvents = [];
        updateEventsTable();
        return;
    }

    filteredEvents = eventsData.filter(event =>
        event.title.toLowerCase().includes(query.toLowerCase()) ||
        event.location.toLowerCase().includes(query.toLowerCase()) ||
        event.description.toLowerCase().includes(query.toLowerCase())
    );

    updateEventsTable();
}

// Modal Management
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Create New Event
function createNewEvent() {
    clearEventForm('create');
    openModal('createEventModal');
}

function clearEventForm(mode) {
    const prefix = mode === 'edit' ? 'edit' : 'event';
    
    const titleEl = document.getElementById(prefix + 'Title');
    if (titleEl) titleEl.value = '';
    
    const typeEl = document.getElementById(prefix + 'Type');
    if (typeEl) typeEl.value = 'seminar';
    
    const dateTimeEl = document.getElementById(prefix + 'DateTime');
    if (dateTimeEl) dateTimeEl.value = '';
    
    const locationEl = document.getElementById(prefix + 'Location');
    if (locationEl) locationEl.value = '';
    
    const descEl = document.getElementById(prefix + 'Description');
    if (descEl) descEl.value = '';
    
    const capacityEl = document.getElementById(prefix + 'Capacity');
    if (capacityEl) capacityEl.value = '50';
    
    if (mode === 'edit') {
        const idEl = document.getElementById('editEventId');
        if (idEl) idEl.value = '';
    }
}

function saveNewEvent() {
    const title = document.getElementById('eventTitle').value;
    const type = document.getElementById('eventType').value;
    const dateTime = document.getElementById('eventDateTime').value;
    const location = document.getElementById('eventLocation').value;
    const description = document.getElementById('eventDescription').value;
    const capacity = parseInt(document.getElementById('eventCapacity').value) || 50;

    const validation = validateEventData({ title, type, date: dateTime, location, capacity });
    if (!validation.valid) {
        showNotification(validation.message, 'error');
        return;
    }

    const duplicateEvent = eventsData.find(e => e.title.toLowerCase() === title.toLowerCase());
    if (duplicateEvent) {
        showNotification('An event with this title already exists', 'error');
        return;
    }

    const newEvent = {
        id: Date.now(),
        title: title,
        type: type,
        date: dateTime,
        location: location,
        description: description,
        registrations: 0,
        capacity: capacity,
        status: "upcoming",
        createdAt: new Date().toISOString()
    };

    eventsData.push(newEvent);
    updateAllViews();
    closeModal('createEventModal');
    showNotification(`Event "${title}" created successfully!`, 'success');
}

// Edit Event
function editEvent(id) {
    const event = eventsData.find(e => e.id === id);
    if (!event) {
        showNotification('Event not found', 'error');
        return;
    }

    clearEventForm('edit');
    
    document.getElementById('editEventId').value = id;
    document.getElementById('editTitle').value = event.title;
    document.getElementById('editType').value = event.type;
    document.getElementById('editDateTime').value = event.date.substring(0, 16);
    document.getElementById('editLocation').value = event.location;
    document.getElementById('editDescription').value = event.description;
    document.getElementById('editCapacity').value = event.capacity;

    openModal('editEventModal');
}

function saveEventChanges() {
    const id = parseInt(document.getElementById('editEventId').value);
    const event = eventsData.find(e => e.id === id);

    if (!event) {
        showNotification('Event not found', 'error');
        return;
    }
    
    const title = document.getElementById('editTitle').value;
    const type = document.getElementById('editType').value;
    const dateTime = document.getElementById('editDateTime').value;
    const location = document.getElementById('editLocation').value;
    const description = document.getElementById('editDescription').value;
    const capacity = parseInt(document.getElementById('editCapacity').value) || 50;
    
    const validation = validateEventData({ title, type, date: dateTime, location, capacity });
    if (!validation.valid) {
        showNotification(validation.message, 'error');
        return;
    }
    
    const duplicateEvent = eventsData.find(e => e.title.toLowerCase() === title.toLowerCase() && e.id !== id);
    if (duplicateEvent) {
        showNotification('An event with this title already exists', 'error');
        return;
    }

    event.title = title;
    event.type = type;
    event.date = dateTime;
    event.location = location;
    event.description = description;
    event.capacity = capacity;
    event.updatedAt = new Date().toISOString();

    updateAllViews();
    closeModal('editEventModal');
    showNotification(`Event "${event.title}" updated!`, 'success');
}

// Delete Event
function deleteEvent(id) {
    const event = eventsData.find(e => e.id === id);
    if (!event) {
        showNotification('Event not found', 'error');
        return;
    }

    showDeleteConfirmation(event);
}

function showDeleteConfirmation(event) {
    let confirmModal = document.getElementById('confirmDeleteModal');
    if (!confirmModal) {
        confirmModal = document.createElement('div');
        confirmModal.id = 'confirmDeleteModal';
        confirmModal.className = 'modal-overlay';
        confirmModal.innerHTML = `
            <div class="modal-content" style="max-width: 450px;">
                <div class="modal-header">
                    <h3>Confirm Deletion</h3>
                    <span class="close-modal" onclick="closeConfirmDeleteModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the event <strong id="deleteEventTitle"></strong>?</p>
                    <p>This action cannot be undone and will remove all associated data.</p>
                    <div class="form-group" style="margin-top: 15px;">
                        <label>
                            <input type="checkbox" id="confirmDeleteCheckbox" /> 
                            I understand this action cannot be undone
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeConfirmDeleteModal()">Cancel</button>
                    <button class="btn btn-danger" id="confirmDeleteBtn" disabled>Confirm Delete</button>
                </div>
            </div>
        `;
        document.body.appendChild(confirmModal);
        
        const checkbox = document.getElementById('confirmDeleteCheckbox');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        checkbox.addEventListener('change', function() {
            confirmBtn.disabled = !this.checked;
        });
    }
    
    document.getElementById('deleteEventTitle').textContent = event.title;
    
    const checkbox = document.getElementById('confirmDeleteCheckbox');
    if (checkbox) {
        checkbox.checked = false;
        document.getElementById('confirmDeleteBtn').disabled = true;
    }
    
    confirmModal.style.display = 'flex';
    
    document.getElementById('confirmDeleteBtn').onclick = function() {
        performEventDeletion(event.id);
        closeConfirmDeleteModal();
    };
}

function closeConfirmDeleteModal() {
    const confirmModal = document.getElementById('confirmDeleteModal');
    if (confirmModal) {
        confirmModal.style.display = 'none';
    }
}

function performEventDeletion(id) {
    const eventIndex = eventsData.findIndex(e => e.id === id);
    if (eventIndex !== -1) {
        const event = eventsData[eventIndex];
        eventsData.splice(eventIndex, 1);
        updateAllViews();
        showNotification(`Event "${event.title}" deleted!`, 'warning');
    }
}

// View Event Details
function viewEventDetails(id) {
    const event = eventsData.find(e => e.id === id);
    if (!event) {
        showNotification('Event not found', 'error');
        return;
    }
    
    let detailsModal = document.getElementById('eventDetailsModal');
    if (!detailsModal) {
        detailsModal = document.createElement('div');
        detailsModal.id = 'eventDetailsModal';
        detailsModal.className = 'modal-overlay';
        detailsModal.innerHTML = `
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h3>Event Details</h3>
                    <span class="close-modal" onclick="closeEventDetailsModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="event-details">
                        <h4 id="detailEventTitle"></h4>
                        <div class="detail-row" style="margin: 10px 0;">
                            <strong>Type:</strong> <span id="detailEventType"></span>
                        </div>
                        <div class="detail-row" style="margin: 10px 0;">
                            <strong>Date:</strong> <span id="detailEventDate"></span>
                        </div>
                        <div class="detail-row" style="margin: 10px 0;">
                            <strong>Time:</strong> <span id="detailEventTime"></span>
                        </div>
                        <div class="detail-row" style="margin: 10px 0;">
                            <strong>Location:</strong> <span id="detailEventLocation"></span>
                        </div>
                        <div class="detail-row" style="margin: 10px 0;">
                            <strong>Capacity:</strong> <span id="detailEventCapacity"></span>
                        </div>
                        <div class="detail-row" style="margin: 10px 0;">
                            <strong>Registrations:</strong> <span id="detailEventRegistrations"></span>
                        </div>
                        <div class="detail-row" style="margin: 10px 0;">
                            <strong>Status:</strong> <span id="detailEventStatus"></span>
                        </div>
                        <div class="detail-row" style="margin: 10px 0;">
                            <strong>Description:</strong>
                            <p id="detailEventDescription" style="margin-top: 10px;"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeEventDetailsModal()">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(detailsModal);
    }
    
    document.getElementById('detailEventTitle').textContent = event.title;
    document.getElementById('detailEventType').textContent = event.type.charAt(0).toUpperCase() + event.type.slice(1);
    
    const eventDate = new Date(event.date);
    document.getElementById('detailEventDate').textContent = eventDate.toLocaleDateString();
    document.getElementById('detailEventTime').textContent = eventDate.toLocaleTimeString();
    
    document.getElementById('detailEventLocation').textContent = event.location;
    document.getElementById('detailEventCapacity').textContent = event.capacity;
    document.getElementById('detailEventRegistrations').textContent = `${event.registrations} registered`;
    document.getElementById('detailEventStatus').innerHTML = `<span class="event-status status-${event.status}">${event.status.charAt(0).toUpperCase() + event.status.slice(1)}</span>`;
    document.getElementById('detailEventDescription').textContent = event.description || 'No description provided.';
    
    detailsModal.style.display = 'flex';
}

function closeEventDetailsModal() {
    const detailsModal = document.getElementById('eventDetailsModal');
    if (detailsModal) {
        detailsModal.style.display = 'none';
    }
}

// View Attendees
function viewAttendees(eventId) {
    const event = eventsData.find(e => e.id === eventId);
    if (!event) {
        showNotification('Event not found', 'error');
        return;
    }
    
    const eventAttendees = attendeesData.filter(attendee => attendee.eventId === eventId);
    createAttendeesModal(event, eventAttendees);
}

function createAttendeesModal(event, attendees) {
    let attendeesModal = document.getElementById('attendeesModal');
    if (!attendeesModal) {
        attendeesModal = document.createElement('div');
        attendeesModal.id = 'attendeesModal';
        attendeesModal.className = 'modal-overlay';
        attendeesModal.innerHTML = `
            <div class="modal-content" style="max-width: 800px; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column;">
                <div class="modal-header">
                    <h3 id="attendeesModalTitle">Attendees</h3>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn btn-sm" id="addAttendeeBtn" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-plus"></i> Add</button>
                        <button class="btn btn-sm" id="exportAttendeesBtn" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-download"></i> Export</button>
                        <span class="close-modal" onclick="closeAttendeesModal()">&times;</span>
                    </div>
                </div>
                <div class="modal-body" style="overflow-y: auto; flex: 1;">
                    <div style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <div id="attendeesCount">Total: <strong>0</strong> attendees</div>
                        <div>
                            <select id="attendeeStatusFilter" style="padding: 5px;">
                                <option value="all">All Status</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="pending">Pending</option>
                                <option value="checked-in">Checked-in</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="attendees-list">
                        <table class="attendees-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: var(--dark-gray);">
                                    <th style="padding: 10px; text-align: left;">Name</th>
                                    <th style="padding: 10px; text-align: left;">Email</th>
                                    <th style="padding: 10px; text-align: left;">Phone</th>
                                    <th style="padding: 10px; text-align: left;">Status</th>
                                    <th style="padding: 10px; text-align: left;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="attendeesTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeAttendeesModal()">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(attendeesModal);
        
        // Add event listeners for modal buttons
        document.getElementById('addAttendeeBtn').addEventListener('click', () => addNewAttendee(event.id));
        document.getElementById('exportAttendeesBtn').addEventListener('click', () => exportEventAttendees(event.id));
        document.getElementById('attendeeStatusFilter').addEventListener('change', (e) => filterAttendeesByStatus(event.id, e.target.value));
    }
    
    document.getElementById('attendeesModalTitle').textContent = `Attendees for: ${event.title}`;
    populateAttendeesTable(event.id, attendees);
    attendeesModal.style.display = 'flex';
}

function populateAttendeesTable(eventId, attendees) {
    const tableBody = document.getElementById('attendeesTableBody');
    const countDiv = document.getElementById('attendeesCount');
    
    if (countDiv) {
        countDiv.innerHTML = `Total: <strong>${attendees.length}</strong> attendees`;
    }
    
    if (!tableBody) return;
    
    tableBody.innerHTML = attendees.map(attendee => {
        const statusClass = `status-${attendee.status.replace('-', '')}`;
        return `
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid var(--medium-gray);">${attendee.name}</td>
                <td style="padding: 10px; border-bottom: 1px solid var(--medium-gray);">${attendee.email}</td>
                <td style="padding: 10px; border-bottom: 1px solid var(--medium-gray);">${attendee.phone}</td>
                <td style="padding: 10px; border-bottom: 1px solid var(--medium-gray);">
                    <span class="event-status ${statusClass}">${attendee.status}</span>
                </td>
                <td style="padding: 10px; border-bottom: 1px solid var(--medium-gray);">
                    <i class="fas fa-check" title="Check-in" onclick="checkInAttendee(${attendee.id})" style="margin-right: 8px; cursor: pointer; color: var(--success);"></i>
                    <i class="fas fa-trash" title="Delete" onclick="deleteAttendee(${attendee.id}, ${attendee.eventId})" style="cursor: pointer; color: var(--danger);"></i>
                </td>
            </tr>
        `;
    }).join('');
}

function addNewAttendee(eventId) {
    const name = prompt("Enter attendee name:");
    if (!name) return;
    
    const email = prompt("Enter email address:");
    const phone = prompt("Enter phone number:");
    
    const newAttendee = {
        id: Date.now(),
        eventId: eventId,
        name: name,
        email: email || '',
        phone: phone || '',
        status: 'pending',
        registrationDate: new Date().toISOString()
    };
    
    attendeesData.push(newAttendee);
    
    // Update event registration count
    const event = eventsData.find(e => e.id === eventId);
    if (event) {
        event.registrations++;
    }
    
    const eventAttendees = attendeesData.filter(a => a.eventId === eventId);
    populateAttendeesTable(eventId, eventAttendees);
    updateAllViews();
    showNotification(`Added attendee: ${name}`, 'success');
}

function filterAttendeesByStatus(eventId, status) {
    let filteredAttendees = attendeesData.filter(a => a.eventId === eventId);
    
    if (status !== 'all') {
        filteredAttendees = filteredAttendees.filter(a => a.status === status);
    }
    
    populateAttendeesTable(eventId, filteredAttendees);
}

function exportEventAttendees(eventId) {
    const event = eventsData.find(e => e.id === eventId);
    if (!event) return;
    
    const eventAttendees = attendeesData.filter(a => a.eventId === eventId);
    
    const content = `Attendees for: ${event.title}\nDate: ${new Date(event.date).toLocaleDateString()}\n\n` +
                   eventAttendees.map(a => 
                       `${a.name}, ${a.email}, ${a.phone}, ${a.status}`
                   ).join('\n');

    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${event.title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}_attendees.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showNotification(`Attendees exported for: ${event.title}`, 'success');
}

function closeAttendeesModal() {
    const attendeesModal = document.getElementById('attendeesModal');
    if (attendeesModal) {
        attendeesModal.style.display = 'none';
    }
}

function checkInAttendee(attendeeId) {
    const attendee = attendeesData.find(a => a.id === attendeeId);
    if (!attendee) return;
    
    attendee.status = "checked-in";
    
    const eventAttendees = attendeesData.filter(a => a.eventId === attendee.eventId);
    populateAttendeesTable(attendee.eventId, eventAttendees);
    
    showNotification(`Checked in: ${attendee.name}`, 'success');
}

function deleteAttendee(attendeeId, eventId) {
    const attendee = attendeesData.find(a => a.id === attendeeId);
    if (!attendee) return;
    
    if (confirm(`Are you sure you want to remove ${attendee.name} from the event?`)) {
        const index = attendeesData.findIndex(a => a.id === attendeeId);
        if (index > -1) {
            attendeesData.splice(index, 1);
        }
        
        // Update event registration count
        const event = eventsData.find(e => e.id === eventId);
        if (event && event.registrations > 0) {
            event.registrations--;
        }
        
        const eventAttendees = attendeesData.filter(a => a.eventId === eventId);
        populateAttendeesTable(eventId, eventAttendees);
        updateAllViews();
        
        showNotification(`Removed attendee: ${attendee.name}`, 'warning');
    }
}

// Generate QR Code
function generateCheckInQR(eventId) {
    const event = eventsData.find(e => e.id === eventId);
    if (!event) return;
    
    showNotification(`QR Check-in ready for: ${event.title}`, 'info');
}

// Quick Actions
function sendReminders() {
    showNotification('Sending reminders to all upcoming event attendees...', 'info');
    setTimeout(() => {
        showNotification('Reminders sent successfully!', 'success');
    }, 1500);
}

function printMaterials() {
    showNotification('Preparing materials for printing...', 'info');
    setTimeout(() => {
        showNotification('Print job sent to printer!', 'success');
    }, 1500);
}

function exportAttendees() {
    showNotification('Exporting attendee data...', 'info');
    setTimeout(() => {
        const content = eventsData.map(event =>
            `${event.title}\nDate: ${new Date(event.date).toLocaleDateString()}\nAttendees: ${event.registrations}/${event.capacity}\n\n`
        ).join('');

        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `event-attendees-${new Date().toISOString().split('T')[0]}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showNotification('Attendee data exported!', 'success');
    }, 1000);
}

function generateReports() {
    showNotification('Generating event reports...', 'info');
    setTimeout(() => {
        showNotification('Reports generated successfully!', 'success');
    }, 2000);
}

// Notification System
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <i class="fas fa-times" style="margin-left: auto; cursor: pointer;" onclick="this.parentNode.remove()"></i>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}

// File upload functionality
function handleFileUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    if (file.type !== 'application/json') {
        showNotification('Please upload a JSON file', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const importedData = JSON.parse(e.target.result);
            if (validateImportData(importedData)) {
                importEventsData(importedData);
            }
        } catch (error) {
            showNotification('Error parsing JSON file', 'error');
            console.error('Import error:', error);
        }
    };
    reader.readAsText(file);
}

function validateImportData(data) {
    if (!Array.isArray(data)) {
        showNotification('Invalid data format: Expected an array', 'error');
        return false;
    }

    for (const event of data) {
        const validation = validateEventData(event);
        if (!validation.valid) {
            showNotification(`Invalid event: ${validation.message}`, 'error');
            return false;
        }
    }

    return true;
}

function importEventsData(importedEvents) {
    // Generate new IDs for imported events to avoid conflicts
    importedEvents.forEach(event => {
        event.id = Date.now() + Math.floor(Math.random() * 1000);
        event.createdAt = new Date().toISOString();
        
        // Ensure all required fields have default values
        if (!event.registrations) event.registrations = 0;
        if (!event.status) event.status = 'upcoming';
        if (!event.description) event.description = '';
    });

    eventsData = [...eventsData, ...importedEvents];
    updateAllViews();
    showNotification(`Successfully imported ${importedEvents.length} events`, 'success');
}

// Export events data
function exportEventsData() {
    const dataStr = JSON.stringify(eventsData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
    
    const exportFileName = `events-export-${new Date().toISOString().split('T')[0]}.json`;
    
    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileName);
    linkElement.click();
    
    showNotification('Events data exported successfully', 'success');
}

// Bulk operations
function showBulkOperations() {
    let bulkModal = document.getElementById('bulkOperationsModal');
    if (!bulkModal) {
        bulkModal = document.createElement('div');
        bulkModal.id = 'bulkOperationsModal';
        bulkModal.className = 'modal-overlay';
        bulkModal.innerHTML = `
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h3>Bulk Operations</h3>
                    <span class="close-modal" onclick="closeBulkOperationsModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="bulk-options">
                        <h4>Select operation:</h4>
                        <div class="bulk-option" onclick="performBulkOperation('updateStatus')">
                            <i class="fas fa-exchange-alt"></i>
                            <div>
                                <strong>Update Status</strong>
                                <p>Update status for selected events</p>
                            </div>
                        </div>
                        <div class="bulk-option" onclick="performBulkOperation('sendNotifications')">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Send Notifications</strong>
                                <p>Send notifications to all attendees</p>
                            </div>
                        </div>
                        <div class="bulk-option" onclick="performBulkOperation('exportSelected')">
                            <i class="fas fa-download"></i>
                            <div>
                                <strong>Export Selected</strong>
                                <p>Export selected events data</p>
                            </div>
                        </div>
                        <div class="bulk-option" onclick="performBulkOperation('deleteSelected')" style="color: var(--danger);">
                            <i class="fas fa-trash"></i>
                            <div>
                                <strong>Delete Selected</strong>
                                <p>Delete selected events</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeBulkOperationsModal()">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(bulkModal);
    }
    
    bulkModal.style.display = 'flex';
}

function closeBulkOperationsModal() {
    const modal = document.getElementById('bulkOperationsModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function performBulkOperation(operation) {
    const selectedEvents = getSelectedEvents();
    
    if (selectedEvents.length === 0) {
        showNotification('Please select events first', 'error');
        return;
    }

    switch (operation) {
        case 'updateStatus':
            showStatusUpdateModal(selectedEvents);
            break;
        case 'sendNotifications':
            sendBulkNotifications(selectedEvents);
            break;
        case 'exportSelected':
            exportSelectedEvents(selectedEvents);
            break;
        case 'deleteSelected':
            confirmBulkDelete(selectedEvents);
            break;
    }
    
    closeBulkOperationsModal();
}

function getSelectedEvents() {
    // This would need to be implemented based on your selection mechanism
    // For now, returning empty array - you can implement checkboxes in your table
    return [];
}

// Event status management
function updateEventStatus(eventId, newStatus) {
    const event = eventsData.find(e => e.id === eventId);
    if (!event) return;

    const oldStatus = event.status;
    event.status = newStatus;
    event.updatedAt = new Date().toISOString();

    updateAllViews();
    showNotification(`Event "${event.title}" status changed from ${oldStatus} to ${newStatus}`, 'info');
}

// Date utilities
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(date - now);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        return 'Today';
    } else if (diffDays === 1) {
        return 'Tomorrow';
    } else if (diffDays <= 7) {
        return `${diffDays} days`;
    } else {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }
}

// Sort events
function sortEvents(criteria) {
    switch (criteria) {
        case 'date':
            eventsData.sort((a, b) => new Date(a.date) - new Date(b.date));
            break;
        case 'title':
            eventsData.sort((a, b) => a.title.localeCompare(b.title));
            break;
        case 'registrations':
            eventsData.sort((a, b) => b.registrations - a.registrations);
            break;
        case 'capacity':
            eventsData.sort((a, b) => b.capacity - a.capacity);
            break;
    }
    updateEventsTable();
}

// Duplicate event
function duplicateEvent(eventId) {
    const originalEvent = eventsData.find(e => e.id === eventId);
    if (!originalEvent) return;

    const duplicatedEvent = {
        ...originalEvent,
        id: Date.now(),
        title: `${originalEvent.title} (Copy)`,
        registrations: 0,
        status: 'upcoming',
        createdAt: new Date().toISOString()
    };

    eventsData.push(duplicatedEvent);
    updateAllViews();
    showNotification(`Event "${originalEvent.title}" duplicated`, 'success');
}

// Data persistence
function saveToLocalStorage() {
    try {
        const dataToSave = {
            events: eventsData,
            attendees: attendeesData,
            lastUpdated: new Date().toISOString()
        };
        localStorage.setItem('eventManagementData', JSON.stringify(dataToSave));
        return true;
    } catch (error) {
        console.error('Error saving to localStorage:', error);
        showNotification('Error saving data', 'error');
        return false;
    }
}

function loadFromLocalStorage() {
    try {
        const savedData = localStorage.getItem('eventManagementData');
        if (savedData) {
            const parsedData = JSON.parse(savedData);
            if (parsedData.events && Array.isArray(parsedData.events)) {
                eventsData = parsedData.events;
            }
            if (parsedData.attendees && Array.isArray(parsedData.attendees)) {
                attendeesData = parsedData.attendees;
            }
            return true;
        }
    } catch (error) {
        console.error('Error loading from localStorage:', error);
    }
    return false;
}

// Auto-save functionality
let autoSaveInterval;
function startAutoSave(intervalMinutes = 5) {
    if (autoSaveInterval) {
        clearInterval(autoSaveInterval);
    }
    
    autoSaveInterval = setInterval(() => {
        if (saveToLocalStorage()) {
            console.log('Auto-save completed');
        }
    }, intervalMinutes * 60 * 1000);
}

// Initialize data with localStorage
function initializeData() {
    const loaded = loadFromLocalStorage();
    if (!loaded || eventsData.length === 0) {
        initializeSampleData();
    }
    
    // Start auto-save
    startAutoSave(5);
}

// Cleanup function
function cleanup() {
    if (autoSaveInterval) {
        clearInterval(autoSaveInterval);
    }
}

// Add CSS for additional components
function addAdditionalStyles() {
    const styles = `
        .bulk-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .bulk-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .bulk-option:hover {
            background-color: var(--light-gray);
            transform: translateX(5px);
        }
        
        .bulk-option i {
            font-size: 24px;
            color: var(--accent);
        }
        
        .bulk-option strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .bulk-option p {
            margin: 0;
            color: var(--text-gray);
            font-size: 14px;
        }
        
        .attendees-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .attendees-table th {
            background-color: var(--dark-gray);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--medium-gray);
        }
        
        .attendees-table td {
            padding: 12px;
            border-bottom: 1px solid var(--medium-gray);
        }
        
        .attendees-table tr:hover {
            background-color: var(--light-gray);
        }
    `;
    
    const styleSheet = document.createElement('style');
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
}

// Add modal HTML to the page
function addModalsToPage() {
    const modals = `
        <!-- Create Event Modal -->
        <div id="createEventModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Create New Event</h3>
                    <span class="close-modal" onclick="closeModal('createEventModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Event Title *</label>
                        <input type="text" id="eventTitle" placeholder="Enter event title" required>
                    </div>
                    <div class="form-group">
                        <label>Event Type *</label>
                        <select id="eventType" required>
                            <option value="">Select type</option>
                            <option value="seminar">Seminar</option>
                            <option value="workshop">Workshop</option>
                            <option value="training">Training</option>
                            <option value="conference">Conference</option>
                            <option value="fair">Community Fair</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date & Time *</label>
                        <input type="datetime-local" id="eventDateTime" required>
                    </div>
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" id="eventLocation" placeholder="Enter event location" required>
                    </div>
                    <div class="form-group">
                        <label>Capacity</label>
                        <input type="number" id="eventCapacity" placeholder="Enter capacity" value="50">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="eventDescription" placeholder="Enter event description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('createEventModal')">Cancel</button>
                    <button class="btn btn-success" onclick="saveNewEvent()">Create Event</button>
                </div>
            </div>
        </div>

        <!-- Edit Event Modal -->
        <div id="editEventModal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Event</h3>
                    <span class="close-modal" onclick="closeModal('editEventModal')">&times;</span>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editEventId">
                    <div class="form-group">
                        <label>Event Title *</label>
                        <input type="text" id="editTitle" required>
                    </div>
                    <div class="form-group">
                        <label>Event Type *</label>
                        <select id="editType" required>
                            <option value="seminar">Seminar</option>
                            <option value="workshop">Workshop</option>
                            <option value="training">Training</option>
                            <option value="conference">Conference</option>
                            <option value="fair">Community Fair</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date & Time *</label>
                        <input type="datetime-local" id="editDateTime" required>
                    </div>
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" id="editLocation" required>
                    </div>
                    <div class="form-group">
                        <label>Capacity</label>
                        <input type="number" id="editCapacity">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="editDescription" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal('editEventModal')">Cancel</button>
                    <button class="btn btn-success" onclick="saveEventChanges()">Save Changes</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modals);
}

// Utility functions for table selection
function selectAllEvents(selectAll) {
    const checkboxes = document.querySelectorAll('.event-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll;
    });
}

function getSelectedEventIds() {
    const checkboxes = document.querySelectorAll('.event-checkbox:checked');
    return Array.from(checkboxes).map(cb => parseInt(cb.value));
}

// Print functionality
function printEventList() {
    const printContent = document.querySelector('.events-table').outerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Event List - ${new Date().toLocaleDateString()}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    table { border-collapse: collapse; width: 100%; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; }
                </style>
            </head>
            <body>
                <h2>Event List - ${new Date().toLocaleDateString()}</h2>
                ${printContent}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Export to CSV
function exportToCSV() {
    const headers = ['Title', 'Type', 'Date', 'Time', 'Location', 'Registrations', 'Capacity', 'Status'];
    const rows = eventsData.map(event => {
        const date = new Date(event.date);
        return [
            `"${event.title}"`,
            event.type,
            date.toLocaleDateString(),
            date.toLocaleTimeString(),
            `"${event.location}"`,
            event.registrations,
            event.capacity,
            event.status
        ];
    });

    const csvContent = [
        headers.join(','),
        ...rows.map(row => row.join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `events-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    showNotification('Events exported to CSV successfully', 'success');
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N for new event
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        createNewEvent();
    }
    
    // Ctrl/Cmd + F for focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.querySelector('.search-box input');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape to close modals
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal-overlay[style*="display: flex"]');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

// Initialize the page
document.addEventListener('DOMContentLoaded', function () {
    console.log('Initializing Event Management System...');
    
    setActiveNavigation();
    addAdditionalStyles();
    initializeData();
    setupEventListeners();
    updateAllViews();
    initializeFilters();
    addModalsToPage();
    
    // Add file upload listener
    const fileUpload = document.querySelector('input[type="file"]');
    if (fileUpload) {
        fileUpload.addEventListener('change', handleFileUpload);
    }
    
    // Add cleanup on page unload
    window.addEventListener('beforeunload', cleanup);
    
    console.log('Event Management System initialized successfully');
});