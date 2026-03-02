#!/usr/bin/env python3
"""Check database encoding directly"""
import mysql.connector

# Connect to database
conn = mysql.connector.connect(
    host="127.0.0.1",
    user="root",
    password="",
    database="creaco",
    charset='utf8mb4',
    use_unicode=True
)

cursor = conn.cursor()

# Execute SET NAMES
cursor.execute('SET NAMES utf8mb4')
cursor.execute('SET CHARACTER SET utf8mb4')
cursor.execute('SET character_set_results=utf8mb4')

# Query one resource
cursor.execute("SELECT nom, contenu FROM ressource LIMIT 1")
result = cursor.fetchone()

if result:
    nom = result[0]
    contenu = result[1][:100] if result[1] else ""
    
    print(f"Resource name: {nom}")
    print(f"Type: {type(nom)}")
    print(f"Repr: {repr(nom)}")
    print(f"\nContent preview: {contenu}")
    print(f"Type: {type(contenu)}")
    print(f"Repr: {repr(contenu)}")
    
    #Check if these are correct
    if "é" in nom or "à" in nom or "è" in nom:
        print("\n✓ French characters are correctly encoded!")
    else:
        print("\n✗ French characters are CORRUPTED in the database!")

cursor.close()
conn.close()
