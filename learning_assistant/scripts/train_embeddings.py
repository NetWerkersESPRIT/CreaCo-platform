"""
Script to build and train embeddings index from course data
Run after syncing courses
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from services.qa_service import QAService
from config import EMBEDDINGS_MODEL_PATH

def train_embeddings():
    """Build embeddings index from all courses and resources"""
    print("Starting embeddings training...")
    
    qa_service = QAService()
    
    # Check if courses are loaded
    if not qa_service.courses_data:
        print("No courses found! Run sync_courses.py first.")
        return False
    
    print(f"Found {len(qa_service.courses_data)} courses")
    
    # Initialize embeddings from courses
    success = qa_service.initalize_course_embeddings()
    
    if success:
        embeddings_count = len(qa_service.embedding_service.texts_index)
        print(f"✓ Embeddings training completed!")
        print(f"✓ Created embeddings for {embeddings_count} documents")
        print(f"✓ Index saved to {EMBEDDINGS_MODEL_PATH}")
        return True
    else:
        print("Failed to train embeddings")
        return False

if __name__ == '__main__':
    success = train_embeddings()
    sys.exit(0 if success else 1)
