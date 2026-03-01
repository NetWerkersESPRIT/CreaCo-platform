#!/usr/bin/env python3
"""Better check of database encoding"""
import mysql.connector
from learning_assistant.services.db_service import DatabaseService

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
                print("✓ French accents are CORRECT!")
            elif 'Ã©' in res['titre'] or 'Ã ' in res['titre']:
                print("✗ French accents are CORRUPTED (showing as Ã©, à, etc.)")
            else:
                print("? No French accents in this resource")
