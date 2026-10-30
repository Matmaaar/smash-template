/* smash : JS commun à toutes les pages (repris de la maquette : common(), bindTilt, méga-menu, porte d'âge). */
(() => {
	// Inertie du scroll fluide (Lenis) : 0,1 = très doux, 0,2 = presque natif. La maquette était à 0,09.
	const SCROLL_LERP = .16;
	const $ = (s, c = document) => c.querySelector(s);
	const $$ = (s, c = document) => [...c.querySelectorAll(s)];
	const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
	const hoverable = matchMedia('(hover:hover)').matches;
	const animate = !reduce && !!window.gsap; // mouvement réduit : contenu affiché tel quel
	const smash = (window.smash = { $, $$, reduce, hoverable, animate, lenis: null });

	if (animate) {
		gsap.registerPlugin(ScrollTrigger, SplitText);
		smash.lenis = new Lenis({ lerp: SCROLL_LERP });
		smash.lenis.on('scroll', ScrollTrigger.update);
		gsap.ticker.add(t => smash.lenis.raf(t * 1000));
		gsap.ticker.lagSmoothing(0);
	}

	/* Carte étiquette : penche en 3D vers la souris, reflet qui suit */
	smash.bindTilt = (root = document) => {
		if (!animate || !hoverable) return;
		$$('.lab', root).forEach(l => {
			const card = $('.lab__card', l);
			if (!card || l.dataset.tilt) return;
			l.dataset.tilt = 1;
			const rx = gsap.quickTo(card, 'rotationX', { duration: .5, ease: 'power3' }), ry = gsap.quickTo(card, 'rotationY', { duration: .5, ease: 'power3' });
			l.addEventListener('pointermove', e => {
				const r = card.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height;
				ry((x - .5) * 18); rx((.5 - y) * 14);
				card.style.setProperty('--gx', x * 100 + '%'); card.style.setProperty('--gy', y * 100 + '%');
			});
			l.addEventListener('pointerenter', () => gsap.to(card, { y: -10, scale: 1.03, duration: .5, ease: 'back.out(2)' }));
			l.addEventListener('pointerleave', () => { rx(0); ry(0); gsap.to(card, { y: 0, scale: 1, duration: .6, ease: 'expo.out' }); });
		});
	};

	/* Porte d'âge : cookie d'un an au clic sur « oui » */
	const gate = $('#gate');
	if (gate && getComputedStyle(gate).display !== 'none') {
		const root = document.documentElement, S = $$('#gateS i');
		root.style.overflow = 'hidden';
		smash.lenis && smash.lenis.stop();
		$('#yes').focus({ preventScroll: true });
		if (animate) {
			gsap.from(S, { x: i => [-120, 120, -120][i], opacity: 0, duration: 1, ease: 'expo.out', stagger: .12, delay: .2 });
			gsap.from('.gate .big, .gate__btns, .gate small', { y: 30, opacity: 0, duration: .8, ease: 'expo.out', stagger: .08, delay: .7 });
		}
		const done = () => { gate.remove(); root.style.overflow = ''; root.classList.add('age-ok'); smash.lenis && smash.lenis.start(); };
		$('#yes').addEventListener('click', () => {
			document.cookie = 'smash_age=1;max-age=31536000;path=/;SameSite=Lax';
			if (!animate) return done();
			gsap.timeline({ onComplete: done }).to('.gate__in', { opacity: 0, y: -30, duration: .4 }).to(gate, { yPercent: -100, duration: .8, ease: 'expo.inOut' });
		});
		$('#no').addEventListener('click', () => {
			$('#nomsg').style.display = 'block';
			animate && gsap.fromTo(S, { x: 0 }, { x: i => [8, -8, 8][i], duration: .08, repeat: 5, yoyo: true });
		});
	}

	/* Méga-menu « nos bières » : ouverture au survol et au clavier, fermeture douce */
	const dd = $('#dd-b');
	if (dd) {
		let timer;
		const close = () => { clearTimeout(timer); dd.classList.remove('open'); };
		const open = () => {
			if (innerWidth <= 860 || dd.classList.contains('open')) return;
			clearTimeout(timer); dd.classList.add('open');
			animate && gsap.from('#mm-labs a', { y: 20, opacity: 0, duration: .5, ease: 'expo.out', stagger: .04, delay: .1 });
		};
		dd.addEventListener('mouseenter', open);
		dd.addEventListener('mouseleave', () => { timer = setTimeout(close, 160); });
		dd.addEventListener('focusin', open);
		dd.addEventListener('focusout', e => { if (!dd.contains(e.relatedTarget)) close(); });
		addEventListener('keydown', e => { if (e.key === 'Escape' && dd.classList.contains('open')) { close(); $('a', dd).focus(); } });
	}

	smash.bindTilt();
	if (!animate) return;

	/* Animations communes (common() de la maquette) */
	$$('[data-lines]').forEach(el => { const s = new SplitText(el, { type: 'lines', mask: 'lines' }); gsap.from(s.lines, { yPercent: 110, duration: 1.15, ease: 'expo.out', stagger: .09, delay: .25 }); });
	$$('[data-up]').forEach(el => { const s = new SplitText(el, { type: 'lines', mask: 'lines' }); gsap.from(s.lines, { yPercent: 110, duration: 1.1, ease: 'expo.out', stagger: .08, scrollTrigger: { trigger: el, start: 'top 88%' } }); });
	$$('[data-words]').forEach(el => { const s = new SplitText(el, { type: 'words' }); gsap.fromTo(s.words, { opacity: .14 }, { opacity: 1, stagger: .1, ease: 'none', scrollTrigger: { trigger: el, start: 'top 80%', end: 'bottom 50%', scrub: true } }); });
	$$('[data-par]').forEach(el => gsap.fromTo(el, { yPercent: -6 }, { yPercent: 6, ease: 'none', scrollTrigger: { trigger: el.parentElement, start: 'top bottom', end: 'bottom top', scrub: true } }));
	$$('.stepped').forEach(sec => gsap.fromTo(sec, { '--step': '90px' }, { '--step': '28px', ease: 'none', scrollTrigger: { trigger: sec, start: 'top bottom', end: 'top 50%', scrub: true } }));
	$$('.ph-head .kick, .ph-head p').forEach(el => gsap.from(el, { y: 20, opacity: 0, duration: .9, ease: 'expo.out', delay: .4 }));
	ScrollTrigger.batch($$('.lab, .mate, .equip > div, .post, .post-row, .dl a, .cbox'), { start: 'top 92%', once: true, onEnter: b => gsap.from(b, { y: 70, opacity: 0, duration: 1, ease: 'expo.out', stagger: .06 }) });
	const drop = $('#pipedrop');
	if (drop) gsap.to(drop, { top: '100%', ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: .4 } });

	document.fonts && document.fonts.ready.then(() => ScrollTrigger.refresh());
})();
