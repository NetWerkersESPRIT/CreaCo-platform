#!/usr/bin/env python3
import codecs

files = ['schema_update.sql', 'schema_updates.sql']

for filename in files:
    # Try different encodings to read the file
    for encoding in ['utf-16', 'utf-16-le', 'utf-16-be', 'utf-8-sig', 'utf-8', 'latin-1']:
        try:
            with open(filename, 'r', encoding=encoding) as f:
                content = f.read()
            # Write back as UTF-8 without BOM
            with open(filename, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"✓ Cleaned {filename} (was {encoding})")
            break
        except (UnicodeDecodeError, UnicodeError):
            continue
