(() => {
  const openBtn = document.getElementById('openConsentModal');
  const modal = document.getElementById('consentModal');
  const closeBtn = document.getElementById('closeConsentModal');

  if (!modal) return;

  openBtn?.addEventListener('click', () => {
    modal.hidden = false;
  });

  closeBtn?.addEventListener('click', () => {
    modal.hidden = true;
  });

  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.hidden = true;
  });
})();
