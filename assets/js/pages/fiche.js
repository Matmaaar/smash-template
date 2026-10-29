/* smash : fiche bière (BUILD.fiche de la maquette) — entrée de l'étiquette et des lignes. */
(() => {
	const { animate } = window.smash || {};
	if (!animate) return;
	gsap.from('.fiche__label .lab__card', { rotationY: -50, x: -80, opacity: 0, duration: 1.2, ease: 'expo.out', delay: .2 });
	gsap.from('#fiche dl > div', { x: 30, opacity: 0, duration: .7, ease: 'expo.out', stagger: .04, delay: .4 });
})();
