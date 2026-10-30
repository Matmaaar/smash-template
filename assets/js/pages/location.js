/* smash : devis de location en direct (initLoc() + price() de la maquette).
   Aucune valeur en dur : prix dans les data-*, règles et textes dans data-config (page-location-tireuses-a-bieres.php). */
(() => {
	const { $, $$, animate } = window.smash || {};
	const rent = $ && $('#rent');
	if (!rent) return;

	const C = JSON.parse(rent.dataset.config), R = C.rules, T = C.i18n;
	const S = { prof: 'part', qty: {}, eph: 0, taps: { t1: 0, t2: 0 }, gear: {}, cups: 0, cupsN: 100 };
	const fmt = v => typeof v === 'number' ? (v % 1 ? v.toFixed(2).replace('.', ',') : v) + ' €' : v;
	const tpl = (s, ...a) => { let i = 0; return s.replace(/%(\d\$)?[ds]/g, (m, n) => a[n ? +n[0] - 1 : i++]); };
	const clamp = (n, max) => Math.max(0, Math.min(max, Math.round(+n || 0)));
	const pop = el => animate && gsap.fromTo(el, { scale: 1.35 }, { scale: 1, duration: .35, ease: 'back.out(3)' });

	/* Segmentés : la pastille glisse sous le bouton actif */
	const seg = (root, onPick) => {
		const pill = $('.seg__pill', root), btns = $$('button', root);
		const place = (b, anim) => {
			const v = { x: b.offsetLeft - 4, width: b.offsetWidth };
			if (anim && animate) gsap.to(pill, { ...v, duration: .45, ease: 'expo.out' });
			else { pill.style.transform = `translateX(${v.x}px)`; pill.style.width = v.width + 'px'; }
		};
		btns.forEach(b => b.addEventListener('click', () => {
			btns.forEach(x => { x.classList.toggle('on', x === b); x.setAttribute('aria-pressed', x === b); });
			place(b, true); onPick(b.dataset.v);
		}));
		const init = () => place($('button.on', root) || btns[0], false);
		requestAnimationFrame(init); addEventListener('resize', init);
	};
	seg($('#seg-prof'), v => { S.prof = v; applyProf(); price(); });
	seg($('#seg-cups'), v => { S.cups = +v; $('#cupsq').classList.toggle('on', S.cups > 0); price(); });

	const cn = $('#cups-n');
	cn.addEventListener('input', () => { S.cupsN = clamp(cn.value, 100000); price(); });
	cn.addEventListener('focus', () => cn.select());
	$$('#cupsq button').forEach(b => b.addEventListener('click', () => { S.cupsN = clamp(S.cupsN + +b.dataset.d, 100000); cn.value = S.cupsN; price(); }));

	const applyProf = () => {
		const pro = S.prof === 'pro';
		$$('.part-only', rent.parentElement).forEach(e => { e.hidden = pro; });
		$$('.pro-only', rent.parentElement).forEach(e => { e.hidden = !pro; });
		$('#t-mode').textContent = pro ? T.pro : T.part;
		$$('.keg__meta span[data-p]').forEach(s => { s.textContent = pro ? T.quote : s.dataset.p; });
		animate && gsap.fromTo('#kegs .keg, #taps, #gear', { y: 8, opacity: .4 }, { y: 0, opacity: 1, duration: .4, stagger: .02 });
	};

	/* Le petit morceau d'étiquette qui file dans le ticket */
	const fly = src => {
		if (!animate) return;
		const r = src.getBoundingClientRect(), t = $('#t-list').getBoundingClientRect(), img = $('img', src);
		const f = document.createElement('div');
		f.className = 'fly';
		f.style.cssText += `left:${r.left}px;top:${r.top}px;width:${r.width}px;height:${r.height}px;background:#ebe9e3 center/cover;${img ? `background-image:url("${img.currentSrc || img.src}")` : ''}`;
		document.body.appendChild(f);
		gsap.to(f, { left: t.left + 20, top: t.top + 10, width: 60, height: 30, rotation: gsap.utils.random(-15, 15), opacity: .2, duration: .65, ease: 'power3.in', onComplete: () => f.remove() });
	};

	/* Fûts : tuile étiquette, clic = +1, ou stepper */
	$$('#kegs .keg').forEach(el => {
		const id = el.dataset.k, inp = $('input', el);
		const cur = () => id === 'eph' ? S.eph : (S.qty[id] || 0);
		const set = (n, flyIt) => {
			n = clamp(n, 999);
			if (id === 'eph') S.eph = n; else S.qty[id] = n;
			el.classList.toggle('has', n > 0); inp.value = n; pop(inp);
			if (flyIt) fly($('.keg__lab', el));
			price();
		};
		$$('.stepper button', el).forEach(b => b.addEventListener('click', () => set(cur() + +b.dataset.d, +b.dataset.d > 0)));
		inp.addEventListener('input', () => set(inp.value, false));
		inp.addEventListener('focus', () => inp.select());
		$('.keg__lab', el).addEventListener('click', () => set(cur() + 1, true));
	});

	/* Tireuses (particulier) : autant qu'on veut */
	$$('#taps .tapt').forEach(el => {
		const t = el.dataset.t, inp = $('input', el);
		const set = n => { S.taps[t] = clamp(n, 99); inp.value = S.taps[t]; el.classList.toggle('has', S.taps[t] > 0); pop(inp); price(); };
		$$('button', el).forEach(b => b.addEventListener('click', () => set(S.taps[t] + +b.dataset.d)));
		inp.addEventListener('input', () => set(inp.value));
		inp.addEventListener('focus', () => inp.select());
	});

	/* Matériel (pro) : choix multiples */
	$$('#gear button').forEach(b => b.addEventListener('click', () => {
		S.gear[b.dataset.g] = !S.gear[b.dataset.g];
		b.classList.toggle('on', S.gear[b.dataset.g]); b.setAttribute('aria-pressed', S.gear[b.dataset.g]);
		price();
	}));

	/* Dates : vérification des jours en direct (1 = lundi … 7 = dimanche) */
	const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
	const dow = v => { if (!v) return null; const d = new Date(v + 'T12:00').getDay(); return d === 0 ? 7 : d; };
	const nextDay = (from, days) => { const d = new Date(from); for (let i = 1; i <= 14; i++) { d.setDate(d.getDate() + 1); if (days.includes(dow(iso(d)))) return d; } return d; };
	const check = () => {
		[['#r-pick', '#h-pick', R.pickupDays, T.badPickup], ['#r-back', '#h-back', R.returnDays, T.badReturn]].forEach(([i, h, days, bad]) => {
			const d = dow($(i).value), el = $(h), ok = days.includes(d);
			el.className = d === null ? '' : ok ? 'ok' : 'bad';
			el.textContent = d === null ? '' : ok ? T.ok : bad;
		});
	};
	const pick = nextDay(new Date(), R.pickupDays);
	$('#r-pick').value = iso(pick); $('#r-back').value = iso(nextDay(pick, R.returnDays));
	$('#r-pick').addEventListener('change', check); $('#r-back').addEventListener('change', check); check();

	/* Le ticket : mêmes règles que smash_rental_quote() côté serveur */
	function price() {
		const pro = S.prof === 'pro', items = [];
		let total = 0, kegs = 0;
		$$('#kegs .keg[data-price]').forEach(el => {
			const q = S.qty[el.dataset.k] || 0; if (!q) return;
			const p = +el.dataset.price; kegs += q;
			items.push([`${q} × ${el.dataset.name}`, pro ? T.quote : q * p]); if (!pro) total += q * p;
		});
		if (S.eph) { kegs += S.eph; items.push([`${S.eph} × ${T.eph}`, T.onRequest]); }
		const free = !pro && kegs >= R.freeTapFrom;
		if (!pro) {
			const { t1, t2 } = S.taps;
			if (t1) {
				const paid = free ? t1 - 1 : t1;
				if (free) items.push([T.freeTapLine, T.free, true]);
				if (paid) { items.push([`${paid} × ${T.tap1}`, paid * R.taps.t1]); total += paid * R.taps.t1; }
			}
			if (t2) { items.push([`${t2} × ${T.tap2}`, t2 * R.taps.t2]); total += t2 * R.taps.t2; }
		} else Object.entries(S.gear).forEach(([g, on]) => on && items.push([g, T.quote]));
		if (S.cups && S.cupsN && R.cups[S.cups] !== undefined) {
			const v = S.cupsN * R.cups[S.cups];
			items.push([tpl(T.cupsLine, S.cupsN, S.cups), pro ? T.quote : v]); if (!pro) total += v;
		}
		total = Math.round(total * 100) / 100;

		const e1 = $('#taps [data-t="t1"] em');
		if (e1) e1.innerHTML = free ? `<s>${fmt(R.taps.t1)}</s>${T.oneFree}` : fmt(R.taps.t1);
		const list = $('#t-list');
		list.innerHTML = '';
		if (!items.length) { const li = document.createElement('li'); li.className = 'empty'; li.textContent = T.empty; list.appendChild(li); }
		items.forEach(([a, b, f]) => {
			const li = document.createElement('li'); if (f) li.className = 'free';
			[a, fmt(b)].forEach(txt => { const s = document.createElement('span'); s.textContent = txt; li.appendChild(s); });
			list.appendChild(li);
		});

		const t = $('#t-total'), from = +(t.dataset.v || 0);
		t.dataset.v = total;
		if (pro) t.textContent = T.quote;
		else if (animate) { const o = { v: from }; gsap.to(o, { v: total, duration: .5, ease: 'power2.out', onUpdate: () => { t.textContent = fmt(Math.round(o.v)); }, onComplete: () => { t.textContent = fmt(total); } }); }
		else t.textContent = fmt(total);

		const plural = n => n > 1 ? T.kegs : T.keg;
		let msg;
		if (pro) msg = kegs >= R.proFreeFrom ? T.proFree : tpl(T.needPro, R.proFreeFrom - kegs, plural(R.proFreeFrom - kegs));
		else if (kegs < R.freeTapFrom) msg = tpl(T.needKegs, R.freeTapFrom - kegs, plural(R.freeTapFrom - kegs));
		else msg = S.taps.t1 ? T.tapFreeOk : T.tapFreeAdd;
		const n = $('#t-nudge');
		if (n.dataset.m !== msg) { n.dataset.m = msg; n.lastElementChild.textContent = msg; animate && gsap.fromTo(n, { scale: .95 }, { scale: 1, duration: .45, ease: 'back.out(3)' }); }
	}

	/* Envoi : route REST, qui recalcule tout et transmet au formulaire Gravity Forms */
	const form = $('#t-form'), err = $('#t-error'), send = $('#t-send');
	form.addEventListener('submit', async e => {
		e.preventDefault();
		err.hidden = true;
		const fields = ['#t-name', '#t-mail', '#t-tel'].map(s => $(s));
		fields.forEach(f => f.setAttribute('aria-invalid', !f.value.trim() || (f.type === 'email' && !f.checkValidity())));
		const bad = fields.find(f => f.getAttribute('aria-invalid') === 'true');
		if (bad) { bad.focus(); return; }

		const label = send.innerHTML;
		send.disabled = true; send.textContent = T.sending;
		const v = s => $(s).value.trim();
		try {
			const res = await fetch(C.rest, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Smash-Nonce': C.nonce, 'X-WP-Nonce': C.wpNonce },
				body: JSON.stringify({
					prof: S.prof, kegs: S.qty, eph: S.eph, taps: S.taps, gear: Object.keys(S.gear).filter(g => S.gear[g]),
					cups: S.cups, cups_n: S.cupsN, pickup: v('#r-pick'), return: v('#r-back'),
					name: v('#t-name'), email: v('#t-mail'), phone: v('#t-tel'), event: v('#t-event'), notes: v('#t-notes'), website: v('#t-website'),
				}),
			});
			const data = await res.json().catch(() => ({}));
			if (!res.ok || !data.ok) throw new Error(data.message || T.error);
			const done = $('#t-done');
			const show = () => { form.hidden = true; $('#t-nudge').hidden = true; $('.ticket__foot').hidden = true; done.style.display = 'block'; done.focus(); };
			if (!animate) return show();
			gsap.timeline().to('#t-form, #t-nudge, .ticket__foot', { opacity: 0, height: 0, marginTop: 0, paddingTop: 0, duration: .4 }).add(show).from(done, { y: 20, opacity: 0, duration: .5 });
		} catch (ex) {
			err.textContent = ex.message || T.error; err.hidden = false;
			send.disabled = false; send.innerHTML = label;
		}
	});

	applyProf(); price();
})();
