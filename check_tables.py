#!/usr/bin/env python
import mysql.connector
import os

# Direct database connection (not using config.py)
try:
    conn = mysql.connector.connect(
        host="127.0.0.1",
        user="root",
        password="",
        database="creaco"
    )
    cursor = conn.cursor()
    
    # Check tables
    cursor.execute("SHOW TABLES")
    tables = cursor.fetchall()
    print("Tables in 'creaco' database:")
    for table in tables:
        print(f"  - {table[0]}")
    
    # Check cours table structure if it exists
    if any(t[0] == 'cours' for t in tables):
        print("\nCours table structure:")
        cursor.execute("DESCRIBE cours")
        cols = cursor.fetchall()
        for col in cols:
            print(f"  - {col[0]}: {col[1]}")
    
    # Check user table structure if it exists
    if any(t[0] == 'user' for t in tables):
        print("\nUser table structure:")
        cursor.execute("DESCRIBE user")
        cols = cursor.fetchall()
        for col in cols:
            print(f"  - {col[0]}: {col[1]}")
    
    cursor.close()
    conn.close()
    print("\n✓ Database connection successful!")
    
except Exception as e:
    print(f"✗ Error: {e}")
