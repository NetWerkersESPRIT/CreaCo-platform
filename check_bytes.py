#!/usr/bin/env python3
"""Check bytes of the returned text"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'learning_assistant'))

from services.db_service import DatabaseService

db_service = DatabaseService()
courses = db_service.get_all_courses_and_resources()

if courses:
    res = courses[0]['resources'][0]
    title = res['titre']
    
    print(f"Title: {title}")
    print(f"\nBytes: {title.encode('utf-8')}")
    print(f"\nChecking each character:")
    for i, char in enumerate(title):
        if ord(char) > 127:  # Non-ASCII
            print(f"  Position {i}: {char} (U+{ord(char):04X})")
