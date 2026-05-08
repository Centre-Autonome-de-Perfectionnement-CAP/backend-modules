from pathlib import Path
import re

repo_root = Path(r'c:/Users/DG/backend-modules')
target_dir = repo_root / 'public' / 'services' / 'app-cap-frontend'


def find_conflict_files(root_path):
    conflicted = []
    for path in root_path.rglob('*'):
        if path.is_file():
            text = path.read_text(encoding='utf-8', errors='ignore')
            if '<<<<<<<' in text and '=======' in text and '>>>>>>>' in text:
                conflicted.append(path)
    return conflicted


def resolve_conflicts_in_text(text):
    pattern = re.compile(r'<<<<<<<.*?\n(.*?)\n=======\n.*?\n>>>>>>>.*?\n', re.S)
    changed = False

    def keep_first(match):
        nonlocal changed
        changed = True
        return match.group(1) + '\n'

    new_text = pattern.sub(keep_first, text)
    return new_text, changed


def resolve_conflicts(files):
    resolved = []
    for path in files:
        content = path.read_text(encoding='utf-8', errors='ignore')
        new_content, changed = resolve_conflicts_in_text(content)
        if changed:
            path.write_text(new_content, encoding='utf-8')
            resolved.append(path)
    return resolved


def main():
    conflict_files = find_conflict_files(target_dir)
    print(f'Found {len(conflict_files)} conflicted files in {target_dir}')
    resolved_files = resolve_conflicts(conflict_files)
    print(f'Resolved {len(resolved_files)} files')
    remaining = [p for p in conflict_files if '<<<<<<<' in p.read_text(encoding='utf-8', errors='ignore')]
    print(f'Remaining unresolved files: {len(remaining)}')
    for path in remaining:
        print(' -', path)


if __name__ == '__main__':
    main()
