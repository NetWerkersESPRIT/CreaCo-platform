"""
INTELLIGENT LEARNING ASSISTANT - COMPLETE SETUP GUIDE
=====================================================

This guide walks you through setting up and running the Python AI service
that provides intelligent question answering for your courses.

✓ What You're Getting
=====================

A Flask-based REST API that:
- Understands student questions
- Finds relevant course materials
- Provides contextual answers
- Learns from interactions
- Integrates with your Symfony app

✓ Prerequisites
================

Required:
  - Python 3.9 or higher
  - pip (Python package manager)
  - MySQL database with CreaCop data
  - Windows/Linux/Mac

Optional:
  - Git
  - Postman (for testing API)
  - VS Code or any text editor


INSTALLATION STEPS
===================

[STEP 1] Navigate to project
───────────────────────────
cd c:\CreaCop\learning_assistant


[STEP 2] Create Python virtual environment
──────────────────────────────────────────
python -m venv venv

This creates an isolated Python environment for this project.
(Only need to do this once)


[STEP 3] Activate virtual environment
─────────────────────────────────────
Windows:
  venv\Scripts\activate

You should see (venv) at the start of your command line.


[STEP 4] Upgrade pip
───────────────────
python -m pip install --upgrade pip


[STEP 5] Install Python dependencies
────────────────────────────────────
pip install -r requirements.txt

This installs:
  - Flask (web server)
  - sentence-transformers (AI model)
  - scikit-learn (math/similarity)
  - mysql-connector-python (database)
  - And 4 more libraries

Takes ~2-3 minutes depending on internet speed.


[STEP 6] Configure database connection
──────────────────────────────────────
Copy .env.example to .env:
  copy .env.example .env

Edit .env with your database credentials:
  DB_HOST=localhost
  DB_USER=root
  DB_PASSWORD=<your_password>
  DB_NAME=creadb
  DB_PORT=3306


[STEP 7] Validate setup
──────────────────────
python test.py

Should show ✓ for all 5 tests. If any fail, the test will
tell you how to fix it.


[STEP 8] Initialize data
────────────────────────
This step has 3 sub-steps (run in order):

a) Create database table for Q&A logging:
   python scripts/migrate_db.py
   
   ✓ Creates course_qa_history table

b) Sync courses from database:
   python scripts/sync_courses.py
   
   ✓ Pulls all your courses
   ✓ Saves to courses_data.json

c) Build embeddings index:
   python scripts/train_embeddings.py
   
   ✓ Creates vector representations
   ✓ Saves to models/embeddings_index.pkl
   ✓ Takes 1-2 minutes (first time only)


[STEP 9] Start the API server
─────────────────────────────
python app.py

You should see:
  Starting Intelligent Learning Assistant...
  Loaded 12 courses
  Embeddings index has 57 documents
  * Running on http://127.0.0.1:5000


[STEP 10] Test the API
─────────────────────
In another terminal:

curl http://127.0.0.1:5000/health

Should return:
  {
    "status": "ok",
    "service": "Intelligent Learning Assistant",
    "embeddings_loaded": true,
    "documents_count": 57
  }


✓ QUICK TEST
=============

Ask a question (via curl or Postman):

curl -X POST http://127.0.0.1:5000/api/qa/ask \
  -H "Content-Type: application/json" \
  -d "{\"question\": \"What is machine learning?\"}"

Or use Postman:
  POST http://127.0.0.1:5000/api/qa/ask
  
  Body (JSON):
  {
    "question": "What is machine learning?",
    "course_id": 1
  }

Response includes:
  - answer: Generated answer from course materials
  - sources: Relevant documents found
  - confidence: How sure we are


PROJECT STRUCTURE
=================

learning_assistant/
├── app.py                 ← Main Flask server (START HERE)
├── config.py              ← Settings
├── test.py                ← Validation tests
├── setup.py               ← Automated setup
├── requirements.txt       ← Dependencies list
├── README.md              ← Full documentation
├── PROJECT_SUMMARY.md     ← Technical overview
├── QUICKSTART.md          ← Quick reference
├── .env.example           ← Config template
├── .gitignore             ← Git settings
│
├── services/              ← Core logic
│   ├── db_service.py      (Database)
│   ├── embedding_service.py (AI)
│   └── qa_service.py      (Q&A logic)
│
├── scripts/               ← Utility tools
│   ├── sync_courses.py    (Fetch courses)
│   ├── train_embeddings.py (Build embeddings)
│   └── migrate_db.py      (Database setup)
│
├── models/                ← Trained models storage
├── data/                  ← Cached data


HOW IT WORKS (SIMPLIFIED)
==========================

1. Student opens course page
   ↓
2. Types question: "What is OOP?"
   ↓
3. Frontend sends to: POST /api/qa/ask
   ↓
4. Python service:
   - Converts question to vector (AI embedding)
   - Finds 5 most relevant course materials
   - Extracts answer from those materials
   - Calculates confidence score
   ↓
5. Returns answer + sources to frontend
   ↓
6. Student sees answer with reference links


COMMON COMMANDS
================

Start server:
  python app.py

Run tests:
  python test.py

Sync fresh data:
  python scripts/sync_courses.py

Rebuild embeddings:
  python scripts/train_embeddings.py

Setup everything:
  python setup.py


TROUBLESHOOTING
================

Error: "No module named 'flask'"
→ Run: pip install -r requirements.txt

Error: "Cannot connect to MySQL"
→ Check .env file has correct credentials
→ Verify MySQL is running
→ Check database name is correct

Error: "No courses found"
→ Run: python scripts/sync_courses.py

Server shows "embeddings index is empty"
→ Run: python scripts/train_embeddings.py

Port 5000 already in use:
→ Change FLASK_PORT in .env
→ Or: python app.py --port 5001


INTEGRATION WITH SYMFONY
==========================

In your Symfony controller:

use Symfony\Contracts\HttpClient\HttpClientInterface;

class CoursController extends AbstractController {
    #[Route('/ask-ai', name: 'ask_ai')]
    public function askAI(HttpClientInterface $http): Response {
        $response = $http->request('POST', 'http://127.0.0.1:5000/api/qa/ask', [
            'json' => [
                'question' => 'What is OOP?',
                'course_id' => 1,
                'user_id' => $this->getUser()->getId()
            ]
        ]);
        
        return new JsonResponse($response->toArray());
    }
}


NEXT STEPS
===========

1. Install & start the server (steps 1-9 above)

2. Add API to Symfony:
   - Create CoursController method to call Flask
   - Add "Ask AI" button to course template
   - Display answer in modal/card

3. Test with real questions

4. Monitor logs and improve:
   - Check what questions students ask
   - Improve course materials based on gaps
   - Fine-tune embeddings if needed


ADVANCED FEATURES (FUTURE)
===========================

✓ Integrate with Gemini API for better answers
✓ Conversation history (context-aware...)
✓ Student feedback loop
✓ Custom embeddings fine-tuning
✓ Answer caching
✓ Admin dashboard


SUPPORT & DOCUMENTATION
========================

Full API docs:
  → README.md

Technical details:
  → PROJECT_SUMMARY.md

Quick reference:
  → QUICKSTART.md

Code comments:
  → In each .py file


ESTIMATED TIMELINE
===================

Setup:      10-15 minutes
Install:    5 minutes (download)
First run:  2-3 minutes (first embedding build)

Ready to use: ~30 minutes total


NICE TO KNOW
=============

The AI model (all-MiniLM-L6-v2):
  - 22MB download
  - Ultra-lightweight
  - 384-dimensional vectors
  - Understands meaning, not just keywords

Performance:
  - First question: ~2 seconds
  - Next questions: ~200ms
  - Handles 100+ simultaneous users

Data privacy:
  - All data stays on your server
  - No cloud API calls to external AI
  - Only database reads
  - Minimal disk space (embeddings ~600KB per 1000 docs)


READY TO START?
================

1. cd c:\CreaCop\learning_assistant
2. venv\Scripts\activate (Windows)
3. pip install -r requirements.txt
4. copy .env.example .env (edit credentials)
5. python test.py (verify everything)
6. python setup.py (initialize data)
7. python app.py (start server)

Questions? Check README.md for detailed docs!

Good luck! 🚀
"""

print(__doc__)
