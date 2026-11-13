"""Build a phpMyAdmin import for the selected database; never include table/seed SQL."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]

def normalize_delimiters(sql: str) -> str:
    """Keep DROP on the default delimiter; isolate compound CREATE statements."""
    delimiter = ';'
    buffer = []
    statements = []
    for line in sql.splitlines():
        stripped = line.strip()
        if stripped.upper().startswith('DELIMITER '):
            if any(x.strip() and not x.lstrip().startswith('--') for x in buffer):
                raise ValueError('Incomplete statement before DELIMITER')
            buffer = []
            delimiter = stripped.split(None, 1)[1]
            continue
        if not buffer and (not stripped or stripped.startswith('--')):
            continue
        buffer.append(line)
        current = '\n'.join(buffer).rstrip()
        if current.endswith(delimiter):
            statements.append(current[:-len(delimiter)].strip())
            buffer = []
    if any(x.strip() and not x.lstrip().startswith('--') for x in buffer):
        raise ValueError('Incomplete final SQL statement')
    result = []
    for statement in statements:
        # Strip leading comments only for classification, preserving SQL content.
        actual = re.sub(r'^(?:\s*--[^\n]*\n)*', '', statement).lstrip()
        if re.match(r'CREATE\s+PROCEDURE\b', actual, re.I):
            result.append('DELIMITER $$\n' + statement + '$$\nDELIMITER ;\n')
        elif re.match(r'DROP\s+PROCEDURE\b', actual, re.I):
            result.append(statement + ';\n')
        else:
            raise ValueError('Unexpected statement in procedure source: ' + actual[:80])
    return '\n'.join(result)

def split_bundle(content: str, directory: Path, selected: list[str] | None = None) -> None:
    definitions = {}
    for match in re.finditer(r'DELIMITER \$\$\n(CREATE PROCEDURE (\w+)\b.*?)\$\$\nDELIMITER ;', content, re.S):
        definitions[match[2]] = match[1]
    names = selected if selected is not None else list(definitions)
    if len(names) != len(set(names)):
        raise ValueError('Duplicate requested procedure names')
    unknown = set(names) - definitions.keys()
    if unknown:
        raise ValueError('Unknown procedures: ' + ', '.join(sorted(unknown)))
    directory.mkdir(exist_ok=True)
    total = (len(names) + 49) // 50
    for index, offset in enumerate(range(0, len(names), 50), 1):
        group = names[offset:offset + 50]
        sql = f'-- Part {index}/{total}: {len(group)} procedures. Select the target database before import.\nSET NAMES utf8mb4;\n\n'
        for name in group:
            sql += f'DROP PROCEDURE IF EXISTS {name};\n\nDELIMITER $$\n{definitions[name]}$$\nDELIMITER ;\n\n'
        sql = '\n'.join(line.rstrip() for line in sql.splitlines()).rstrip() + '\n'
        (directory / f'part-{index:02d}.sql').write_text(sql, encoding='utf-8')
    print(f'{directory.name}: {len(names)} procedures in {total} files (maximum 50 per file)')

def build() -> Path:
    sources = [ROOT / 'database/procedures.sql', *sorted((ROOT / 'database/procedures').glob('*.sql'))]
    parts = ["-- BDS Viet: complete stored-procedure installation.\n"
             "-- Select your existing database in phpMyAdmin, then import this file.\n"
             "-- Does not create/drop tables, import seed data, or select a database name.\n"
             "-- Requires CREATE ROUTINE / ALTER ROUTINE / EXECUTE privileges.\n"
             "SET NAMES utf8mb4;\n"]
    for source in sources:
        sql = source.read_text(encoding='utf-8-sig')
        sql = re.sub(r'^\s*USE\s+[^;]+;\s*$', '', sql, flags=re.M | re.I)
        if re.search(r'\b(?:CREATE\s+DATABASE|DROP\s+TABLE|TRUNCATE\s+TABLE|ALTER\s+TABLE)\b', sql, re.I):
            raise ValueError(f'Unexpected schema mutation in {source}')
        parts.append(f'\n-- Source: {source.relative_to(ROOT).as_posix()}\n{normalize_delimiters(sql)}\n')
    output = ROOT / 'deploy/procedures-online.sql'
    content = '\n'.join(parts)
    content = '\n'.join(line.rstrip() for line in content.splitlines()).rstrip() + '\n'
    output.write_text(content, encoding='utf-8')
    content = output.read_text(encoding='utf-8')
    split_bundle(content, ROOT / 'deploy/procedures-parts-50')
    missing_file = ROOT / 'deploy/procedures-missing.txt'
    if missing_file.exists():
        missing = [line.strip() for line in missing_file.read_text(encoding='utf-8').splitlines() if line.strip()]
        split_bundle(content, ROOT / 'deploy/procedures-missing-parts-50', missing)
    names = sorted(set(re.findall(r'CREATE\s+PROCEDURE\s+(\w+)', output.read_text(encoding='utf-8'), re.I)))
    expected = '\nUNION ALL\n'.join("SELECT '" + name + "' AS procedure_name" for name in names)
    check = ("-- Run after importing procedures-online.sql in the SAME selected database.\n"
             "-- Success: no rows returned (no missing procedures).\n"
             "SELECT expected.procedure_name AS missing_procedure\nFROM (\n" + expected +
             "\n) AS expected\nLEFT JOIN information_schema.ROUTINES AS actual\n"
             " ON actual.ROUTINE_SCHEMA=DATABASE() AND actual.ROUTINE_TYPE='PROCEDURE'\n"
             " AND actual.ROUTINE_NAME=expected.procedure_name\n"
             "WHERE actual.ROUTINE_NAME IS NULL\nORDER BY expected.procedure_name;\n")
    (ROOT / 'deploy/check-procedures.sql').write_text(check, encoding='utf-8')
    return output

if __name__ == '__main__':
    path = build()
    names = set(re.findall(r'CREATE\s+PROCEDURE\s+(\w+)', path.read_text(encoding='utf-8'), re.I))
    print(f'{path}: {len(names)} unique procedures')
