#!/usr/bin/env python3
"""Test the actual response encoding"""
import requests
import json

response = requests.post('http://127.0.0.1:5000/api/qa/ask', json={
    'question': 'What is video editing?',
    'course_id': 1,
    'user_id': 1,
    'top_k': 3
}, timeout=30)

print(f"Response encoding (from Content-Type): {response.encoding}")
print(f"Actual response text encoding: {response.apparent_encoding}")
print(f"Headers: {dict(response.headers)}")

result = response.json()
answer = result['answer']

print(f"\nAnswer text:")
print(answer[:200])

print(f"\nLooking for correct 'vidéo' (should be: vidéo with é):")
if 'vidéo' in answer:
    print("✓ FOUND CORRECT 'vidéo'")
elif 'vidÃ©o' in answer or 'vidÃ©o' in answer:
    print("✗ Found CORRUPTED 'vidÃ©o' - double encoding detected")
else:
    print("? Pattern not found")
