#!/usr/bin/env python3
"""Debug the AI response to video editing question"""
import requests
import json

question = 'What is video editing and why is it important?'
print(f'Testing question: {question}\n')

# Direct call to Python API with higher top_k to see what's matching
response = requests.post('http://127.0.0.1:5000/api/qa/ask', json={
    'question': question,
    'course_id': 1,
    'user_id': 1,
    'top_k': 10
}, timeout=30)

result = response.json()
print(f'Status: {response.status_code}')
print(f'Answer: {result.get("answer", "N/A")[:250]}')
print(f'\nConfidence: {result.get("confidence", 0):.2f}')
print(f'Sources found: {len(result.get("sources", []))}')

if result.get('sources'):
    print(f'\nTop sources:')
    for i, src in enumerate(result.get('sources', [])[:5], 1):
        score = src.get('score', 0)
        text = src.get('text', 'N/A')[:100]
        print(f'  {i}. Score {score:.2f}: {text}...')
else:
    print('\nNo sources found - checking embeddings...')
    # Check what's in the database
    health = requests.get('http://127.0.0.1:5000/health').json()
    print(f'Health check: {health}')
