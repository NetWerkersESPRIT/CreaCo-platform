"""
Test Script - Validates Learning Assistant setup
Run this to verify everything is working
"""
import sys
import os
sys.path.insert(0, os.path.dirname(__file__))

import json

def test_imports():
    """Test Python dependencies"""
    print("\n" + "="*60)
    print("Test 1: Python Dependencies")
    print("="*60)
    
    dependencies = {
        'flask': 'Flask',
        'sentence_transformers': 'Sentence Transformers',
        'sklearn': 'scikit-learn',
        'mysql.connector': 'MySQL Connector',
        'numpy': 'NumPy',
        'pandas': 'Pandas',
        'joblib': 'joblib',
    }
    
    all_ok = True
    for module, name in dependencies.items():
        try:
            __import__(module)
            print(f"✓ {name}")
        except ImportError as e:
            print(f"✗ {name} - {e}")
            all_ok = False
    
    return all_ok

def test_database():
    """Test database connection"""
    print("\n" + "="*60)
    print("Test 2: Database Connection")
    print("="*60)
    
    try:
        from services.db_service import DatabaseService
        db = DatabaseService()
        conn = db.get_connection()
        
        if conn:
            print("✓ MySQL connection successful")
            
            # Try to fetch courses
            cursor = conn.cursor(dictionary=True)
            cursor.execute("SELECT COUNT(*) as count FROM cours")
            result = cursor.fetchone()
            course_count = result['count']
            print(f"✓ Database has {course_count} courses")
            
            cursor.close()
            conn.close()
            return True
        else:
            print("✗ Failed to connect to MySQL")
            print("  Check .env file database credentials")
            return False
    except Exception as e:
        print(f"✗ Database error: {e}")
        return False

def test_embeddings():
    """Test embedding service"""
    print("\n" + "="*60)
    print("Test 3: Embedding Service")
    print("="*60)
    
    try:
        from services.embedding_service import EmbeddingService
        embeddings = EmbeddingService()
        
        print(f"✓ Embedding model loaded (all-MiniLM-L6-v2)")
        print(f"✓ Current index has {len(embeddings.texts_index)} documents")
        
        if len(embeddings.texts_index) == 0:
            print("⚠ Index is empty - run 'python scripts/train_embeddings.py'")
            return True  # Not a failure, just not initialized
        else:
            return True
    except Exception as e:
        print(f"✗ Embedding error: {e}")
        return False

def test_qa_service():
    """Test Q&A service"""
    print("\n" + "="*60)
    print("Test 4: Q&A Service")
    print("="*60)
    
    try:
        from services.qa_service import QAService
        qa = QAService()
        
        print(f"✓ Q&A service initialized")
        print(f"✓ Loaded {len(qa.courses_data)} courses")
        
        if len(qa.courses_data) == 0:
            print("⚠ No courses loaded - run 'python scripts/sync_courses.py'")
            return True
        
        # Show first course
        course = qa.courses_data[0]
        print(f"✓ Sample course: {course['titre']}")
        print(f"  Resources: {len(course.get('resources', []))}")
        
        return True
    except Exception as e:
        print(f"✗ Q&A service error: {e}")
        return False

def test_files():
    """Test project files"""
    print("\n" + "="*60)
    print("Test 5: Project Files")
    print("="*60)
    
    files = {
        'app.py': 'Flask API server',
        'config.py': 'Configuration',
        'requirements.txt': 'Dependencies',
        'README.md': 'Documentation',
        'services/db_service.py': 'Database service',
        'services/embedding_service.py': 'Embedding service',
        'services/qa_service.py': 'Q&A service',
        'scripts/sync_courses.py': 'Sync script',
        'scripts/train_embeddings.py': 'Training script',
    }
    
    all_ok = True
    for filepath, desc in files.items():
        full_path = os.path.join(os.path.dirname(__file__), filepath)
        if os.path.exists(full_path):
            print(f"✓ {desc}")
        else:
            print(f"✗ Missing: {filepath}")
            all_ok = False
    
    return all_ok

def main():
    """Run all tests"""
    print("""
    
╔════════════════════════════════════════════════════════════╗
║  Intelligent Learning Assistant - Validation Tests         ║
╚════════════════════════════════════════════════════════════╝
    """)
    
    tests = [
        ("Dependencies", test_imports),
        ("Database", test_database),
        ("Embeddings", test_embeddings),
        ("Q&A Service", test_qa_service),
        ("Files", test_files),
    ]
    
    results = []
    for name, test_func in tests:
        try:
            result = test_func()
            results.append((name, result))
        except Exception as e:
            print(f"\n✗ Test '{name}' crashed: {e}")
            results.append((name, False))
    
    # Summary
    print("\n" + "="*60)
    print("Test Summary")
    print("="*60)
    
    passed = sum(1 for _, result in results if result)
    total = len(results)
    
    for name, result in results:
        status = "✓ PASS" if result else "✗ FAIL"
        print(f"{status}: {name}")
    
    print(f"\nPassed: {passed}/{total}")
    
    if passed == total:
        print("""
╔════════════════════════════════════════════════════════════╗
║  All tests passed! Ready to start the API server.          ║
║                                                            ║
║  Run:  python app.py                                       ║
╚════════════════════════════════════════════════════════════╝
        """)
        return True
    else:
        print("""
╔════════════════════════════════════════════════════════════╗
║  Some tests failed. Follow the instructions above.         ║
║                                                            ║
║  Common issues:                                            ║
║  1. Missing dependencies: pip install -r requirements.txt  ║
║  2. No database connection: Check .env file                ║
║  3. No courses: python scripts/sync_courses.py             ║
║  4. No embeddings: python scripts/train_embeddings.py      ║
╚════════════════════════════════════════════════════════════╝
        """)
        return False

if __name__ == '__main__':
    success = main()
    sys.exit(0 if success else 1)
