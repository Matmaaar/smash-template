<?php
/**
 * Transition « étiquette qui grandit » entre les pages (View Transitions entre documents).
 *
 * Au clic sur une étiquette (catalogue, accueil, gammes, méga-menu, articles), elle devient la grande
 * étiquette de la fiche bière ; au retour, elle reprend sa place. Le reste de la page fond.
 * - Chrome, Edge, Safari récents. Les autres navigateurs naviguent normalement.
 * - Coupée si l'utilisateur a demandé moins d'animations (CSS dans @media prefers-reduced-motion).
 * - Le nom de transition « label » est porté en permanence par l'étiquette de la fiche (CSS), et posé
 *   en JS sur la seule carte concernée côté liste (sinon une grille aurait des dizaines de noms en double).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', function () {
	// Le pied de page doit être lu avant le premier affichage : au retour sur une liste, la carte à animer doit exister.
	echo '<link rel="expect" href="#smash-foot" blocking="render">' . "\n";
	?>
<script>
(function () {
	if (!('onpagereveal' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	var slug = function (u) { var m = String(u || '').match(/\/bieres\/([^\/?#]+)/); return m && m[1]; };
	var clicked = null;
	// La carte d'étiquette d'un lien vers la bière « s » : d'abord le lien cliqué, sinon le premier visible dans le contenu.
	var card = function (s) {
		var pick = function (a) {
			if (!a || slug(a.href) !== s) return null;
			var el = a.querySelector('.lab__card') || a.querySelector('i') || (a.matches('.tile, .flat') ? a : null);
			return el && el.getBoundingClientRect().width ? el : null;
		};
		var hit = pick(clicked);
		if (hit) return hit;
		var links = document.querySelectorAll('#contenu a[href]');
		for (var i = 0; i < links.length; i++) { if ((hit = pick(links[i]))) return hit; }
		return null;
	};
	addEventListener('click', function (e) { clicked = e.target.closest && e.target.closest('a[href]'); }, true);
	// Départ d'une liste vers une fiche : la carte cliquée prend le nom « label ».
	addEventListener('pageswap', function (e) {
		if (!e.viewTransition || !e.activation || document.querySelector('.fiche__label')) return;
		var el = card(slug(e.activation.entry.url));
		if (el) el.style.viewTransitionName = 'label';
	});
	// Arrivée sur une liste depuis une fiche (retour) : la carte de cette bière reprend le nom « label ».
	addEventListener('pagereveal', function (e) {
		if (!e.viewTransition || document.querySelector('.fiche__label')) return;
		var from = window.navigation && navigation.activation && navigation.activation.from;
		var el = from && card(slug(from.url));
		if (!el) return;
		el.style.viewTransitionName = 'label';
		e.viewTransition.finished.finally(function () { el.style.viewTransitionName = ''; });
	});
})();
</script>
	<?php
}, 1 );
