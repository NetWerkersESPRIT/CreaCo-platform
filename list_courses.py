#!/usr/bin/env python3
"""Check what courses are in the cache"""
import json

with open('learning_assistant/data/courses_data.json', 'r', encoding='utf-8') as f:
    courses = json.load(f)

print(f'Total courses: {len(courses)}\n')
print('Courses and their resources:')
for course in courses:
    titre = course.get('titre', 'N/A')
    desc = course.get('description', 'N/A')[:50]
    resources = course.get('resources', [])
    print(f'\n✓ {titre}')
    print(f'  Description: {desc}...')
    print(f'  Resources: {len(resources)}')
    if resources:
        for j, res in enumerate(resources[:2], 1):
            res_name = res.get('titre', 'N/A')
            print(f'    {j}. {res_name}')
