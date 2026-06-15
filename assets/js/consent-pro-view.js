(() => {
  const modal = document.getElementById('consentModal');
  const close = document.getElementById('closeConsentModal');
  const title = document.getElementById('modalTitle');
  const meta = document.getElementById('modalMeta');
  const body = document.getElementById('modalBody');
  const date = document.getElementById('modalDate');
  const sigWrap = document.getElementById('signaturePreview');
  const sig = document.getElementById('modalSignature');

  document.querySelectorAll('.consent-row').forEach((row) => {
    row.addEventListener('click', () => {
      title.textContent = row.dataset.title || '同意書';
      const customer = row.dataset.customer || 'テンプレート';
      const phone = row.dataset.phone ? ' / ' + row.dataset.phone : '';
      meta.textContent = customer + phone;
      body.textContent = row.dataset.body || '';

      const signedAt = row.dataset.signedAt || '';
      const created = row.dataset.created || '';
      date.textContent = signedAt ? `署名日時：${signedAt}` : `作成日時：${created}`;

      if (row.dataset.signature) {
        sig.src = row.dataset.signature;
        sigWrap.hidden = false;
      } else {
        sig.removeAttribute('src');
        sigWrap.hidden = true;
      }

      modal.hidden = false;
    });
  });

  close?.addEventListener('click', () => {
    modal.hidden = true;
  });

  modal?.addEventListener('click', (e) => {
    if (e.target === modal) modal.hidden = true;
  });
})();
