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

	/* Méga-menus (aperçus de « nos bières », « la brasserie », « le blog »…) : survol après un court délai,
	   clavier (focus) et Échap. Passer d'un lien à l'autre change le panneau sans le refermer. */
	const dds = $$('#nav .dd');
	if (dds.length) {
		const nav = $('#nav');
		let current = null, openTimer, closeTimer;
		const close = () => {
			clearTimeout(openTimer); clearTimeout(closeTimer);
			if (current) current.classList.remove('open');
			current = null;
			nav.classList.remove('mega-swap');
		};
		smash.closeMega = close;
		const open = dd => {
			clearTimeout(openTimer); clearTimeout(closeTimer);
			if (innerWidth <= 860 || current === dd) return;
			const swap = !!current;
			if (current) current.classList.remove('open');
			nav.classList.toggle('mega-swap', swap);
			dd.classList.add('open');
			current = dd;
			animate && gsap.fromTo($$('.mega__in > *', dd), { y: swap ? 10 : 20, opacity: 0 }, { y: 0, opacity: 1, duration: swap ? .35 : .5, ease: 'expo.out', stagger: .04, delay: swap ? 0 : .1, overwrite: true });
		};
		dds.forEach(dd => {
			dd.addEventListener('mouseenter', () => { clearTimeout(closeTimer); clearTimeout(openTimer); openTimer = setTimeout(() => open(dd), current ? 60 : 150); });
			dd.addEventListener('mouseleave', () => { clearTimeout(openTimer); closeTimer = setTimeout(close, 180); });
			dd.addEventListener('focusin', () => open(dd));
			dd.addEventListener('focusout', e => { if (!e.relatedTarget || !e.relatedTarget.closest('#nav .dd')) close(); });
		});
		addEventListener('keydown', e => {
			if (e.key !== 'Escape' || !current) return;
			const link = $('a', current);
			close();
			link.focus();
		});
	}

	/* Logo : blanc sur fond sombre / photo, noir sur fond clair. On regarde ce qu'il y a derrière lui. */
	const logo = $('#logo');
	if (logo) {
		const bgUnder = () => {
			const r = logo.getBoundingClientRect();
			const stack = document.elementsFromPoint(r.left + r.width / 2, r.top + r.height / 2);
			for (const el of stack) {
				if (el.closest('#logo, #top, #wpadminbar')) continue;
				if (el.tagName === 'IMG' || el.closest('.hero__img')) return 0; // photo : logo blanc
				const m = getComputedStyle(el).backgroundColor.match(/[\d.]+/g);
				if (m && (m[3] === undefined || +m[3] > .5)) return (.2126 * m[0] + .7152 * m[1] + .0722 * m[2]) / 255;
			}
			return 0;
		};
		let ticking = false;
		const update = () => { ticking = false; logo.classList.toggle('is-dark', !document.documentElement.classList.contains('menu-open') && bgUnder() > .6); };
		const ask = () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } };
		addEventListener('scroll', ask, { passive: true }); addEventListener('resize', ask); update();
		smash.updateLogo = ask;
	}

	/* Menu plein écran (mobile) */
	const menuBtn = $('#menu-btn'), mnav = $('#mnav');
	if (menuBtn && mnav) {
		const toggle = open => {
			mnav.hidden = !open;
			menuBtn.setAttribute('aria-expanded', String(open));
			menuBtn.textContent = open ? menuBtn.dataset.close : menuBtn.dataset.open;
			document.documentElement.classList.toggle('menu-open', open);
			smash.updateLogo && smash.updateLogo();
			smash.lenis && (open ? smash.lenis.stop() : smash.lenis.start());
			if (open) {
				$('a', mnav).focus({ preventScroll: true });
				animate && gsap.from($$('a', mnav), { y: 40, opacity: 0, duration: .6, ease: 'expo.out', stagger: .05 });
			} else menuBtn.focus({ preventScroll: true });
		};
		menuBtn.addEventListener('click', () => toggle(mnav.hidden));
		addEventListener('keydown', e => { if (e.key === 'Escape' && !mnav.hidden) toggle(false); });
		addEventListener('resize', () => { if (innerWidth > 860 && !mnav.hidden) toggle(false); });
	}

	/* Nav : se cache quand on descend, revient quand on remonte ; le bouton devis flottant fait l'inverse.
	   Jamais cachée en haut de page, ni quand le méga-menu est ouvert ou que le focus est dans la nav. */
	const top = $('#top'), fab = $('#fab-cta'), body = document.body;
	if (top) {
		const SHOW_UNDER = 100, SENSITIVITY = 6; // px
		let last = scrollY, hidden = false;
		const set = h => {
			if (h === hidden) return;
			hidden = h;
			body.classList.toggle('nav-hidden', h);
			if (h && smash.closeMega) smash.closeMega();
			if (fab) {
				fab.tabIndex = h ? 0 : -1; fab.setAttribute('aria-hidden', String(!h));
				if (h) fab.classList.add('pop'); // n'agit qu'une fois : la classe reste, l'animation ne rejoue pas
			}
		};
		const onScroll = () => {
			const y = scrollY, d = y - last;
			if (y < SHOW_UNDER) set(false);
			else if (d > SENSITIVITY && !document.querySelector('#nav .dd.open') && !top.contains(document.activeElement)) set(true);
			else if (d < -SENSITIVITY) set(false);
			if (Math.abs(d) > SENSITIVITY || y < SHOW_UNDER) last = y;
		};
		addEventListener('scroll', onScroll, { passive: true });
		top.addEventListener('focusin', () => set(false));
	}

	smash.bindTilt();
	if (!animate) return;

	/* Animations communes (common() de la maquette) */
	// Découpe en lignes masquées pour l'effet « la ligne monte ». L'interligne des gros titres est très serré :
	// on agrandit la fenêtre de chaque ligne vers le bas pour ne pas couper les jambages (p, q, j, g).
	const split = el => {
		const s = new SplitText(el, { type: 'lines', mask: 'lines' });
		(s.masks || []).forEach(m => { m.style.paddingBottom = '.18em'; m.style.marginBottom = '-.18em'; });
		return s;
	};
	$$('[data-lines]').forEach(el => { const s = split(el); gsap.from(s.lines, { yPercent: 110, duration: 1.15, ease: 'expo.out', stagger: .09, delay: .25, onComplete: () => s.revert() }); });
	$$('[data-up]').forEach(el => { const s = split(el); gsap.from(s.lines, { yPercent: 110, duration: 1.1, ease: 'expo.out', stagger: .08, onComplete: () => s.revert(), scrollTrigger: { trigger: el, start: 'top 88%' } }); });
	$$('[data-words]').forEach(el => { const s = new SplitText(el, { type: 'words' }); gsap.fromTo(s.words, { opacity: .14 }, { opacity: 1, stagger: .1, ease: 'none', scrollTrigger: { trigger: el, start: 'top 80%', end: 'bottom 50%', scrub: true } }); });
	$$('[data-par]').forEach(el => gsap.fromTo(el, { yPercent: -6 }, { yPercent: 6, ease: 'none', scrollTrigger: { trigger: el.parentElement, start: 'top bottom', end: 'bottom top', scrub: true } }));
	$$('.ph-head .kick, .ph-head p').forEach(el => gsap.from(el, { y: 20, opacity: 0, duration: .9, ease: 'expo.out', delay: .4 }));
	ScrollTrigger.batch($$('.lab, .mate, .equip > div, .post, .post-row, .dl a, .cbox'), { start: 'top 92%', once: true, onEnter: b => gsap.from(b, { y: 70, opacity: 0, duration: 1, ease: 'expo.out', stagger: .06 }) });

	document.fonts && document.fonts.ready.then(() => ScrollTrigger.refresh());
})();
