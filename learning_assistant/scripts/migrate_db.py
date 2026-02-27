"""
Migration script to create course_qa_history table in database
Run this once to set up the database for Q&A logging
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from services.db_service import DatabaseService

def create_qa_table():
    """Create the course_qa_history table"""
    print("Creating course_qa_history table...")
    
    db_service = DatabaseService()
    connection = db_service.get_connection()
    
    if not connection:
        print("Failed to connect to database!")
        return False
    
    try:
        cursor = connection.cursor()
        
        # Create table
        create_table_query = """
        CREATE TABLE IF NOT EXISTS course_qa_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            question LONGTEXT NOT NULL,
            answer LONGTEXT,
            user_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES cours(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_course_id (course_id),
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """
        
        cursor.execute(create_table_query)
        connection.commit()
        
        print("✓ Table created successfully!")
        
        # Check table
        check_query = "DESCRIBE course_qa_history"
        cursor.execute(check_query)
        columns = cursor.fetchall()
        
        print(f"✓ Table has {len(columns)} columns:")
        for col in columns:
            print(f"  - {col[0]}: {col[1]}")
        
        cursor.close()
        connection.close()
        return True
    
    except Exception as e:
        print(f"✗ Error creating table: {e}")
        return False

if __name__ == '__main__':
    success = create_qa_table()
    sys.exit(0 if success else 1)
