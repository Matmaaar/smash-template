# Optimisations : sécurité, vitesse, code (04/11/2026)

Ce document suit l'audit du 04/11/2026. Il dit ce qui a été changé, pourquoi, et ce qui reste à faire.
**Le contenu de démo n'a pas été touché** (`demo/`, `demo/setup.php`, `tools/demo/`), à la demande de l'utilisateur.

## En bref

| Page (connecté en admin, local) | Requêtes SQL avant | après | Temps PHP avant | après |
|---|---:|---:|---:|---:|
| Accueil `/` | 213 | **90** | 1 184 ms | 645 ms |
| Catalogue `/nos-bieres/` | 458 | **73** | 2 009 ms | 884 ms |
| Blog `/le-blog/` | 278 | **59** | 1 219 ms | 692 ms |
| La brasserie | 161 | **75** | 888 ms | 698 ms |
| Location | 138 | **65** | 940 ms | 640 ms |
| Contact | 137 | **49** | 925 ms | 631 ms |
| Fiche bière | 138 | **56** | 940 ms | 623 ms |
| Article | 143 | **62** | 927 ms | 759 ms |

Accueil : **252 → 120 images** dans le HTML, **154 → 89 Ko** de HTML.

Mesures prises sur WordPress Playground en local (WP 6.5, PHP 8.3, sans cache d'objets, admin connecté,
contenu de démo complet avec ses 286 images), 2e chargement de chaque page. Les temps de Playground sont lents
dans l'absolu : c'est l'écart qui compte.

Vérifié après les changements : les 19 URL du sitemap répondent (200, 404 sur la page inconnue), un seul H1 par page,
aucune notice PHP du thème. Dans un vrai navigateur : devis envoyé, contact envoyé, mode pro, méga-menu, mur d'étiquettes.
Sur les 5 pages comparées ligne à ligne (contact, catalogue, blog, fiche bière, location), le HTML est le même
qu'avant, à part les classes qui remplacent les `style=""` et les attributs des formulaires.

---

## 1. Sécurité

### Formulaires (devis de location et contact) : nouveau fichier `inc/forms.php`

**Avant** : la protection reposait sur un nonce écrit dans le HTML et un champ piège. Pour un visiteur anonyme,
ce nonce est le même pour tout le monde : un robot le lit dans la page et peut envoyer des demandes à volonté.
Chaque envoi déclenche les e-mails de Gravity Forms. Et avec un cache de page, le nonce en cache expire
(12 à 24 h) : tous les visiteurs voyaient alors « La page a expiré ».

**Maintenant :**
- **Jeton demandé au moment de l'envoi**, par la route `GET /wp-json/smash/v1/jeton?form=devis|contact`
  (réponse `Cache-Control: no-store`). Plus rien de périssable dans le HTML : **les pages peuvent être mises en cache
  sans limite de durée**. L'en-tête `X-WP-Nonce` n'est plus envoyé : WordPress traite la demande et l'envoi
  en visiteur anonyme, le jeton correspond donc toujours (même pour un admin connecté).
- **Limite d'envois par adresse IP** : 5 envois par 10 minutes et par formulaire (réglable avec les constantes
  `SMASH_FORM_RATE` et `SMASH_FORM_WINDOW`), 30 jetons par 10 minutes. Au-delà : erreur 429 avec un message clair.
  Derrière Cloudflare ou un proxy, brancher le filtre `smash_client_ip` pour lire la vraie IP.
- **Longueur maximale des champs** : nom 100, e-mail 254, téléphone 30, évènement 150, précisions 3 000,
  message 5 000 caractères (filtre `smash_form_limits`). Avant : aucune limite.
- **Cloudflare Turnstile (anti-robot), en option** : il s'active seul si les deux clés sont dans `wp-config.php` :
  ```php
  define( 'SMASH_TURNSTILE_SITE_KEY', '…' );
  define( 'SMASH_TURNSTILE_SECRET', '…' );
  ```
  Le widget apparaît dans les deux formulaires du thème, et le script de Cloudflare n'est chargé que sur ces pages.
  Sans les clés, rien ne change.
- Le champ piège `website` reste, et il est testé en premier.

### Devis : contrôles côté serveur (`inc/location.php`)
- **Dates vérifiées par le serveur** (avant : seulement en JavaScript) : date valide, jour de retrait et de retour
  autorisés (page d'options « Location »), retrait pas dans le passé, retour après le retrait.
- **Les erreurs de Gravity Forms ne sont plus montrées au visiteur** (elles révélaient des ID de champs et des
  messages internes). Il voit un message simple ; le détail va dans `debug.log` si `WP_DEBUG_LOG` est actif.

### Données structurées (`inc/schema.php`)
- JSON-LD encodé avec `JSON_HEX_TAG` : un `</script>` dans un champ (nom du site par exemple) ne peut plus
  fermer la balise et injecter du code.

### Vérification automatique : `phpcs.xml.dist`
Règles WordPress de sécurité (échappement, nonces, entrées), base de données, traductions, fonctions obsolètes,
compatibilité PHP 8.0+. Lancement : `phpcs` à la racine du thème (installation dans le fichier).
Résultat : **0 erreur**. Les 25 « sorties non échappées » signalées au départ étaient toutes des faux positifs
(fonctions du thème qui échappent déjà, nombres) : les fonctions sont déclarées dans la config et les cas restants annotés.
Il reste 35 avertissements « commentaire pour les traducteurs manquant » (sans effet sur la sécurité).

---

## 2. Vitesse

### Images chargées une par une → en une fois (`inc/data.php`)
C'était la plus grosse source de requêtes : chaque étiquette affichée coûtait 2 requêtes (l'image, puis ses
métadonnées). Nouvelle fonction `smash_prime_images()` : toutes les images d'une liste de bières sont chargées
en une seule fois. Appelée par `smash_beers()` et `smash_post_beers_many()`.

### Bières citées dans les articles
- **Avant** : une requête par bière citée (`get_page_by_path()` dans une boucle), répétée pour chaque article.
- **Maintenant** : `smash_post_beers_many( $articles )` charge les bières de **tous les articles d'une liste en une
  seule requête** (liste du blog, aperçu du méga-menu). `smash_post_beers()` passe par elle et garde le même
  résultat, dans l'ordre du texte. La normalisation des slugs est celle de WordPress (`/bieres/moonspell-/`
  trouve bien « moonspell »).

### Méga-menu mis en cache
Les aperçus du menu (bières, brasserie, blog, contact, téléchargements) étaient reconstruits **à chaque page vue**
(~60 requêtes). Ils ne dépendent pas de la page affichée : ils sont maintenant gardés en cache 12 h
(`smash_cached_html()`). **Le cache est vidé automatiquement** à chaque modification de contenu : enregistrement,
mise à la corbeille ou suppression d'un contenu, d'une image ou d'un terme, menu modifié, options ACF enregistrées,
changement de page d'accueil, de format de date ou de nom du site. Pour le désactiver (débogage) :
`define( 'SMASH_NO_CACHE', true );`.

### Listes de bières mémorisées
`smash_beers()` garde son résultat pendant la requête : le méga-menu, l'accueil et le catalogue demandaient
plusieurs fois les mêmes listes.

### Formulaire Gravity Forms lu une seule fois
- `smash_gf_location_form()` est mémorisé pendant la requête.
- Le type « Demandes (démo) » ne lit plus le formulaire Gravity Forms à chaque page publique (seulement dans l'admin).
- Prix des fûts (production) : les 6 bières sont chargées en une requête au lieu de 6.

### Mur d'étiquettes de l'accueil (`front-page.php`, `assets/js/pages/home.js`)
- **216 → 84 tuiles.** Avant, chaque rangée reprenait les 36 bières deux fois. Maintenant, chaque bière est dans
  une seule rangée, répétée juste assez pour couvrir un écran 4K, puis doublée pour la boucle. Le rendu est le même.
- **Accessibilité** : chaque bière du mur a un vrai lien (36), les répétitions sont cachées aux lecteurs d'écran.
  Avant : 36 liens, mais seulement 12 bières différentes, chacune répétée 3 fois.
- **L'animation s'arrête quand le mur n'est pas à l'écran** (`IntersectionObserver`). Avant, elle tournait en
  permanence, même en bas de page (CPU et batterie sur mobile).
- La largeur des rangées est mesurée une fois (puis au redimensionnement et au chargement des images ou des
  polices), et non plus à chaque image de l'animation. Ça évite de recalculer la mise en page 60 fois par seconde.

### Cache navigateur (`inc/assets.php`)
Les URL des CSS et JS portent maintenant la date de modification du fichier (`?ver=0.1.0.1730000000`).
Avant, la version restait `0.1.0` : après une mise à jour, les navigateurs et CDN pouvaient garder l'ancien fichier.

### Police inutilisée supprimée
`switzer-latin-900-normal.woff2` était déclarée mais **jamais utilisée** (aucun texte en graisse 900) : fichier et
`@font-face` supprimés. Les graisses 500 et 600 sont gardées : elles servent.

### Images en WebP (`inc/setup.php`)
Les tailles générées à l'envoi d'un JPEG (vignettes, étiquettes, etc.) sont enregistrées en WebP, 25 à 35 % plus
légères. WordPress garde le JPEG si le serveur ne sait pas écrire le WebP. **Ne concerne que les nouveaux envois** :
pour les images déjà en ligne, lancer `wp media regenerate` ou l'extension « Regenerate Thumbnails ».
Le fichier d'origine (taille « full », utilisée par le hero) reste en JPEG : envoyer un hero en WebP ou AVIF.

---

## 3. Code

- **Bug corrigé** : en production, la note envoyée à Gravity Forms disait « tireuse offerte (**3** fûts ou plus) »
  en dur, même si le seuil était changé dans les options. Elle lit maintenant le réglage.
- **Bug corrigé** : en mode « pro / asso », les tireuses du mode particulier restaient affichées. `style="display:grid"`
  sur le bloc écrasait l'attribut `hidden`. Corrigé en CSS (`.part-only[hidden], .pro-only[hidden]`), vérifié
  dans le navigateur.
- **Slugs des pages clés regroupés** dans `inc/fields-map.php` (nouvelle section `pages` : catalogue, location,
  brasserie, contact, téléchargements), lus par `smash_page()` / `smash_page_slug()`. Si le slug d'une page change
  sur le vrai site, on le change là et le bon template est choisi quand même (filtre `page_template_hierarchy`
  dans `inc/setup.php`).
- **`style=""` des templates remplacés par des classes** (`.sec--flush`, `.sec--catalog`, `.head--wall`,
  `.prose--after`, `.lab--static`, `.faq--page`). Il reste deux styles calculés (position de la pile d'étiquettes,
  nombre de colonnes `--n`) : c'est normal.
- Image de section de l'accueil : la fonction anonyme `$smash_img` devient `smash_front_img()` (`inc/template-tags.php`).
- `tools/scrape/__pycache__/` retiré du repo et ajouté au `.gitignore`.
- `OPTIMISATIONS.md` et `phpcs.xml.dist` exclus du zip livré (`.distignore`).

---

## 4. Pas fait, et pourquoi

| Point de l'audit | Décision |
|---|---|
| `demo/setup.php` appelable depuis le web | **Laissé tel quel**, à la demande de l'utilisateur. Il est exclu du zip (`.distignore`) : **ne jamais déployer le thème par git ou FTP avec le dossier `demo/`**. |
| Demandes de démo (RGPD) | Démo uniquement : non traité. En production, les demandes vont dans Gravity Forms (durée de conservation à régler dans GF). |
| Charger GSAP, ScrollTrigger, SplitText et Lenis seulement là où ils servent | Vérifié : **toutes les pages** utilisent les animations communes (titres, apparitions, méga-menu). Il faudrait revoir l'ordre de chargement de tous les scripts de page, pour un gain réservé aux visiteurs en « mouvement réduit ». Pas rentable pour l'instant. |
| Couleur du logo (`elementsFromPoint` au scroll) | Déjà limité à une mesure par image (`requestAnimationFrame`). Le remplacer demanderait d'annoter chaque section avec sa couleur. À faire si un profilage mobile montre un ralentissement. |
| `backdrop-filter: blur` de la nav | Choix de design : gardé. À réduire sur mobile si ça rame sur les Android d'entrée de gamme. |
| Test automatique « le devis JS = le devis PHP » | Pas encore fait. Les deux calculs n'ont pas été modifiés. |
| Fichiers vendor en double dans `maquette/js/` | Gardés : la maquette doit rester ouvrable seule. Elle est exclue du zip. |

## 5. À vérifier le jour de la mise en ligne

1. **Cloudflare Turnstile** : créer un widget (gratuit) et mettre les deux clés dans `wp-config.php`.
2. **Derrière Cloudflare** : brancher `smash_client_ip` pour lire `CF-Connecting-IP`, sinon la limite par IP
   s'applique à l'IP de Cloudflare :
   ```php
   add_filter( 'smash_client_ip', fn( $ip ) => $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $ip );
   ```
   À ne faire **que** si le serveur n'accepte que le trafic venant de Cloudflare (sinon l'en-tête peut être falsifié).
3. **Gravity Forms** : `GFAPI::submit_form()` (utilisé par le devis) ne passe sans doute pas par le reCAPTCHA,
   le honeypot ni l'Akismet du formulaire GF. La protection du devis est donc celle du thème (jeton, limite par IP,
   Turnstile). Le contact de production reste le formulaire GF n° 3, avec ses propres protections.
4. **Cache de page** (WP Rocket ou autre) : plus de contrainte de durée de vie pour les formulaires. Ne pas mettre
   en cache les routes `/wp-json/smash/v1/*` (WP Rocket ne le fait pas par défaut).
5. Après l'import des images : `wp media regenerate` pour avoir les tailles en WebP.
