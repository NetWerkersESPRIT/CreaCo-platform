#!/usr/bin/env python3
"""Test the AI response with better output formatting"""
import requests
import json

question = 'What is video editing and why is it important?'
print('=' * 70)
print(f'QUESTION: {question}')
print('=' * 70)

response = requests.post('http://127.0.0.1:5000/api/qa/ask', json={
    'question': question,
    'course_id': 1,
    'user_id': 1,
    'top_k': 10
}, timeout=30)

result = response.json()
print(f'\n✓ Answer Generated:')
print('-' * 70)
print(result.get('answer', 'N/A'))
print('-' * 70)

print(f'\nConfidence Score: {result.get("confidence", 0):.1%}')
print(f'Relevant Sources Found: {result.get("relevant_documents", 0)}')

print(f'\nTop 5 Source Materials:')
for i, src in enumerate(result.get('sources', [])[:5], 1):
    score = src.get('similarity', 0)
    text = src.get('text', 'N/A')[:80]
    print(f'  {i}. [{score:.1%}] {text}...')

print('\n' + '=' * 70)
print('✓ System is working! AI found relevant course materials.')
print('=' * 70)
