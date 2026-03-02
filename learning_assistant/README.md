# Intelligent Learning Assistant

An AI-powered learning assistant that helps students get contextual answers to questions about their course materials.

## Features

- 🤖 **Semantic Search**: Uses AI embeddings to find relevant course content
- 💬 **Question Answering**: Answers student questions based on course materials
- 📊 **Interaction Logging**: Tracks Q&A interactions for continuous improvement
- 🔄 **Auto-Sync**: Syncs courses and resources from MySQL database
- 🚀 **REST API**: Flask API for easy integration with Symfony frontend

## Architecture

```
Flask API Server (Python)
  ├── Embedding Service (Semantic Search)
  ├── Q&A Service (Answer Generation)
  └── Database Service (MySQL Integration)
```

## Prerequisites

- Python 3.9+
- MySQL database (CreaCop backend)
- pip (Python package manager)

## Installation

### 1. Create Python Virtual Environment

```bash
cd learning_assistant
python -m venv venv

# Activate virtual environment
# On Windows:
venv\Scripts\activate
# On Linux/Mac:
source venv/bin/activate
```

### 2. Install Dependencies

```bash
pip install -r requirements.txt
```

This will install:
- Flask: Web framework
- sentence-transformers: AI embeddings
- scikit-learn: Machine learning
- mysql-connector-python: Database connection
- And more...

### 3. Configure Database Connection

Update `.env` file in project root with database credentials:

```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_password
DB_NAME=creadb
DB_PORT=3306
FLASK_HOST=127.0.0.1
FLASK_PORT=5000
FLASK_DEBUG=True
```

### 4. Sync Courses and Build Embeddings

```bash
# Sync courses from database
python scripts/sync_courses.py

# Output:
# ✓ Synced 12 courses
# ✓ Total resources: 45
# - Introduction to Python (5 resources)
# ...
```

```bash
# Build embeddings index
python scripts/train_embeddings.py

# Output:
# ✓ Embeddings training completed!
# ✓ Created embeddings for 57 documents
# ✓ Index saved to ./models/embeddings_index.pkl
```

## Usage

### Start the API Server

```bash
python app.py

# Output:
# Starting Intelligent Learning Assistant...
# Loaded 12 courses
# Embeddings index has 57 documents
# * Running on http://127.0.0.1:5000
```

### API Endpoints

#### 1. Ask a Question

**POST** `/api/qa/ask`

```json
{
  "question": "What is machine learning?",
  "course_id": 1,
  "user_id": 5,
  "top_k": 5
}
```

**Response:**
```json
{
  "answer": "Based on the course materials...",
  "sources": [
    {
      "text": "Machine learning is a subset of artificial intelligence...",
      "similarity": 0.87
    }
  ],
  "confidence": 0.87,
  "relevant_documents": 3
}
```

#### 2. Get Course Summary

**GET** `/api/courses/{course_id}/summary`

**Response:**
```json
{
  "title": "Python Basics",
  "description": "Learn Python programming...",
  "level": "beginner",
  "category": "Programming",
  "resources_count": 5,
  "resources": [
    {"title": "Variables and Data Types", "type": "video"},
    {"title": "Control Flow", "type": "article"}
  ]
}
```

#### 3. List All Courses

**GET** `/api/courses`

**Response:**
```json
{
  "total_courses": 12,
  "courses": [
    {"id": 1, "title": "Python Basics", "level": "beginner", "resources_count": 5},
    ...
  ]
}
```

#### 4. Health Check

**GET** `/health`

**Response:**
```json
{
  "status": "ok",
  "service": "Intelligent Learning Assistant",
  "embeddings_loaded": true,
  "documents_count": 57
}
```

### Admin Endpoints

#### Sync Courses from Database

**POST** `/api/admin/sync-courses`

Fetches latest courses from MySQL and updates cache.

#### Rebuild Embeddings

**POST** `/api/admin/rebuild-embeddings`

Clears and rebuilds the embeddings index. Use after adding new courses.

#### Service Status

**GET** `/api/admin/status`

Returns current service statistics.

## Integration with Symfony Frontend

### From Symfony Controller

```php
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoursController extends AbstractController
{
    public function askQuestion(HttpClientInterface $httpClient): Response
    {
        $response = $httpClient->request('POST', 'http://127.0.0.1:5000/api/qa/ask', [
            'json' => [
                'question' => 'What is OOP?',
                'course_id' => 1,
                'user_id' => $this->getUser()->getId()
            ]
        ]);
        
        $data = $response->toArray();
        return new JsonResponse($data);
    }
}
```

### From JavaScript Frontend

```javascript
// Ask a question
async function askQuestion(question, courseId) {
    const response = await fetch('http://127.0.0.1:5000/api/qa/ask', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            question: question,
            course_id: courseId,
            user_id: userId
        })
    });
    
    const data = await response.json();
    console.log(data.answer);
}

// Usage in course page
document.getElementById('ask-btn').addEventListener('click', () => {
    const question = document.getElementById('question-input').value;
    askQuestion(question, courseId);
});
```

## Project Structure

```
learning_assistant/
├── app.py                      # Flask API server
├── config.py                   # Configuration settings
├── requirements.txt            # Python dependencies
├── services/
│   ├── db_service.py          # MySQL database connection
│   ├── embedding_service.py   # Semantic search & embeddings
│   └── qa_service.py          # Q&A generation
├── scripts/
│   ├── sync_courses.py        # Sync from database
│   └── train_embeddings.py    # Build embeddings
├── models/                     # Trained model storage
│   └── embeddings_index.pkl   # Embeddings index
├── data/                       # Cached data
│   ├── courses_data.json      # Course metadata
│   └── qa_history.json        # Q&A interactions
└── README.md                   # This file
```

## How It Works

### 1. Course Embedding

```
Courses & Resources
        ↓
    Sync from DB
        ↓
Text Processing
        ↓
Sentence Transformer (all-MiniLM-L6-v2)
        ↓
Vector Embeddings (384 dimensions)
        ↓
Save Index (embeddings_index.pkl)
```

### 2. Question Answering

```
Student Question
        ↓
Encode Question (same model)
        ↓
Calculate Similarity (cosine)
        ↓
Retrieve Top 5 Documents
        ↓
Generate Answer from Context
        ↓
Return with Sources & Score
```

## Performance Tips

1. **Increase `top_k`** for more thorough searches (default: 5)
2. **Lower `similarity_threshold`** for broader results (default: 0.5)
3. **Rebuild embeddings** after adding many new courses
4. **Cache answers** in Symfony app for popular questions

## Troubleshooting

### Database Connection Error

```
MySQL Error: Unknown database 'creadb'
```

Check `.env` file database credentials and MySQL is running.

### Out of Memory Error

When training embeddings on large courses:

```bash
# Reduce batch processing or increase system memory
```

### No Documents Returned

1. Ensure courses are synced: `python scripts/sync_courses.py`
2. Check embeddings are built: `python scripts/train_embeddings.py`
3. Verify `/health` endpoint returns documents_count > 0

## Future Enhancements

- [ ] Integration with Gemini API for better answer generation
- [ ] Fine-tune embeddings on course-specific vocabulary
- [ ] Real-time feedback to improve answer quality
- [ ] Multi-language support
- [ ] Spam/quality filtering on Q&A
- [ ] Conversation history (context-aware answers)

## License

Internal - CreaCo Project

## Support

For issues or questions, check the logs:

```bash
# Check Flask logs
python app.py  # Shows all requests and errors

# Check database sync logs
python scripts/sync_courses.py
```
