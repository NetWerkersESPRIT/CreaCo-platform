"""
Configuration settings for Learning Assistant
"""
import os
from dotenv import load_dotenv

load_dotenv()

# Flask Configuration
FLASK_HOST = os.getenv('FLASK_HOST', '127.0.0.1')
FLASK_PORT = int(os.getenv('FLASK_PORT', 5000))
DEBUG = os.getenv('FLASK_DEBUG', 'True').lower() == 'true'

# Database Configuration
DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_USER = os.getenv('DB_USER', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')
DB_NAME = os.getenv('DB_NAME', 'creadb')
DB_PORT = int(os.getenv('DB_PORT', 3306))

# Model Configuration
MODEL_NAME = 'all-MiniLM-L6-v2'  # Lightweight sentence transformer
EMBEDDINGS_MODEL_PATH = './models/embeddings_index.pkl'
COURSES_DATA_PATH = './data/courses_data.json'
QA_HISTORY_PATH = './data/qa_history.json'

# API Configuration
MAX_CONTEXT_LENGTH = 8  # Number of top similar documents to consider
SIMILARITY_THRESHOLD = 0.25  # Minimum similarity score for relevance (lowered for better matching)
