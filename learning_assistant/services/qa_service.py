"""
Q&A Service - Generates answers based on course content embeddings
"""
from services.embedding_service import EmbeddingService
from services.db_service import DatabaseService
import json
from config import COURSES_DATA_PATH
import os

class QAService:
    def __init__(self):
        self.embedding_service = EmbeddingService()
        self.db_service = DatabaseService()
        self.courses_data = self.load_courses_data()
    
    def load_courses_data(self):
        """Load cached courses data or fetch from database"""
        if os.path.exists(COURSES_DATA_PATH):
            try:
                with open(COURSES_DATA_PATH, 'r') as f:
                    return json.load(f)
            except:
                pass
        
        # Fetch from database
        courses = self.db_service.get_all_courses_and_resources()
        if courses:
            with open(COURSES_DATA_PATH, 'w') as f:
                json.dump(courses, f)
        return courses
    
    def initalize_course_embeddings(self):
        """
        Build embeddings index from all course resources
        Call this after syncing courses from database
        """
        documents = []
        document_metadata = []
        
        for course in self.courses_data:
            # Add course description
            course_title_desc = f"Course: {course['titre']}\n{course['description']}"
            documents.append(course_title_desc)
            document_metadata.append({
                'course_id': course['id'],
                'course_name': course['titre'],
                'type': 'course_overview'
            })
            
            # Add each resource
            if course.get('resources'):
                for resource in course['resources']:
                    if resource:
                        resource_text = f"{resource['titre']}\n{resource.get('contenu', '')}"
                        documents.append(resource_text)
                        document_metadata.append({
                            'course_id': course['id'],
                            'course_name': course['titre'],
                            'resource_id': resource['id'],
                            'resource_name': resource['titre'],
                            'type': 'resource'
                        })
        
        if documents:
            self.embedding_service.add_documents(documents)
            print(f"Initialized {len(documents)} documents in embedding index")
            return True
        return False
    
    def answer_question(self, question, course_id=None, top_k=5):
        """
        Answer a student question based on course content
        Returns: {answer, sources, relevance_score}
        """
        # Find relevant documents
        relevant_docs = self.embedding_service.get_similar_documents(
            question, 
            top_k=top_k,
            threshold=0.4
        )
        
        if not relevant_docs:
            return {
                'answer': "I don't have enough information about this topic. Please try a different question or contact your instructor.",
                'sources': [],
                'confidence': 0.0,
                'relevant_documents': []
            }
        
        # Build context from relevant documents
        context = "\n\n".join([doc['text'][:500] for doc in relevant_docs])
        
        # Create the answer (in production, you'd use Gemini API here)
        answer = self.generate_answer_from_context(question, context)
        
        return {
            'answer': answer,
            'sources': [
                {
                    'text': doc['text'][:200],
                    'similarity': doc['score']
                }
                for doc in relevant_docs
            ],
            'confidence': relevant_docs[0]['score'] if relevant_docs else 0.0,
            'relevant_documents': len(relevant_docs)
        }
    
    def generate_answer_from_context(self, question, context):
        """
        Generate answer from context
        In production, call Gemini API for better answers
        """
        # Simple template-based response (can be replaced with LLM call)
        answer = f"""Based on the course materials:

{context}

This information should help you understand the concept of your question: "{question}"

If you need more detailed explanations, please refer to the full resource or contact your instructor."""
        
        return answer
    
    def save_interaction(self, course_id, question, answer, user_id=None):
        """Save Q&A interaction for improvement"""
        return self.db_service.save_qa_interaction(course_id, question, answer, user_id)
    
    def get_course_summary(self, course_id):
        """Get a summary of course content"""
        course = next((c for c in self.courses_data if c['id'] == course_id), None)
        if not course:
            return None
        
        return {
            'title': course['titre'],
            'description': course['description'],
            'level': course['niveau'],
            'category': course.get('categorie'),
            'resources_count': len(course.get('resources', [])),
            'resources': [
                {
                    'title': r['titre'],
                    'type': r['type']
                }
                for r in course.get('resources', [])
            ]
        }
