/* smash : la brasserie (BUILD.brasserie de la maquette) — compteurs des chiffres, entrée des valeurs. */
(() => {
	const { $$, animate } = window.smash || {};
	if (!animate) return;
	$$('.stats b[data-n]').forEach(b => {
		const o = { v: 0 }, end = +b.dataset.n;
		b.textContent = '0';
		gsap.to(o, { v: end, duration: 1.6, ease: 'power2.out', scrollTrigger: { trigger: b, start: 'top 85%' }, onUpdate: () => { b.textContent = Math.round(o.v); } });
	});
	const values = $$('.values li');
	if (values.length) gsap.from(values, { x: -40, opacity: 0, duration: .9, ease: 'expo.out', stagger: .1, scrollTrigger: { trigger: '.values', start: 'top 85%' } });
})();
