#!/usr/bin/env bash
# Applique tools/demo/data.json au WordPress local d'export déjà lancé (http://127.0.0.1:9400),
# sans le recréer : nouvelles images ajoutées une par une, textes et réglages recopiés.
# Ensuite : tools/static/export.sh pour régénérer docs/.
set -euo pipefail
curl --noproxy '*' -sS --fail "http://127.0.0.1:9400/wp-content/themes/smash/tools/static/sync.php"
echo
