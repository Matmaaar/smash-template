# smash : thème WordPress

Thème sur mesure (PHP, classique) pour la brasserie smash, à partir de la maquette dans `maquette/`.
Le plan complet et l'avancement sont dans [`PLAN.md`](PLAN.md).

## Démo en ligne (WordPress Playground)

[Ouvrir la démo](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/matmaaar/smash-template/claude/bold-ramanujan-he85fr/blueprint.json)

Le blueprint installe le thème depuis ce repo (branche `claude/bold-ramanujan-he85fr`), le plugin Secure Custom Fields, importe `demo/demo-content.xml` puis lance `demo/setup.php` (accueil, blog, menus, infos globales, permaliens). On arrive connecté en admin.

> Le repo doit être **public** pour que Playground y accède. Si la branche change (fusion dans `main`), mettre à jour les URL dans `blueprint.json`, `README.md` et `tools/demo/build.py` (`DEFAULT_BASE`).

## En local (wp-env, Docker requis)

```bash
npx wp-env start
npx wp-env run cli wp theme activate smash
npx wp-env run cli wp import wp-content/themes/smash/demo/demo-content.xml --authors=create
npx wp-env run cli wp eval-file wp-content/themes/smash/demo/setup.php
```

## Contenu de démo

- Source : `tools/demo/data.json` (provisoire, repris de la maquette).
- Génération : `python3 tools/demo/build.py` → `demo/demo-content.xml`, `demo/site.json`, `demo/media/` (étiquettes générées).
- Les images sont lues depuis `raw.githubusercontent.com` : pousser `demo/media/` avant de tester Playground.

## Où sont les choses

| Fichier | Rôle |
|---|---|
| `inc/fields-map.php` | **Le** fichier à adapter à la mise en ligne : noms des champs ACF réels, ID Gravity Forms |
| `inc/data.php` | Seule couche qui lit les champs (`smash_beer()`, `smash_beers()`, `smash_member()`, `smash_post_beers()`, `smash_option()`) |
| `inc/content-types.php` | CPT `bieres`, `membre`, `telechargement` et taxonomies, déclarés seulement s'ils n'existent pas déjà |
| `inc/options.php` | Page d'options « Infos smash » + groupes de champs démo (constante `SMASH_DEMO_FIELDS`) |
| `inc/assets.php` | CSS, polices hébergées (préchargées), GSAP / ScrollTrigger / SplitText / Lenis, `smash_page_script()` |
| `assets/css/smash.css` | CSS de la maquette |
| `assets/js/core.js` | Animations communes, désactivées si `prefers-reduced-motion` |

Templates : pour l'instant `index.php` affiche tous les contenus ; les templates dédiés arrivent aux étapes 2 à 5 (voir `PLAN.md`).

Le zip livré au client exclut les fichiers listés dans `.distignore`.
