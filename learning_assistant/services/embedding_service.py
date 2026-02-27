"""
Embedding Service - Handles semantic embeddings and similarity search
"""
import joblib
import os
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity
import numpy as np
from config import MODEL_NAME, EMBEDDINGS_MODEL_PATH

class EmbeddingService:
    def __init__(self):
        self.model = SentenceTransformer(MODEL_NAME)
        self.embeddings_index = None
        self.texts_index = None
        self.load_or_build_index()
    
    def load_or_build_index(self):
        """Load existing embeddings index or initialize empty"""
        if os.path.exists(EMBEDDINGS_MODEL_PATH):
            try:
                data = joblib.load(EMBEDDINGS_MODEL_PATH)
                self.embeddings_index = data['embeddings']
                self.texts_index = data['texts']
                print(f"Loaded embeddings index with {len(self.texts_index)} documents")
            except Exception as e:
                print(f"Error loading embeddings: {e}. Starting fresh.")
                self.embeddings_index = np.array([])
                self.texts_index = []
        else:
            self.embeddings_index = np.array([])
            self.texts_index = []
    
    def add_documents(self, documents):
        """
        Add documents to the embeddings index
        documents: list of strings (course content)
        """
        if not documents:
            return False
        
        try:
            # Generate embeddings for new documents
            new_embeddings = self.model.encode(documents, show_progress_bar=False)
            
            # Append to existing embeddings
            if len(self.embeddings_index) > 0:
                self.embeddings_index = np.vstack([self.embeddings_index, new_embeddings])
                self.texts_index.extend(documents)
            else:
                self.embeddings_index = new_embeddings
                self.texts_index = documents
            
            # Save the updated index
            self.save_index()
            print(f"Added {len(documents)} documents. Total: {len(self.texts_index)}")
            return True
        except Exception as e:
            print(f"Error adding documents: {e}")
            return False
    
    def get_similar_documents(self, query, top_k=5, threshold=0.5):
        """
        Find similar documents for a given query
        Returns: list of (text, similarity_score) tuples
        """
        if len(self.texts_index) == 0:
            return []
        
        try:
            # Encode the query
            query_embedding = self.model.encode([query], show_progress_bar=False)[0]
            
            # Calculate similarities
            similarities = cosine_similarity([query_embedding], self.embeddings_index)[0]
            
            # Get top k results above threshold
            top_indices = np.argsort(similarities)[::-1]
            results = []
            
            for idx in top_indices[:top_k]:
                score = float(similarities[idx])
                if score >= threshold:
                    results.append({
                        'text': self.texts_index[idx],
                        'score': score,
                        'index': int(idx)
                    })
            
            return results
        except Exception as e:
            print(f"Error searching documents: {e}")
            return []
    
    def save_index(self):
        """Save embeddings index to disk"""
        try:
            os.makedirs(os.path.dirname(EMBEDDINGS_MODEL_PATH), exist_ok=True)
            joblib.dump({
                'embeddings': self.embeddings_index,
                'texts': self.texts_index
            }, EMBEDDINGS_MODEL_PATH)
            return True
        except Exception as e:
            print(f"Error saving embeddings: {e}")
            return False
    
    def clear_index(self):
        """Clear the embeddings index"""
        self.embeddings_index = np.array([])
        self.texts_index = []
        if os.path.exists(EMBEDDINGS_MODEL_PATH):
            os.remove(EMBEDDINGS_MODEL_PATH)
        print("Embeddings index cleared")
