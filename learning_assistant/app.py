"""
Flask API Server for Intelligent Learning Assistant
Provides endpoints for Q&A, course information, and interaction logging
"""
from flask import Flask, request, jsonify
from flask_cors import CORS
from services.qa_service import QAService
from config import FLASK_HOST, FLASK_PORT, DEBUG
import traceback

app = Flask(__name__)
CORS(app)

# Initialize Q&A service
qa_service = QAService()

# ========================
# Health Check Endpoint
# ========================
@app.route('/health', methods=['GET'])
def health_check():
    """Check if the API server is running"""
    return jsonify({
        'status': 'ok',
        'service': 'Intelligent Learning Assistant',
        'embeddings_loaded': len(qa_service.embedding_service.texts_index) > 0,
        'documents_count': len(qa_service.embedding_service.texts_index)
    }), 200

# ========================
# Q&A Endpoints
# ========================
@app.route('/api/qa/ask', methods=['POST'])
def ask_question():
    """
    Ask a question about course content
    Request: {question: str, course_id: int (optional), top_k: int (optional)}
    Response: {answer: str, sources: list, confidence: float}
    """
    try:
        data = request.get_json()
        question = data.get('question')
        course_id = data.get('course_id')
        top_k = data.get('top_k', 5)
        user_id = data.get('user_id')
        
        if not question:
            return jsonify({'error': 'Question is required'}), 400
        
        if len(question) < 3:
            return jsonify({'error': 'Question must be at least 3 characters'}), 400
        
        # Get answer from Q&A service
        response = qa_service.answer_question(question, course_id, top_k)
        
        # Save interaction (optional)
        if course_id:
            qa_service.save_interaction(course_id, question, response['answer'], user_id)
        
        return jsonify(response), 200
    
    except Exception as e:
        print(f"Error in ask_question: {e}")
        print(traceback.format_exc())
        return jsonify({
            'error': 'Internal server error',
            'message': str(e)
        }), 500

# ========================
# Course Information Endpoints
# ========================
@app.route('/api/courses/<int:course_id>/summary', methods=['GET'])
def get_course_summary(course_id):
    """Get course information and resources summary"""
    try:
        summary = qa_service.get_course_summary(course_id)
        
        if not summary:
            return jsonify({'error': 'Course not found'}), 404
        
        return jsonify(summary), 200
    
    except Exception as e:
        print(f"Error in get_course_summary: {e}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/courses', methods=['GET'])
def list_courses():
    """List all courses with embeddings loaded"""
    try:
        courses = [
            {
                'id': c['id'],
                'title': c['titre'],
                'level': c['niveau'],
                'category': c.get('categorie'),
                'resources_count': len(c.get('resources', []))
            }
            for c in qa_service.courses_data
        ]
        
        return jsonify({
            'total_courses': len(courses),
            'courses': courses
        }), 200
    
    except Exception as e:
        print(f"Error in list_courses: {e}")
        return jsonify({'error': str(e)}), 500

# ========================
# Admin/Management Endpoints
# ========================
@app.route('/api/admin/sync-courses', methods=['POST'])
def sync_courses():
    """Fetch latest courses from database and rebuild embeddings"""
    try:
        # Fetch courses from database
        courses = qa_service.db_service.get_all_courses_and_resources()
        
        if not courses:
            return jsonify({'error': 'No courses found'}), 404
        
        # Update cached data
        import json
        from config import COURSES_DATA_PATH
        with open(COURSES_DATA_PATH, 'w') as f:
            json.dump(courses, f)
        
        qa_service.courses_data = courses
        
        return jsonify({
            'message': 'Courses synced successfully',
            'courses_count': len(courses)
        }), 200
    
    except Exception as e:
        print(f"Error in sync_courses: {e}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/admin/rebuild-embeddings', methods=['POST'])
def rebuild_embeddings():
    """Clear and rebuild the embeddings index"""
    try:
        # Clear existing index
        qa_service.embedding_service.clear_index()
        
        # Rebuild from courses data
        success = qa_service.initalize_course_embeddings()
        
        if success:
            return jsonify({
                'message': 'Embeddings rebuilt successfully',
                'documents_count': len(qa_service.embedding_service.texts_index)
            }), 200
        else:
            return jsonify({'error': 'No courses to embed'}), 400
    
    except Exception as e:
        print(f"Error in rebuild_embeddings: {e}")
        return jsonify({'error': str(e)}), 500

@app.route('/api/admin/status', methods=['GET'])
def admin_status():
    """Get current service status"""
    return jsonify({
        'status': 'running',
        'models_loaded': True,
        'courses_loaded': len(qa_service.courses_data),
        'embeddings_count': len(qa_service.embedding_service.texts_index),
        'model_name': 'all-MiniLM-L6-v2'
    }), 200

# ========================
# Error Handlers
# ========================
@app.errorhandler(404)
def not_found(e):
    return jsonify({'error': 'Endpoint not found'}), 404

@app.errorhandler(405)
def method_not_allowed(e):
    return jsonify({'error': 'Method not allowed'}), 405

@app.errorhandler(500)
def internal_error(e):
    return jsonify({'error': 'Internal server error'}), 500

# ========================
# Main Entry Point
# ========================
if __name__ == '__main__':
    print("Starting Intelligent Learning Assistant...")
    print(f"Loaded {len(qa_service.courses_data)} courses")
    print(f"Embeddings index has {len(qa_service.embedding_service.texts_index)} documents")
    
    app.run(
        host=FLASK_HOST,
        port=FLASK_PORT,
        debug=DEBUG
    )
