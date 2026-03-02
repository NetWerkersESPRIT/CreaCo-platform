#!/bin/bash
# Quick Start Guide for Intelligent Learning Assistant

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  Intelligent Learning Assistant - Quick Start              ║"
echo "╚════════════════════════════════════════════════════════════╝"

echo ""
echo "Step 1: Create virtual environment"
echo "cd learning_assistant"
echo "python -m venv venv"
echo ""

echo "Step 2: Activate virtual environment"
echo "# On Windows:"
echo "venv\Scripts\activate"
echo ""
echo "# On Linux/Mac:"
echo "source venv/bin/activate"
echo ""

echo "Step 3: Install dependencies"
echo "pip install -r requirements.txt"
echo ""

echo "Step 4: Configure database (.env file)"
echo "Copy .env.example to .env and update database credentials"
echo ""

echo "Step 5: Run setup script"
echo "python setup.py"
echo ""
echo "This will:"
echo "  - Create database table for Q&A logging"
echo "  - Sync all courses from database"
echo "  - Build embeddings index"
echo ""

echo "Step 6: Start the API server"
echo "python app.py"
echo ""

echo "Step 7: Test the API"
echo "curl http://127.0.0.1:5000/health"
echo ""

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  For more details, see README.md                           ║"
echo "╚════════════════════════════════════════════════════════════╝"
