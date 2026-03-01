# Intelligent Learning Assistant - Symfony Integration Guide

## Overview

The AI Learning Assistant is now fully integrated with your Symfony application. Students can ask questions about course content and get instant AI-powered answers directly from the course page.

## Architecture

```
Student Questions
       ↓
Symfony Controller (AIAssistantController.php)
       ↓
HTTP Request
       ↓
Python Flask API (learning_assistant/app.py)
       ↓
AI Processing (Embeddings + Semantic Search)
       ↓
Database (Course Content)
       ↓
Response with Answer + Sources
       ↓
Symfony Controller returns JSON
       ↓
JavaScript Updates Chat UI
```

## Files Created/Modified

### New Symfony Files
- `src/Controller/AIAssistantController.php` - Proxy controller for AI requests

### Modified Symfony Files
- `templates/front/course/show.html.twig` - Added AI Assistant widget and JavaScript

## Setup & Configuration

### Step 1: Set Up Python Virtual Environment (if not done)

```bash
cd c:\CreaCop\learning_assistant
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### Step 2: Configure Database Connection

Create or update `.env` in `learning_assistant/` folder:

```env
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=creadb
DB_PORT=3306
FLASK_HOST=127.0.0.1
FLASK_PORT=5000
FLASK_DEBUG=True
```

Make sure this matches your MySQL credentials in Symfony's `.env`.

### Step 3: Initialize AI Service

Run these commands in the `learning_assistant/` directory:

```bash
# Create database table
python scripts/migrate_db.py

# Sync courses from database
python scripts/sync_courses.py

# Build embeddings index (takes 1-2 minutes first time)
python scripts/train_embeddings.py
```

### Step 4: Start Python API Server

In a **separate terminal**, run:

```bash
cd c:\CreaCop\learning_assistant
venv\Scripts\activate
python app.py
```

You should see:
```
Starting Intelligent Learning Assistant...
Loaded 12 courses
Embeddings index has 57 documents
 * Running on http://127.0.0.1:5000
```

### Step 5: Start Symfony Server

In another terminal (keeping the Python server running):

```bash
cd c:\CreaCop
symfony serve
```

## Testing the Integration

1. Open your browser: `http://127.0.0.1:8000`
2. Log in / Navigate to a course
3. Scroll down to the **"AI Learning Assistant"** section
4. Ask a question like: "What is machine learning?"
5. The AI will respond with an answer based on course materials

## How It Works

### Frontend Flow (JavaScript)

1. Student enters question in the form
2. JavaScript sends POST request to `/ai/question` endpoint
3. Loading spinner shows while AI processes
4. Response received and displayed in chat
5. Sources shown with confidence scores

### Backend Flow (Symfony Controller)

1. `AIAssistantController::askQuestion()` receives the request
2. Validates the question
3. Calls Python API at `http://127.0.0.1:5000/api/qa/ask`
4. Returns JSON response to frontend

### AI Service (Python)

1. Receives question
2. Converts to vector embedding
3. Searches embeddings index for similar course content
4. Extracts top 5 matches
5. Generates answer from matched content
6. Returns with sources and confidence

## API Endpoints

### Symfony Endpoints

```
POST   /ai/question              → Ask question about course
GET    /ai/course/{id}/summary   → Get course AI summary  
GET    /ai/health                → Check AI service status
```

### Python Endpoints (for advanced use)

```
POST   /api/qa/ask               → Ask question
GET    /api/courses              → List courses
GET    /health                   → Health check
```

## Troubleshooting

### "AI Service is Offline" Message

**Problem**: Red status indicator shows AI is not available

**Solutions:**
1. Check Python server is running:
   ```bash
   curl http://127.0.0.1:5000/health
   ```

2. If not running, start it:
   ```bash
   cd learning_assistant
   venv\Scripts\activate
   python app.py
   ```

3. Check firewall isn't blocking port 5000

### "Failed to get answer from AI service"

**Problem**: Error when submitting questions

**Solutions:**
1. Check database connection in `.env` (MySQL credentials)
2. Verify courses are synced:
   ```bash
   python scripts/sync_courses.py
   ```
3. Check embeddings are built:
   ```bash
   python scripts/train_embeddings.py
   ```
4. Check Python server logs for details

### No courses appear

**Problem**: AI says "No content found" or similar

**Solutions:**
1. Make sure you have courses in the database
2. Sync fresh data:
   ```bash
   python scripts/sync_courses.py
   python scripts/train_embeddings.py
   ```
3. Restart Python server

### Slow responses

**Problem**: Takes 3+ seconds to get answers

**Solutions:**
- This is normal on first question (model load)
- Subsequent questions should be 200-500ms
- If very slow, check if courses/embeddings are too large
- Can reduce `top_k` in AIAssistantController (currently 5)

## Configuration Options

### Adjust AI Response Quality

In `learning_assistant/config.py`:

```python
MAX_CONTEXT_LENGTH = 5      # More sources = better answers but slower
SIMILARITY_THRESHOLD = 0.5  # Lower = broader results
```

### Change API Port

If port 5000 is in use, edit `learning_assistant/.env`:

```env
FLASK_PORT=5001  # Change to any available port
```

Then update `AIAssistantController.php`:

```php
private $pythonApiUrl = 'http://127.0.0.1:5001';  # Match the port
```

### Disable AI Assistant Temporarily

Remove or comment out the AI Assistant section in `templates/front/course/show.html.twig` (lines 107-154).

## Monitoring & Logs

### Check AI Service Status

```bash
curl http://127.0.0.1:5000/api/admin/status
```

### View Q&A Interactions

In MySQL:

```sql
SELECT * FROM course_qa_history 
ORDER BY created_at DESC 
LIMIT 10;
```

### Check Python Server Logs

The terminal running `python app.py` shows all API requests:

```
127.0.0.1 - - [27/Feb/2026 14:23:45] "POST /api/qa/ask HTTP/1.1" 200 -
127.0.0.1 - - [27/Feb/2026 14:23:47] "GET /health HTTP/1.1" 200 -
```

## Performance Tips

1. **First Setup**: Building embeddings takes 1-2 minutes. This is one-time.

2. **Response Time**:
   - First question: ~2 seconds (models load)
   - Subsequent: ~200-500ms (normal)

3. **Memory Usage**: ~500MB for Python service (normal, scales with course size)

4. **Database**: Minimal impact - only reads for syncing, occasional writes to Q&A history

## Future Enhancements

- [ ] Fine-tune embeddings on course vocabulary
- [ ] Add conversation history (context-aware answers)
- [ ] Implement answer rating/feedback
- [ ] Admin dashboard for Q&A analytics
- [ ] Multi-language support
- [ ] Save favorite Q&A pairs
- [ ] Integrate with Gemini API for even better answers

## Deployment (Production)

For production deployment:

1. **Python API**: Use WSGI server (Gunicorn):
   ```bash
   pip install gunicorn
   gunicorn -w 4 -b 0.0.0.0:5000 app:app
   ```

2. **Symfony**: Use standard Symfony deployment
   ```bash
   symfony deploy
   ```

3. **Security**: 
   - Keep Python API on internal network only
   - Don't expose to internet directly
   - Use environment variables for secrets
   - Enable CORS only for your domain

4. **Scaling**:
   - Use multiple worker processes
   - Cache frequently asked questions
   - Consider updating embeddings monthly

## Database Migration (if needed)

If the `course_qa_history` table doesn't exist, create it:

```bash
python learning_assistant/scripts/migrate_db.py
```

Or manually in MySQL:

```sql
CREATE TABLE course_qa_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    question LONGTEXT NOT NULL,
    answer LONGTEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES cours(id),
    FOREIGN KEY (user_id) REFERENCES user(id)
);
```

## Support & Documentation

- **Python Setup**: See `learning_assistant/INSTALL.md`
- **API Docs**: See `learning_assistant/README.md`
- **Technical Details**: See `learning_assistant/PROJECT_SUMMARY.md`
- **Quick Reference**: See `learning_assistant/QUICKSTART.md`

## Summary

You now have a fully functional AI Learning Assistant integrated with:

✅ Symfony controllers to handle requests  
✅ Beautiful UI widget in course pages  
✅ Real-time Q&A chat interface  
✅ Source attribution with confidence scores  
✅ Database integration for history  
✅ Error handling and loading states  

**To use it, simply:**
1. Start Python API: `python app.py` (in learning_assistant folder)
2. Start Symfony: `symfony serve`
3. Go to any course page
4. Ask a question!

Enjoy your AI Learning Assistant! 🚀
