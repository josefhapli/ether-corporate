"""Check deployable HTML, local links, images and CSS assets without dependencies."""
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit, unquote
import re

ROOT = Path(__file__).resolve().parents[1]
PAGES = [ROOT/'index.html', ROOT/'404.html'] + sorted(p for p in ROOT.glob('*/index.html') if p.parent.name not in {'dist', 'review'}) + sorted(ROOT.glob('ideas/*/index.html'))

class Page(HTMLParser):
    def __init__(self, path):
        super().__init__(convert_charrefs=True)
        self.path, self.ids, self.refs, self.errors = path, set(), [], []
        self.h1 = 0
        self.title = False
        self.description = False
    def handle_starttag(self, tag, attributes):
        a = dict(attributes)
        if tag == 'h1': self.h1 += 1
        if tag == 'title': self.title = True
        if tag == 'meta' and a.get('name') == 'description' and a.get('content'): self.description = True
        if 'id' in a:
            if a['id'] in self.ids: self.errors.append(f"Duplicate ID: {a['id']}")
            self.ids.add(a['id'])
        if tag == 'img' and 'alt' not in a: self.errors.append('Image missing alt')
        for name in ('href', 'src'):
            if a.get(name): self.refs.append(a[name])

parsed = {}
for path in PAGES:
    page = Page(path); page.feed(path.read_text()); parsed[path] = page
    if page.h1 != 1: page.errors.append(f'Expected one h1, got {page.h1}')
    if not page.title or not page.description: page.errors.append('Missing title or description')

def resolve(source, url):
    path = unquote(urlsplit(url).path)
    result = ROOT/path.lstrip('/') if path.startswith('/') else source.parent/path
    if not path: result = source
    if result.is_dir(): result = result/'index.html'
    return result.resolve()

for path, page in parsed.items():
    for ref in page.refs:
        url = urlsplit(ref)
        if url.scheme or url.netloc: continue
        target = resolve(path, ref)
        if not target.is_file(): page.errors.append(f'Missing local destination: {ref}')
        elif url.fragment and target in parsed and unquote(url.fragment) not in parsed[target].ids:
            page.errors.append(f'Missing fragment: {ref}')
for css in [ROOT/'styles.css', ROOT/'assets/fonts.css']:
    for ref in re.findall(r'url\([\'\"]?([^\)\'\"]+)', css.read_text()):
        if not urlsplit(ref).scheme and not resolve(css, ref).is_file(): raise SystemExit(f'Missing CSS asset: {ref}')
errors = [f'{p.relative_to(ROOT)}: {error}' for p, page in parsed.items() for error in page.errors]
if errors: raise SystemExit('\n'.join(errors))
print(f'PASS: {len(PAGES)} pages; titles, descriptions, h1s, IDs, local links, image alt text and font assets.')
