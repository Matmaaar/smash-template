/* smash : catalogue « nos bières » (BUILD.bieres de la maquette) — onglets, ancres, « voir plus ». */
(() => {
	const { $, $$, animate } = window.smash || {};
	const root = $ && $('[data-catalog]');
	if (!root) return;

	const list = $('[role=tablist]', root), tabs = $$('[role=tab]', root), panels = $$('[role=tabpanel]', root);
	const ids = panels.map(p => p.id).concat('toutes'); // « toutes » : les trois listes à la suite
	const tabId = t => t.id.replace(/^tab-/, '');
	list.hidden = false;

	const reveal = cards => {
		window.smash.bindTilt(root);
		if (animate && cards.length) gsap.fromTo(cards.slice(0, 16), { y: 36, opacity: 0 }, { y: 0, opacity: 1, duration: .6, ease: 'expo.out', stagger: .04, overwrite: 'auto', clearProps: 'transform,opacity' });
	};
	const select = (id, { focus = false, push = true, anim = true } = {}) => {
		if (!ids.includes(id)) id = ids[ids.length - 1];
		const all = id === 'toutes';
		tabs.forEach(t => {
			const on = tabId(t) === id;
			t.setAttribute('aria-selected', on);
			t.tabIndex = on ? 0 : -1;
			if (on && focus) t.focus();
		});
		panels.forEach(p => { p.hidden = !all && p.id !== id; });
		root.classList.toggle('is-all', all);
		if (push) history.replaceState(null, '', '#' + id);
		if (anim) reveal($$((all ? '' : `#${id} `) + '.lab-wrap:not([hidden]) .lab', root));
		window.ScrollTrigger && ScrollTrigger.refresh();
	};

	tabs.forEach((t, i) => {
		t.addEventListener('click', () => select(tabId(t)));
		t.addEventListener('keydown', e => {
			const d = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
			if (e.key === 'Home' || e.key === 'End') { e.preventDefault(); select(tabId(tabs[e.key === 'Home' ? 0 : tabs.length - 1]), { focus: true }); }
			if (!d) return;
			e.preventDefault();
			select(tabId(tabs[(i + d + tabs.length) % tabs.length]), { focus: true });
		});
	});

	// Affichage 1 ou 2 colonnes (mobile), mémorisé dans le navigateur.
	const toggle = $('.view-toggle', root);
	if (toggle) {
		toggle.hidden = false;
		const setCols = n => {
			root.classList.toggle('is-1col', n === '1');
			$$('[data-cols]', toggle).forEach(b => b.setAttribute('aria-pressed', b.dataset.cols === n));
			try { localStorage.setItem('smash-cols', n); } catch (e) {}
		};
		let saved = '2';
		try { saved = localStorage.getItem('smash-cols') || '2'; } catch (e) {}
		setCols(saved);
		$$('[data-cols]', toggle).forEach(b => b.addEventListener('click', () => { setCols(b.dataset.cols); window.ScrollTrigger && ScrollTrigger.refresh(); }));
	}

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
