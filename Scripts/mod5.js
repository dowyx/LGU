// Survey & Feedback Collection JavaScript
        function createNewSurvey() {
            alert('Opening survey creating');
            // In a real application, this would open a survey creation modal
        }

        function sendSurveyReminder() {
            alert('Sending survey reminders to participants...');
        }

        function exportSurveyData() {
            alert('Exporting survey data...');
        }

        function analyzeTrends() {
            alert('Analyzing feedback trends...');
        }

        function generateInsights() {
            alert('Generating actionable insights...');
        }

        // Set active navigation
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = 'Survey-Feedback-Collection.html';
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
                    alert(`Searching surveys for: "${this.value}"`);
                }
            });

            // Filter items functionality
            const filterItems = document.querySelectorAll('.filter-item');
            filterItems.forEach(item => {
                item.addEventListener('click', function() {
                    filterItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    console.log(`Filter applied: ${this.textContent}`);
                });
            });

            // Survey form submission
            const surveyForm = document.querySelector('.survey-form');
            const launchButton = document.querySelector('.survey-builder .btn');
            launchButton.addEventListener('click', function(e) {
                e.preventDefault();
                const title = surveyForm.querySelector('input[type="text"]').value;
                const type = surveyForm.querySelector('select').value;

                if (title && type) {
                    alert(`Survey "${title}" builder launched!`);
                    surveyForm.reset();
                } else {
                    alert('Please fill in all required fields');
                }
            });

            // Question type selection
            const questionTypes = document.querySelectorAll('.question-type');
            questionTypes.forEach(type => {
                type.addEventListener('click', function() {
                    const questionType = this.querySelector('div').textContent;
                    alert(`Selected question type: ${questionType}`);
                    questionTypes.forEach(t => t.style.backgroundColor = 'var(--dark-gray)');
                    this.style.backgroundColor = 'var(--medium-gray)';
                });
            });

            // Action icons functionality
            const actionIcons = document.querySelectorAll('.survey-actions i');
            actionIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const action = this.getAttribute('title');
                    alert(`${action} action triggered`);
                });
            });

            // Quick action buttons
            const quickActions = document.querySelectorAll('.quick-actions-grid .action-btn');
            quickActions.forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.querySelector('span').textContent;
                    console.log(`Quick action: ${action}`);
                });
            });

            // View all responses button
            const viewResponsesBtn = document.querySelector('.module-card:nth-child(5) .btn');
            viewResponsesBtn.addEventListener('click', function() {
                alert('Opening all responses view...');
            });

            // Word cloud interaction
            const wordItems = document.querySelectorAll('.word-item');
            wordItems.forEach(word => {
                word.addEventListener('click', function() {
                    const wordText = this.textContent;
                    console.log(`Word clicked: ${wordText}`);
                });
            });
        });

      // Survey & Feedback Collection JavaScript with full CRUD functionality
let surveysData = [];
let dashboardStats = {};

const API_BASE_URL = '/api';

// Initialize the page
document.addEventListener('DOMContentLoaded', async function() {
    await initializePage();
});

async function initializePage() {
    setActiveNavigation();
    await loadSurveysData();
    await loadDashboardStats();
    setupEventListeners();
    renderSurveysTable();
    renderActiveSurveys();
    renderRecentResponses();
    renderSentimentAnalysis();
    renderDashboardStats();
    renderChannelStats();
}

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

// Load surveys data from API
async function loadSurveysData() {
    try {
        const response = await fetch(`${API_BASE_URL}/surveys`);
        if (response.ok) {
            surveysData = await response.json();
        } else {
            console.error('Failed to load surveys data');
            surveysData = [];
        }
    } catch (error) {
        console.error('Error loading surveys:', error);
        surveysData = [];
    }
}

// Load dashboard statistics
async function loadDashboardStats() {
    try {
        const response = await fetch(`${API_BASE_URL}/surveys/dashboard/stats`);
        if (response.ok) {
            dashboardStats = await response.json();
        } else {
            console.error('Failed to load dashboard stats');
            dashboardStats = {};
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        dashboardStats = {};
    }
}

// Setup event listeners
function setupEventListeners() {
    // Search functionality
    const searchInput = document.querySelector('.search-box input');
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            searchSurveys(this.value);
        }
    });

    // Filter functionality
    const filterItems = document.querySelectorAll('.filter-item');
    filterItems.forEach(item => {
        item.addEventListener('click', function() {
            filterItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            filterSurveys(this.textContent);
        });
    });

    // Survey form submission
    const launchButton = document.querySelector('.survey-builder .btn');
    if (launchButton) {
        launchButton.addEventListener('click', function(e) {
            e.preventDefault();
            createNewSurvey();
        });
    }

    // Question type selection
    const questionTypes = document.querySelectorAll('.question-type');
    questionTypes.forEach(type => {
        type.addEventListener('click', function() {
            const questionType = this.querySelector('div').textContent;
            showToast(`Selected question type: ${questionType}`);
            questionTypes.forEach(t => t.style.backgroundColor = 'var(--dark-gray)');
            this.style.backgroundColor = 'var(--medium-gray)';
        });
    });

    // Quick action buttons
    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.querySelector('span').textContent;
            switch(action) {
                case 'Send Reminders':
                    sendSurveyReminder();
                    break;
                case 'Export Data':
                    exportSurveyData();
                    break;
                case 'Analyze Trends':
                    analyzeTrends();
                    break;
                case 'Generate Insights':
                    generateInsights();
                    break;
            }
        });
    });

    // Create Survey button
    document.querySelector('.module-header .btn').addEventListener('click', openCreateSurveyModal);

    // View All Responses button
    const viewResponsesBtn = document.querySelector('.module-card:nth-child(5) .btn');
    if (viewResponsesBtn) {
        viewResponsesBtn.addEventListener('click', viewAllResponses);
    }

    // Word cloud interaction
    const wordItems = document.querySelectorAll('.word-item');
    wordItems.forEach(word => {
        word.addEventListener('click', function() {
            const wordText = this.textContent;
            searchSurveys(wordText);
        });
    });
}

// Render surveys table
function renderSurveysTable(filteredData = surveysData) {
    const tableBody = document.querySelector('.surveys-table tbody');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    if (filteredData.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-gray);">
                <i class="fas fa-clipboard-list" style="font-size: 48px; margin-bottom: 15px; display: block; color: var(--medium-gray);"></i>
                <p>No surveys found. Click "Create Survey" to add your first survey.</p>
            </td>
        `;
        tableBody.appendChild(row);
        return;
    }

    filteredData.forEach(survey => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div style="font-weight: 600;">${escapeHtml(survey.name)}</div>
                <div style="font-size: 12px; color: var(--text-gray);">${escapeHtml(survey.description)}</div>
            </td>
            <td><span class="survey-type type-${survey.type}">${capitalizeFirst(survey.type)}</span></td>
            <td>
                <div>${survey.responses || 0}</div>
                <div style="height: 4px; background-color: var(--dark-gray); border-radius: 2px; margin-top: 5px;">
                    <div style="width: ${survey.completionRate || 0}%; height: 100%; background-color: var(--success); border-radius: 2px;"></div>
                </div>
            </td>
            <td>${survey.completionRate || 0}%</td>
            <td>
                ${renderStars(survey.avgRating || 0)}
            </td>
            <td><span class="survey-status status-${survey.status}">${capitalizeFirst(survey.status)}</span></td>
            <td>
                <div class="survey-actions">
                    <i class="fas fa-chart-bar" title="Analytics" onclick="viewSurveyAnalytics(${survey.id})"></i>
                    <i class="fas fa-download" title="Export" onclick="exportSingleSurvey(${survey.id})"></i>
                    <i class="fas fa-edit" title="Edit" onclick="editSurvey(${survey.id})"></i>
                    ${survey.status === 'draft' ?
                        `<i class="fas fa-play" title="Launch" onclick="launchSurvey(${survey.id})"></i>` :
                        `<i class="fas fa-trash" title="Delete" onclick="deleteSurvey(${survey.id})"></i>`}
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Render stars for rating
function renderStars(rating) {
    const stars = Math.round(rating);
    let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        starsHtml += `<span class="star ${i <= stars ? 'filled' : ''}"><i class="fas fa-star"></i></span>`;
    }
    return `<div class="rating-display">${starsHtml}</div>`;
}

// Render active surveys
function renderActiveSurveys() {
    const activeSurveysContainer = document.querySelector('.survey-list');
    if (!activeSurveysContainer) return;

    const activeSurveys = surveysData.filter(s => s.status === 'active').slice(0, 4);

    if (activeSurveys.length === 0) {
        activeSurveysContainer.innerHTML = `
            <div style="text-align: center; padding: 20px; color: var(--text-gray);">
                <i class="fas fa-clipboard-check" style="font-size: 32px; margin-bottom: 10px;"></i>
                <p>No active surveys</p>
            </div>
        `;
        return;
    }

    activeSurveysContainer.innerHTML = activeSurveys.map(survey => `
        <div class="survey-item ${survey.status}">
            <div class="survey-name">${escapeHtml(survey.name)}</div>
            <div class="survey-details">${escapeHtml(survey.description)}</div>
            <div class="progress-container">
                <div class="progress-bar" style="width: ${survey.completionRate || 0}%"></div>
            </div>
            <div class="survey-stats">
                <span class="response-count">${survey.responses || 0} responses</span>
                <span class="completion-rate">${survey.completionRate || 0}% completion</span>
            </div>
        </div>
    `).join('');
}

// Render recent responses
async function renderRecentResponses() {
    try {
        const response = await fetch(`${API_BASE_URL}/responses`);
        if (response.ok) {
            const allResponses = await response.json();
            const recentResponses = allResponses.slice(-3).reverse();

            const responsesContainer = document.querySelector('.response-list');
            if (!responsesContainer) return;

            if (recentResponses.length === 0) {
                responsesContainer.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: var(--text-gray);">
                        <i class="fas fa-comments" style="font-size: 32px; margin-bottom: 10px;"></i>
                        <p>No responses yet</p>
                    </div>
                `;
                return;
            }

            responsesContainer.innerHTML = recentResponses.map(response => `
                <div class="response-item">
                    <div class="rating-display">
                        ${renderStars(response.rating || 0)}
                    </div>
                    <div class="response-text">
                        "${escapeHtml(response.feedback || 'No feedback provided')}"
                    </div>
                    <div class="response-meta">
                        <span>From: Survey #${response.surveyId}</span>
                        <span>${formatTimeAgo(response.createdAt)}</span>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading recent responses:', error);
    }
}

// Render sentiment analysis
function renderSentimentAnalysis() {
    if (!dashboardStats.sentiment) return;

    const sentimentData = dashboardStats.sentiment;
    const total = sentimentData.positive + sentimentData.neutral + sentimentData.negative;

    if (total === 0) return;

    const positivePercent = Math.round((sentimentData.positive / total) * 100);
    const neutralPercent = Math.round((sentimentData.neutral / total) * 100);
    const negativePercent = Math.round((sentimentData.negative / total) * 100);

    // Update sentiment bars
    const positiveFill = document.querySelector('.positive .sentiment-fill');
    const neutralFill = document.querySelector('.neutral .sentiment-fill');
    const negativeFill = document.querySelector('.negative .sentiment-fill');

    if (positiveFill) positiveFill.style.width = `${positivePercent}%`;
    if (neutralFill) neutralFill.style.width = `${neutralPercent}%`;
    if (negativeFill) negativeFill.style.width = `${negativePercent}%`;

    // Update percentages
    const positivePercentSpan = document.querySelector('.positive span:last-child');
    const neutralPercentSpan = document.querySelector('.neutral span:last-child');
    const negativePercentSpan = document.querySelector('.negative span:last-child');

    if (positivePercentSpan) positivePercentSpan.textContent = `${positivePercent}%`;
    if (neutralPercentSpan) neutralPercentSpan.textContent = `${neutralPercent}%`;
    if (negativePercentSpan) negativePercentSpan.textContent = `${negativePercent}%`;
}

// Render dashboard stats
function renderDashboardStats() {
    const statValues = document.querySelectorAll('.stat-value');
    if (statValues.length >= 4 && dashboardStats) {
        statValues[0].textContent = dashboardStats.activeSurveys || 0;
        statValues[1].textContent = dashboardStats.totalResponses || 0;
        statValues[2].textContent = `${dashboardStats.avgCompletionRate || 0}%`;
        statValues[3].textContent = `${dashboardStats.avgSatisfaction || 0}★`;
    }
}

// Render channel stats
function renderChannelStats() {
    if (!dashboardStats.channels) return;

    const channelData = [
        { name: 'Email', icon: 'fas fa-envelope', count: dashboardStats.channels.email || 0 },
        { name: 'SMS', icon: 'fas fa-mobile-alt', count: dashboardStats.channels.sms || 0 },
        { name: 'Web Portal', icon: 'fas fa-globe', count: dashboardStats.channels.web || 0 },
        { name: 'QR Code', icon: 'fas fa-qrcode', count: dashboardStats.channels.qr || 0 }
    ];

    const channelContainer = document.querySelector('.channel-stats');
    if (!channelContainer) return;

    channelContainer.innerHTML = channelData.map(channel => `
        <div class="channel-item">
            <i class="${channel.icon}"></i>
            <div class="channel-details">
                <div class="channel-name">${channel.name}</div>
                <div class="channel-response">${calculateResponseRate(channel.count)} response rate • ${channel.count} responses</div>
            </div>
        </div>
    `).join('');
}

// Calculate response rate (mock function)
function calculateResponseRate(count) {
    const baseRate = Math.min(100, Math.max(10, count * 0.5));
    return `${Math.round(baseRate)}%`;
}

// Open create survey modal
function openCreateSurveyModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Create New Survey</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="createSurveyForm">
                    <div class="form-group">
                        <label for="surveyName">Survey Title *</label>
                        <input type="text" id="surveyName" placeholder="Enter survey title" required>
                    </div>
                    <div class="form-group">
                        <label for="surveyType">Survey Type *</label>
                        <select id="surveyType" required>
                            <option value="">Select type</option>
                            <option value="campaign">Campaign Feedback</option>
                            <option value="event">Event Evaluation</option>
                            <option value="service">Service Satisfaction</option>
                            <option value="research">Public Research</option>
                            <option value="general">General Feedback</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="surveyDescription">Description *</label>
                        <textarea id="surveyDescription" rows="3" placeholder="Enter survey description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Questions</label>
                        <div class="question-types">
                            <div class="question-type" onclick="selectQuestionType('multiple_choice')">
                                <i class="fas fa-dot-circle"></i>
                                <div>Multiple Choice</div>
                            </div>
                            <div class="question-type" onclick="selectQuestionType('checkbox')">
                                <i class="fas fa-check-square"></i>
                                <div>Checkboxes</div>
                            </div>
                            <div class="question-type" onclick="selectQuestionType('rating')">
                                <i class="fas fa-star"></i>
                                <div>Rating Scale</div>
                            </div>
                            <div class="question-type" onclick="selectQuestionType('text')">
                                <i class="fas fa-comment"></i>
                                <div>Open Text</div>
                            </div>
                        </div>
                        <div id="questionsContainer" style="margin-top: 15px;"></div>
                        <button type="button" class="btn btn-secondary" onclick="addQuestion()" style="width: 100%; margin-top: 10px;">
                            <i class="fas fa-plus"></i> Add Question
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Survey</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    setupModalClose(modal);

    const form = modal.querySelector('#createSurveyForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        await createSurvey();
        modal.remove();
    });
}

// Create new survey
async function createSurvey() {
    const name = document.querySelector('#surveyName').value;
    const type = document.querySelector('#surveyType').value;
    const description = document.querySelector('#surveyDescription').value;

    const questions = [];
    const questionElements = document.querySelectorAll('.question-item');
    questionElements.forEach((item, index) => {
        const questionText = item.querySelector('.question-text').value;
        const questionType = item.getAttribute('data-type');
        const required = item.querySelector('.question-required').checked;

        if (questionText) {
            const question = {
                id: index + 1,
                type: questionType,
                text: questionText,
                required: required
            };

            if (questionType === 'multiple_choice' || questionType === 'checkbox') {
                const options = item.querySelectorAll('.option-input');
                question.options = Array.from(options).map(opt => opt.value).filter(opt => opt.trim() !== '');
            }

            questions.push(question);
        }
    });

    try {
        const response = await fetch(`${API_BASE_URL}/surveys`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name, type, description, questions })
        });

        if (response.ok) {
            const newSurvey = await response.json();
            showToast('Survey created successfully!');
            await loadSurveysData();
            await loadDashboardStats();
            renderSurveysTable();
            renderActiveSurveys();
            renderDashboardStats();
        } else {
            const error = await response.text();
            showToast(`Failed to create survey: ${error}`, 'error');
        }
    } catch (error) {
        console.error('Create survey error:', error);
        showToast('Error creating survey', 'error');
    }
}

// Add question to survey builder
function addQuestion() {
    const questionsContainer = document.querySelector('#questionsContainer');
    const questionCount = questionsContainer.querySelectorAll('.question-item').length + 1;

    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item';
    questionDiv.setAttribute('data-type', 'text');
    questionDiv.innerHTML = `
        <div style="background-color: var(--dark-gray); padding: 15px; border-radius: 8px; margin-bottom: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-weight: 600;">Question ${questionCount}</span>
                <button type="button" class="btn btn-danger" onclick="removeQuestion(this)" style="padding: 5px 10px; font-size: 12px;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <input type="text" class="question-text" placeholder="Enter question text" style="width: 100%; margin-bottom: 10px;">
            <div class="question-options" style="display: none;">
                <div style="font-size: 12px; color: var(--text-gray); margin-bottom: 5px;">Options:</div>
                <div class="options-container"></div>
                <button type="button" class="btn btn-secondary" onclick="addOption(this)" style="padding: 5px 10px; font-size: 12px; margin-top: 5px;">
                    <i class="fas fa-plus"></i> Add Option
                </button>
            </div>
            <div style="margin-top: 10px;">
                <label style="font-size: 12px; color: var(--text-gray);">
                    <input type="checkbox" class="question-required"> Required question
                </label>
            </div>
        </div>
    `;

    questionsContainer.appendChild(questionDiv);
}

// Remove question from survey builder
function removeQuestion(button) {
    const questionItem = button.closest('.question-item');
    questionItem.remove();
    renumberQuestions();
}

// Renumber questions
function renumberQuestions() {
    const questionItems = document.querySelectorAll('.question-item');
    questionItems.forEach((item, index) => {
        const span = item.querySelector('span');
        if (span) {
            span.textContent = `Question ${index + 1}`;
        }
        // Update question ID if stored
        item.setAttribute('data-id', index + 1);
    });
}

// Select question type
function selectQuestionType(type) {
    const activeQuestion = document.querySelector('#questionsContainer .question-item:last-child');
    if (!activeQuestion) {
        addQuestion();
        setTimeout(() => selectQuestionType(type), 100);
        return;
    }

    activeQuestion.setAttribute('data-type', type);
    const optionsDiv = activeQuestion.querySelector('.question-options');
    const optionsContainer = activeQuestion.querySelector('.options-container');

    // Show/hide options based on question type
    if (type === 'multiple_choice' || type === 'checkbox') {
        optionsDiv.style.display = 'block';
        // Add initial option if none exists
        if (optionsContainer.children.length === 0) {
            addOption(activeQuestion.querySelector('.btn-secondary'));
        }
    } else {
        optionsDiv.style.display = 'none';
    }
}

// Add option to question
function addOption(button) {
    const optionsContainer = button.previousElementSibling;
    const optionCount = optionsContainer.children.length + 1;

    const optionDiv = document.createElement('div');
    optionDiv.className = 'option-item';
    optionDiv.style.display = 'flex; align-items: center; gap: 10px; margin-bottom: 5px;';
    optionDiv.innerHTML = `
        <input type="text" class="option-input" placeholder="Option ${optionCount}" style="flex: 1;">
        <button type="button" class="btn btn-danger" onclick="removeOption(this)" style="padding: 5px 10px; font-size: 12px;">
            <i class="fas fa-times"></i>
        </button>
    `;

    optionsContainer.appendChild(optionDiv);
}

// Remove option from question
function removeOption(button) {
    const optionItem = button.closest('.option-item');
    optionItem.remove();
}

// Edit survey
async function editSurvey(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/surveys/${id}`);
        if (!response.ok) throw new Error('Survey not found');

        const survey = await response.json();

        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 600px;">
                <div class="modal-header">
                    <h3>Edit Survey</h3>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="editSurveyForm">
                        <div class="form-group">
                            <label for="editSurveyName">Survey Title *</label>
                            <input type="text" id="editSurveyName" value="${escapeHtml(survey.name)}" required>
                        </div>
                        <div class="form-group">
                            <label for="editSurveyType">Survey Type *</label>
                            <select id="editSurveyType" required>
                                <option value="campaign" ${survey.type === 'campaign' ? 'selected' : ''}>Campaign Feedback</option>
                                <option value="event" ${survey.type === 'event' ? 'selected' : ''}>Event Evaluation</option>
                                <option value="service" ${survey.type === 'service' ? 'selected' : ''}>Service Satisfaction</option>
                                <option value="research" ${survey.type === 'research' ? 'selected' : ''}>Public Research</option>
                                <option value="general" ${survey.type === 'general' ? 'selected' : ''}>General Feedback</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editSurveyDescription">Description *</label>
                            <textarea id="editSurveyDescription" rows="3" required>${escapeHtml(survey.description)}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="editSurveyStatus">Status</label>
                            <select id="editSurveyStatus">
                                <option value="draft" ${survey.status === 'draft' ? 'selected' : ''}>Draft</option>
                                <option value="active" ${survey.status === 'active' ? 'selected' : ''}>Active</option>
                                <option value="closed" ${survey.status === 'closed' ? 'selected' : ''}>Closed</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        setupModalClose(modal);

        const form = modal.querySelector('#editSurveyForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await updateSurvey(id);
            modal.remove();
        });
    } catch (error) {
        console.error('Error loading survey:', error);
        showToast('Error loading survey', 'error');
    }
}

// Update survey
async function updateSurvey(id) {
    const name = document.querySelector('#editSurveyName').value;
    const type = document.querySelector('#editSurveyType').value;
    const description = document.querySelector('#editSurveyDescription').value;
    const status = document.querySelector('#editSurveyStatus').value;

    try {
        const response = await fetch(`${API_BASE_URL}/surveys/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name, type, description, status })
        });

        if (response.ok) {
            showToast('Survey updated successfully!');
            await loadSurveysData();
            await loadDashboardStats();
            renderSurveysTable();
            renderActiveSurveys();
            renderDashboardStats();
        } else {
            const error = await response.text();
            showToast(`Failed to update survey: ${error}`, 'error');
        }
    } catch (error) {
        console.error('Update survey error:', error);
        showToast('Error updating survey', 'error');
    }
}

// Launch survey
async function launchSurvey(id) {
    if (!confirm('Are you sure you want to launch this survey? It will become active and available for responses.')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/surveys/${id}/launch`, {
            method: 'POST'
        });

        if (response.ok) {
            showToast('Survey launched successfully!');
            await loadSurveysData();
            await loadDashboardStats();
            renderSurveysTable();
            renderActiveSurveys();
            renderDashboardStats();
        } else {
            const error = await response.text();
            showToast(`Failed to launch survey: ${error}`, 'error');
        }
    } catch (error) {
        console.error('Launch survey error:', error);
        showToast('Error launching survey', 'error');
    }
}

// Delete survey
async function deleteSurvey(id) {
    if (!confirm('Are you sure you want to delete this survey? All responses will also be deleted.')) {
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/surveys/${id}`, {
            method: 'DELETE'
        });

        if (response.ok) {
            showToast('Survey deleted successfully!');
            await loadSurveysData();
            await loadDashboardStats();
            renderSurveysTable();
            renderActiveSurveys();
            renderDashboardStats();
        } else {
            const error = await response.text();
            showToast(`Failed to delete survey: ${error}`, 'error');
        }
    } catch (error) {
        console.error('Delete survey error:', error);
        showToast('Error deleting survey', 'error');
    }
}

// View survey analytics
async function viewSurveyAnalytics(id) {
    try {
        const [surveyResponse, statsResponse] = await Promise.all([
            fetch(`${API_BASE_URL}/surveys/${id}`),
            fetch(`${API_BASE_URL}/surveys/${id}/stats`)
        ]);

        if (!surveyResponse.ok || !statsResponse.ok) {
            throw new Error('Failed to load survey data');
        }

        const survey = await surveyResponse.json();
        const stats = await statsResponse.json();

        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 800px;">
                <div class="modal-header">
                    <h3>Survey Analytics: ${escapeHtml(survey.name)}</h3>
                    <span class="close-modal">&times;</span>
                </div>
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px;">
                        <div style="background-color: var(--dark-gray); padding: 20px; border-radius: 8px;">
                            <div style="font-size: 32px; font-weight: 700; color: var(--accent);">${stats.totalResponses}</div>
                            <div style="color: var(--text-gray);">Total Responses</div>
                        </div>
                        <div style="background-color: var(--dark-gray); padding: 20px; border-radius: 8px;">
                            <div style="font-size: 32px; font-weight: 700; color: var(--accent);">${stats.avgRating}</div>
                            <div style="color: var(--text-gray);">Average Rating</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <h4 style="margin-bottom: 10px;">Sentiment Analysis</h4>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 80px;">Positive</span>
                                <div style="flex: 1; height: 20px; background-color: var(--medium-gray); border-radius: 10px; overflow: hidden;">
                                    <div style="width: ${(stats.sentiment.positive / Math.max(1, stats.totalResponses)) * 100}%; height: 100%; background-color: var(--success);"></div>
                                </div>
                                <span>${stats.sentiment.positive} (${Math.round((stats.sentiment.positive / Math.max(1, stats.totalResponses)) * 100)}%)</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 80px;">Neutral</span>
                                <div style="flex: 1; height: 20px; background-color: var(--medium-gray); border-radius: 10px; overflow: hidden;">
                                    <div style="width: ${(stats.sentiment.neutral / Math.max(1, stats.totalResponses)) * 100}%; height: 100%; background-color: var(--warning);"></div>
                                </div>
                                <span>${stats.sentiment.neutral} (${Math.round((stats.sentiment.neutral / Math.max(1, stats.totalResponses)) * 100)}%)</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="width: 80px;">Negative</span>
                                <div style="flex: 1; height: 20px; background-color: var(--medium-gray); border-radius: 10px; overflow: hidden;">
                                    <div style="width: ${(stats.sentiment.negative / Math.max(1, stats.totalResponses)) * 100}%; height: 100%; background-color: var(--danger);"></div>
                                </div>
                                <span>${stats.sentiment.negative} (${Math.round((stats.sentiment.negative / Math.max(1, stats.totalResponses)) * 100)}%)</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <h4 style="margin-bottom: 10px;">Response Channels</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                            <div style="background-color: var(--dark-gray); padding: 10px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 20px; font-weight: 700;">${stats.channels.email || 0}</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Email</div>
                            </div>
                            <div style="background-color: var(--dark-gray); padding: 10px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 20px; font-weight: 700;">${stats.channels.web || 0}</div>
                                <div style="font-size: 12px; color: var(--text-gray);">Web</div>
                            </div>
                            <div style="background-color: var(--dark-gray); padding: 10px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 20px; font-weight: 700;">${stats.channels.sms || 0}</div>
                                <div style="font-size: 12px; color: var(--text-gray);">SMS</div>
                            </div>
                            <div style="background-color: var(--dark-gray); padding: 10px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 20px; font-weight: 700;">${stats.channels.qr || 0}</div>
                                <div style="font-size: 12px; color: var(--text-gray);">QR Code</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn" onclick="exportSingleSurvey(${id})">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                    <button class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        setupModalClose(modal);
    } catch (error) {
        console.error('Error loading analytics:', error);
        showToast('Error loading survey analytics', 'error');
    }
}

// Export single survey data
async function exportSingleSurvey(id) {
    try {
        const response = await fetch(`${API_BASE_URL}/surveys/${id}/export`);
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `survey_${id}_export.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showToast('Export started!');
        } else {
            showToast('Failed to export survey data', 'error');
        }
    } catch (error) {
        console.error('Export error:', error);
        showToast('Error exporting survey data', 'error');
    }
}

// Search surveys
async function searchSurveys(query) {
    if (!query.trim()) {
        renderSurveysTable();
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/surveys/search/${encodeURIComponent(query)}`);
        if (response.ok) {
            const results = await response.json();
            renderSurveysTable(results);
        }
    } catch (error) {
        console.error('Search error:', error);
        // Fallback to client-side search
        const results = surveysData.filter(survey =>
            survey.name.toLowerCase().includes(query.toLowerCase()) ||
            survey.description.toLowerCase().includes(query.toLowerCase()) ||
            survey.type.toLowerCase().includes(query.toLowerCase())
        );
        renderSurveysTable(results);
    }
}

// Filter surveys
function filterSurveys(filter) {
    let filtered = [...surveysData];

    switch(filter.toLowerCase()) {
        case 'active':
            filtered = filtered.filter(s => s.status === 'active');
            break;
        case 'campaign feedback':
            filtered = filtered.filter(s => s.type === 'campaign');
            break;
        case 'event feedback':
            filtered = filtered.filter(s => s.type === 'event');
            break;
        case 'service satisfaction':
            filtered = filtered.filter(s => s.type === 'service');
            break;
        case 'high priority':
            filtered = filtered.filter(s => s.avgRating < 3);
            break;
        // 'all surveys' - show all
    }

    renderSurveysTable(filtered);
}

// Submit survey response
async function submitSurveyResponse(surveyId, responseData) {
    try {
        const response = await fetch(`${API_BASE_URL}/surveys/${surveyId}/responses`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(responseData)
        });

        if (response.ok) {
            return await response.json();
        } else {
            throw new Error('Failed to submit response');
        }
    } catch (error) {
        console.error('Submit response error:', error);
        throw error;
    }
}

// View all responses
function viewAllResponses() {
    showToast('Opening all responses view...');
    // In a real implementation, this would open a modal with all responses
}

// Export survey data
async function exportSurveyData() {
    try {
        // Get all surveys and responses
        const [surveysResponse, responsesResponse] = await Promise.all([
            fetch(`${API_BASE_URL}/surveys`),
            fetch(`${API_BASE_URL}/responses`)
        ]);

        if (!surveysResponse.ok || !responsesResponse.ok) {
            throw new Error('Failed to load data');
        }

        const allSurveys = await surveysResponse.json();
        const allResponses = await responsesResponse.json();

        // Generate CSV
        let csv = 'Survey Name,Type,Status,Responses,Avg Rating\n';
        allSurveys.forEach(survey => {
            csv += `"${survey.name}","${survey.type}","${survey.status}","${survey.responses}","${survey.avgRating}"\n`;
        });

        csv += '\n\nAll Responses:\n';
        csv += 'Survey Name,Respondent,Rating,Feedback,Sentiment,Channel,Date\n';

        allResponses.forEach(response => {
            const survey = allSurveys.find(s => s.id === response.surveyId);
            const surveyName = survey ? survey.name : 'Unknown Survey';
            csv += `"${surveyName}","${response.respondent}","${response.rating}","${response.feedback}","${response.sentiment}","${response.channel}","${response.createdAt}"\n`;
        });

        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'all_surveys_export.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);

        showToast('Export completed!');
    } catch (error) {
        console.error('Export error:', error);
        showToast('Error exporting data', 'error');
    }
}

// Send survey reminders
function sendSurveyReminder() {
    showToast('Sending survey reminders to participants...');
    // In a real implementation, this would call an API to send reminders
}

// Analyze trends
function analyzeTrends() {
    showToast('Analyzing feedback trends...');
    // In a real implementation, this would open a trends analysis modal
}

// Generate insights
function generateInsights() {
    showToast('Generating actionable insights...');
    // In a real implementation, this would generate and display insights
}

// Helper functions
function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 60) return `${diffMins} minutes ago`;
    if (diffHours < 24) return `${diffHours} hours ago`;
    return `${diffDays} days ago`;
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

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'toast';
    if (type === 'error') toast.classList.add('error');
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Initialize question types selection
function initQuestionTypes() {
    const questionTypes = document.querySelectorAll('.question-type');
    questionTypes.forEach(type => {
        type.addEventListener('click', function() {
            const questionType = this.querySelector('div').textContent;
            showToast(`Selected question type: ${questionType}`);
            questionTypes.forEach(t => t.style.backgroundColor = 'var(--dark-gray)');
            this.style.backgroundColor = 'var(--medium-gray)';
        });
    });
}

