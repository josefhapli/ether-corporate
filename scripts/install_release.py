"""Install only manifest-listed corporate files, with private backup and rollback."""
from pathlib import Path
import hashlib,json,os,shutil,sys,datetime
stage=Path(sys.argv[1]).resolve()
root=Path('/home/xkor122g3sbz/public_html')
private=Path('/home/xkor122g3sbz/ether-releases')
m=json.loads((stage/'release-manifest.json').read_text())
files=m['files']
for name,digest in files.items():
 p=Path(name)
 if p.is_absolute() or '..' in p.parts or name.startswith(('products/catalyst/','wp-','docs/','tests/')):raise SystemExit('Unsafe manifest path: '+name)
 source=stage/p
 if source.is_symlink() or hashlib.sha256(source.read_bytes()).hexdigest()!=digest:raise SystemExit('Source hash mismatch: '+name)
 for q in [root/p]+list((root/p).parents):
  if q==root:break
  if q.is_symlink():raise SystemExit('Refusing destination symlink: '+str(q))
 if (root/p).exists() and not (root/p).is_file():raise SystemExit('Destination is not a file: '+name)
backup=private/('backup-'+datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%dT%H%M%SZ'))
backup.mkdir(parents=True,mode=0o700)
records={}
for name in files:
 target=root/name
 records[name]=target.is_file()
 if target.is_file():
  dest=backup/'original'/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(target,dest)
(backup/'records.json').write_text(json.dumps(records,indent=2))
(backup/'release-manifest.json').write_text(json.dumps(m,indent=2))
rollback='''from pathlib import Path
import json,shutil,os
b=Path(__file__).resolve().parent
root=Path('/home/xkor122g3sbz/public_html')
for name,existed in json.loads((b/'records.json').read_text()).items():
 t=root/name
 if existed:
  temp=t.with_name(t.name+'.ether-restore')
  shutil.copy2(b/'original'/name,temp);os.replace(temp,t)
 elif t.exists():
  dest=b/'replaced-release'/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.move(str(t),str(dest))
print('Corporate files restored; unrelated applications unchanged.')
'''
(backup/'rollback.py').write_text(rollback)
order=sorted(files,key=lambda n:(n in ['index.html','.htaccess'],n=='.htaccess',n))
try:
 for name in order:
  dest=root/name;dest.parent.mkdir(parents=True,exist_ok=True)
  temp=dest.with_name(dest.name+'.ether-upload')
  shutil.copy2(stage/name,temp);temp.chmod(0o644);os.replace(temp,dest)
 for name,digest in files.items():
  if hashlib.sha256((root/name).read_bytes()).hexdigest()!=digest:raise RuntimeError('Destination hash mismatch: '+name)
except Exception:
 exec(compile(rollback,str(backup/'rollback.py'),'exec'),{'__file__':str(backup/'rollback.py')})
 raise
print('INSTALLED',len(files),'files; backup and rollback:',backup)
