const express = require('express');
const multer = require('multer');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
app.use(cors());
app.use(express.json());

// Ensure uploads directory exists
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
        const uniqueName = Date.now() + '-' + file.originalname;
        cb(null, uniqueName);
    }
});

const upload = multer({
    storage: storage,
    limits: { fileSize: 100 * 1024 * 1024 }, // 100MB limit
    fileFilter: (req, file, cb) => {
        const allowedTypes = /jpeg|jpg|png|gif|pdf|doc|docx|mp4|mp3|wav|avi|mov/;
        const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase());
        const mimetype = allowedTypes.test(file.mimetype);

        if (extname && mimetype) {
            return cb(null, true);
        }
        cb(new Error('File type not allowed'));
    }
});

// In-memory database (replace with real database in production)
let contentItems = [
    {
        id: 1,
        name: "Emergency Response Plan.pdf",
        category: "Documents",
        size: "4.2 MB",
        modified: "2024-07-15",
        status: "approved",
        tags: ["emergency", "safety"],
        filePath: "uploads/sample1.pdf",
        version: "3.2",
        description: "Emergency response procedures"
    },
    {
        id: 2,
        name: "Fire Safety Infographic.png",
        category: "Images",
        size: "2.8 MB",
        modified: "2024-07-14",
        status: "approved",
        tags: ["fire", "safety"],
        filePath: "uploads/sample2.png",
        version: "1.0",
        description: "Fire safety guidelines visual"
    }
];

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

// Upload new content
app.post('/api/content', upload.single('file'), (req, res) => {
    try {
        const { name, category, tags, description } = req.body;
        const file = req.file;

        if (!file) {
            return res.status(400).json({ error: 'No file uploaded' });
        }

        const newItem = {
            id: contentItems.length + 1,
            name: name || file.originalname,
            category: category || 'Documents',
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

// Update content item
app.put('/api/content/:id', (req, res) => {
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
        fs.unlinkSync(item.filePath);
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

// Upload multiple files
app.post('/api/content/bulk', upload.array('files', 10), (req, res) => {
    try {
        const files = req.files;
        const newItems = files.map((file, index) => ({
            id: contentItems.length + index + 1,
            name: file.originalname,
            category: getCategoryFromExtension(file.originalname),
            size: formatFileSize(file.size),
            modified: new Date().toISOString().split('T')[0],
            status: 'pending',
            tags: [],
            filePath: file.path,
            version: '1.0',
            description: ''
        }));

        contentItems.push(...newItems);
        res.status(201).json(newItems);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Search content
app.get('/api/content/search/:query', (req, res) => {
    const query = req.params.query.toLowerCase();
    const results = contentItems.filter(item =>
        item.name.toLowerCase().includes(query) ||
        item.category.toLowerCase().includes(query) ||
        item.tags.some(tag => tag.toLowerCase().includes(query)) ||
        item.description.toLowerCase().includes(query)
    );
    res.json(results);
});

// Helper functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function getCategoryFromExtension(filename) {
    const ext = path.extname(filename).toLowerCase();
    const imageExt = ['.jpg', '.jpeg', '.png', '.gif', '.bmp'];
    const videoExt = ['.mp4', '.avi', '.mov', '.wmv'];
    const audioExt = ['.mp3', '.wav', '.aac'];
    const docExt = ['.pdf', '.doc', '.docx', '.txt', '.xls', '.xlsx'];

    if (imageExt.includes(ext)) return 'Images';
    if (videoExt.includes(ext)) return 'Videos';
    if (audioExt.includes(ext)) return 'Audio';
    if (docExt.includes(ext)) return 'Documents';
    return 'Other';
}

// Serve uploaded files statically
app.use('/uploads', express.static('uploads'));

const PORT = 3000;
app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});