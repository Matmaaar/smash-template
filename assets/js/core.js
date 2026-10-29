/* smash : animations communes à toutes les pages (reprises de la maquette, fonction common()). */
(() => {
	const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
	const $$ = (s, c = document) => [...c.querySelectorAll(s)];
	window.smash = { reduce, $$, $: (s, c = document) => c.querySelector(s) };
	if (reduce || !window.gsap) return; // mouvement réduit : contenu affiché tel quel

	gsap.registerPlugin(ScrollTrigger, SplitText);
	const lenis = new Lenis({ lerp: .09 });
	lenis.on('scroll', ScrollTrigger.update);
	gsap.ticker.add(t => lenis.raf(t * 1000));
	gsap.ticker.lagSmoothing(0);
	window.smash.lenis = lenis;

	$$('[data-lines]').forEach(el => { const s = new SplitText(el, { type: 'lines', mask: 'lines' }); gsap.from(s.lines, { yPercent: 110, duration: 1.15, ease: 'expo.out', stagger: .09, delay: .25 }); });
	$$('[data-up]').forEach(el => { const s = new SplitText(el, { type: 'lines', mask: 'lines' }); gsap.from(s.lines, { yPercent: 110, duration: 1.1, ease: 'expo.out', stagger: .08, scrollTrigger: { trigger: el, start: 'top 88%' } }); });
	$$('[data-words]').forEach(el => { const s = new SplitText(el, { type: 'words' }); gsap.fromTo(s.words, { opacity: .14 }, { opacity: 1, stagger: .1, ease: 'none', scrollTrigger: { trigger: el, start: 'top 80%', end: 'bottom 50%', scrub: true } }); });
	$$('[data-par]').forEach(el => gsap.fromTo(el, { yPercent: -6 }, { yPercent: 6, ease: 'none', scrollTrigger: { trigger: el.parentElement, start: 'top bottom', end: 'bottom top', scrub: true } }));
	$$('.stepped').forEach(sec => gsap.fromTo(sec, { '--step': '90px' }, { '--step': '28px', ease: 'none', scrollTrigger: { trigger: sec, start: 'top bottom', end: 'top 50%', scrub: true } }));
	$$('.ph-head .kick, .ph-head p').forEach(el => gsap.from(el, { y: 20, opacity: 0, duration: .9, ease: 'expo.out', delay: .4 }));
	ScrollTrigger.batch($$('.lab, .mate, .equip > div, .post, .post-row, .dl a, .cbox'), { start: 'top 92%', once: true, onEnter: b => gsap.from(b, { y: 70, opacity: 0, duration: 1, ease: 'expo.out', stagger: .06 }) });
	const drop = document.getElementById('pipedrop');
	if (drop) gsap.to(drop, { top: '100%', ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: .4 } });

	document.fonts && document.fonts.ready.then(() => ScrollTrigger.refresh());
})();
