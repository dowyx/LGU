require('dotenv').config();
const express = require('express');
const multer = require('multer');
const cors = require('cors');
const compression = require('compression');
const rateLimit = require('express-rate-limit');
const fs = require('fs');
const path = require('path');

const app = express();
const port = process.env.PORT || 3000;

// Add security middleware
app.use(cors());
app.use(compression());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// Rate limiting
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
});
app.use('/api/', limiter);

// Content Security Policy Middleware
app.use((req, res, next) => {
  res.setHeader(
    "Content-Security-Policy",
    "default-src 'self'; " +
    "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline' 'unsafe-eval'; " +
    "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; " +
    "font-src 'self' https://cdnjs.cloudflare.com data:; " +
    "img-src 'self' data: https:; " +
    "connect-src 'self'"
  );
  next();
});

// Serve static files from root
app.use(express.static(__dirname));

// Serve specific folders
app.use('/Styles', express.static(path.join(__dirname, 'Styles')));
app.use('/Functions', express.static(path.join(__dirname, 'Functions')));
app.use('/Modules', express.static(path.join(__dirname, 'Modules')));

// Create uploads directory if it doesn't exist
const uploadsDir = path.join(__dirname, 'uploads');
if (!fs.existsSync(uploadsDir)) {
    fs.mkdirSync(uploadsDir, { recursive: true });
}

// Configure multer for file uploads
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, 'uploads/');
    },
    filename: (req, file, cb) => {
        // Sanitize filename to prevent path traversal
        const sanitizedFilename = file.originalname
            .replace(/[^a-zA-Z0-9.-]/g, '_')
            .replace(/\.\.{2,}/g, '.') // Prevent double dots
            .replace(/^\.{2,}/, ''); // Prevent leading dots
        
        const uniqueName = Date.now() + '-' + sanitizedFilename;
        cb(null, uniqueName);
    }
});

const upload = multer({
    storage: storage,
    limits: { 
        fileSize: 100 * 1024 * 1024, // 100MB limit
        files: 10 // Limit to 10 files
    },
    fileFilter: (req, file, cb) => {
        // Enhanced file type validation
        const allowedTypes = /jpeg|jpg|png|gif|pdf|doc|docx|ppt|pptx|xls|xlsx|mp4|mp3|wav|avi|mov|txt/;
        const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
        const mimetype = allowedTypes.test(file.mimetype);
        
        // Additional security checks
        const fileName = file.originalname;
        if (fileName.includes('..') || fileName.includes('./') || fileName.includes('\\.')) {
            return cb(new Error('Invalid filename'));
        }

        if (extname && mimetype) {
            return cb(null, true);
        }
        cb(new Error('File type not allowed. Please upload only images, documents, videos, or audio files.'));
    }
});

// Serve uploaded files statically
app.use('/uploads', express.static('uploads'));

// In-memory database for content items
let contentItems = [
    {
        id: 1,
        name: "Emergency Response Plan.pdf",
        category: "Documents",
        size: "4.2 MB",
        modified: "2024-07-15",
        status: "approved",
        tags: ["emergency", "safety", "response"],
        filePath: "uploads/sample1.pdf",
        version: "3.2",
        description: "Emergency response procedures for public safety personnel"
    },
    {
        id: 2,
        name: "Fire Safety Infographic.png",
        category: "Images",
        size: "2.8 MB",
        modified: "2024-07-14",
        status: "approved",
        tags: ["fire", "safety", "infographic"],
        filePath: "uploads/sample2.png",
        version: "1.0",
        description: "Fire safety guidelines visual representation"
    },
    {
        id: 3,
        name: "First Aid Training.mp4",
        category: "Videos",
        size: "128 MB",
        modified: "2024-07-13",
        status: "pending",
        tags: ["firstaid", "training", "medical"],
        filePath: "uploads/sample3.mp4",
        version: "2.1",
        description: "Basic first aid training video for community volunteers"
    },
    {
        id: 4,
        name: "Community Safety Newsletter.docx",
        category: "Documents",
        size: "1.5 MB",
        modified: "2024-07-12",
        status: "draft",
        tags: ["newsletter", "community", "awareness"],
        filePath: "uploads/sample4.docx",
        version: "1.0",
        description: "Monthly community safety awareness newsletter"
    },
    {
        id: 5,
        name: "Emergency Alert Audio.mp3",
        category: "Audio",
        size: "5.3 MB",
        modified: "2024-07-11",
        status: "approved",
        tags: ["alert", "emergency", "audio"],
        filePath: "uploads/sample5.mp3",
        version: "1.2",
        description: "Multilingual emergency alert audio recordings"
    }
];

// API Routes

// Get all content items
app.get('/api/content', (req, res) => {
    res.json(contentItems);
});

// Get single content item
app.get('/api/content/:id', (req, res) => {
    const item = contentItems.find(i => i.id === parseInt(req.params.id));
    if (!item) return res.status(404).json({ error: 'Content not found' });
    res.json(item);
});

// Validate content input
function validateContentInput(req, res, next) {
    const { name, category, description } = req.body;
    
    // Validate name if provided
    if (name && typeof name !== 'string') {
        return res.status(400).json({ error: 'Name must be a string' });
    }
    
    // Validate category if provided
    if (category && typeof category !== 'string') {
        return res.status(400).json({ error: 'Category must be a string' });
    }
    
    // Validate description if provided
    if (description && typeof description !== 'string') {
        return res.status(400).json({ error: 'Description must be a string' });
    }
    
    next();
}

// Upload new content (single file)
app.post('/api/content', upload.single('file'), validateContentInput, (req, res) => {
    try {
        const { name, category, tags, description } = req.body;
        const file = req.file;

        if (!file) {
            return res.status(400).json({ error: 'No file uploaded' });
        }

        // Generate ID more efficiently
        const newId = getNextId(contentItems);
        
        const newItem = {
            id: newId,
            name: name || file.originalname,
            category: category || getCategoryFromExtension(file.originalname),
            size: formatFileSize(file.size),
            modified: new Date().toISOString().split('T')[0],
            status: 'pending',
            tags: tags ? tags.split(',').map(tag => tag.trim()) : [],
            filePath: file.path,
            version: '1.0',
            description: description || ''
        };

        contentItems.push(newItem);
        res.status(201).json(newItem);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Upload multiple files
app.post('/api/content/bulk', upload.array('files', 10), (req, res) => {
    try {
        const files = req.files;

        if (!files || files.length === 0) {
            return res.status(400).json({ error: 'No files uploaded' });
        }

        const newItems = files.map((file, index) => {
            const newId = getNextId(contentItems, index);
            return {
                id: newId,
                name: file.originalname,
                category: getCategoryFromExtension(file.originalname),
                size: formatFileSize(file.size),
                modified: new Date().toISOString().split('T')[0],
                status: 'pending',
                tags: [],
                filePath: file.path,
                version: '1.0',
                description: ''
            };
        });

        contentItems.push(...newItems);
        res.status(201).json(newItems);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Validate content update input
function validateContentUpdateInput(req, res, next) {
    const { name, category, description, status } = req.body;
    
    // Validate name if provided
    if (name && typeof name !== 'string') {
        return res.status(400).json({ error: 'Name must be a string' });
    }
    
    // Validate category if provided
    if (category && typeof category !== 'string') {
        return res.status(400).json({ error: 'Category must be a string' });
    }
    
    // Validate description if provided
    if (description && typeof description !== 'string') {
        return res.status(400).json({ error: 'Description must be a string' });
    }
    
    // Validate status if provided
    if (status && !['draft', 'pending', 'approved'].includes(status)) {
        return res.status(400).json({ error: 'Status must be one of: draft, pending, approved' });
    }
    
    next();
}

// Update content item
app.put('/api/content/:id', validateContentUpdateInput, (req, res) => {
    const id = parseInt(req.params.id);
    const index = contentItems.findIndex(i => i.id === id);

    if (index === -1) return res.status(404).json({ error: 'Content not found' });

    const updatedItem = {
        ...contentItems[index],
        ...req.body,
        modified: new Date().toISOString().split('T')[0]
    };

    contentItems[index] = updatedItem;
    res.json(updatedItem);
});

// Delete content item
app.delete('/api/content/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const index = contentItems.findIndex(i => i.id === id);

    if (index === -1) return res.status(404).json({ error: 'Content not found' });

    // Delete the actual file
    const item = contentItems[index];
    if (fs.existsSync(item.filePath)) {
        try {
            fs.unlinkSync(item.filePath);
        } catch (error) {
            console.error('Error deleting file:', error);
        }
    }

    contentItems.splice(index, 1);
    res.json({ message: 'Content deleted successfully' });
});

// Download file
app.get('/api/download/:id', (req, res) => {
    const item = contentItems.find(i => i.id === parseInt(req.params.id));
    if (!item || !fs.existsSync(item.filePath)) {
        return res.status(404).json({ error: 'File not found' });
    }

    res.download(item.filePath);
});

// Search content
app.get('/api/content/search/:query', (req, res) => {
    const query = req.params.query.toLowerCase();
    const results = contentItems.filter(item =>
        item.name.toLowerCase().includes(query) ||
        item.category.toLowerCase().includes(query) ||
        (item.tags && item.tags.some(tag => tag.toLowerCase().includes(query))) ||
        (item.description && item.description.toLowerCase().includes(query))
    );
    res.json(results);
});

// Get content statistics
app.get('/api/content/stats', (req, res) => {
    const stats = {
        totalItems: contentItems.length,
        byCategory: {
            Documents: contentItems.filter(item => item.category === 'Documents').length,
            Images: contentItems.filter(item => item.category === 'Images').length,
            Videos: contentItems.filter(item => item.category === 'Videos').length,
            Audio: contentItems.filter(item => item.category === 'Audio').length
        },
        byStatus: {
            approved: contentItems.filter(item => item.status === 'approved').length,
            pending: contentItems.filter(item => item.status === 'pending').length,
            draft: contentItems.filter(item => item.status === 'draft').length
        }
    };
    res.json(stats);
});

// Helper functions
function getNextId(array, offset = 0) {
    if (array.length === 0) return 1 + offset;
    
    // Find the highest ID in the array
    let maxId = 0;
    for (const item of array) {
        if (item.id > maxId) {
            maxId = item.id;
        }
    }
    
    return maxId + 1 + offset;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getCategoryFromExtension(filename) {
    const ext = path.extname(filename).toLowerCase();
    const imageExt = ['.jpg', '.jpeg', '.png', '.gif', '.bmp', '.svg', '.webp'];
    const videoExt = ['.mp4', '.avi', '.mov', '.wmv', '.flv', '.mkv'];
    const audioExt = ['.mp3', '.wav', '.aac', '.ogg', '.flac'];
    const docExt = ['.pdf', '.doc', '.docx', '.txt', '.xls', '.xlsx', '.ppt', '.pptx'];

    if (imageExt.includes(ext)) return 'Images';
    if (videoExt.includes(ext)) return 'Videos';
    if (audioExt.includes(ext)) return 'Audio';
    if (docExt.includes(ext)) return 'Documents';
    return 'Other';
}

// Default route for home page
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'home.html'));
});

// Route for module files (with path traversal protection)
app.get('/Modules/:filename', (req, res) => {
    const filename = req.params.filename;
    
    // Normalize and validate the path to prevent traversal attacks
    const normalizedPath = path.normalize(filename);
    
    // Check if the normalized path contains '..' which indicates traversal
    if (normalizedPath.includes('..') || path.isAbsolute(normalizedPath) || !normalizedPath.match(/^[a-zA-Z0-9._-]+$/)) {
        return res.status(400).send('Invalid filename');
    }
    
    res.sendFile(path.join(__dirname, 'Modules', normalizedPath));
});

// API health check
app.get('/api/health', (req, res) => {
    res.json({
        status: 'OK',
        timestamp: new Date().toISOString(),
        totalFiles: contentItems.length
    });
});

// Error handling middleware
app.use((err, req, res, next) => {
    console.error(`Error occurred: ${err.message}`, {
        url: req.url,
        method: req.method,
        ip: req.ip,
        userAgent: req.get('User-Agent')
    });
    
    // Send appropriate error response
    if (err instanceof SyntaxError && err.status === 400 && 'body' in err) {
        res.status(400).json({ error: 'Invalid JSON format' });
    } else {
        res.status(500).json({ error: 'Internal server error' });
    }
});

app.listen(port, () => {
    console.log(`Server running at http://localhost:${port}`);
    console.log(`API available at http://localhost:${port}/api/content`);
    console.log(`Uploads directory: ${uploadsDir}`);
});

// Survey & Feedback Collection APIs

// In-memory database for surveys
let surveys = [
    {
        id: 1,
        name: "Summer Safety Campaign Feedback",
        description: "Measuring campaign effectiveness and public awareness",
        type: "campaign",
        status: "active",
        responses: 1247,
        completionRate: 78,
        avgRating: 4.2,
        createdAt: "2024-06-15",
        endDate: "2024-08-15",
        questions: [
            { id: 1, type: "rating", text: "How effective was the safety campaign?", required: true },
            { id: 2, type: "multiple_choice", text: "Which safety topics were most helpful?", options: ["Fire Safety", "Emergency Response", "First Aid", "Road Safety"], required: true },
            { id: 3, type: "text", text: "Suggestions for improvement", required: false }
        ]
    },
    {
        id: 2,
        name: "Community First Aid Workshop Evaluation",
        description: "Post-event feedback for workshop improvement",
        type: "event",
        status: "active",
        responses: 85,
        completionRate: 92,
        avgRating: 4.0,
        createdAt: "2024-07-10",
        endDate: "2024-07-31",
        questions: [
            { id: 1, type: "rating", text: "Workshop content quality", required: true },
            { id: 2, type: "multiple_choice", text: "Instructor effectiveness", options: ["Excellent", "Good", "Average", "Poor"], required: true },
            { id: 3, type: "text", text: "Additional topics you'd like covered", required: false }
        ]
    }
];

// In-memory database for survey responses
let surveyResponses = [
    {
        id: 1,
        surveyId: 1,
        respondent: "John D.",
        rating: 4,
        feedback: "The first aid workshop was extremely helpful. The instructors were knowledgeable and patient with questions.",
        sentiment: "positive",
        createdAt: "2024-07-15T14:30:00Z",
        channel: "web"
    },
    {
        id: 2,
        surveyId: 1,
        respondent: "Maria S.",
        rating: 5,
        feedback: "Emergency response was quick and professional during the recent incident. Thank you for your service!",
        sentiment: "positive",
        createdAt: "2024-07-15T10:15:00Z",
        channel: "email"
    }
];

// Survey APIs

// Get all surveys
app.get('/api/surveys', (req, res) => {
    res.json(surveys);
});

// Get single survey
app.get('/api/surveys/:id', (req, res) => {
    const survey = surveys.find(s => s.id === parseInt(req.params.id));
    if (!survey) return res.status(404).json({ error: 'Survey not found' });
    res.json(survey);
});

// Validate survey input
function validateSurveyInput(req, res, next) {
    const { name, description, type } = req.body;
    
    // Validate required fields
    if (!name || typeof name !== 'string' || name.trim().length === 0) {
        return res.status(400).json({ error: 'Name is required and must be a non-empty string' });
    }
    
    if (!description || typeof description !== 'string' || description.trim().length === 0) {
        return res.status(400).json({ error: 'Description is required and must be a non-empty string' });
    }
    
    if (!type || typeof type !== 'string' || !['campaign', 'event', 'feedback'].includes(type)) {
        return res.status(400).json({ error: 'Type is required and must be one of: campaign, event, feedback' });
    }
    
    // Validate name length
    if (name.length > 255) {
        return res.status(400).json({ error: 'Name must be 255 characters or less' });
    }
    
    // Validate description length
    if (description.length > 1000) {
        return res.status(400).json({ error: 'Description must be 1000 characters or less' });
    }
    
    next();
}

// Create new survey
app.post('/api/surveys', validateSurveyInput, (req, res) => {
    try {
        const { name, description, type, questions } = req.body;

        if (!name || !description || !type) {
            return res.status(400).json({ error: 'Name, description, and type are required' });
        }

        // Generate ID more efficiently
        const newId = getNextId(surveys);
        
        const newSurvey = {
            id: newId,
            name,
            description,
            type,
            status: "draft",
            responses: 0,
            completionRate: 0,
            avgRating: 0,
            createdAt: new Date().toISOString().split('T')[0],
            endDate: null,
            questions: questions || []
        };

        surveys.push(newSurvey);
        res.status(201).json(newSurvey);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Update survey
app.put('/api/surveys/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const index = surveys.findIndex(s => s.id === id);

    if (index === -1) return res.status(404).json({ error: 'Survey not found' });

    surveys[index] = {
        ...surveys[index],
        ...req.body
    };

    res.json(surveys[index]);
});

// Delete survey
app.delete('/api/surveys/:id', (req, res) => {
    const id = parseInt(req.params.id);
    const index = surveys.findIndex(s => s.id === id);

    if (index === -1) return res.status(404).json({ error: 'Survey not found' });

    surveys.splice(index, 1);

    // Also delete related responses
    surveyResponses = surveyResponses.filter(r => r.surveyId !== id);

    res.json({ message: 'Survey deleted successfully' });
});

// Launch/Activate survey
app.post('/api/surveys/:id/launch', (req, res) => {
    const id = parseInt(req.params.id);
    const index = surveys.findIndex(s => s.id === id);

    if (index === -1) return res.status(404).json({ error: 'Survey not found' });

    surveys[index].status = "active";
    surveys[index].launchedAt = new Date().toISOString();

    res.json(surveys[index]);
});

// Close survey
app.post('/api/surveys/:id/close', (req, res) => {
    const id = parseInt(req.params.id);
    const index = surveys.findIndex(s => s.id === id);

    if (index === -1) return res.status(404).json({ error: 'Survey not found' });

    surveys[index].status = "closed";
    surveys[index].closedAt = new Date().toISOString();

    res.json(surveys[index]);
});

// Survey Responses APIs

// Get all responses for a survey
app.get('/api/surveys/:id/responses', (req, res) => {
    const surveyId = parseInt(req.params.id);
    const responses = surveyResponses.filter(r => r.surveyId === surveyId);
    res.json(responses);
});

// Submit response
app.post('/api/surveys/:id/responses', (req, res) => {
    try {
        const surveyId = parseInt(req.params.id);
        const { respondent, rating, feedback, sentiment, channel } = req.body;

        const survey = surveys.find(s => s.id === surveyId);
        if (!survey) return res.status(404).json({ error: 'Survey not found' });

        // Generate ID more efficiently
        const newId = getNextId(surveyResponses);
        
        const newResponse = {
            id: newId,
            surveyId,
            respondent: respondent || "Anonymous",
            rating: rating || 0,
            feedback: feedback || "",
            sentiment: sentiment || "neutral",
            channel: channel || "web",
            createdAt: new Date().toISOString()
        };

        surveyResponses.push(newResponse);

        // Update survey statistics
        const surveyIndex = surveys.findIndex(s => s.id === surveyId);
        surveys[surveyIndex].responses++;

        // Update average rating
        const surveyResponsesList = surveyResponses.filter(r => r.surveyId === surveyId);
        if (surveyResponsesList.length > 0) {
            const totalRating = surveyResponsesList.reduce((sum, r) => sum + (r.rating || 0), 0);
            surveys[surveyIndex].avgRating = (totalRating / surveyResponsesList.length).toFixed(1);
        }

        res.status(201).json(newResponse);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Get survey statistics
app.get('/api/surveys/:id/stats', (req, res) => {
    const surveyId = parseInt(req.params.id);
    const surveyResponsesList = surveyResponses.filter(r => r.surveyId === surveyId);

    const stats = {
        totalResponses: surveyResponsesList.length,
        avgRating: surveyResponsesList.length > 0 ?
            (surveyResponsesList.reduce((sum, r) => sum + (r.rating || 0), 0) / surveyResponsesList.length).toFixed(1) : 0,
        sentiment: {
            positive: surveyResponsesList.filter(r => r.sentiment === 'positive').length,
            neutral: surveyResponsesList.filter(r => r.sentiment === 'neutral').length,
            negative: surveyResponsesList.filter(r => r.sentiment === 'negative').length
        },
        channels: {
            email: surveyResponsesList.filter(r => r.channel === 'email').length,
            web: surveyResponsesList.filter(r => r.channel === 'web').length,
            sms: surveyResponsesList.filter(r => r.channel === 'sms').length,
            qr: surveyResponsesList.filter(r => r.channel === 'qr').length
        }
    };

    res.json(stats);
});

// Get all survey responses
app.get('/api/responses', (req, res) => {
    res.json(surveyResponses);
});

// Export survey data
app.get('/api/surveys/:id/export', (req, res) => {
    const surveyId = parseInt(req.params.id);
    const survey = surveys.find(s => s.id === surveyId);
    const responses = surveyResponses.filter(r => r.surveyId === surveyId);

    if (!survey) return res.status(404).json({ error: 'Survey not found' });

    // Generate CSV data
    let csv = 'Survey Name,Description,Type,Status,Responses,Avg Rating\n';
    csv += `"${survey.name}","${survey.description}","${survey.type}","${survey.status}","${survey.responses}","${survey.avgRating}"\n\n`;
    csv += 'Response Data:\n';
    csv += 'ID,Respondent,Rating,Feedback,Sentiment,Channel,Date\n';

    responses.forEach(response => {
        csv += `"${response.id}","${response.respondent}","${response.rating}","${response.feedback}","${response.sentiment}","${response.channel}","${response.createdAt}"\n`;
    });

    res.setHeader('Content-Type', 'text/csv');
    res.setHeader('Content-Disposition', `attachment; filename=survey_${surveyId}_export.csv`);
    res.send(csv);
});

// Search surveys
app.get('/api/surveys/search/:query', (req, res) => {
    const query = req.params.query.toLowerCase();
    const results = surveys.filter(survey =>
        survey.name.toLowerCase().includes(query) ||
        survey.description.toLowerCase().includes(query) ||
        survey.type.toLowerCase().includes(query)
    );
    res.json(results);
});

// Get dashboard statistics
app.get('/api/surveys/dashboard/stats', (req, res) => {
    const totalSurveys = surveys.length;
    const activeSurveys = surveys.filter(s => s.status === 'active').length;
    const totalResponses = surveyResponses.length;

    // Calculate average completion rate
    const avgCompletionRate = surveys.length > 0 ?
        (surveys.reduce((sum, s) => sum + s.completionRate, 0) / surveys.length).toFixed(1) : 0;

    // Calculate average satisfaction
    const avgSatisfaction = surveys.length > 0 ?
        (surveys.reduce((sum, s) => sum + parseFloat(s.avgRating), 0) / surveys.length).toFixed(1) : 0;

    // Sentiment analysis
    const sentiment = {
        positive: surveyResponses.filter(r => r.sentiment === 'positive').length,
        neutral: surveyResponses.filter(r => r.sentiment === 'neutral').length,
        negative: surveyResponses.filter(r => r.sentiment === 'negative').length
    };

    const stats = {
        totalSurveys,
        activeSurveys,
        totalResponses,
        avgCompletionRate,
        avgSatisfaction,
        sentiment
    };

    res.json(stats);
});

