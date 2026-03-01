# ✅ AI Learning Assistant - Setup Checklist

Use this checklist to ensure everything is properly configured before using the AI Learning Assistant.

## 🔧 Pre-Setup Requirements

- [ ] Python 3.9+ installed (`python --version`)
- [ ] MySQL running with CreaCop database
- [ ] Symfony project working (`symfony serve` works)
- [ ] 2-3 GB free disk space
- [ ] 2+ cores processor
- [ ] 2+ GB RAM available

## 📥 Installation Steps

### Step 1: Python Environment
- [ ] Navigate to: `cd c:\CreaCop\learning_assistant`
- [ ] Create venv: `python -m venv venv`
- [ ] Activate venv: `venv\Scripts\activate`
- [ ] See `(venv)` prefix in terminal
- [ ] Upgrade pip: `python -m pip install --upgrade pip`
- [ ] Install deps: `pip install -r requirements.txt`
- [ ] Wait for downloads (2-3 minutes)

### Step 2: Configuration
- [ ] Copy template: `copy .env.example .env`
- [ ] Edit .env with MySQL credentials from Symfony
  - [ ] DB_HOST (usually localhost)
  - [ ] DB_USER (usually root)
  - [ ] DB_PASSWORD
  - [ ] DB_NAME (usually creadb)
  - [ ] DB_PORT (usually 3306)

### Step 3: Database Setup
- [ ] MySQL is running
- [ ] Can connect: `python scripts/migrate_db.py`
- [ ] See checkmarks: `✓ Table created successfully!`

### Step 4: Data Sync
- [ ] Sync courses: `python scripts/sync_courses.py`
- [ ] See output: `✓ Synced 12 courses` (count may differ)
- [ ] Verify data: `✓ Total resources: XX`
- [ ] Build embeddings: `python scripts/train_embeddings.py`
- [ ] Wait 1-2 minutes for embeddings build
- [ ] See: `✓ Created embeddings for XX documents`

### Step 5: Test Setup (Optional but Recommended)
- [ ] Run tests: `python test.py`
- [ ] All 5 tests show ✓ PASS
- [ ] Dependencies OK
- [ ] Database OK
- [ ] Embeddings OK
- [ ] Q&A Service OK
- [ ] Files OK

## 🚀 Starting the Services

### Terminal 1: Python API
- [ ] In `learning_assistant` folder, activate: `venv\Scripts\activate`
- [ ] Start server: `python app.py`
- [ ] See: `Starting Intelligent Learning Assistant...`
- [ ] See: `Loaded X courses`
- [ ] See: `Embeddings index has X documents`
- [ ] See: `Running on http://127.0.0.1:5000`
- [ ] **Keep this terminal open!**

### Terminal 2: Symfony
- [ ] In `CreaCop` folder
- [ ] Start: `symfony serve`
- [ ] See: `Settings file:` and URL
- [ ] **Keep this terminal open!**

## ✅ Verification

### Check Python API
- [ ] Open new terminal/browser
- [ ] Run: `curl http://127.0.0.1:5000/health`
- [ ] See JSON response with status "ok"
- [ ] See documents_count > 0

### Check Symfony Controller
- [ ] In Symfony terminal, see no errors
- [ ] No "AIAssistantController not found" errors
- [ ] Routes are loaded

### Check Database
- [ ] MySQL shows `course_qa_history` table exists
- [ ] Run: `SELECT COUNT(*) FROM course_qa_history;`
- [ ] No errors (table may be empty)

## 🎯 Testing the UI

### Test in Browser
- [ ] Open: `http://127.0.0.1:8000` (or your Symfony URL)
- [ ] Log in with your account
- [ ] Navigate to any course
- [ ] Scroll to find "AI Learning Assistant" section
- [ ] See chat widget with initial message
- [ ] See "Ask a question" input field
- [ ] Check status indicator (green, not red)

### Test Asking a Question
- [ ] Type: "What is this course about?"
- [ ] Click "Ask" button
- [ ] See loading spinner appear
- [ ] Wait 1-3 seconds
- [ ] See AI response in chat
- [ ] See sources with confidence score
- [ ] No error messages

### Test Error Handling
- [ ] Stop Python server (press Ctrl+C)
- [ ] Try to ask another question
- [ ] See error: "AI service is offline"
- [ ] Restart Python server: `python app.py`
- [ ] Verify it works again

## 🐛 Common Issues & Fixes

### "MySQL Error: Cannot connect"
- [ ] Verify MySQL is running
- [ ] Check credentials in .env match Symfony's .env
- [ ] Test connection: `python scripts/sync_courses.py`

### "AI Service Offline" in UI
- [ ] Check Python server is running
- [ ] Run: `curl http://127.0.0.1:5000/health`
- [ ] If failed, restart Python server
- [ ] Check port 5000 isn't in use

### "No courses found"
- [ ] Run sync again: `python scripts/sync_courses.py`
- [ ] Check MySQL has courses with status='published'
- [ ] Rebuild embeddings: `python scripts/train_embeddings.py`

### Slow responses (3+ seconds)
- [ ] First question: normal (model loads ~2s)
- [ ] Subsequent: should be 200-500ms
- [ ] If consistently slow, check CPU/RAM usage

### JavaScript errors in browser console
- [ ] Open DevTools (F12)
- [ ] Check Console tab for errors
- [ ] Common: CORS issue → Python server must be running
- [ ] Try hard refresh: Ctrl+Shift+R

## 📊 Performance Benchmarks

You should see these times (after first run):

| Operation | Expected Time |
|-----------|----------------|
| Ask a question | 200-500ms |
| Load page | 1-2 seconds |
| Python startup | 2-3 seconds |
| Build embeddings (once) | 1-2 minutes |
| Get AI response | 200-500ms |

## 🔐 Security Checklist

- [ ] Python API only accessible on localhost (127.0.0.1)
- [ ] Database credentials in .env (not committed to git)
- [ ] No sensitive data in logs/console
- [ ] Questions stored in database (safe)
- [ ] User IDs tied to questions (privacy)

## 📈 What's Working?

- [ ] Python service starts without errors
- [ ] Symfony sees the controller
- [ ] Course page shows AI widget
- [ ] Questions submit and get responses
- [ ] Sources display with confidence
- [ ] Database logs interactions
- [ ] Error handling works
- [ ] Service health check works

## 🎓 Next Steps

Once everything checks out:

1. **For Development**
   - Monitor database: `SELECT * FROM course_qa_history;`
   - Check logs in Python terminal
   - Test with various questions

2. **For Production** (Future)
   - Use WSGI server (Gunicorn)
   - Set up proper logging
   - Add rate limiting
   - Configure monitoring

3. **Enhancements** (Optional)
   - Fine-tune embeddings on your vocabulary
   - Add Gemini API for better answers
   - Create admin dashboard for analytics
   - Add answer rating system

## 📞 Troubleshooting Resources

If something doesn't work:

1. **Check Documentation**
   - [ ] Read GET_STARTED.md (quick overview)
   - [ ] Read INSTALL.md (installation details)
   - [ ] Read PYTORCH_INTEGRATION.md (integration details)
   - [ ] Read README.md (API reference)

2. **Check Logs**
   - [ ] Python terminal for errors
   - [ ] Symfony terminal for errors
   - [ ] Browser console (F12) for JS errors
   - [ ] MySQL for query errors

3. **Run Diagnostics**
   - [ ] `python test.py` (all components)
   - [ ] `curl http://127.0.0.1:5000/health` (Python API)
   - [ ] Check database table exists

4. **Restart Everything**
   - [ ] Stop Python (Ctrl+C)
   - [ ] Stop Symfony (Ctrl+C)
   - [ ] Kill any lingering processes
   - [ ] Start fresh

## ✨ Success Indicators

You'll know everything is working when:

✅ Python server starts with "Loaded X courses"  
✅ Browser shows green "AI Ready" indicator  
✅ Questions get responses in 200-500ms  
✅ Answers include relevant sources  
✅ No errors in any console  
✅ Database stores Q&A history  
✅ Mobile interface is responsive  
✅ Different courses work correctly  

## 🎉 Ready to Go!

Once you've checked all boxes, your AI Learning Assistant is ready for production use!

**Students can now:**
- Ask questions instantly
- Get answers from course materials
- See relevant sources
- Learn more effectively

**Teachers can:**
- Monitor what students ask
- Improve confusing materials
- Track engagement
- Optimize courses

---

## Final Verification Checklist

**Today's Date**: _______________

**Checklist Completed**: ☐ Yes ☐ No

**All Tests Pass**: ☐ Yes ☐ No

**Ready to Deploy**: ☐ Yes ☐ No

**Notes**:
_________________________________
_________________________________

---

**Congratulations!** 🎉 Your AI Learning Assistant is ready to transform your course learning experience!
