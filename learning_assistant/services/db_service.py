"""
Database Service - Handles MySQL connections and queries
"""
import mysql.connector
from config import DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT
import json
from datetime import datetime

class DatabaseService:
    def __init__(self):
        self.host = DB_HOST
        self.user = DB_USER
        self.password = DB_PASSWORD
        self.database = DB_NAME
        self.port = DB_PORT
    
    def get_connection(self):
        """Create and return a database connection"""
        try:
            connection = mysql.connector.connect(
                host=self.host,
                user=self.user,
                password=self.password,
                database=self.database,
                port=self.port
            )
            return connection
        except mysql.connector.Error as err:
            print(f"Database Error: {err}")
            return None
    
    def get_all_courses_and_resources(self):
        """
        Fetch all courses and their resources from database
        Returns list of dictionaries with course info and content
        """
        connection = self.get_connection()
        if not connection:
            return []
        
        try:
            cursor = connection.cursor(dictionary=True)
            
            query = """
            SELECT 
                c.id,
                c.titre,
                c.description,
                c.niveau,
                c.slug,
                cat.nom as categorie,
                GROUP_CONCAT(
                    JSON_OBJECT(
                        'id', r.id,
                        'titre', r.nom,
                        'contenu', r.contenu,
                        'type', r.type
                    ) SEPARATOR '||'
                ) as resources
            FROM cours c
            LEFT JOIN categorie_cours cat ON c.categorie_id = cat.id
            LEFT JOIN ressource r ON c.id = r.cours_id
            WHERE c.statut = 'published' AND c.deleted_at IS NULL
            GROUP BY c.id
            """
            
            cursor.execute(query)
            courses = cursor.fetchall()
            
            # Parse resources JSON
            for course in courses:
                if course['resources']:
                    resources_str = course['resources'].split('||')
                    course['resources'] = [json.loads(r) if r else None for r in resources_str]
                else:
                    course['resources'] = []
            
            cursor.close()
            return courses
        except Exception as e:
            print(f"Error fetching courses: {e}")
            return []
        finally:
            connection.close()
    
    def save_qa_interaction(self, course_id, question, answer, user_id=None):
        """
        Save Q&A interaction to database for learning
        """
        connection = self.get_connection()
        if not connection:
            return False
        
        try:
            cursor = connection.cursor()
            
            # Insert into a new table (we'll create this)
            insert_query = """
            INSERT INTO course_qa_history (course_id, question, answer, user_id, created_at)
            VALUES (%s, %s, %s, %s, %s)
            """
            
            cursor.execute(insert_query, (
                course_id,
                question,
                answer,
                user_id,
                datetime.now()
            ))
            
            connection.commit()
            cursor.close()
            return True
        except Exception as e:
            print(f"Error saving Q&A: {e}")
            return False
        finally:
            connection.close()
    
    def get_user_course_progress(self, user_id, course_id):
        """
        Get user's progress in a specific course
        """
        connection = self.get_connection()
        if not connection:
            return None
        
        try:
            cursor = connection.cursor(dictionary=True)
            
            query = """
            SELECT 
                ucp.user_id,
                ucp.courses_id,
                ucp.progress_percentage,
                ucp.status,
                COUNT(urp.id) as resources_accessed
            FROM user_cours_progress ucp
            LEFT JOIN user_ressource_progress urp ON ucp.user_id = urp.user_id 
                AND urp.cours_id = ucp.courses_id
            WHERE ucp.user_id = %s AND ucp.courses_id = %s
            GROUP BY ucp.user_id, ucp.courses_id
            """
            
            cursor.execute(query, (user_id, course_id))
            result = cursor.fetchone()
            cursor.close()
            return result
        except Exception as e:
            print(f"Error fetching user progress: {e}")
            return None
        finally:
            connection.close()
