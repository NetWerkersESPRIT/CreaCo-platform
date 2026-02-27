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

print(f"\n\nSearching for 'multicam'...")
if 'multicam' in answer:
    idx = answer.find('multicam')
    print(f"Found 'multicam' at position {idx}")
    context = answer[idx:idx+80]
    print(f"Context: {context}")
    
    # Check the bytes
    context_bytes = context.encode('utf-8')
    print(f"\nUTF-8 bytes: {context_bytes}")
    
    # Check for the specific é character
    if '\xc3\xa9'.encode('utf-8') in context_bytes:
        print("Actually found the check")
    
    # Simpler check
    if 'é' in context:
        print("✓ Contains correct é (shown as proper character)")
    elif 'Ã©' in context:
        print("✗ Contains corrupted 'Ã©'")
    else:
        print("? Neither found")
        # Print what we do have
        print(f"Characters in context: {[c for c in context if ord(c) > 127]}")
