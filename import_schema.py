#!/usr/bin/env python3
import mysql.connector
import sys
import io

# Database configuration from .env
config = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'database': 'creaco',
    'charset': 'utf8mb4',
    'use_unicode': True,
    'autocommit': True
}

try:
    # Connect to MySQL
    conn = mysql.connector.connect(**config)
    cursor = conn.cursor()
    
    print("✓ Connected to MySQL database 'creaco'")
    
    # Read the files with different encoding attempts
    schemas = []
    for filename in ['schema_update.sql', 'schema_updates.sql']:
        try:
            # Try UTF-8 with BOM removal
            with open(filename, 'r', encoding='utf-8-sig') as f:
                content = f.read()
            schemas.append(content)
            print(f"✓ Read {filename}")
        except UnicodeDecodeError:
            # Fallback to latin-1
            with open(filename, 'r', encoding='latin-1') as f:
                content = f.read()
            schemas.append(content)
            print(f"✓ Read {filename} (latin-1)")
    
    # Combine schemas
    full_schema = '\n'.join(schemas)
    
    # Split by semicolon and execute
    statements = full_schema.split(';')
    
    executed = 0
    failed = 0
    for i, statement in enumerate(statements):
        stmt = statement.strip()
        if stmt and not stmt.startswith('--'):
            try:
                cursor.execute(stmt)
                executed += 1
            except mysql.connector.Error as err:
                failed += 1
                print(f"✗ Error executing statement {i}: {err.msg}")
    
    print(f"✓ Successfully executed {executed} SQL statements ({failed} failed)")
    
    # Verify tables were created
    cursor.execute("SHOW TABLES;")
    tables = cursor.fetchall()
    print(f"✓ Database now contains {len(tables)} tables:")
    for table in tables:
        print(f"  - {table[0]}")
    
    cursor.close()
    conn.close()
    print("\n✓ Schema import completed successfully!")
    
except mysql.connector.Error as err:
    print(f"✗ MySQL Error: {err}")
    sys.exit(1)
except Exception as err:
    print(f"✗ Error: {err}")
    import traceback
    traceback.print_exc()
    sys.exit(1)
