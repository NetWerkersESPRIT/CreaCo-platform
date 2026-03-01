#!/usr/bin/env python3
"""Check bytes of the course title"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'learning_assistant'))

from services.db_service import DatabaseService

db_service = DatabaseService()
courses = db_service.get_all_courses_and_resources()

if courses:
    course = courses[0]
    title = course['titre']
    desc = course['description'][:100]
    
    print(f"Course Title: {title}")
    print(f"Bytes: {title.encode('utf-8')}")
    print(f"\nChecking characters in title:")
    for i, char in enumerate(title):
        if ord(char) > 127:  # Non-ASCII
            print(f"  Position {i}: {char} (U+{ord(char):04X} - {name_unicode(ord(char))})")
    
    print(f"\n\nDescription: {desc}...")
    print(f"Bytes: {desc.encode('utf-8')}")

def name_unicode(codepoint):
    if codepoint == 0xE9:
        return "LATIN SMALL LETTER E WITH ACUTE"
    elif codepoint == 0xC3:
        return "LATIN CAPITAL LETTER A WITH TILDE (part of double-encoded)"
    return "OTHER"
