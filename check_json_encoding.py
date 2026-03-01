#!/usr/bin/env python3
"""Check the raw JSON response to see actual encoding"""
import requests
import json

question = 'What is video editing and why is it important?'

response = requests.post('http://127.0.0.1:5000/api/qa/ask', json={
    'question': question,
    'course_id': 1,
    'user_id': 1,
    'top_k': 5
}, timeout=30)

# Get raw JSON
result = response.json()

# Save to file to inspect
with open('response.json', 'w', encoding='utf-8') as f:
    json.dump(result, f, ensure_ascii=False, indent=2)

print("Response saved to response.json")
print("\nFirst 500 chars of answer:")
print(result['answer'][:500])
print("\nChecking response headers:")
print(f"Content-Type: {response.headers.get('Content-Type', 'N/A')}")
print(f"Charset in header: {'utf-8' if 'utf-8' in response.headers.get('Content-Type', '').lower() else 'NOT SET'}")
