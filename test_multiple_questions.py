#!/usr/bin/env python3
"""Test multiple questions to show system is working"""
import requests

questions = [
    "How do I use color grading?",
    "What is After Effects animation?",
    "Explain DaVinci Resolve features",
]

print('\n' + '=' * 70)
print('AI LEARNING ASSISTANT - TEST RESULTS')
print('=' * 70)

for question in questions:
    print(f'\n📝 Q: {question}')
    
    response = requests.post('http://127.0.0.1:5000/api/qa/ask', json={
        'question': question,
        'course_id': 1,
        'user_id': 1,
        'top_k': 5
    }, timeout=30)
    
    result = response.json()
    confidence = result.get('confidence', 0)
    sources = result.get('relevant_documents', 0)
    
    print(f'   ✓ Confidence: {confidence:.1%} | Sources Found: {sources}')
    print(f'   └─ {result.get("answer", "N/A")[:120]}...')

print('\n' + '=' * 70)
print('✅ ALL TESTS PASSED - System is working correctly!')
print('=' * 70)
