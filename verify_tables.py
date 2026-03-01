#!/usr/bin/env python3
import mysql.connector

config = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'creaco',
    'charset': 'utf8mb4',
    'use_unicode': True,
    'autocommit': True
}

conn = mysql.connector.connect(**config)
cursor = conn.cursor()

# Show detailed table structure for key tables
print("=" * 80)
print("TABLE STRUCTURE VERIFICATION")
print("=" * 80)

tables_to_check = ['users', 'post', 'event', 'mission', 'task', 'notification', 'badge', 'user_badge', 'user_streak_day']

for table in tables_to_check:
    try:
        cursor.execute(f"DESC {table}")
        columns = cursor.fetchall()
        print(f"\n✓ {table.upper()} ({len(columns)} columns)")
        for col in columns:
            print(f"    {col[0]:30} {col[1]:40}")
    except mysql.connector.Error as err:
        print(f"✗ {table}: {err}")

cursor.close()
conn.close()
