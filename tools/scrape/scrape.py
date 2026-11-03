#!/usr/bin/env python3
"""Récupère le contenu public de smash.bzh pour la démo du thème.

- API REST : bières (gammes, location), articles, membres.
- HTML des fiches bières : les champs ACF (non exposés dans l'API), lus dans l'ordre affiché.
- HTML de /la-brasserie/ : équipe (photo, poste, présentation) et textes de présentation.
- Images converties en WebP dans demo/scraped/ (étiquettes, photos, articles, équipe).

Met à jour tools/demo/data.json (beers, posts, team, textes de la brasserie) sans toucher au reste.
Usage : python3 -I tools/scrape/scrape.py   (≈ 4 min : 1 requête par seconde pour ménager leur serveur)
"""
import html
import io
import json
import re
import time
import urllib.request
from pathlib import Path

from PIL import Image

SITE = 'https://smash.bzh'
ROOT = Path(__file__).resolve().parents[2]
DATA_FILE = ROOT / 'tools/demo/data.json'
OUT = ROOT / 'demo/scraped'
UA = {'User-Agent': 'smash-theme-demo/1.0'}
ICONS = ('description', 'ingredients', 'aspect', 'groupe-103', 'a-la-degustation', 'logo', 'header-rs', 'footer-', 'contact-', 'plan-de-travail')
RANGES = {'bieres-permanentes': 'perm', 'bieres-ephemeres': 'eph', 'bieres-archivees': 'old'}


def fetch(url, binary=False):
    req = urllib.request.Request(url, headers=UA)
    for attempt in range(3):
        try:
            with urllib.request.urlopen(req, timeout=60) as r:
                data = r.read()
                return data if binary else data.decode('utf-8'), r.headers
        except Exception:  # noqa: BLE001 — on réessaie puis on abandonne cette ressource
            time.sleep(2 * (attempt + 1))
    return None, None


def rest(path):
    out, page = [], 1
    while True:
        sep = '&' if '?' in path else '?'
        body, headers = fetch(f'{SITE}/wp-json/wp/v2/{path}{sep}per_page=100&page={page}')
        if body is None:
            break
        out += json.loads(body)
        if page >= int(headers.get('X-WP-TotalPages', '1')):
            break
        page += 1
        time.sleep(.5)
    return out


def text(s):
    return html.unescape(re.sub(r'<[^>]+>', '', s or '')).strip()


def content_lines(s):
    """Texte de la zone de contenu (entre le header et le footer Beaver Builder), une ligne par bloc."""
    a = s.find('data-type="header"')
    a = s.find('</header>', a) if a > 0 else 0
    b = s.find('data-type="footer"')
    s = s[a:b if b > 0 else len(s)]
    s = re.sub(r'<script.*?</script>|<style.*?</style>|<noscript.*?</noscript>', '', s, flags=re.S)

    def img(m):
        tag = m.group(0)
        src = re.search(r'data-lazy-src="([^"]+)"', tag) or re.search(r'\ssrc="([^"]+)"', tag)
        return '\n[IMG ' + (src.group(1) if src else '') + ']\n'

    s = re.sub(r'<img[^>]*>', img, s)
    s = re.sub(r'<br\s*/?>', ' ', s)
    s = re.sub(r'</(p|div|h\d|li|span)>', '\n', s)
    t = html.unescape(re.sub(r'<[^>]+>', '', s))
    out = []
    for line in t.split('\n'):
        line = re.sub(r'\s+', ' ', line).strip()
        if line and (not out or out[-1] != line):
            out.append(line)
    return out


def photo_urls(lines):
    """Images de contenu (hors pictos et logos), sans doublons, dans l'ordre."""
    seen = []
    for line in lines:
        m = re.match(r'\[IMG (https?://[^\]]+)\]', line)
        if not m:
            continue
        url = m.group(1)
        name = url.rsplit('/', 1)[-1].lower()
        if '/uploads/' not in url or name.endswith('.svg') or any(i in name for i in ICONS):
            continue
        if url not in seen:
            seen.append(url)
    return seen


def save_webp(url, rel, width):
    """Télécharge une image, la redimensionne et l'enregistre en WebP dans demo/scraped/."""
    target = OUT / rel
    if target.exists():
        return 'scraped/' + rel
    data, _ = fetch(url, binary=True)
    if not data:
        return ''
    try:
        im = Image.open(io.BytesIO(data))
        im = im.convert('RGBA' if im.mode in ('RGBA', 'LA', 'P') else 'RGB')
        if im.width > width:
            im = im.resize((width, round(im.height * width / im.width)), Image.LANCZOS)
        target.parent.mkdir(parents=True, exist_ok=True)
        im.save(target, 'WEBP', quality=78, method=6)
    except Exception:  # noqa: BLE001 — image illisible : on la saute
        return ''
    return 'scraped/' + rel


def parse_beer(lines, name):
    """Champs d'une fiche bière à partir de ses lignes de texte."""
    b = {}
    end = next((i for i, l in enumerate(lines) if l.startswith('Toutes nos bières')), len(lines))
    lines = lines[:end]
    head_end = next((i for i, l in enumerate(lines) if l == 'Description'), len(lines))
    head = [l for l in lines[:head_end] if not l.startswith('[IMG')]
    # Ligne 1 = nom (parfois avec un emoji), ligne 2 = « Style - 6,5° ».
    if head and name.lower().split()[0] in head[0].lower():
        head = head[1:]
    if head:
        m = re.match(r'^(.*?)\s*-\s*([\d.,]+)\s*°', head[0])
        if m:
            b['style'], b['abv'] = m.group(1).strip(), m.group(2).replace('.', ',')
            head = head[1:]
        elif len(head[0]) < 40:
            b['style'] = head[0]
            head = head[1:]
    formats = [l for l in head if len(l) <= 45 and re.search(r'fût|canette|bouteille|disponible|cl\b|\bl\b', l, re.I)]
    rest_ = [l for l in head if l not in formats and len(l) > 45]
    if formats:
        b['formats'] = ' · '.join(formats)
    if rest_:
        b['intro'] = ' '.join(rest_)

    sections, cur = {}, None
    for l in lines[head_end:]:
        if l.startswith('[IMG'):
            continue
        key = {'Description': 'desc', 'Ingrédients': 'ingr', 'Aspect': 'look',
               'Conditions de dégustation idéales': 'when', 'Accord bière et mets': 'food'}.get(l)
        if key:
            cur = key
            sections[cur] = []
        elif cur:
            sections[cur].append(l)
    for key in ('desc', 'look', 'when', 'food'):
        if sections.get(key):
            b[key] = ' '.join(sections[key]) if key != 'desc' else '\n\n'.join(sections[key])
    # Ingrédients : « • Malt : / • Hops : / • Yeast : / • Other : », sinon texte libre.
    ingr, field = {}, None
    for l in sections.get('ingr', []):
        m = re.match(r'^[•\-]?\s*(Malts?|Hops?|Houblons?|Yeast|Levure|Other|Autres?)\s*:?\s*(.*)$', l, re.I)
        if m:
            field = {'malt': 'malts', 'malts': 'malts', 'hop': 'hops', 'hops': 'hops', 'houblon': 'hops', 'houblons': 'hops',
                     'yeast': 'yeast', 'levure': 'yeast', 'other': 'other', 'autre': 'other', 'autres': 'other'}[m.group(1).lower()]
            ingr[field] = [m.group(2)] if m.group(2) else []
        elif field:
            ingr[field].append(l)
        else:
            ingr.setdefault('other', []).append(l)
    for k, v in ingr.items():
        if v:
            b[k] = ', '.join(x.strip(' ,') for x in v if x.strip(' ,'))
    return b


def slugify(name):
    import unicodedata
    s = unicodedata.normalize('NFKD', name).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')


def parse_team(lines, start):
    """Équipe de /la-brasserie/ : [photo] nom, poste, puis la présentation jusqu'à la photo suivante.
    (Lue sur la page plutôt que dans l'API : certains membres y ont un titre vide.)"""
    team, i = [], start
    while i + 2 < len(lines):
        m = re.match(r'\[IMG (https?://[^\]]+)\]', lines[i])
        name = lines[i + 1]
        if m and not name.startswith('[IMG') and len(name) <= 30 and len(name.split()) <= 3:
            j, bio = i + 3, []
            while j < len(lines) and not lines[j].startswith('[IMG'):
                bio.append(lines[j])
                j += 1
            slug = slugify(re.sub(r'[^\w\s-]', '', name)) or f'membre-{len(team) + 1}'
            team.append({'slug': slug, 'name': re.sub(r'[^\w\s\'-]', '', name).strip(), 'role': lines[i + 2],
                         'bio': '\n\n'.join(bio), 'photo': save_webp(m.group(1), f'team/{slug}.webp', 600)})
            i = j
        else:
            i += 1
    return team


def main():
    data = json.loads(DATA_FILE.read_text(encoding='utf-8'))
    old_kegs = {b['slug']: (b.get('keg_price'), b.get('keg_volume')) for b in data['beers'] if b.get('keg_price')}

    terms = {t['id']: t['slug'] for t in rest('type-de-bieres?_fields=id,slug')}
    raw_beers = rest('bieres?_fields=id,slug,title,type-de-bieres,type-de-location,date')
    print(f'{len(raw_beers)} bières')

    beers = []
    for n, rb in enumerate(raw_beers, 1):
        name = text(rb['title']['rendered'])
        slug = rb['slug']
        page, _ = fetch(f'{SITE}/bieres/{slug}/')
        time.sleep(1)
        if not page:
            print('  ! page introuvable', slug)
            continue
        lines = content_lines(page)
        rng = next((RANGES[terms[t]] for t in rb['type-de-bieres'] if terms.get(t) in RANGES), 'old')
        beer = {'slug': slug, 'name': name, 'range': rng, 'rent': bool(rb.get('type-de-location'))}
        beer.update(parse_beer(lines, name))
        imgs = photo_urls(lines)
        label = next((u for u in imgs if 'banderole' in u.lower()), imgs[0] if imgs else None)
        if label:
            beer['label'] = save_webp(label, f'labels/{slug}.webp', 1200)
        photo = next((u for u in imgs if u != label), None)
        if photo and rng != 'old':  # archivées : étiquette seulement (poids de la démo)
            beer['photo'] = save_webp(photo, f'photos/{slug}.webp', 900)
        if slug in old_kegs:
            beer['keg_price'], beer['keg_volume'] = old_kegs[slug]
        beers.append({k: v for k, v in beer.items() if v not in ('', None)})
        if n % 20 == 0:
            print(f'  {n}/{len(raw_beers)}')

    # Articles : contenu tel quel (les liens /bieres/… alimentent les étiquettes du thème).
    raw_posts = rest('posts?_fields=slug,title,date,content,featured_media')
    media_ids = ','.join(str(p['featured_media']) for p in raw_posts if p['featured_media'])
    media = {m['id']: m['source_url'] for m in rest(f'media?include={media_ids}&_fields=id,source_url')} if media_ids else {}
    posts = []
    for p in raw_posts:
        post = {'slug': p['slug'], 'title': text(p['title']['rendered']), 'date': p['date'][:10],
                'html': re.sub(r'\sdata-pm-slice="[^"]*"', '', p['content']['rendered'])}
        if p['featured_media'] in media:
            post['thumb'] = save_webp(media[p['featured_media']], f'posts/{p["slug"]}.webp', 1200)
        posts.append(post)
    print(f'{len(posts)} articles')

    # Équipe et textes de /la-brasserie/.
    page, _ = fetch(f'{SITE}/la-brasserie/')
    lines = content_lines(page or '')
    start = next((i for i, l in enumerate(lines) if l.lower().startswith('la team')), len(lines))
    team = parse_team(lines, start)
    print(f'{len(team)} membres')

    intro = lines[:start]
    values = []
    for k, l in enumerate(intro):
        if l.startswith('[IMG') and k + 1 < len(intro) and not intro[k + 1].startswith('[IMG') and k > 2:
            body = []
            for x in intro[k + 2:]:
                if x.startswith('[IMG'):
                    break
                body.append(x)
            values.append((intro[k + 1], ' '.join(body)))
    brew = data['page_fields'].setdefault('la-brasserie', {})
    plain = [l for l in intro if not l.startswith('[IMG')]
    if len(plain) >= 3:
        brew['intro_text'] = plain[1]
        brew['brew_text'] = plain[2]
    for n, (t, x) in enumerate(values[:3], 1):
        brew[f'value_{n}_title'], brew[f'value_{n}_text'] = t.lower().rstrip(' !'), x

    data['beers'], data['posts'], data['team'] = beers, posts, team
    data['_note'] = 'Contenu réel de smash.bzh (tools/scrape/scrape.py, ' + time.strftime('%d/%m/%Y') + ') + textes de la maquette pour les blocs absents du site.'
    DATA_FILE.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    size = sum(f.stat().st_size for f in OUT.rglob('*') if f.is_file()) / 1e6
    print(f'images : {size:.1f} Mo dans {OUT.relative_to(ROOT)}')


if __name__ == '__main__':
    main()
