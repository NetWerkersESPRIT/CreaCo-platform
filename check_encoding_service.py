#!/usr/bin/env python3
"""Better check of database encoding"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'learning_assistant'))

from services.db_service import DatabaseService

# Use the actual service
db_service = DatabaseService()
courses = db_service.get_all_courses_and_resources()

if courses:
    print(f"Found {len(courses)} courses\n")
    
    # Check first course
    course = courses[0]
    print(f"Course: {course['titre']}")
    
    # Check resources
    if course['resources']:
        res = course['resources'][0]
        if res:
            print(f"First resource title: {res['titre']}")
            print(f"Contenu: {res['contenu'][:100]}")
            
            # Check for accents
            if 'é' in res['titre'] or 'à' in res['titre'] or 'è' in res['titre']:
                print("\n✓ French accents are CORRECT!")
            elif 'Ã©' in res['titre'] or 'Ã ' in res['titre']:
                print("\n✗ French accents are CORRUPTED (showing as Ã©, Ã , etc.)")
                print("\nThis is a UTF-8 encoding issue in the database or MySQL connection.")
            else:
                print("\n? No French accents detected in resource title")
