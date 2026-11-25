/* smash : formulaire de contact démo (sans Gravity Forms) → route REST smash/v1/contact. */
(() => {
	const form = document.getElementById('cform');
	if (!form) return;
	const err = form.querySelector('.cform__error'), done = form.querySelector('.cform__done'), btn = form.querySelector('button[type=submit]');
	form.addEventListener('input', e => e.target.removeAttribute('aria-invalid'));
	form.addEventListener('submit', async e => {
		e.preventDefault();
		err.hidden = true;
		const req = [...form.querySelectorAll('[required]')];
		req.forEach(f => f.setAttribute('aria-invalid', !f.value.trim() || (f.type === 'email' && !f.checkValidity())));
		const bad = req.find(f => f.getAttribute('aria-invalid') === 'true');
		if (bad) { bad.focus(); return; }
		btn.disabled = true;
		const data = Object.fromEntries(new FormData(form));
		try {
			const res = await fetch(form.dataset.rest, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Smash-Nonce': form.dataset.nonce, 'X-WP-Nonce': form.dataset.wpnonce }, body: JSON.stringify(data) });
			const json = await res.json().catch(() => ({}));
			if (!res.ok || !json.ok) throw new Error(json.message || '');
			[...form.children].forEach(c => { if (c !== done) c.hidden = true; });
			done.hidden = false; done.focus();
		} catch (ex) {
			err.textContent = ex.message || err.dataset.fallback || '';
			err.hidden = !err.textContent; btn.disabled = false;
		}
	});
})();
