#!/usr/bin/env python3
"""Génère le contenu de démo du thème à partir de tools/demo/data.json.

Sorties :
  demo/demo-content.xml   export WXR (importé par Playground / wp-env)
  demo/site.json          réglages lus par demo/setup.php (options, menus, pages spéciales)
  demo/media/             images (étiquettes générées + photos de la maquette)

Usage : python3 tools/demo/build.py [--base-url URL_DU_DOSSIER_MEDIA]
"""
import argparse
import json
import math
import shutil
from datetime import datetime
from pathlib import Path
from xml.sax.saxutils import escape

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[2]
DATA = json.loads((ROOT / 'tools/demo/data.json').read_text(encoding='utf-8'))
MEDIA = ROOT / 'demo/media'
SOURCE_IMG = ROOT / 'maquette/img'
FONT_BOLD = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'
DEFAULT_BASE = 'https://raw.githubusercontent.com/matmaaar/smash-template/claude/bold-ramanujan-he85fr/demo/media/'

RANGE_TERMS = {
    'perm': ('bieres-permanentes', 'Bières permanentes'),
    'eph': ('bieres-ephemeres', 'Bières éphémères'),
    'old': ('bieres-archivees', 'Bières archivées'),
}
LOCATION_TERMS = {'particuliers': 'Particuliers', 'evenementiel': 'Événementiel'}


def hex_rgb(h):
    h = h.lstrip('#')
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def fake_label(beer, path):
    """Étiquette provisoire au format 2:1, comme fakeLabel() dans la maquette."""
    w, h = 1200, 600
    a, z = (hex_rgb(c) for c in beer['c'])
    img = Image.new('RGB', (w, h))
    px = ImageDraw.Draw(img)
    for x in range(w):
        t = x / (w - 1)
        px.line([(x, 0), (x, h)], fill=tuple(round(a[i] + (z[i] - a[i]) * t) for i in range(3)))
    ink = (247, 246, 242) if beer.get('dark') else (13, 13, 13)
    overlay = Image.new('RGBA', (w, h), (0, 0, 0, 0))
    od = ImageDraw.Draw(overlay)
    for i in range(7):
        cx, cy, r = 380 + i * 70, 300 + math.sin(i + len(beer['name'])) * 120, 30 + (i % 3) * 22
        od.ellipse([cx - r, cy - r, cx + r, cy + r], fill=ink + (46,))
    img = Image.alpha_composite(img.convert('RGBA'), overlay)
    # Nom et style à la verticale, à gauche.
    txt = Image.new('RGBA', (h, 260), (0, 0, 0, 0))
    td = ImageDraw.Draw(txt)
    size = 110
    while size > 40 and td.textlength(beer['name'].lower(), font=ImageFont.truetype(FONT_BOLD, size)) > h - 80:
        size -= 4
    td.text((40, 10 + (110 - size)), beer['name'].lower(), font=ImageFont.truetype(FONT_BOLD, size), fill=ink)
    td.text((44, 150), beer['style'].upper(), font=ImageFont.truetype(FONT_BOLD, 28), fill=ink)
    img.alpha_composite(txt.rotate(90, expand=True), (40, 0))
    logo = Image.open(SOURCE_IMG / ('s-white.png' if beer.get('dark') else 's-black.png')).convert('RGBA').resize((130, 130))
    img.alpha_composite(logo, (1030, 40))
    ImageDraw.Draw(img).text((1095, 212), 'smash', font=ImageFont.truetype(FONT_BOLD, 40), fill=ink, anchor='mm')
    img.convert('RGB').save(path, 'JPEG', quality=84, optimize=True)


def cdata(s):
    return '<![CDATA[' + s.replace(']]>', ']]]]><![CDATA[>') + ']]>'


def p(text):
    return f'<!-- wp:paragraph -->\n<p>{escape(text)}</p>\n<!-- /wp:paragraph -->'


def meta(key, value):
    return f'<wp:postmeta><wp:meta_key>{cdata(key)}</wp:meta_key><wp:meta_value>{cdata(str(value))}</wp:meta_value></wp:postmeta>'


def item(pid, title, slug, ptype, date, content='', excerpt='', parent=0, order=0, terms=(), metas=(), extra=''):
    stamp = f'{date} 10:00:00'
    cats = ''.join(f'<category domain="{d}" nicename="{n}">{cdata(lbl)}</category>' for d, n, lbl in terms)
    return f'''
<item>
<title>{cdata(title)}</title>
<dc:creator>{cdata('smash')}</dc:creator>
<content:encoded>{cdata(content)}</content:encoded>
<excerpt:encoded>{cdata(excerpt)}</excerpt:encoded>
<wp:post_id>{pid}</wp:post_id>
<wp:post_date>{cdata(stamp)}</wp:post_date>
<wp:post_date_gmt>{cdata(stamp)}</wp:post_date_gmt>
<wp:comment_status>{cdata('closed')}</wp:comment_status>
<wp:ping_status>{cdata('closed')}</wp:ping_status>
<wp:post_name>{cdata(slug)}</wp:post_name>
<wp:status>{cdata('inherit' if ptype == 'attachment' else 'publish')}</wp:status>
<wp:post_parent>{parent}</wp:post_parent>
<wp:menu_order>{order}</wp:menu_order>
<wp:post_type>{cdata(ptype)}</wp:post_type>
<wp:post_password>{cdata('')}</wp:post_password>
<wp:is_sticky>0</wp:is_sticky>{extra}
{cats}
{''.join(metas)}
</item>'''


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--base-url', default=DEFAULT_BASE, help='URL publique du dossier demo/media/')
    base = ap.parse_args().base_url.rstrip('/') + '/'

    if MEDIA.exists():
        shutil.rmtree(MEDIA)
    (MEDIA / 'labels').mkdir(parents=True)

    items, today = [], '2026-01-01'
    next_id = [2000]

    def attachment(rel, title, alt):
        pid = next_id[0]
        next_id[0] += 1
        url = base + rel
        items.append(item(pid, title, Path(rel).stem, 'attachment', today,
                          metas=[meta('_wp_attachment_image_alt', alt)],
                          extra=f'\n<wp:attachment_url>{cdata(url)}</wp:attachment_url>'))
        return pid

    # Photos de la maquette (photos des bières + images citées par l'accueil, « img:fichier »).
    photos = {}
    wanted = {b['photo'] for b in DATA['beers'] if b.get('photo')}
    wanted |= {v[4:] for v in DATA['front'].values() if v.startswith('img:')}
    wanted -= {b['label'] for b in DATA['beers'] if b.get('label')}  # déjà importées comme étiquettes
    for name in sorted(wanted):
        shutil.copy(SOURCE_IMG / name, MEDIA / name)
        photos[name] = attachment(name, Path(name).stem, '')

    # Bières.
    fmap = {'label': 'banderole', 'photo': 'photo', 'style': 'style', 'abv': 'degre', 'formats': 'formats',
            'intro': 'accroche', 'desc': 'description', 'malts': 'malt', 'hops': 'houblons', 'yeast': 'levure',
            'other': 'other', 'look': 'aspect', 'when': 'degustation', 'food': 'accords'}
    beer_ids, pid = {}, 1000
    for order, b in enumerate(DATA['beers']):
        if b.get('label'):
            shutil.copy(SOURCE_IMG / b['label'], MEDIA / 'labels' / b['label'])
            rel = 'labels/' + b['label']
        else:
            rel = f"labels/{b['slug']}.jpg"
            fake_label(b, MEDIA / rel)
        label_id = attachment(rel, f"Étiquette {b['name']}", f"Étiquette de la bière {b['name']}")
        photos.setdefault(Path(rel).name, label_id)
        values = {**b, 'label': label_id, 'photo': photos.get(b.get('photo'), '')}
        metas = [meta(key, values[k]) for k, key in fmap.items() if values.get(k) not in (None, '')]
        metas.append(meta('_thumbnail_id', label_id))
        slug, lbl = RANGE_TERMS[b['range']]
        terms = [('type-de-bieres', slug, lbl)]
        if b.get('rent'):
            terms += [('type-de-location', s, l) for s, l in LOCATION_TERMS.items()]
        items.append(item(pid, b['name'], b['slug'], 'bieres', today, order=order, terms=terms, metas=metas))
        beer_ids[b['slug']] = pid
        pid += 1

    # Articles : les bières sont citées par des liens /bieres/xxx/, comme sur le vrai site.
    names = {b['slug']: b['name'] for b in DATA['beers']}
    pid = 1100
    for post in DATA['posts']:
        body = [p(post.get('intro') or 'Nouvelle série de bières éphémères, disponibles jusqu\'à épuisement.')]
        for s in post['beers']:
            body.append(f'<!-- wp:paragraph -->\n<p><a href="/bieres/{s}/">{escape(names[s])}</a></p>\n<!-- /wp:paragraph -->')
        items.append(item(pid, post['title'], post['slug'], 'post', post['date'], '\n\n'.join(body),
                          terms=[('category', 'uncategorized', 'Uncategorized')]))
        pid += 1

    # Équipe.
    pid = 1200
    for order, (slug, name, role, bio) in enumerate(DATA['team']):
        items.append(item(pid, name, slug, 'membre', today, order=order, metas=[meta('poste', role), meta('bio', bio)]))
        pid += 1

    # Pages.
    pid, roles = 1300, {}
    for order, page in enumerate(DATA['pages']):
        content = p(page['content']) if page['content'] else ''
        items.append(item(pid, page['title'], page['slug'], 'page', today, content, order=order))
        if page.get('role'):
            roles[page['role']] = page['slug']
        pid += 1

    desc = {RANGE_TERMS[k][0]: v for k, v in DATA.get('range_descriptions', {}).items()}
    terms_xml = ''.join(
        f'<wp:term><wp:term_taxonomy>{tax}</wp:term_taxonomy><wp:term_slug>{cdata(s)}</wp:term_slug><wp:term_name>{cdata(l)}</wp:term_name>'
        f'<wp:term_description>{cdata(desc.get(s, ""))}</wp:term_description></wp:term>'
        for tax, group in (('type-de-bieres', RANGE_TERMS.values()), ('type-de-location', LOCATION_TERMS.items()))
        for s, l in group)

    wxr = f'''<?xml version="1.0" encoding="UTF-8" ?>
<!-- Contenu de démo du thème smash, généré par tools/demo/build.py le {datetime.now():%Y-%m-%d}. Ne pas modifier à la main. -->
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/">
<channel>
<title>{escape(DATA['site']['name'])}</title>
<link>https://smash.bzh</link>
<description>{escape(DATA['site']['description'])}</description>
<language>fr-FR</language>
<wp:wxr_version>1.2</wp:wxr_version>
<wp:base_site_url>https://smash.bzh</wp:base_site_url>
<wp:base_blog_url>https://smash.bzh</wp:base_blog_url>
<wp:author><wp:author_id>1</wp:author_id><wp:author_login>{cdata('smash')}</wp:author_login><wp:author_email>{cdata('contact@smash.bzh')}</wp:author_email><wp:author_display_name>{cdata('smash')}</wp:author_display_name></wp:author>
{terms_xml}
{''.join(items)}
</channel>
</rss>
'''
    (ROOT / 'demo/demo-content.xml').write_text(wxr, encoding='utf-8')

    site = {
        'site': DATA['site'],
        'options': DATA['options'],
        'menus': DATA['menus'],
        'front_page': roles.get('front'),
        'posts_page': roles.get('posts'),
        'cta': DATA['cta'],
        'page_fields': DATA.get('page_fields', {}),
        # Champs de l'accueil : « img:fichier » devient l'ID de la pièce jointe ; « page:slug » est résolu par setup.php.
        'front': {k: (photos[v[4:]] if v.startswith('img:') else v) for k, v in DATA['front'].items()},
    }
    (ROOT / 'demo/site.json').write_text(json.dumps(site, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f'{len(items)} contenus, médias dans {MEDIA.relative_to(ROOT)}')


if __name__ == '__main__':
    main()
