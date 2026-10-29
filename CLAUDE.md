# Projet smash : consignes pour chaque session

Thème WordPress classique (PHP) pour smash.bzh, à partir de la maquette dans `maquette/`.
Tout le contexte est dans **`PLAN.md`** : le lire en entier avant de faire quoi que ce soit.

## Règles de travail
- **Une session = une seule étape** du tableau « 0. Avancement » de `PLAN.md` : la première qui n'est pas ✅, sauf si l'utilisateur en demande une autre.
- Ne pas enchaîner sur l'étape suivante : à la fin de l'étape, s'arrêter.
- Fin de session, obligatoirement :
  1. mettre à jour le tableau d'avancement (statut + notes : ce qui reste, les problèmes rencontrés) ;
  2. ajouter une ligne au journal des sessions ;
  3. commit + push ;
  4. résumer à l'utilisateur ce qui a été fait et quelle sera la prochaine étape.
- Si une étape est trop grosse pour une session, la découper dans le tableau (ex. 3a / 3b), faire la première partie et noter le reste.
- Économiser le contexte : lire la maquette par morceaux (`grep` / `sed -n`), pas en entier ; peu de captures d'écran.

## Repères
- `maquette/` = référence visuelle, ce n'est pas du code du thème (à exclure du zip du thème).
- Le thème est à la racine du repo (voir l'arborescence dans `PLAN.md` §4).
- Aucun texte en dur dans les templates ; les champs ACF passent toujours par `inc/fields-map.php`.
