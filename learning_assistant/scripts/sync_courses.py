"""
Script to sync course data from MySQL database
Run this before first use or whenever courses are updated
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from services.db_service import DatabaseService
from services.qa_service import QAService
import json
from config import COURSES_DATA_PATH

def sync_courses():
    """Fetch all courses and resources from database"""
    print("Starting course synchronization...")
    
    db_service = DatabaseService()
    courses = db_service.get_all_courses_and_resources()
    
    if not courses:
        print("No courses found in database!")
        return False
    
    # Save to cache file
    os.makedirs(os.path.dirname(COURSES_DATA_PATH), exist_ok=True)
    with open(COURSES_DATA_PATH, 'w', encoding='utf-8') as f:
        json.dump(courses, f, ensure_ascii=False, indent=2)
    
    print(f"✓ Synced {len(courses)} courses")
    
    # Show summary
    total_resources = sum(len(c.get('resources', [])) for c in courses)
    print(f"✓ Total resources: {total_resources}")
    
    for course in courses[:5]:  # Show first 5
        resources_count = len(course.get('resources', []))
        print(f"  - {course['titre']} ({resources_count} resources)")
    
    if len(courses) > 5:
        print(f"  ... and {len(courses) - 5} more courses")
    
    return True

if __name__ == '__main__':
    success = sync_courses()
    sys.exit(0 if success else 1)
