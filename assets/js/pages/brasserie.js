/* smash : la brasserie (BUILD.brasserie de la maquette) — compteurs des chiffres, entrée des valeurs, cartes de l'équipe dépliables. */
(() => {
	const { $$, animate } = window.smash || {};
	if (!$$) return;

	// Équipe : chaque carte s'ouvre et se ferme (bouton aria-expanded ; sans JS, tout est ouvert).
	$$('.mate__btn').forEach(btn => {
		const bio = document.getElementById(btn.getAttribute('aria-controls'));
		const card = btn.closest('.mate');
		if (!bio) return;
		const set = open => {
			btn.setAttribute('aria-expanded', open);
			card.classList.toggle('is-open', open);
			bio.firstElementChild.inert = !open;
		};
		set(false);
		btn.addEventListener('click', () => {
			set(btn.getAttribute('aria-expanded') !== 'true');
			if (window.ScrollTrigger) setTimeout(() => ScrollTrigger.refresh(), 500);
		});
	});

	if (!animate) return;
	$$('.stats b[data-n]').forEach(b => {
		const o = { v: 0 }, end = +b.dataset.n;
		b.textContent = '0';
		gsap.to(o, { v: end, duration: 1.6, ease: 'power2.out', scrollTrigger: { trigger: b, start: 'top 85%' }, onUpdate: () => { b.textContent = Math.round(o.v); } });
	});
	const values = $$('.values li');
	if (values.length) gsap.from(values, { x: -40, opacity: 0, duration: .9, ease: 'expo.out', stagger: .1, scrollTrigger: { trigger: '.values', start: 'top 85%' } });
})();
