# AI Learning Assistant - Architecture Visualization

## Complete System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         STUDENT BROWSER                             │
│                                                                     │
│  ┌────────────────────────────────────────────────────────────┐   │
│  │  Course Page: show.html.twig                              │   │
│  │                                                            │   │
│  │  ┌──────────────────────────────────────────────────┐    │   │
│  │  │ AI Learning Assistant Widget                     │    │   │
│  │  │                                                  │    │   │
│  │  │ [Chat Messages Display Area]                    │    │   │
│  │  │ - AI: "Hello, ask me..."                        │    │   │
│  │  │ - User: "What is OOP?"                          │    │   │
│  │  │ - AI: "Object-Oriented Programming is..."      │    │   │
│  │  │                                                  │    │   │
│  │  │ [Input Form]                                    │    │   │
│  │  │ [Question Input box] [Ask Button]               │    │   │
│  │  │                                                  │    │   │
│  │  │ [Status] Green checkmark "AI Ready"             │    │   │
│  │  └──────────────────────────────────────────────────┘    │   │
│  │                                                            │   │
│  │  JavaScript Handler                                      │   │
│  │  - Form submission listener                              │   │
│  │  - HTTP POST to /ai/question                             │   │
│  │  - Update UI with responses                              │   │
│  │  - Error handling                                         │   │
│  └────────────────────────────────────────────────────────────┘   │
│                          ↓ HTTP POST                               │
└──────────────────────────┼───────────────────────────────────────┘
                           │
                ┌──────────┴──────────┐
                │                     │
┌───────────────▼──────────────────┐  │
│  SYMFONY WEB SERVER              │  │
│  Port: 8000                      │  │
│                                  │  │
│  ┌─────────────────────────────┐ │  │
│  │ AIAssistantController.php   │ │  │
│  │                             │ │  │
│  │ POST /ai/question           │ │──┘
│  │  ├─ Validate input          │ │
│  │  ├─ Add course_id & user_id │ │
│  │  └─ Call Python API         │ │
│  │                             │ │
│  │ GET /ai/health              │ │
│  │  └─ Check service status    │ │
│  └─────────────────────────────┘ │
│                                  │
│  Routes configured               │
│  - /ai/question                  │
│  - /ai/course/{id}/summary       │
│  - /ai/health                    │
└──────────────────────────────────┘
                │
        HTTP Request :5000
                │
                ▼
┌──────────────────────────────────────────┐
│  PYTHON FLASK API SERVER                 │
│  Port: 5000                              │
│                                          │
│  ┌────────────────────────────────────┐ │
│  │ app.py - Flask Routes              │ │
│  │                                    │ │
│  │ POST /api/qa/ask                   │ │
│  │  └─ Route to QA Service            │ │
│  │                                    │ │
│  │ GET /api/courses                   │ │
│  │ GET /api/courses/{id}/summary      │ │
│  │ GET /health                        │ │
│  │ POST /api/admin/sync-courses       │ │
│  │ POST /api/admin/rebuild-embeddings │ │
│  └────────────────────────────────────┘ │
│                                          │
│  ┌────────────────────────────────────┐ │
│  │ QA Service (qa_service.py)         │ │
│  │                                    │ │
│  │ ├─ Load course data                │ │
│  │ ├─ Call Embedding Service          │ │
│  │ ├─ Extract context                 │ │
│  │ ├─ Generate answer                 │ │
│  │ ├─ Save to database                │ │
│  │ └─ Return JSON response            │ │
│  └─────────────┬──────────┬────────────┘ │
│               │          │                │
│        ┌──────▼─┐   ┌────▼──────────┐    │
│        │         │   │              │    │
│  ┌─────▼──────┬──▼───▼──┐   ┌──────▼───┐│
│  │ Embedding  │ Database│   │   Admin  ││
│  │ Service    │ Service │   │ Commands ││
│  │ (embedding_│(db_     │   │(scripts/ ││
│  │  service.py service. │   │ ├─Sync   ││
│  │           py)        │   │ ├─Train  ││
│  │           │          │   │ └─Migrate││
│  │ - Load    │- Fetch  │   │          ││
│  │  model    │ courses  │   │ Called:  ││
│  │ - Encode  │- Fetch  │   │ python   ││
│  │  question│ resources│   │ scripts/ ││
│  │ - Search  │- Save   │   │ X.py     ││
│  │  similar │ Q&A      │   │          ││
│  │ - Return  │- Get user│  │          ││
│  │  results  │ progress │  │          ││
│  └─────┬─────┴────┬─────┴──┴──────────┘│
│        │          │                     │
│     Embeddings   MySQL                  │
│     Index File   Connection              │
│     (models/)    │                      │
│                  ▼                      │
└──────────────────┼──────────────────────┘
                   │
        ┌──────────▼──────────┐
        │                     │
      ┌─▼──────────────────┐  │
      │  MYSQL DATABASE    │  │
      │  CreaCop           │  │
      │                    │  │
      │  Tables:           │  │
      │  ├─ cours          │  │
      │  ├─ ressource      │  │
      │  ├─ user           │  │
      │  ├─ user_cours_    │  │
      │  │  progress       │  │
      │  └─ course_qa_     │  │◄─── New Table (created automatically)
      │     history        │  │
      │                    │  │
      │  Stores:           │  │
      │  ├─ Course content │  │
      │  ├─ Resources      │  │
      │  └─ Q&A History    │  │
      └────────────────────┘  │
                               │
      ┌────────────────────────┘
      │
      ▼
  ┌──────────────────────┐
  │  Embeddings Index    │
  │  (models/)           │
  │  embeddings_         │
  │  index.pkl           │
  │                      │
  │  Format: Pickle      │
  │  Contains:           │
  │  - Vector embeddings │
  │    (384 dimensions)  │
  │  - Text indices      │
  │  - Metadata          │
  │                      │
  │  Created once,       │
  │  Loaded on startup   │
  └──────────────────────┘
```

## Data Flow Sequence Diagram

```
Timeline: Question Asked to Answer Displayed

T+0s    Student types "What is OOP?"
        │
        ├─ Validates question (not empty)
        ├─ Shows loading spinner
        └─ Creates form data

T+0.1s  JavaScript
        │
        └─ HTTP POST /ai/question
           Payload: {
             question: "What is OOP?",
             course_id: 1,
             user_id: 5
           }

T+0.2s  Symfony Controller (AIAssistantController)
        │
        ├─ Receives POST request
        ├─ Validates question
        ├─ Extracts course_id, user_id
        └─ HTTP POST to Python API

T+0.3s  Python Flask API (app.py)
        │
        ├─ Receives /api/qa/ask request
        ├─ Routes to QA Service
        └─ Starts processing

T+0.5s  QA Service (qa_service.py)
        │
        ├─ Loads course data
        │  └─ From courses_data.json cache
        │
        ├─ Calls Embedding Service
        │  │
        │  ├─ Encodes question to vector
        │  │  └─ Using all-MiniLM-L6-v2 model
        │  │
        │  ├─ Searches embeddings index
        │  │  └─ Finds 5 most similar documents
        │  │
        │  └─ Returns top matches + scores
        │
        ├─ Extracts context from matches
        │
        ├─ Generates answer from context
        │  └─ Using contextual template
        │
        ├─ Database: Saves Q&A interaction
        │  └─ INSERT into course_qa_history
        │
        └─ Returns JSON response
           {
             answer: "OOP is Object-Oriented...",
             sources: [{text: "...", similarity: 0.87}],
             confidence: 0.87
           }

T+0.7s  Symfony Controller
        │
        └─ Returns JSON to JavaScript

T+0.8s  JavaScript
        │
        ├─ Hides loading spinner
        ├─ Parses response
        ├─ Adds AI message to chat
        ├─ Displays answer
        ├─ Shows sources with confidence
        └─ Scrolls chat to bottom

T+1.0s  Browser UI
        │
        ├─ User sees answer
        ├─ User sees sources
        ├─ User sees confidence score
        └─ User can ask another question

✓ Complete flow: ~1 second
```

## Component Interaction Map

```
                    ┌──────────────────┐
                    │     Student      │
                    │    (Browser)     │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │  Course Page     │
                    │  show.html.twig  │
                    │                  │
                    │  AI Widget       │
                    │  + JavaScript    │
                    └────────┬─────────┘
                             │
             ┌───────────────┼─────────────┐
             │               │             │
      ┌──────▼───┐   ┌───────▼──┐  ┌──────▼────┐
      │ Chat UI  │   │ Form     │  │ Status    │
      │ Messages │   │ Handler  │  │ Indicator │
      └────┬─────┘   └────┬─────┘  └───┬──────┘
           │              │            │
           └──────────────┼────────────┘
                          │
                   HTTP POST /ai/question
                          │
      ┌───────────────────▼────────────────┐
      │  AIAssistantController.php         │
      │  Symfony                           │
      │                                    │
      │  ├─ Ask Question                  │
      │  │  └─ Proxy to Python            │
      │  ├─ Get Course Summary             │
      │  │  └─ Course details              │
      │  └─ Health Check                   │
      │     └─ Service status              │
      └────────────┬─────────────────────┘
                   │
            HTTP POST :5000
                   │
      ┌────────────▼─────────────────────┐
      │  Python Flask API                │
      │  app.py                          │
      │                                  │
      ├─ Route requests                  │
      ├─ Validate input                  │
      ├─ Instantiate services            │
      └─ Return JSON responses           │
      │                                  │
      └────┬────────────┬──────────┬─────┘
           │            │          │
      ┌────▼──┐  ┌──────▼───┐ ┌───▼──────┐
      │ QA    │  │Embedding │ │Database  │
      │Service│  │ Service  │ │ Service  │
      │       │  │          │ │          │
      │Load  │  │Encode    │ │MySQL    │
      │coords│  │question  │ │Connect   │
      │Parse │  │Search    │ │Fetch    │
      │ans   │  │vectors   │ │courses   │
      │      │  │Find top  │ │Fetch    │
      │Save  │  │matches   │ │resource │
      │Q&A   │  │Return    │ │Save Q&A │
      │      │  │scores    │ │          │
      └────┬─┘  └──────┬───┘ └───┬──────┘
           │           │         │
           └───────────┼─────────┘
                       │
      ┌────────────────▼─────────────┐
      │  Data & Models Storage       │
      │                              │
      ├─ courses_data.json            │
      │  └─ Course metadata cache    │
      │                              │
      ├─ embeddings_index.pkl         │
      │  └─ Vector embeddings        │
      │     (384-dim vectors)        │
      │                              │
      ├─ MySQL Database              │
      │  ├─ cours (courses)           │
      │  ├─ ressource (materials)     │
      │  └─ course_qa_history (Q&A)  │
      │                              │
      └──────────────────────────────┘
```

## Request/Response Message Format

```
REQUEST from Browser to Symfony:
║
╠═ POST /ai/question
║  ├─ Headers:
║  │  ├─ Content-Type: application/json
║  │  └─ X-Requested-With: XMLHttpRequest
║  │
║  └─ Body:
║     │
║     └─ {
║        "question": "What is machine learning?",
║        "course_id": 1,
║        "user_id": 5
║       }
║
▼

RESPONSE from Symfony to Browser:
║
╠═ 200 OK
║  ├─ Headers:
║  │  └─ Content-Type: application/json
║  │
║  └─ Body:
║     │
║     └─ {
║        "success": true,
║        "answer": "Machine learning is...",
║        "sources": [
║          {
║            "text": "Machine learning is a...",
║            "similarity": 0.87
║          },
║          {
║            "text": "It involves training...",
║            "similarity": 0.82
║          }
║        ],
║        "confidence": 0.87
║       }


REQUEST from Symfony to Python:
║
╠═ POST http://127.0.0.1:5000/api/qa/ask
║  ├─ Headers:
║  │  └─ Content-Type: application/json
║  │
║  └─ Body:
║     │
║     └─ {
║        "question": "What is machine learning?",
║        "course_id": 1,
║        "user_id": 5,
║        "top_k": 5
║       }
║
▼

RESPONSE from Python to Symfony:
║
╠═ 200 OK
║  ├─ Headers:
║  │  └─ Content-Type: application/json
║  │
║  └─ Body:
║     │
║     └─ {
║        "answer": "Machine learning is...",
║        "sources": [...],
║        "confidence": 0.87,
║        "relevant_documents": 3
║       }
```

## File Organization

```
c:\CreaCop\
│
├─ src/Controller/
│  └─ AIAssistantController.php
│     ├─ askQuestion()
│     ├─ getCourseSummary()
│     └─ health()
│
├─ templates/front/course/
│  └─ show.html.twig
│     ├─ AI Assistant Widget (HTML)
│     ├─ JavaScript Handler
│     └─ CSS Animations
│
├─ learning_assistant/  ← Python service
│  │
│  ├─ app.py  ← Main Flask server
│  ├─ config.py  ← Settings
│  │
│  ├─ services/
│  │  ├─ db_service.py      ← MySQL operations
│  │  ├─ embedding_service.py  ← AI embeddings
│  │  └─ qa_service.py        ← Q&A logic
│  │
│  ├─ scripts/
│  │  ├─ migrate_db.py       ← Create tables
│  │  ├─ sync_courses.py     ← Fetch courses
│  │  └─ train_embeddings.py ← Build embeddings
│  │
│  ├─ models/
│  │  └─ embeddings_index.pkl ← Embeddings storage
│  │
│  ├─ data/
│  │  ├─ courses_data.json   ← Course cache
│  │  └─ qa_history.json     ← Q&A history
│  │
│  ├─ requirements.txt  ← Python dependencies
│  ├─ .env.example      ← Config template
│  └─ [Documentation]
│
├─ GET_STARTED.md            ← Quick setup
├─ SETUP_CHECKLIST.md        ← Setup verification
├─ IMPLEMENTATION_SUMMARY.md ← Complete overview
└─ learning_assistant/
   └─ SYMFONY_INTEGRATION.md ← Integration guide
```

## Technology Stack Layers

```
┌─────────────────────────────────────────┐
│     User Interface Layer                │
│  ▲  HTML (Twig templates)               │
│  │  CSS (Tailwind)                     │
│  │  JavaScript (Vanilla)               │
│  │                                      │
│  └─ Course page with AI widget          │
└─────────────────────────────────────────┘
         ↓ HTTP ↑ JSON
┌─────────────────────────────────────────┐
│     Application Layer - Symfony         │
│  ▲  PHP Controllers                     │
│  │  AIAssistantController               │
│  │  Routing                             │
│  │  HttpClient                          │
│  │                                      │
│  └─ Handles requests, proxies to Python │
└─────────────────────────────────────────┘
         ↓ HTTP ↑ JSON
┌─────────────────────────────────────────┐
│     AI Service Layer - Python           │
│  ▲  Flask (REST API)                    │
│  │  QA Service                          │
│  │  Embedding Service                   │
│  │  Database Service                    │
│  │                                      │
│  └─ Processes questions, finds answers  │
└─────────────────────────────────────────┘
           ↓ SQL ↑ Data
┌─────────────────────────────────────────┐
│     Data Layer                          │
│  ▲  MySQL Database                      │
│  │  Embeddings Index File               │
│  │  Course Cache JSON                   │
│  │                                      │
│  └─ Stores all data and embeddings      │
└─────────────────────────────────────────┘
```

---

**This visualization shows the complete system architecture and how all components interact to provide AI-powered learning assistance to students!** 🎓🤖
