# Thème smash : plan de projet

> Statut : **proposition commerciale**. On n'a pas encore d'accès admin à smash.bzh.
> Objectif : une démo WordPress Playground avec le **vrai contenu** de smash, dans le nouveau design. Ensuite, une mise en ligne quasi « plug and play » quand on aura l'accès.

---

## 0. Avancement (à mettre à jour à la fin de chaque session)

Une session = **une étape**. On coche, on note ce qui reste, on commit, on s'arrête.

| # | Étape | Statut | Notes |
|---|---|---|---|
| 0 | Plan, analyse du site, maquette dans `maquette/` | ✅ fait | relevé du 29/10/2026 |
| 1 | Contenu : script de scraping → `demo/` | ⬜ à faire | |
| 2a | Socle : `style.css`, `functions.php`, `inc/` de base, CSS et polices | ⬜ à faire | |
| 2b | Header (méga-menu), footer, porte d'âge | ⬜ à faire | |
| 2c | Accueil + `.wp-env.json` + `blueprint.json` | ⬜ à faire | |
| 3 | Bières : mapping, « nos bières », taxonomie, fiche, Product | ⬜ à faire | |
| 4 | Location + Gravity Forms / mode démo, FAQPage | ⬜ à faire | |
| 5a | Blog + article (BlogPosting) | ⬜ à faire | |
| 5b | Brasserie, contact, téléchargements, pages légales, plan du site, 404, recherche, README | ⬜ à faire | |
| 6 | Vérification complète + lien Playground testé | ⬜ à faire | |

**Journal des sessions** (le plus récent en haut) :
- 29/10/2026 : analyse du site public, plan, maquette ajoutée au repo.

---

## 1. Réponse courte : est-ce que ce sera plug and play ?

**Oui, presque.** Le thème est construit pour que tout ce qui dépend du site réel tienne dans **un seul fichier de mapping** (`inc/fields-map.php`).

| Ce qui marche tel quel le jour J | Ce qu'il faudra faire le jour J |
|---|---|
| CPT `bieres`, taxonomies `type-de-bieres` et `type-de-location` (mêmes slugs : déjà relevés) | Mettre les **vrais noms des champs ACF** dans `fields-map.php` (≈ 1 h avec l'export ACF) |
| Articles et bières liées (lues dans les liens du texte : rien à saisir) | Vérifier le **type** de chaque champ : image (ID ou URL), texte, liste. Le mapping sait convertir |
| Formulaire Gravity Forms n°7 (location) : ID et champs déjà relevés | Vérifier les ID des champs du formulaire 7 sur l'admin (ils peuvent avoir bougé depuis le relevé) |
| Formulaire 3 (contact) et 2 (newsletter) | Remplir **une fois** les textes des pages éditoriales (accueil, brasserie…) : on les importe depuis la démo, voir §6 |
| Rank Math (on ajoute nos schémas à son graphe) | Recréer les menus (2 min) et tester sur une préprod |

**Pourquoi il reste une étape « textes des pages »** : aujourd'hui, les pages accueil, brasserie, etc. sont des mises en page Beaver Builder. Le nouveau thème ne les lit pas : il affiche ses propres champs, calqués sur la maquette. Ces champs seront pré-remplis avec leur wording, grâce à un import, donc l'équipe n'a rien à ressaisir.

**Limite de la démo Playground** : Gravity Forms est payant et ne s'installe pas dans Playground. Dans la démo :
- le devis fonctionne (calcul, récap) ;
- l'envoi bascule en **mode démo** : la demande est enregistrée dans l'admin (CPT « Demandes (démo) »), ou on affiche simplement le message de succès ;
- les prix viennent d'une page d'options de secours.

En production, le thème détecte Gravity Forms et utilise le formulaire 7.

---

## 2. Ce qu'on sait déjà du site actuel (relevé public du 29/10/2026)

- **Stack** : Astra Pro + thème enfant `smash-child`, Beaver Builder (Themer et PowerPack), ACF, Gravity Forms, Rank Math, Search & Filter Pro, Popup Maker, Instagram Feed, WP Rocket.
- **CPT `bieres`** (171 fiches, l'éditeur n'est pas utilisé) : tout est en champs ACF. Les champs visibles sur une fiche sont :
  - bannière étiquette (`*_BANDEROLE_SITE.jpg`), photo ;
  - style, degré ;
  - formats (fût / canette) ;
  - accroche, description ;
  - malt, houblons, levure, « other » ;
  - aspect ;
  - conditions de dégustation (texte libre) ;
  - accords mets (texte libre).
- **Taxonomies** :
  - `type-de-bieres` : `bieres-permanentes` (6), `bieres-ephemeres` (38), `bieres-archivees` (127) ;
  - `type-de-location` : `particuliers`, `evenementiel`.
- **Articles** (~56) : permaliens à la racine. Les bières d'une série sont des liens `/bieres/xxx/` dans le texte.
- **CPT `membre`** (10) : l'équipe. **CPT `telechargement`** : vide en public, et la page `/telechargements/` n'affiche rien (à leur demander).
- **Pages** (slugs) : `nos-bieres`, `location-tireuses-a-bieres`, `la-brasserie`, `le-blog`, `contact`, `telechargements`, `mentions-legales`, `politique-de-confidentialite`, `sitemap`. Pages à ignorer : `accueil-2`, `nos-bieres-old`, `sitemap-2`.
- **Gravity Forms n°7 (location)** :

  | Rôle | ID des champs |
  |---|---|
  | qui (particulier / pro) | 52 |
  | nom, email, téléphone | 25, 26, 27 |
  | nom de l'évènement | 54 |
  | dates d'enlèvement / de reprise | 28 / 70 |
  | tireuse 1 bec / 2 becs | 62 (50 €) / 63 (80 €) |
  | fûts particulier : Aquatrip, Affluence, Pasadena, Weizh, SP95, Rye Bomb | 5, 7, 71, 10, 9, 8 (prix dans Gravity Forms) |
  | fûts pro (0 €, sur devis) | 55 à 60 |
  | écocups particulier 20 / 50 cl | 73 / 74 (0,30 €) |
  | écocups pro | 76 / 77 |
  | totaux | 50 / 66 |
  | précisions | 41 |

- **Prix** : ils sont déjà modifiables dans les produits Gravity Forms. En production, le thème les lit là (`GFAPI::get_form(7)`), pour n'avoir qu'une seule source. Les règles (tireuse offerte dès 3 fûts, livraison pro dès 12 fûts, caution 200 €) vont dans une page d'options « Location ».
- **Écarts maquette / réel à leur présenter** :
  - moments et accords : la maquette les montre en chips, le site a du texte libre. On affiche le texte, pour ne pas faire ressaisir 171 fiches ;
  - le matériel pro (barnums, comptoirs…) n'a pas de champ dans le formulaire 7 : il part dans « précisions » (champ 41).

---

## 3. Principe d'architecture : la couche de mapping

```php
// inc/fields-map.php : LE fichier à modifier le jour J
return [
  'beer' => [
    'label'  => [ 'key' => 'banderole',   'type' => 'image' ],   // ← vrai nom ACF à mettre
    'photo'  => [ 'key' => 'photo',       'type' => 'image' ],
    'style'  => [ 'key' => 'style',       'type' => 'text'  ],
    'abv'    => [ 'key' => 'degre',       'type' => 'number' ],
    'formats'=> [ 'key' => 'formats',     'type' => 'text'  ],
    'intro'  => [ 'key' => 'accroche',    'type' => 'text'  ],
    'desc'   => [ 'key' => 'description', 'type' => 'html'  ],
    'malts'  => [ 'key' => 'malt',        'type' => 'text'  ],
    'hops'   => [ 'key' => 'houblons',    'type' => 'text'  ],
    'yeast'  => [ 'key' => 'levure',      'type' => 'text'  ],
    'other'  => [ 'key' => 'other',       'type' => 'text'  ],
    'look'   => [ 'key' => 'aspect',      'type' => 'text'  ],
    'when'   => [ 'key' => 'degustation', 'type' => 'html'  ],
    'food'   => [ 'key' => 'accords',     'type' => 'html'  ],
  ],
  'member' => [ 'role' => …, 'bio' => …, 'photo' => … ],
  'gf_location' => [ 'form_id' => 7, 'who' => 52, 'name' => 25, … ],  // le tableau du §2
  'gf_contact' => 3, 'gf_newsletter' => 2,
];
```

- Les templates n'appellent **jamais** `get_field()` directement. Ils passent par `smash_beer( $post )`, `smash_member( $post )` et `smash_gf_location()`, qui lisent le mapping et convertissent les valeurs : une image peut arriver en ID, en URL ou en tableau.
- **Démo** : le thème déclare ses propres groupes ACF (dans `acf-json/`) avec ces mêmes noms. Le contenu de démo les remplit.
- **Jour J** :
  - on met les vrais noms dans le mapping ;
  - on désactive les groupes ACF « démo » des bières et des membres (une constante `SMASH_DEMO_FIELDS`), pour ne pas doubler leurs groupes existants ;
  - on garde les groupes propres au thème (textes des pages, options).

---

## 4. Arborescence du repo (= le thème)

```
smash/
├── style.css, functions.php, screenshot.png
├── inc/
│   ├── fields-map.php      mapping (§3)
│   ├── data.php            smash_beer(), smash_member(), smash_post_beers() (lit les liens /bieres/ du contenu)
│   ├── setup.php           supports, menus, tailles d'images (étiquette 2:1)
│   ├── assets.php          enqueue CSS/JS + JS par template + preload des polices
│   ├── options.php         options « Infos smash » (adresse, horaires, tél, mail, réseaux) + « Location » (règles, prix de secours)
│   ├── age-gate.php        porte d'âge avec cookie `smash_age` (1 an)
│   ├── location.php        rendu des tarifs depuis GF (ou les options en démo) + endpoint REST smash/v1/devis → GFAPI::add_entry ou CPT démo
│   ├── schema.php          Brewery / Product / FAQPage / BlogPosting via le filtre rank_math/json_ld (sortie autonome si Rank Math est absent)
│   └── template-tags.php   composants : label_card, kick, btn, post_row
├── header.php, footer.php  méga-menu alimenté par les bières permanentes
├── front-page.php, home.php, single.php, page.php, index.php, 404.php, search.php
├── archive-bieres.php / page-nos-bieres.php   onglets permanentes / éphémères / anciennes (rendu PHP, #permanentes et #ephemeres)
├── taxonomy-type-de-bieres.php, single-bieres.php
├── page-la-brasserie.php, page-location-tireuses-a-bieres.php, page-contact.php, page-telechargements.php, page-sitemap.php
├── template-parts/
├── acf-json/               groupes du thème (pages, options) + groupes démo (bières, membres)
├── assets/css/smash.css    CSS de la maquette extrait tel quel (sans .gate démo, .wipe, .note)
├── assets/js/vendor/       gsap, ScrollTrigger, SplitText, lenis
├── assets/js/core.js       Lenis, animations communes, inclinaison des étiquettes, méga-menu, porte d'âge, prefers-reduced-motion
├── assets/js/pages/        home.js, bieres.js, fiche.js, brasserie.js, location.js, article.js
├── assets/fonts/           Switzer, Inter Tight, JetBrains Mono (woff2 hébergés en local)
├── assets/img/             logos de la maquette
├── demo/
│   ├── demo-content.xml    WXR généré (§5)
│   ├── page-fields.json    textes des pages éditoriales (§6)
│   └── media/              images de leur site, optimisées (webp)
├── tools/scrape/           script Python qui génère demo/ depuis le site public
├── blueprint.json, .wp-env.json, .gitignore, README.md
```

Règles :
- **aucun texte en dur** dans les templates (tout passe par des champs, des options ou des menus) ;
- **un seul H1** par page ;
- des **`alt`** partout ;
- les **slugs identiques** au site actuel ;
- `wp_get_attachment_image()` pour les `srcset` et le lazy loading (hero en `fetchpriority="high"`) ;
- `prefers-reduced-motion` : pas de Lenis ni de SplitText, mur d'étiquettes figé.

---

## 5. Contenu de la démo : leur vrai contenu, aspiré du site public

Script `tools/scrape/scrape.py` (Python, à lancer une fois, puis on commit son résultat) :
1. **REST API** : articles (titre, slug, date, contenu, image à la une), termes des taxonomies, liste des bières avec leurs termes, membres, pages.
2. **HTML des fiches** (`/bieres/{slug}/`) pour les champs ACF que l'API ne montre pas : on lit chaque bloc (accroche, description, ingrédients, aspect, dégustation, accords, formats, style et degré, URL de la bannière et de la photo).
3. **HTML de `/la-brasserie/`, de l'accueil, de `/location-…/` et de `/contact/`** : leurs textes, pour pré-remplir `page-fields.json` (et donc garder leur wording).
4. **Images** : téléchargées, redimensionnées et converties en webp, puis rangées dans `demo/media/`. Volume visé : moins de 40 Mo.
   - On prend toutes les images des permanentes, des éphémères et des membres, et les bannières des archivées.
   - Si c'est trop lourd, on ne garde que les bannières des archivées.
5. **Génération** :
   - du WXR `demo/demo-content.xml` : mêmes slugs, mêmes termes, pièces jointes pointant vers `raw.githubusercontent.com/.../demo/media/…` ;
   - des metas ACF au format du thème ;
   - des bières liées, que le thème retrouve tout seul grâce aux liens du texte.

**Blueprint Playground** :
- installe le thème (depuis le repo GitHub) et le plugin **Secure Custom Fields** (gratuit, avec pages d'options, compatible avec l'API ACF) ;
- importe `demo-content.xml` et `page-fields.json` ;
- règle les permaliens `/%postname%/`, la page d'accueil statique et la page des articles `le-blog` ;
- connecte en admin automatiquement et ouvre l'accueil.

Lien : `https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/matmaaar/smash-template/main/blueprint.json`

⚠️ **Le repo doit être public** pour que Playground y accède. Comme il contiendra leurs textes et leurs images, on le rend public le temps du rendez-vous, ou on héberge seulement le zip du thème et le contenu de démo ailleurs (une release GitHub publique). À trancher avant la démo.

---

## 6. Jour J (mise en ligne sur le vrai site)

1. Récupérer l'export ACF, puis mettre les vrais noms dans `inc/fields-map.php` et vérifier les ID du formulaire 7.
2. Préprod (copie du site) : installer le thème, `SMASH_DEMO_FIELDS = false`.
3. Commande WP-CLI `wp smash import-page-fields demo/page-fields.json` : elle pré-remplit les textes des pages éditoriales et les options (adresse, horaires, réseaux).
4. Créer les menus et vérifier chaque URL du sitemap, un devis test (une entrée doit apparaître dans le formulaire 7 et la notification partir) et les données structurées.
5. Basculer en production. Les redirections `/nos-bieres-old/` et `/sitemap-2/` se font dans Rank Math (module Redirections). Beaver Builder et Astra peuvent rester installés pendant la transition.

---

## 7. Étapes de développement (un commit par étape)

1. **Contenu** : script de scraping, puis `demo/` généré et relu (on vérifie le wording et les images).
2. **Socle, header, footer, accueil** :
   - `inc/*` de base, CSS et polices, porte d'âge, méga-menu ;
   - `front-page.php` avec la dernière série (dernier article et ses bières liées) et le mur d'étiquettes ;
   - `.wp-env.json` et `blueprint.json`.
3. **Bières** : mapping et `smash_beer()`, page « nos bières » avec ses onglets, taxonomie, fiche, JSON-LD Product.
4. **Location** : tarifs rendus en HTML (Gravity Forms ou options), `location.js` sans valeur en dur, endpoint REST (Gravity Forms ou mode démo), FAQ et FAQPage.
5. **Le reste** : blog, article (BlogPosting), brasserie (membres), contact (Gravity Forms 3 ou formulaire démo), téléchargements, pages légales, plan du site, 404, recherche, README.

## 8. Vérification

- `wp-env` avec import de la démo, puis un `curl` sur chaque URL du sitemap, sur `/bieres/dolmen/` et sur `/type-de-bieres/*` : réponse 200, aucune notice avec `WP_DEBUG`, un seul `<h1>` par page.
- Playwright :
  - captures comparées à la maquette ;
  - console sans erreur ;
  - parcours complet du devis ;
  - test avec `reducedMotion: 'reduce'`.
- JSON-LD extrait des pages et validé.
- Test réel du lien Playground depuis GitHub.

## 9. Questions à poser au client au rendez-vous

- Que mettre dans « téléchargements » ? (Le CPT existe mais il est vide.)
- Accords et moments : on garde leur texte, ou ils veulent des chips (il faudrait alors une saisie) ?
- Newsletter : on garde le formulaire Gravity Forms 2 avec particulier / pro, ou seulement l'email comme dans la maquette ?
- Contenu de l'Instagram : le plugin Instagram Feed actuel, ou des images choisies à la main ?
