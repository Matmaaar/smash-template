/* smash : catalogue « nos bières » (BUILD.bieres de la maquette) — onglets, ancres, « voir plus ». */
(() => {
	const { $, $$, animate } = window.smash || {};
	const root = $ && $('[data-catalog]');
	if (!root) return;

	const list = $('[role=tablist]', root), tabs = $$('[role=tab]', root), panels = $$('[role=tabpanel]', root);
	const ids = panels.map(p => p.id);
	list.hidden = false;

	const reveal = cards => {
		window.smash.bindTilt(root);
		if (animate && cards.length) gsap.from(cards, { y: 50, opacity: 0, duration: .8, ease: 'expo.out', stagger: .05 });
	};
	const select = (id, { focus = false, push = true, anim = true } = {}) => {
		if (!ids.includes(id)) id = ids[0];
		tabs.forEach(t => {
			const on = t.getAttribute('aria-controls') === id;
			t.setAttribute('aria-selected', on);
			t.tabIndex = on ? 0 : -1;
			if (on && focus) t.focus();
		});
		panels.forEach(p => { p.hidden = p.id !== id; });
		if (push) history.replaceState(null, '', '#' + id);
		if (anim) reveal($$(`#${id} .lab-wrap:not([hidden]) .lab`, root));
		window.ScrollTrigger && ScrollTrigger.refresh();
	};

	tabs.forEach((t, i) => {
		t.addEventListener('click', () => select(t.getAttribute('aria-controls')));
		t.addEventListener('keydown', e => {
			const d = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
			if (e.key === 'Home' || e.key === 'End') { e.preventDefault(); select(tabs[e.key === 'Home' ? 0 : tabs.length - 1].getAttribute('aria-controls'), { focus: true }); }
			if (!d) return;
			e.preventDefault();
			select(tabs[(i + d + tabs.length) % tabs.length].getAttribute('aria-controls'), { focus: true });
		});
	});

	// « voir plus » : 12 anciennes de plus à chaque clic.
	$$('[data-more]', root).forEach(btn => btn.addEventListener('click', () => {
		const next = $$('.lab-wrap[hidden]', btn.closest('[role=tabpanel]')).slice(0, 12);
		next.forEach(w => { w.hidden = false; });
		if (!$('.lab-wrap[hidden]', btn.closest('[role=tabpanel]'))) btn.parentElement.remove();
		reveal(next.map(w => $('.lab', w)));
		next[0] && $('a', next[0]).focus({ preventScroll: true });
	}));

	// Onglet de départ : l'ancre de l'URL (liens du méga-menu), sinon celui de la gamme affichée.
	const fromHash = () => location.hash.slice(1);
	select(ids.includes(fromHash()) ? fromHash() : root.dataset.selected, { push: false, anim: false });
	addEventListener('hashchange', () => ids.includes(fromHash()) && select(fromHash(), { push: false }));
})();
