"""Build a review upload package from an explicit file list; never deploys."""
from pathlib import Path
import hashlib, json, shutil, subprocess, argparse

parser = argparse.ArgumentParser()
parser.add_argument("--release", action="store_true", help="Package a launch candidate; does not deploy or certify hosting readiness")
args = parser.parse_args()

ROOT = Path(__file__).resolve().parents[1]
subprocess.run(['python3', str(ROOT/'scripts/check_site.py')], check=True)
revision = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
status = subprocess.check_output(['git', 'status', '--porcelain'], cwd=ROOT, text=True).strip()
label = revision[:12] + ('-working-copy' if status else '')
kind = 'candidate' if args.release else 'review'
out = ROOT/'dist'
stage = out/'site'
if stage.exists(): shutil.rmtree(stage)
stage.mkdir(parents=True)
files = ['.htaccess', 'index.html', '404.html', 'styles.css', 'script.js', 'contact.js', 'robots.txt', 'sitemap.xml']
directories = ['assets', 'expertise', 'case-studies', 'products', 'ideas', 'ether-gov', 'about', 'contact', 'api']
for name in files: shutil.copy2(ROOT/name, stage/name)
for name in directories:
    for source in sorted((ROOT/name).rglob('*')):
        if not source.is_file(): continue
        relative = source.relative_to(ROOT)
        if source.is_symlink() or (any(part.startswith('.') for part in relative.parts) and relative.as_posix() != 'api/.htaccess'):
            raise SystemExit(f'Unexpected hidden file or symlink: {relative}')
        allowed = (name == 'assets' and source.suffix.lower() in {'.css', '.ttf', '.woff', '.woff2', '.jpg', '.jpeg', '.png', '.webp', '.svg', '.txt'}) or (name == 'api' and relative.as_posix() in {'api/contact.php', 'api/contact-lib.php', 'api/.htaccess'}) or (name not in {'assets', 'api'} and source.name == 'index.html' and relative.as_posix() != 'products/catalyst/index.html')
        if not allowed: raise SystemExit(f'Unexpected deployment file: {relative}')
        if relative.parts[:2] == ('products', 'catalyst'): raise SystemExit('Catalyst must never be packaged here')
        target = stage/relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)
manifest = {'revision': revision, 'working_copy': bool(status), 'status': 'launch-candidate' if args.release else 'review-only', 'files': {str(p.relative_to(stage)): hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(stage.rglob('*')) if p.is_file()}}
(stage/'release-manifest.json').write_text(json.dumps(manifest, indent=2)+'\n')
content_hash = hashlib.sha256(json.dumps(manifest['files'], sort_keys=True).encode()).hexdigest()[:12]
archive = shutil.make_archive(str(out/f'ether-{kind}-{label}-{content_hash}'), 'zip', stage)
print(f'{kind.capitalize()} package: {archive}')
