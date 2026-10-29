/* smash : accueil (BUILD.home de la maquette) — pile de la dernière série, mur d'étiquettes, S du hero. */
(() => {
	const { $, $$, animate, hoverable } = window.smash || {};
	if (!$) return;

	// Mur : trois rangées qui défilent, plus vite quand on scrolle. Figé si mouvement réduit.
	const rows = $$('[data-wall] .wall__row');
	if (animate && rows.length) {
		const pos = [0, -400, -200];
		gsap.ticker.add(() => {
			const v = (window.smash.lenis && window.smash.lenis.velocity) || 0;
			rows.forEach((r, i) => {
				pos[i] += (i % 2 ? 1 : -1) * (.6 + Math.abs(v) * .5);
				const half = r.scrollWidth / 2;
				if (pos[i] <= -half) pos[i] += half;
				if (pos[i] > 0) pos[i] -= half;
				r.style.transform = `translateX(${pos[i]}px)`;
			});
		});
	}

	if (!animate) return;

	// Pile des étiquettes de la dernière série : position posée en PHP, ici l'entrée et le survol.
	const flats = $$('[data-stack] .flat');
	flats.forEach((el, i) => {
		const rot = +el.dataset.rot || 0;
		gsap.set(el, { rotation: rot });
		if (!hoverable) return;
		el.addEventListener('mouseenter', () => gsap.to(el, { y: -30, rotation: 0, scale: 1.05, zIndex: 10, duration: .5, ease: 'back.out(2)' }));
		el.addEventListener('mouseleave', () => gsap.to(el, { y: 0, rotation: rot, scale: 1, zIndex: i, duration: .6, ease: 'expo.out' }));
	});
	if (flats.length) gsap.from(flats, { x: 300, y: 120, rotation: 30, opacity: 0, duration: 1.2, ease: 'expo.out', stagger: .12, scrollTrigger: { trigger: '[data-stack]', start: 'top 75%' } });

	// Hero : le S s'assemble une fois, puis flotte légèrement au scroll.
	gsap.from($$('#heroS i'), { x: i => [-80, 80, -80][i], opacity: 0, duration: 1.1, ease: 'expo.out', stagger: .1, delay: .3 });
	gsap.fromTo('#heroS', { y: 0 }, { y: -40, ease: 'none', scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true } });
	gsap.from('.hero .kick, .hero__sub > *', { y: 24, opacity: 0, duration: .9, ease: 'expo.out', stagger: .08, delay: .5 });
})();
