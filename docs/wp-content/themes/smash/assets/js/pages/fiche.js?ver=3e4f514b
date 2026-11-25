/* smash : fiche bière (BUILD.fiche de la maquette) — entrée de l'étiquette et des lignes. */
(() => {
	const { animate } = window.smash || {};
	if (!animate) return;
	// Arrivée depuis une autre page du site : l'étiquette arrive déjà par la transition entre pages (inc/transitions.php).
	const viaTransition = 'onpagereveal' in window && window.navigation && navigation.activation && navigation.activation.from;
	if (!viaTransition) gsap.from('.fiche__label .lab__card', { rotationY: -40, x: -60, opacity: 0, duration: .8, ease: 'expo.out', delay: .05 });
	gsap.from('.fiche__key > div, .fiche__grid > div', { x: 24, opacity: 0, duration: .5, ease: 'expo.out', stagger: .03, delay: .12 });
})();
