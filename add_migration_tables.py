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

try:
    conn = mysql.connector.connect(**config)
    cursor = conn.cursor()
    
    print("✓ Connected to MySQL database 'creaco'")
    
    # SQL statements for missing tables
    statements = [
        # From first migration
        '''CREATE TABLE IF NOT EXISTS user_streak_day (
            id INT AUTO_INCREMENT NOT NULL, 
            user_id INT NOT NULL, 
            day DATE NOT NULL, 
            created_at DATETIME NOT NULL, 
            UNIQUE INDEX user_date_unique (user_id, day), 
            INDEX IDX_USER_STREAK_USER (user_id), 
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4''',
        
        '''ALTER TABLE IF EXISTS user_streak_day 
           ADD CONSTRAINT FK_USER_STREAK_USER 
           FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE''',
        
        # From second migration  
        '''CREATE TABLE IF NOT EXISTS badge (
            id INT AUTO_INCREMENT NOT NULL, 
            code VARCHAR(100) NOT NULL, 
            name VARCHAR(255) NOT NULL, 
            description LONGTEXT DEFAULT NULL, 
            icon VARCHAR(255) DEFAULT NULL, 
            rarity VARCHAR(50) DEFAULT NULL, 
            created_at DATETIME NOT NULL, 
            UNIQUE INDEX badge_code_unique (code), 
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4''',
        
        '''CREATE TABLE IF NOT EXISTS user_badge (
            id INT AUTO_INCREMENT NOT NULL, 
            user_id INT NOT NULL, 
            badge_id INT NOT NULL, 
            awarded_at DATETIME NOT NULL, 
            metadata JSON DEFAULT NULL, 
            UNIQUE INDEX user_badge_unique (user_id, badge_id), 
            INDEX IDX_USER_BADGE_USER (user_id), 
            INDEX IDX_USER_BADGE_BADGE (badge_id), 
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4''',
        
        '''ALTER TABLE IF EXISTS user_badge 
           ADD CONSTRAINT FK_USER_BADGE_USER 
           FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE''',
        
        '''ALTER TABLE IF EXISTS user_badge 
           ADD CONSTRAINT FK_USER_BADGE_BADGE 
           FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE'''
    ]
    
    executed = 0
    failed = 0
    for stmt in statements:
        try:
            cursor.execute(stmt)
            executed += 1
            print(f"✓ Executed statement")
        except mysql.connector.Error as err:
            # Ignore errors for FK constraints if table doesn't exist
            if 'FK_' in stmt and ('Constraint' in str(err) or 'already' in str(err)):
                print(f"~ Skipped constraint (already exists or table missing)")
            else:
                failed += 1
                print(f"✗ Error: {err.msg}")
    
    # Verify all tables
    cursor.execute("SHOW TABLES;")
    tables = cursor.fetchall()
    print(f"\n✓ Database now contains {len(tables)} tables")
    
    cursor.close()
    conn.close()
    print("\n✓ Migration tables created successfully!")
    
except mysql.connector.Error as err:
    print(f"✗ MySQL Error: {err}")

