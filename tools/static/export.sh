#!/usr/bin/env bash
# Copie statique du site de démo, pour GitHub Pages (lien à envoyer : s'ouvre tout de suite, même sur mobile).
#
#   1. WordPress local avec les images, sur http://127.0.0.1:9400, NON connecté :
#      (cd demo && python3 -m http.server 8765) ; python3 -I tools/demo/build.py --base-url http://127.0.0.1:8765/media/
#      puis tools/local/serve.sh avec "login": False ; remettre demo/ en état ensuite (git checkout demo).
#      Copier tools/static/mu-export-fr.php dans wp-content/mu-plugins/ du WordPress local (dates et langue en français).
#   2. tools/static/export.sh [dossier_de_sortie=docs] [url_publique=https://matmaaar.github.io/smash-template/]
#
# Ce que fait le script :
#   - aspire toutes les pages et leurs ressources (wget, liens rendus relatifs) ;
#   - remplace les adresses locales restantes (canonical, JSON-LD…) par l'adresse publique ;
#   - simule l'envoi des formulaires (devis, contact) : sur une copie statique, il n'y a pas de serveur pour les recevoir.
set -euo pipefail
OUT="${1:-docs}"
PUBLIC="${2:-https://matmaaar.github.io/smash-template/}"
LOCAL="http://127.0.0.1:9400"

if [ -z "${NO_WGET:-}" ]; then   # NO_WGET=1 : seulement le nettoyage, sur une copie déjà aspirée
rm -rf "$OUT" && mkdir -p "$OUT"
wget --no-proxy --quiet --mirror --page-requisites --convert-links --adjust-extension \
	--no-host-directories --execute robots=off --directory-prefix="$OUT" \
	--reject-regex '(wp-admin|wp-json|xmlrpc|wp-login|/feed/|/comments/|\?s=|\?p=|replytocom|/embed/|oembed)' \
	"$LOCAL/" "$LOCAL/sitemap/" || true   # wget renvoie 8 dès qu'une ressource manque : on vérifie après.
fi

python3 - "$OUT" "$LOCAL" "$PUBLIC" <<'PY'
import pathlib, re, sys
out, local, public = pathlib.Path(sys.argv[1]), sys.argv[2], sys.argv[3]
# Formulaires : réponse « envoyé » simulée pour les routes du thème (smash/v1/devis et smash/v1/contact).
fake = ('<script>(function(){var f=window.fetch;window.fetch=function(u,o){if(String(u).indexOf("smash/v1/")>-1)'
        '{return new Promise(function(r){setTimeout(function(){r(new Response(\'{"ok":true}\',{status:200,'
        'headers:{"Content-Type":"application/json"}}))},600)})}return f.apply(this,arguments)}})();</script>')
# Page 404 pour GitHub Pages : servie à n'importe quelle profondeur, donc avec des adresses complètes.
# Les fichiers aspirés avec « ?ver= » portent ce suffixe dans leur nom (wget) : on le reproduit.
import urllib.request, urllib.error
try:
    urllib.request.build_opener(urllib.request.ProxyHandler({})).open(local + '/page-introuvable-smash/')
    html404 = ''
except urllib.error.HTTPError as e:
    html404 = e.read().decode('utf-8', 'replace')
if html404:
    html404 = re.sub(re.escape(local) + r'(/[^"\'\s?]+)\?ver=([^"\'\s&]+)',
                     lambda m: local + m.group(1) + '%3Fver=' + m.group(2) + ('.css' if m.group(1).endswith('.css') else ''), html404)
    (out / '404.html').write_text(html404, encoding='utf-8')
n = 0
for p in out.rglob('*.html'):
    s = p.read_text(encoding='utf-8', errors='replace')
    # Liens techniques de WordPress (API, oEmbed, RSD) : inutiles sur une copie statique.
    s = re.sub(r'<link[^>]+(?:oembed|api\.w\.org|EditURI|wp-json|dns-prefetch)[^>]*>\n?', '', s)
    s = s.replace(local + '/', public).replace(local, public.rstrip('/'))
    s = s.replace(local.replace('/', '\\/') + '\\/', public.replace('/', '\\/'))  # JSON échappé
    if '<head>' in s and 'smash/v1/' not in s.split('<head>', 1)[1][:400]:
        s = s.replace('<head>', '<head>\n' + fake, 1)
    p.write_text(s, encoding='utf-8')
    n += 1
(out / '.nojekyll').write_text('')
print(n, 'pages')
PY
du -sh "$OUT"
