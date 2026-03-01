"""
Quick Setup Script - Automates initial setup of Learning Assistant
Run this after installation to prepare the service
"""
import sys
import os
import subprocess

def run_command(cmd, description):
    """Run a command and report results"""
    print(f"\n{'='*60}")
    print(f"Step: {description}")
    print(f"{'='*60}")
    
    try:
        result = subprocess.run(cmd, shell=True, capture_output=False)
        if result.returncode == 0:
            print(f"✓ {description} - Success")
            return True
        else:
            print(f"✗ {description} - Failed")
            return False
    except Exception as e:
        print(f"✗ Error: {e}")
        return False

def setup():
    """Run all setup steps"""
    print("""
    
╔════════════════════════════════════════════════════════════╗
║  Intelligent Learning Assistant - Quick Setup              ║
╚════════════════════════════════════════════════════════════╝
    """)
    
    steps = [
        ("python scripts/migrate_db.py", "Create database table for Q&A logging"),
        ("python scripts/sync_courses.py", "Sync courses from database"),
        ("python scripts/train_embeddings.py", "Build embeddings index"),
    ]
    
    results = []
    for cmd, description in steps:
        success = run_command(cmd, description)
        results.append((description, success))
    
    # Summary
    print(f"\n{'='*60}")
    print("Setup Summary")
    print(f"{'='*60}")
    
    for description, success in results:
        status = "✓ Success" if success else "✗ Failed"
        print(f"{status}: {description}")
    
    all_success = all(success for _, success in results)
    
    if all_success:
        print(f"""
╔════════════════════════════════════════════════════════════╗
║  Setup Completed Successfully!                             ║
║                                                            ║
║  Next Step: Start the API server                          ║
║  $ python app.py                                          ║
║                                                            ║
║  Then test it:                                            ║
║  GET http://127.0.0.1:5000/health                         ║
╚════════════════════════════════════════════════════════════╝
        """)
        return True
    else:
        print(f"""
╔════════════════════════════════════════════════════════════╗
║  Setup Incomplete - Some steps failed                      ║
║  Check errors above and try again                          ║
╚════════════════════════════════════════════════════════════╝
        """)
        return False

if __name__ == '__main__':
    success = setup()
    sys.exit(0 if success else 1)
