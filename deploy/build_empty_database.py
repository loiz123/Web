"""Build a MySQL import for a NEW, empty database without demo accounts.

Run from the repository root: python deploy/build_empty_database.py
The generated file must only be imported into a database the user has selected.
"""
from pathlib import Path
import re

root = Path(__file__).resolve().parent.parent
schema = (root / 'database/schema.sql').read_text(encoding='utf-8')
schema = schema[schema.index('-- ---------- [A]'):]
seed = (root / 'database/seed.sql').read_text(encoding='utf-8')
roles = re.search(r'INSERT INTO roles\b.*?;', seed, re.S).group(0)
catalog = seed[seed.index('INSERT INTO provinces'):seed.index('INSERT INTO properties')].strip()
names = [
    'location_reads.sql', 'appointment.sql', 'account_and_saved_search.sql',
    'interactions.sql', 'property_images.sql', 'report.sql', 'dashboard.sql',
    'admin_users.sql', 'moderation.sql', 'catalog.sql', 'property_detail.sql',
    'property_agent.sql', 'property_search.sql',
]
parts = ['-- Import ONLY into a new empty database selected in phpMyAdmin.\n-- No user accounts are created.\nSET NAMES utf8mb4;\n', schema, roles, catalog]
for name in names:
    sql = (root / 'database/procedures' / name).read_text(encoding='utf-8')
    sql = re.sub(r'^DROP PROCEDURE IF EXISTS [a-zA-Z0-9_]+\$\$\s*', '', sql, flags=re.M)
    parts.append(f'\n-- {name}\n{sql}')
(root / 'deploy/empty_database.sql').write_text('\n\n'.join(parts), encoding='utf-8')
