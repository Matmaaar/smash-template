#!/usr/bin/env bash
# Lance WordPress Playground en local avec le thème monté et la démo importée.
# Pensé pour les sessions Claude Code dans le cloud : wordpress.org et Docker y sont
# inaccessibles, on passe donc par npm (WordPress 6.5) et raw.githubusercontent.com (importeur).
#
#   tools/local/serve.sh [dossier_de_travail]   → http://127.0.0.1:9400 (journal : $WORK/server.log)
#   tools/local/check.sh                        → teste toutes les URL du sitemap
set -euo pipefail
THEME="$(cd "$(dirname "$0")/../.." && pwd)"
WORK="${1:-${TMPDIR:-/tmp}/smash-local}"
mkdir -p "$WORK" && cd "$WORK"

if [ ! -d node_modules/@wp-playground/cli ]; then
	npm init -y >/dev/null
	npm i --silent @wp-playground/cli @wp-playground/wordpress-builds
fi

if [ ! -f wp/wp-load.php ]; then
	rm -rf wp && mkdir wp && (cd wp && unzip -q ../node_modules/@wp-playground/wordpress-builds/src/wordpress/wp-6.5.zip)
fi

IMP=wp/wp-content/plugins/wordpress-importer
if [ ! -f "$IMP/class-wp-import.php" ]; then
	mkdir -p "$IMP/parsers"
	for f in wordpress-importer.php compat.php class-wp-import.php parsers.php parsers/class-wxr-parser.php parsers/class-wxr-parser-simplexml.php parsers/class-wxr-parser-xml.php parsers/class-wxr-parser-regex.php; do
		curl -sSf "https://raw.githubusercontent.com/WordPress/wordpress-importer/0.8.4/src/$f" -o "$IMP/$f"
	done
fi

cat > import.php <<'PHP'
<?php
require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';
define( 'WP_LOAD_IMPORTERS', true );
require '/wordpress/wp-content/plugins/wordpress-importer/wordpress-importer.php';
wp_set_current_user( 1 );
$i = new WP_Import();
$i->fetch_attachments = true;
ob_start();
$i->import( '/wordpress/wp-content/themes/smash/demo/demo-content.xml' );
ob_end_clean();
PHP
python3 - "$WORK" <<'PY'
import json, sys
w = sys.argv[1]
json.dump({"landingPage": "/", "login": True, "steps": [
    {"step": "activateTheme", "themeFolderName": "smash"},
    {"step": "runPHP", "code": open(w + "/import.php").read()},
    {"step": "runPHP", "code": "<?php require '/wordpress/wp-load.php'; require '/wordpress/wp-content/themes/smash/demo/setup.php';"},
]}, open(w + "/blueprint.json", "w"), indent=1)
PY

# Site neuf à chaque lancement.
rm -rf wp/wp-content/database/.ht.*/ wp/wp-content/database/db-path.php wp/wp-content/uploads
: > wp/wp-content/debug.log
nohup npx wp-playground-cli server --port=9400 \
	--mount-before-install="$WORK/wp:/wordpress" --wordpress-install-mode=install-from-existing-files \
	--mount="$THEME:/wordpress/wp-content/themes/smash" --blueprint="$WORK/blueprint.json" \
	--define-bool WP_DEBUG true --define-bool WP_DEBUG_LOG true > "$WORK/server.log" 2>&1 &
until grep -qiE "ready|error" "$WORK/server.log"; do sleep 1; done
tail -1 "$WORK/server.log"
