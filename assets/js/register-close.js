(() => {
  const expected = document.getElementById('expectedCashValue');
  const diffText = document.getElementById('cashDiffText');
  const actualText = document.getElementById('actualCashText');
  const inputs = [...document.querySelectorAll('.denom-input')];

  const yen = (n) => {
    const sign = n < 0 ? '-' : '';
    return sign + '¥' + Math.abs(Math.floor(Number(n) || 0)).toLocaleString('ja-JP');
  };

  function updateCashCount() {
    let total = 0;

    inputs.forEach((input) => {
      const value = Number(input.dataset.value || 0);
      const qty = Math.max(0, Number(input.value || 0));
      const rowTotal = value * qty;
      total += rowTotal;

      const label = input.closest('.denom-row');
      const denomTotal = label?.querySelector('.denom-total');
      if (denomTotal) denomTotal.textContent = yen(rowTotal);
    });

    const expectedCash = Number(expected?.value || 0);
    const diff = total - expectedCash;

    if (actualText) actualText.textContent = yen(total);
    if (diffText) {
      diffText.textContent = yen(diff);
      diffText.classList.toggle('diff-bad', diff !== 0);
    }
  }

  inputs.forEach((input) => {
    input.addEventListener('input', updateCashCount);
    input.addEventListener('change', updateCashCount);
  });

  document.querySelectorAll('.denom-plus').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.denom-row')?.querySelector('.denom-input');
      if (!input) return;
      input.value = Math.max(0, Number(input.value || 0)) + 1;
      updateCashCount();
    });
  });

  document.querySelectorAll('.denom-minus').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.denom-row')?.querySelector('.denom-input');
      if (!input) return;
      input.value = Math.max(0, Number(input.value || 0) - 1);
      updateCashCount();
    });
  });

  updateCashCount();
})();
