/* smash : article — entrée du titre (maquette, BUILD.article). */
(() => {
	const { animate } = window.smash || {};
	if (!animate) return;
	gsap.from('#a-title', { y: 40, opacity: 0, duration: .7, ease: 'expo.out', delay: .05 });
})();
