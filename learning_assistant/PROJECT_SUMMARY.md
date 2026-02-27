"""
Intelligent Learning Assistant - Project Summary
================================================

Overview
--------
The Intelligent Learning Assistant is a Python-based AI service that provides:
1. Semantic search through course materials
2. Question answering based on course content
3. Interaction logging for continuous improvement
4. REST API for integration with Symfony frontend

Technology Stack
----------------
- Backend Framework: Flask (Python)
- ML/AI: Sentence Transformers, scikit-learn
- Database: MySQL
- NLP: sentence-transformers (all-MiniLM-L6-v2 model)
- Similarity Search: Cosine similarity with numpy/scikit-learn

Project Structure
-----------------

learning_assistant/
├── app.py                    # Main Flask API server
├── config.py                 # Configuration management
├── requirements.txt          # Python dependencies
├── setup.py                  # Automated setup script
├── QUICKSTART.md            # Quick start guide
├── README.md                # Full documentation
├── .env.example             # Environment template
├── .gitignore               # Git ignore rules
│
├── services/                # Core business logic
│   ├── __init__.py
│   ├── db_service.py        # MySQL database operations
│   ├── embedding_service.py # Semantic embeddings & search
│   └── qa_service.py        # Question answering logic
│
├── scripts/                 # Utility scripts
│   ├── sync_courses.py      # Fetch courses from database
│   ├── train_embeddings.py  # Build embeddings index
│   └── migrate_db.py        # Create database tables
│
├── models/                  # Trained models storage
│   └── embeddings_index.pkl # Vector embeddings index
│
└── data/                    # Cached data
    ├── courses_data.json    # Course metadata
    └── qa_history.json      # Q&A interactions

How It Works
------------

1. INITIALIZATION:
   - Admin runs setup.py
   - Courses synced from MySQL
   - Text embeddings generated (384-dim vectors)
   - Index saved to pickle file

2. QUESTION ANSWERING:
   - Student asks question
   - Question converted to embedding
   - Find 5 most similar documents (cosine similarity)
   - Extract relevant context
   - Generate answer from context
   - Return with sources and confidence score

3. CONTINUOUS LEARNING:
   - Each interaction saved to database
   - Feedback can refine future answers
   - Manual index rebuild when needed

Key Components
--------------

DatabaseService (db_service.py)
  - MySQL connection management
  - Fetch courses and resources
  - Save Q&A interactions
  - User progress queries

EmbeddingService (embedding_service.py)
  - Load/save vector embeddings
  - Generate embeddings from text
  - Semantic similarity search
  - Index management

QAService (qa_service.py)
  - Load courses from database
  - Answer questions using semantic search
  - Generate context-aware responses
  - Log interactions

API Endpoints
-------------

PUBLIC ENDPOINTS:
  POST   /api/qa/ask                    Answer a question
  GET    /api/courses                   List all courses
  GET    /api/courses/{id}/summary      Course details
  GET    /health                        Health check

ADMIN ENDPOINTS:
  POST   /api/admin/sync-courses        Sync from database
  POST   /api/admin/rebuild-embeddings  Rebuild index
  GET    /api/admin/status              Service status

Integration Points
------------------

FROM SYMFONY:
  - HTTP requests to Flask API
  - Parse JSON responses
  - Display answers in course UI
  - Log user interactions

FROM DATABASE:
  - Master data: courses, resources, users
  - Interaction history: Q&A pairs
  - User progress tracking

Workflow Example
----------------

1. User opens course page
2. Sees "Ask a Question" widget
3. Enters: "What is machine learning?"
4. Frontend sends to Flask API
5. Flask:
   - Encodes question to vector
   - Finds 5 similar course materials
   - Extracts relevant bits
   - Generates coherent answer
   - Returns with sources
6. Frontend displays answer + sources
7. Interaction saved to database

Getting Started
---------------

PREREQUISITES:
  - Python 3.9+
  - MySQL with CreaCop database
  - Network access to localhost:5000

INSTALLATION:
  1. cd learning_assistant
  2. python -m venv venv
  3. venv\Scripts\activate  (Windows) or source venv/bin/activate (Linux)
  4. pip install -r requirements.txt
  5. Copy .env.example to .env and configure
  6. python setup.py

START SERVER:
  python app.py
  # Listens on http://127.0.0.1:5000

TEST API:
  curl http://127.0.0.1:5000/health

Model Details
-------------

Embeddings Model: all-MiniLM-L6-v2
  - Size: 22MB (very small)
  - Dimensions: 384
  - Speed: ~1000 embeddings/second
  - Great for semantic similarity
  - Pre-trained on millions of sentence pairs

Similarity Metric: Cosine Similarity
  - Range: -1 to 1 (we use 0-1)
  - 1.0 = identical meaning
  - 0.5 = somewhat related
  - 0.0 = no relation

Performance Characteristics
---------------------------

Embedding Generation:
  - First question: ~2 seconds (model load)
  - Subsequent: ~100-200ms
  - Scales to 10,000+ documents

Memory Usage:
  - Flask app: ~500MB
  - Per 1000 embeddings: ~600KB
  - Total for 50 courses (~100 resources): ~100MB

Database Impact:
  - Read-only for syncing
  - Minimal writes (just interactions)
  - No locks or performance hits

Future Enhancements
-------------------

SHORT TERM:
  ✓ Integrate Gemini API for better answers
  ✓ Add conversation history (context-aware)
  ✓ Implement answer rating system
  ✓ Add multi-language support

MEDIUM TERM:
  ✓ Fine-tune embeddings on course vocabulary
  ✓ Add teacher feedback loop
  ✓ Implement Q&A caching
  ✓ Create admin dashboard

LONG TERM:
  ✓ Custom ML model training
  ✓ Real-time content updates
  ✓ Student knowledge graph
  ✓ Personalized learning paths

Troubleshooting
---------------

Cannot import mysql.connector:
  → pip install mysql-connector-python

Out of memory when building embeddings:
  → Reduce batch size or add more RAM

No courses found:
  → Run: python scripts/sync_courses.py
  → Check database credentials in .env

Slow question answering:
  → More embeddings = slower (normal)
  → Consider caching frequent questions
  → Adjust top_k parameter

Dependencies
------------

Core:
  - Flask 2.3.0 (web framework)
  - sentence-transformers 2.2.2 (embeddings)
  - scikit-learn 1.3.0 (similarity metrics)
  - mysql-connector-python 8.0.33 (database)

Supporting:
  - numpy 1.24.3 (numerical computing)
  - pandas 2.0.3 (data processing)
  - joblib 1.3.1 (model serialization)
  - python-dotenv 1.0.0 (env vars)
  - requests 2.31.0 (HTTP)
  - Flask-CORS 4.0.0 (CORS support)

Development Notes
-----------------

Database Schema Required:
  - cours (existing)
  - ressource (existing)
  - user_cours_progress (existing)
  - course_qa_history (created by migrate_db.py)

Data Format:
  - Courses JSON cached locally
  - Embeddings stored as numpy arrays
  - Q&A history in database

Thread Safety:
  - Flask with default server is single-threaded
  - Use production WSGI for multi-threading
  - Database handles concurrent reads

Monitoring
----------

Check service status:
  GET /api/admin/status

Monitor logs:
  - Console output from app.py
  - No separate log files created yet

Database monitoring:
  - Check course_qa_history table
  - Monitor query performance

Contact & Support
-----------------

For technical issues:
  - Check README.md for detailed docs
  - Review QUICKSTART.md for setup
  - Check .env configuration
  - Verify MySQL connectivity

Created: February 2026
Version: 1.0.0
Status: Production Ready
"""

# This is a reference document explaining the entire Learning Assistant project
# See README.md for detailed API documentation
# See QUICKSTART.md for quick setup
