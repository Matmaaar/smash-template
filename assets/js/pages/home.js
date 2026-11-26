/* smash : accueil (BUILD.home de la maquette) — pile de la dernière série, mur d'étiquettes, S du hero. */
(() => {
	const { $, $$, animate, hoverable } = window.smash || {};
	if (!$) return;

	// Hero : avec deux images, fondu de l'une à l'autre toutes les 4 s (pas d'alternance si mouvement réduit).
	const slides = $$('.hero__img img');
	if (slides.length > 1 && !window.smash.reduce) {
		let n = 0;
		setInterval(() => {
			if (document.hidden) return;
			slides[n].classList.remove('is-on');
			n = (n + 1) % slides.length;
			slides[n].classList.add('is-on');
		}, 4000);
	}

	// Mur : trois rangées qui défilent toutes seules (plus vite quand on scrolle la page).
	// On peut aussi les prendre en main : glisser à la souris ou au doigt, ou défiler au trackpad.
	// La rangée suit le geste, garde un peu d'élan, puis reprend son défilement 1,5 s plus tard.
	// Mouvement réduit : pas de défilement automatique, mais la prise en main reste possible.
	const wall = $('[data-wall]');
	const rows = $$('[data-wall] .wall__row');
	if (wall && rows.length) {
		const st = rows.map((r, i) => ({ x: [0, -400, -200][i] || 0, dir: i % 2 ? 1 : -1, vel: 0, hold: 0, drag: false }));
		const wrap = (s, r) => { const half = r.scrollWidth / 2; if (s.x <= -half) s.x += half; if (s.x > 0) s.x -= half; };
		const tick = () => {
			const now = performance.now(), v = (window.smash.lenis && window.smash.lenis.velocity) || 0;
			rows.forEach((r, i) => {
				const s = st[i];
				if (!s.drag) {
					if (Math.abs(s.vel) > .05) { s.x += s.vel; s.vel *= .9; }
					else if (animate && now > s.hold) s.x += s.dir * (.6 + Math.abs(v) * .5);
				}
				wrap(s, r);
				r.style.transform = `translateX(${s.x}px)`;
			});
		};
		window.gsap ? gsap.ticker.add(tick) : (function loop() { tick(); requestAnimationFrame(loop); })();

		// Il faut une vraie intention pour prendre la main : glisser de plus de 10 px, plus à l'horizontale
		// qu'à la verticale. Un simple toucher, ou un scroll vertical qui passe sur le mur, ne l'arrête pas.
		let drag = null, suppress = false;
		wall.addEventListener('pointerdown', e => {
			const r = e.target.closest('.wall__row');
			if (!r || e.button > 0) return;
			drag = { s: st[rows.indexOf(r)], x0: e.clientX, y0: e.clientY, x: e.clientX, moved: false, id: e.pointerId };
		});
		wall.addEventListener('pointermove', e => {
			if (!drag || e.pointerId !== drag.id) return;
			const dx = e.clientX - drag.x, ax = Math.abs(e.clientX - drag.x0), ay = Math.abs(e.clientY - drag.y0);
			drag.x = e.clientX;
			if (!drag.moved) {
				if (ay > 10 && ay >= ax) { drag = null; return; }       // scroll vertical : on laisse faire
				if (ax <= 10 || ax < ay * 1.2) return;                  // pas encore d'intention claire
				drag.moved = true; drag.s.drag = true; drag.s.vel = 0;
				wall.classList.add('is-dragging'); wall.setPointerCapture(e.pointerId);
			}
			drag.s.x += dx; drag.s.vel = dx;
		});
		const end = () => {
			if (!drag) return;
			if (drag.moved) { drag.s.drag = false; drag.s.hold = performance.now() + 1500; suppress = true; }
			drag = null;
			wall.classList.remove('is-dragging');
		};
		wall.addEventListener('pointerup', end);
		wall.addEventListener('pointercancel', end);
		// Après un glisser, le relâchement ne doit pas ouvrir la fiche.
		wall.addEventListener('click', e => { if (suppress) { e.preventDefault(); e.stopPropagation(); suppress = false; } }, true);
		wall.addEventListener('dragstart', e => e.preventDefault());
		// Trackpad / molette horizontale.
		wall.addEventListener('wheel', e => {
			if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;
			e.preventDefault();
			const r = e.target.closest('.wall__row') || rows[1];
			const s = st[rows.indexOf(r)];
			s.x -= e.deltaX; s.vel = 0; s.hold = performance.now() + 1500;
		}, { passive: false });
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
	if (flats.length) gsap.from(flats, { x: 300, y: 120, rotation: 30, opacity: 0, duration: .85, ease: 'expo.out', stagger: .08, scrollTrigger: { trigger: '[data-stack]', start: 'top 75%' } });

	// Hero : le S s'assemble une fois, puis flotte légèrement au scroll.
	gsap.from($$('#heroS i'), { x: i => [-80, 80, -80][i], opacity: 0, duration: .8, ease: 'expo.out', stagger: .08, delay: .1 });
	gsap.fromTo('#heroS', { y: 0 }, { y: -40, ease: 'none', scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true } });
	gsap.from('.hero .kick, .hero__sub > *', { y: 24, opacity: 0, duration: .6, ease: 'expo.out', stagger: .06, delay: .15 });
})();
