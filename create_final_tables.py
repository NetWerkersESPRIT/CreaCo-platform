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

# Drop tables that might exist with wrong indexes
try:
    cursor.execute("DROP TABLE IF EXISTS user_badge")
    cursor.execute("DROP TABLE IF EXISTS badge")
    cursor.execute("DROP TABLE IF EXISTS user_streak_day")
except:
    pass

# Create badge table
cursor.execute("""
CREATE TABLE IF NOT EXISTS badge (
    id INT AUTO_INCREMENT NOT NULL,
    code VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description LONGTEXT DEFAULT NULL,
    icon VARCHAR(255) DEFAULT NULL,
    rarity VARCHAR(50) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE INDEX badge_code_unique (code),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4
""")
print("✓ Created badge table")

# Create user_badge table
cursor.execute("""
CREATE TABLE IF NOT EXISTS user_badge (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    awarded_at DATETIME NOT NULL,
    metadata JSON DEFAULT NULL,
    UNIQUE INDEX user_badge_unique (user_id, badge_id),
    INDEX IDX_USER_BADGE_USER (user_id),
    INDEX IDX_USER_BADGE_BADGE (badge_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_USER_BADGE_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT FK_USER_BADGE_BADGE FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4
""")
print("✓ Created user_badge table")

# Create user_streak_day table
cursor.execute("""
CREATE TABLE IF NOT EXISTS user_streak_day (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT NOT NULL,
    day DATE NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE INDEX user_date_unique (user_id, day),
    INDEX IDX_USER_STREAK_USER (user_id),
    PRIMARY KEY (id),
    CONSTRAINT FK_USER_STREAK_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) DEFAULT CHARACTER SET utf8mb4
""")
print("✓ Created user_streak_day table")

# Verify all tables
cursor.execute("SHOW TABLES;")
tables = cursor.fetchall()
print(f"\n✓ Database now contains {len(tables)} tables:")
for idx, table in enumerate(tables, 1):
    print(f"  {idx:2d}. {table[0]}")

cursor.close()
conn.close()
print("\n✓ All tables created successfully!")
