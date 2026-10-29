#!/usr/bin/env bash
# Vérifie chaque URL du sitemap sur le serveur local : code HTTP, nombre de <h1>, notices PHP du thème.
WORK="${1:-${TMPDIR:-/tmp}/smash-local}"
BASE=http://127.0.0.1:9400
JAR="$WORK/jar.txt"; rm -f "$JAR"
curl -sS --noproxy '*' -c "$JAR" -b "$JAR" -L -o /dev/null "$BASE/" # connexion auto de Playground
: > "$WORK/wp/wp-content/debug.log"
for u in / /nos-bieres/ /la-brasserie/ /location-tireuses-a-bieres/ /le-blog/ /contact/ /telechargements/ \
	/mentions-legales/ /politique-de-confidentialite/ /sitemap/ /bieres/aquatrip/ /bieres/rye-bomb/ \
	/type-de-bieres/bieres-permanentes/ /type-de-bieres/bieres-ephemeres/ /type-de-bieres/bieres-archivees/ \
	/bieres-ephemeres-serie-21/ /category/uncategorized/ "/?s=serie" /n-existe-pas/; do
	out=$(curl -sS --noproxy '*' -c "$JAR" -b "$JAR" -w '\n%{http_code}' "$BASE$u")
	echo "$(echo "$out" | tail -1) h1=$(echo "$out" | grep -o '<h1' | wc -l) $u"
done
echo "--- notices PHP hors cœur WordPress :"
grep -v "wp-includes\|wp-admin" "$WORK/wp/wp-content/debug.log" || echo "aucune"
