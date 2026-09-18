"""Build a review upload package from an explicit file list; never deploys."""
from pathlib import Path
import hashlib, json, shutil, subprocess

ROOT = Path(__file__).resolve().parents[1]
subprocess.run(['python3', str(ROOT/'scripts/check_site.py')], check=True)
revision = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()
status = subprocess.check_output(['git', 'status', '--porcelain'], cwd=ROOT, text=True).strip()
label = revision[:12] + ('-working-copy' if status else '')
out = ROOT/'dist'
stage = out/'site'
if stage.exists(): shutil.rmtree(stage)
stage.mkdir(parents=True)
files = ['index.html', '404.html', 'styles.css', 'script.js', 'contact.js']
directories = ['assets', 'expertise', 'case-studies', 'products', 'ideas', 'ether-gov', 'about', 'contact', 'api']
for name in files: shutil.copy2(ROOT/name, stage/name)
for name in directories: shutil.copytree(ROOT/name, stage/name)
manifest = {'revision': revision, 'working_copy': bool(status), 'status': 'review-only', 'files': {str(p.relative_to(stage)): hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(stage.rglob('*')) if p.is_file()}}
(stage/'release-manifest.json').write_text(json.dumps(manifest, indent=2)+'\n')
archive = shutil.make_archive(str(out/f'ether-review-{label}'), 'zip', stage)
print(f'Review package: {archive}')
