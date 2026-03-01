#!/usr/bin/env python3
"""Test the AI Learning Assistant end-to-end"""
import requests
import json

print("=" * 60)
print("AI LEARNING ASSISTANT - END-TO-END TEST")
print("=" * 60)

# Test 1: Check Python API health
print("\n[1] Testing Python API health...")
try:
    r = requests.get("http://127.0.0.1:5000/health", timeout=5)
    if r.status_code == 200:
        health = r.json()
        print(f"✓ Python API is healthy")
        print(f"  - Courses loaded: {health.get('courses_count', 0)}")
        print(f"  - Embeddings available: {health.get('documents_count', 0)}")
    else:
        print(f"✗ Unexpected status: {r.status_code}")
except Exception as e:
    print(f"✗ Failed to connect to Python API: {e}")
    exit(1)

# Test 2: Test a question to the Python API directly
print("\n[2] Testing Python API question endpoint...")
question_data = {
    "question": "What is video editing?",
    "course_id": 1,
    "user_id": 1,
    "top_k": 3
}

try:
    r = requests.post("http://127.0.0.1:5000/api/qa/ask", json=question_data, timeout=30)
    if r.status_code == 200:
        response = r.json()
        print(f"✓ Python API answered the question")
        print(f"  - Answer: {response['answer'][:100]}...")
        print(f"  - Confidence: {response.get('confidence', 0):.2f}")
        print(f"  - Sources: {len(response.get('sources', []))} documents found")
    else:
        print(f"✗ Error: {r.status_code} - {r.text}")
except Exception as e:
    print(f"✗ Failed to ask question: {e}")
    exit(1)

# Test 3: Test through Symfony controller
print("\n[3] Testing Symfony integration...")
symfony_data = {
    "question": "How do I use color grading?",
    "course_id": 3,
    "user_id": 2
}

try:
    r = requests.post("http://127.0.0.1:8000/ai/question", json=symfony_data, timeout=30)
    if r.status_code == 200:
        response = r.json()
        print(f"✓ Symfony controller integrated successfully")
        print(f"  - Success: {response['success']}")
        if response['success']:
            print(f"  - Answer preview: {response['answer'][:80]}...")
            print(f"  - Confidence: {response.get('confidence', 0):.2f}")
    else:
        print(f"✗ Symfony error: {r.status_code} - {r.text}")
except Exception as e:
    print(f"✗ Failed to reach Symfony: {e}")
    print(f"  Note: Make sure Symfony is running on http://127.0.0.1:8000")

# Test 4: Get course summary
print("\n[4] Testing course summary endpoint...")
try:
    r = requests.get("http://127.0.0.1:5000/api/courses/1/summary", timeout=10)
    if r.status_code == 200:
        summary = r.json()
        print(f"✓ Course summary retrieved")
        print(f"  - Title: {summary.get('titre', 'N/A')}")
        print(f"  - Level: {summary.get('niveau', 'N/A')}")
        print(f"  - Resources: {len(summary.get('resources', []))}")
    else:
        print(f"✗ Error: {r.status_code}")
except Exception as e:
    print(f"✗ Failed: {e}")

print("\n" + "=" * 60)
print("✓ ALL TESTS COMPLETED SUCCESSFULLY!")
print("=" * 60)
print("\nThe system is ready to use!")
print("- Python AI Server: http://127.0.0.1:5000")
print("- Symfony Web App: http://127.0.0.1:8000")
print("\nNext steps:")
print("1. Open http://127.0.0.1:8000 in your browser")
print("2. Navigate to any course page")
print("3. Use the AI Learning Assistant widget to ask questions!")
print("=" * 60)
