#!/usr/bin/env python3
"""Check what the QA service returns directly"""
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), 'learning_assistant'))

from services.qa_service import QAService

qa_service = QAService()
result = qa_service.answer_question('What is video editing?', course_id=1, top_k=3)

# Check the answer
answer = result['answer']
print("Answer from QA service:")
print(answer[:200])

print(f"\n\nSearching for French text...")
if 'multicam' in answer:
    idx = answer.find('multicam')
    print(f"Found 'multicam' at position {idx}")
    print(f"Context: {answer[idx:idx+50]}")
    
    # Check the bytes
    context = answer[idx:idx+50].encode('utf-8')
    print(f"UTF-8 bytes: {context}")
    
    if b'\xc3\xa9' in context:  # é in UTF-8
        print("✓ Contains correct UTF-8 encoded é")
    elif b'Ã©' in context.decode('utf-8'):
        print("✗ Contains corrupted é")
