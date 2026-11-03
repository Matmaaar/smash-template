# Thème smash : plan de projet

> Statut : **proposition commerciale**. On n'a pas encore d'accès admin à smash.bzh.
> Objectif : une démo WordPress Playground avec le **vrai contenu** de smash, dans le nouveau design. Ensuite, une mise en ligne quasi « plug and play » quand on aura l'accès.

---

## 0. Avancement (à mettre à jour à la fin de chaque session)

Une session = **une étape**. On coche, on note ce qui reste, on commit, on s'arrête.

**Ordre validé le 29/10** : la base Playground d'abord, le scraping à la fin. Le design va encore bouger, donc on veut vite une base solide sur Playground pour itérer dessus. Le contenu réel ne dépend pas du design et vient en dernier.

Dès que le thème existe, **on itère le design directement dans le thème**. `maquette/` devient une référence figée, pour ne pas maintenir deux versions.

Contenu de démo provisoire : celui de la maquette (tableaux JS `PERM`, `EPH`, `OLD`, `POSTS`, `TEAM`, `KEGS`, plus les textes et `maquette/img/`), converti en un petit WXR avec les vrais slugs. Il couvre 6 permanentes, quelques éphémères et anciennes, environ 5 articles, les 10 membres et toutes les pages du sitemap.

| # | Étape | Statut | Notes |
|---|---|---|---|
| 0 | Plan, analyse du site, maquette dans `maquette/` | ✅ fait | relevé du 29/10/2026 |
| 1 | **Socle + pipeline Playground** : `style.css`, `functions.php`, `inc/` de base (setup, assets, fields-map, data, options), CSS et polices, `index.php`, petit WXR de démo tiré de la maquette, `blueprint.json`, `.wp-env.json`. Objectif : le thème s'active sur Playground, toutes les URL du sitemap répondent | ✅ fait | Testé en local (Playground CLI, WP 6.5) : les 20 URL testées répondent (200, 404 sur une page inconnue), 1 seul H1, aucune notice du thème. Lien Playground en ligne validé par l'utilisateur dans Edge le 29/10 (images importées depuis raw.githubusercontent.com OK). Le WordPress local n'a pas de réseau : les images n'y sont pas importées, c'est normal. Choix : groupes ACF démo générés en PHP depuis `fields-map.php` (pas d'`acf-json/`, une seule source) ; CPT/taxos déclarés seulement s'ils manquent ; polices Switzer + JetBrains Mono hébergées (Inter Tight abandonnée, simple police de secours). Provisoire : `hero.jpg` et `can-affluence.jpg` sont encore référencés dans le CSS → à passer en champs à l'étape 2. Header/footer = version de base (méga-menu et CTA « devis tireuse » à l'étape 2). |
| 2 | Header (méga-menu), footer, porte d'âge, accueil | ✅ fait | `front-page.php` (7 sections de la maquette), groupe ACF « Accueil » (`smash_front_fields()` dans `inc/options.php`, lu par `smash_front()`), `inc/menus.php` (classe `cta` = bouton du header, page `nos-bieres` ou classe `mega` = méga-menu), `inc/age-gate.php` (cookie `smash_age`, classes `no-js` / `age-ok` posées dans le `<head>`, compatible avec le cache de page), `assets/js/core.js` (porte d'âge, méga-menu, `smash.bindTilt()`), `assets/js/pages/home.js`. Les étiquettes sont des `<img>` (srcset, lazy) via `smash_label_img()` / `smash_label_card()`. Testé en local : 200 partout, un seul H1, porte d'âge + cookie + méga-menu OK, y compris en `reducedMotion`. Les micro-libellés d'interface (« la gamme », « la fiche »…) sont des `__()`. À voir aux itérations : liens `#permanentes` / `#ephemeres` / `#anciennes` du méga-menu à brancher à l'étape 3 ; colonne newsletter visible seulement si Gravity Forms (form 2). |
| 3 | Bières : « nos bières », taxonomie, fiche, Product | ✅ fait | `page-nos-bieres.php` + `taxonomy-type-de-bieres.php` partagent `template-parts/beer-catalog.php` : 3 listes rendues en PHP (SEO, sans JS = listes à la suite), onglets ARIA (clic, flèches, Début / Fin), ancres `#permanentes` / `#ephemeres` / `#anciennes` (liens du méga-menu), anciennes : 6 visibles puis « voir plus » +12 (non testé en démo : seulement 6 anciennes). `single-bieres.php` : seules les lignes remplies s'affichent ; bouton « en fût pour ta soirée » si la bière a un terme `type-de-location` (**prix à ajouter à l'étape 4**). Nouveau groupe ACF « En-tête de page » (`intro_kick` / `intro_title` / `intro_text`, `template-parts/page-head.php`) réutilisable à l'étape 5. `inc/schema.php` : Product (ajouté au graphe de Rank Math s'il est actif). Testé en local : 200, un seul H1, onglets et ancres OK, y compris en `reducedMotion`. |
| 4 | Location (mode démo et Gravity Forms), FAQPage | ✅ fait | `inc/location.php` : `smash_rental()` (prix lus dans les produits du formulaire GF 7 si présent, sinon champs démo `prix_fut` / `volume_fut` des bières + options), `smash_rental_quote()` (calcul côté serveur, mêmes règles que le JS), route REST `smash/v1/devis` (nonce + honeypot, `GFAPI::submit_form` → notifications et exports inchangés ; en démo : CPT privé « Demandes de devis (démo) »). Sous-page d'options « Location » (règles, jours, matériel pro, message, prix de secours). `page-location-tireuses-a-bieres.php` : tous les tarifs dans le HTML ; FAQ = contenu de la page en blocs « Détails » → FAQPage. `location.js` sans aucune valeur en dur. Fiche bière : « en fût pour ta soirée · 90 € ». Testé : envoi en démo (total recalculé OK) et **avec un faux GFAPI** (valeurs `input_*` vérifiées en particulier et en pro, dates jj/mm/aaaa, prénom / nom, précisions), erreurs 422 / 403, parcours navigateur complet y compris `reducedMotion`. **Jour J** : vérifier les slugs des 6 bières et les ID dans `fields-map.php` (`gf_location`), le format de date des champs 28 / 70, et que le total GF (champs 50 / 66) se calcule bien. Le cache de page (WP Rocket) doit garder une durée de vie inférieure à 12 h (jeton de la page). |
| 5a | Blog, article, la brasserie + équipe | ✅ fait | `home.php` (en-tête de la page des articles + lignes `.post-row` avec mini-étiquettes des bières citées, pagination), `single.php` (date, titre, cartes des bières citées via `smash_post_beers()`, contenu en largeur de lecture, article précédent / suivant), `page-la-brasserie.php` (photo, grande phrase mot à mot, 3 chiffres animés, 3 valeurs, 4 machines, équipe = CPT `membre`, ancre `#la-team`) + groupe ACF « La brasserie » (`smash_brew_fields()`, sans répéteur, rattaché à la page `la-brasserie`). `brasserie.js`, `article.js`, BlogPosting dans `inc/schema.php`. Testé : 200, un seul H1, 9 articles, 4 cartes, 3 / 3 / 4 / 10 blocs, pas de débordement mobile, `reducedMotion` OK. |
| 5b | Contact, téléchargements, pages légales, plan du site, 404, recherche + JSON-LD Brewery sur tout le site | ✅ fait | `page-contact.php` (coordonnées depuis « Infos smash » + formulaire Gravity Forms 3 s'il existe, sinon formulaire démo → REST `smash/v1/contact`, `inc/contact.php`, messages dans « Demandes (démo) »), `page-telechargements.php` (CPT `telechargement` + champ fichier `fichier` dans `fields-map.php` ; démo : 3 PDF d'exemple + zip des logos générés par `build.py`), `page.php` (pages légales), `page-sitemap.php` (pages, bières par gamme, articles, généré automatiquement), `404.php`, `search.php` + `searchform.php`. Brewery (adresse découpée, téléphone, e-mail, réseaux) dans `inc/schema.php`. Testé : 200 partout, un seul H1, envoi du contact démo OK, pas de débordement mobile. **Les téléchargements ne s'affichent pas en local** (pas de réseau pour importer les fichiers) : à vérifier sur Playground. |
| 6 | *Itérations design* (autant de sessions que nécessaire) | ⬜ à faire | Inertie du scroll : constante `SCROLL_LERP` en tête de `assets/js/core.js` (0,16 depuis le 30/10 ; maquette : 0,09). La « goutte » de la maquette est supprimée (30/10) : `.arw` (flèche) dans les boutons et liens, `.mark` (petit bloc aux coins asymétriques) devant les sur-titres et dans la liste des valeurs (brasserie, étape 5), mini logo `.S` sur le rail de scroll, rien dans les messages du ticket. **Échelle typographique** (02/11, passe « finesse », environ −35 %) : variables `--fs-hero`, `--fs-h1`, `--fs-h2`, `--fs-h2-sm`, `--fs-lead`, `--fs-card`, `--fw-display` (700) en tête de `smash.css` = le seul endroit à régler. Étiquettes en 4 colonnes (catalogue), permanentes de l'accueil sur une ligne (`.labels--row`), mur et pile plus petits, sections et boutons resserrés, `main.page{overflow-x:clip}` (débordement mobile de la pile animée). 02/11 : jambages des titres coupés pendant l'animation corrigés (fenêtres de SplitText agrandies de .18em vers le bas, puis découpe annulée à la fin de l'animation) ; CTA du header = petit bouton compact (plus de bloc pleine hauteur) ; plus de flèches dans les boutons (seul l'indice « la fiche → » au survol des étiquettes reste) ; plus de bouton dans le hero de la démo (le champ reste, vide = pas de bouton). 02/11 : plus de « marche » en escalier entre les sections (`.stepped` / `--step` supprimés ; les classes restent dans le HTML sans effet) ; permanentes de l'accueil en 4 colonnes (`.labels--row`, 4 + 2 avec 6 bières ; 3 colonnes = 3 + 3 si besoin). 02/11 : **nav qui se cache en descendant et revient en remontant** (`core.js`, classe `nav-hidden` sur `body`, seuils `SHOW_UNDER` 100 px / `SENSITIVITY` 6 px, jamais cachée si le méga-menu est ouvert ou si le focus est dedans) ; **bouton « devis tireuse » flottant** en bas à droite (`#fab-cta` dans `header.php`), visible seulement quand la nav est cachée, absent de la page location. 02/11 : rail de scroll vertical (`.pipe` + mini S) supprimé. 02/11 : CTA plus visible **sans couleur** (choix de l'utilisateur : la DA reste en noir et blanc). Bouton flottant blanc en `mix-blend-mode: difference` : noir sur les sections claires, blanc sur les noires, plus grand, « pop » à la 1re apparition (animation CSS `scale`, classe `.pop`). Bouton de la nav plus grand et en graisse 800, avec un double contour (`outline` décalé). 02/11 : **fiche bière hiérarchisée et visible sans scroller** (1440×900 : dernière info à 738 px) : nom > accroche > 3 infos clés `.fiche__key` (degré en plus gros, style, formats) > ingrédients (`.is-ingr`, 1rem / 500) > textes longs (`.is-text`, .92rem, gris) dans une grille de 2 colonnes `.fiche__grid` ; bouton « en fût » sous l'étiquette (sur mobile : après l'accroche) ; marge haute réduite. 02/11 : **nouvelle nav flottante** : plus de barre noire. Logo seul à gauche, **hors du header** (`#logo`, `mix-blend-mode: difference`, qui ne marche pas à l'intérieur du header) ; liens dans une **pilule centrale** en verre dépoli (`backdrop-filter`, coins asymétriques) ; « devis tireuse » dans une petite pilule à droite. Le méga-menu est un panneau arrondi centré sous la pilule (largeur fixe, parce que `backdrop-filter` en fait le bloc conteneur). Mobile ≤ 860 px : bouton « menu » → **menu plein écran** `#mnav` (liens en très gros, Échap pour fermer, focus géré). 02/11 : logo en **blanc franc** sur sa propre pilule sombre (plus de `mix-blend-mode`, couleur jugée bizarre) ; liens de la nav en blanc (#fff) ; « devis tireuse » = bouton blanc seul, sans cadre autour (ombre douce pour les fonds clairs) ; bouton « menu » mobile sur sa propre pilule. 02/11 : plus de pilule derrière le logo : **le logo passe blanc ou noir selon ce qu'il y a derrière lui** (`core.js`, `elementsFromPoint` + luminance du fond, classe `.is-dark`, recalcul au scroll) ; nav un peu plus grande (hauteur 56 px, liens 1.05rem, logo 1.45rem). 03/11 : **plus de texte forcé en minuscules** (29 `text-transform:lowercase` et les `mb_strtolower` supprimés ; 124 chaînes d'interface et les textes de démo en casse de phrase ; seuls les fragments insérés en milieu de phrase restent en minuscules : « fût(s) », jours, « éphémère » et « tireuse 1 bec » dans le ticket) ; **cartes de l'équipe** : grande photo 4:5, puis nom, poste et texte, toutes de même taille (`grid-auto-rows:1fr`) et alignées, logo S si pas de photo ; permanentes de l'accueil en **3 colonnes**, titre « Nos bières permanentes ». Équipe (03/11) : cartes dépliables, JS dans `brasserie.js` (sans JS, tout est ouvert). Nav (03/11) : un aperçu par lien (`smash_panel_*` dans `inc/menus.php`), choisi selon le slug de la page visée ; un panneau sans contenu = lien simple (ex. téléchargements sans fichier). |
| 7 | **Scraping du contenu réel** (§5) : remplace le WXR provisoire, avec le même format et les mêmes slugs | ✅ fait | `tools/scrape/scrape.py` (03/11) : 171 bières (6 / 38 / 127 ; toutes avec leur étiquette du catalogue `/nos-bieres/` depuis le 03/11 ; `.lab__ph` reste en secours si une bière n'a pas d'étiquette), 56 articles (contenu réel, image à la une, liens internes rendus relatifs dans `build.py`), 10 membres (lus sur /la-brasserie/ : 2 fiches `membre` ont un titre vide dans leur admin), textes de présentation et valeurs de la brasserie. Images en WebP dans `demo/scraped/` (17 Mo), servies depuis GitHub comme `demo/media/`. Plages d'ID du WXR revues (bières 1000, articles 1300, équipe 1400, téléchargements 1450, pages 1500, médias 2000+). Le reste (accueil, location, stats et machines de la brasserie, téléchargements) garde les textes de la maquette (absents de leur site). Repo laissé public (OK de l'utilisateur, site d'un ami). **À vérifier sur Playground** : durée de l'import (224 images). |
| 8 | Vérification complète (§8) + README + lien Playground final | ⬜ à faire | |

**À faire plus tard (idées validées par l'utilisateur le 03/11, pas encore commencées)** :
1. **Smash sessions** (vidéos YouTube « boiler room » à la brasserie) : CPT « Smash sessions » (titre, artiste, lien YouTube collé, miniature récupérée automatiquement) ; section noire sur l'accueil (1 grande vidéo + 2 ou 3 petites, bouton vers la chaîne) ; **lecteur chargé seulement au clic** (miniature + bouton lecture, `youtube-nocookie.com`, pas de cookie avant le clic) ; JSON-LD VideoObject ; plus tard, une page `/sessions/` éventuelle, et un aperçu de la dernière session dans le panneau « la brasserie ». **Il manque le lien de la chaîne ou 3 ou 4 liens de vidéos** (YouTube inaccessible depuis le conteneur) : sinon, vidéos factices dans la démo.

**Journal des sessions** (le plus récent en haut) :
- 03/11/2026 : en-tête de page avec photo de fond (nouveau champ facultatif « Image de fond » du groupe « En-tête de page ») ; la brasserie reprend la photo du hero de leur site (`brasserie-smash-ensemble.jpg`, cachée dans le CSS de Beaver Builder) et la photo `machine1.jpg` pour la section brew (au lieu d'une image de la maquette). `build.py` accepte `img:scraped/…` dans `page_fields`.
- 03/11/2026 : étiquettes corrigées : on prend maintenant les visuels « à plat » du catalogue `/nos-bieres/` (pages `paged-2/N` et `paged-3/N`) pour les 171 bières, au lieu de l'image des fiches (souvent une canette) ; les canettes des éphémères sont gardées en `photo`. Format des étiquettes : 7:4 (tailles `smash-label` 1200×686).
- 03/11/2026 : équipe en cartes dépliables (photo encadrée, nom + poste, bouton + pour la présentation), photo de Porter (le chat), textes EasyBrau et centrifugeuse dans « Les machines » (champs « Texte long 1 / 2 »), scraper corrigé (la présentation de Porter avalait la section suivante) ; aperçus dans la nav pour la brasserie, le blog, contact et téléchargements (`smash_nav_item()` dans `inc/menus.php`, classe `no-apercu` pour en retirer un).
- 03/11/2026 : majuscules en début de phrase partout, cartes de l'équipe refaites, permanentes de l'accueil en 3 colonnes (« Nos bières permanentes »).
- 03/11/2026 : étape 7 faite : vrai contenu de smash.bzh dans la démo (bières, articles, équipe, images).
- 03/11/2026 : plan de l'étape 7 (scraping) précisé et validé ; à lancer dans une nouvelle session.
- 03/11/2026 : idées notées pour plus tard : aperçus dans la nav pour chaque lien, section Smash sessions (vidéos YouTube).
- 03/11/2026 : étape 5b faite : toutes les pages du sitemap ont maintenant leur gabarit.
- 03/11/2026 : étape 5 découpée en 5a / 5b ; 5a faite (blog, article, brasserie + équipe).
- 02/11/2026 : logo sans pilule, blanc ou noir automatiquement selon le fond ; nav agrandie.
- 02/11/2026 : nav plus lumineuse (liens blancs), logo blanc franc sur pilule, bouton devis sans cadre.
- 02/11/2026 : nav flottante centrée (pilule en verre dépoli sur l'image), logo qui s'inverse selon le fond, menu mobile plein écran.
- 02/11/2026 : fiche bière refaite (hiérarchie typographique en 5 niveaux, tout visible sans scroller, bouton « en fût » sous l'étiquette).
- 02/11/2026 : CTA « devis tireuse » plus visible (bouton flottant qui s'inverse selon le fond, nav avec double contour), sans couleur.
- 02/11/2026 : rail de scroll vertical supprimé.
- 02/11/2026 : nav cachée au scroll vers le bas, bouton devis flottant en bas à droite.
- 02/11/2026 : marche entre les sections supprimée, étiquettes de l'accueil en 4 colonnes.
- 02/11/2026 : jambages coupés (bug d'animation) corrigés, CTA du header compact, flèches retirées des boutons, bouton du hero retiré.
- 02/11/2026 : passe « finesse » (tout était trop gros) : échelle typographique centralisée, graisse 700, étiquettes plus petites ; débordement mobile de l'accueil corrigé.
- 30/10/2026 : goutte remplacée par une flèche, un bloc aux coins asymétriques et le mini logo S, à la demande de l'utilisateur.
- 30/10/2026 : scroll moins « mou » (Lenis `lerp` 0,09 → 0,16), à la demande de l'utilisateur.
- 30/10/2026 : étape 4 faite (location : devis en direct, envoi vers Gravity Forms ou mode démo, FAQPage).
- 29/10/2026 : étape 3 faite (catalogue en onglets, pages de gamme, fiche bière, JSON-LD Product).
- 29/10/2026 : étape 2 validée sur Playground par l'utilisateur (front + admin OK, images OK).
- 29/10/2026 : étape 2 faite (header + méga-menu, footer, porte d'âge, accueil).
- 29/10/2026 : Playground testé par l'utilisateur, il marche dans Edge. Il plante dans son Firefox, y compris sans le thème : c'est un réglage du navigateur.
- 29/10/2026 : étape 1 faite (socle du thème, contenu de démo tiré de la maquette, blueprint Playground, wp-env).
- 29/10/2026 : nouvel ordre des étapes (base Playground d'abord, scraping à la fin).
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

## 7. Étapes de développement

Voir le tableau de la section 0 : il fait référence pour l'ordre et le statut.

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
