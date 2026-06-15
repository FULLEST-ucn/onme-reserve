(() => {
  const cart = [];
  const yen = (n) => '¥' + Math.max(0, Math.floor(Number(n) || 0)).toLocaleString('ja-JP');

  const els = {
    cartList: document.getElementById('cartList'),
    subtotalInput: document.getElementById('subtotalInput'),
    discountInput: document.getElementById('discountInput'),
    discountDisplay: document.getElementById('discountDisplay'),
    depositHidden: document.getElementById('depositInputHidden'),
    depositDisplay: document.getElementById('depositDisplay'),
    depositArea: document.getElementById('depositArea'),
    depositError: document.getElementById('depositError'),
    checkoutButton: document.getElementById('checkoutButton'),
    noteHidden: document.getElementById('noteHidden'),
    noteTextarea: document.getElementById('noteTextarea'),
    subtotalText: document.getElementById('subtotalText'),
    discountText: document.getElementById('discountText'),
    totalText: document.getElementById('totalText'),
    netText: document.getElementById('netText'),
    changeText: document.getElementById('changeText'),
    form: document.getElementById('posRegisterForm'),
    toast: document.getElementById('changeToast'),
    toastAmount: document.getElementById('changeToastAmount'),
    customName: document.getElementById('customItemName'),
    customPrice: document.getElementById('customItemPrice'),
    customAdd: document.getElementById('addCustomItem'),
  };

  function totals() {
    const subtotal = cart.reduce((sum, item) => sum + item.price, 0);
    const discount = Math.max(0, Number(els.discountDisplay?.value || 0));
    const total = Math.max(0, subtotal - discount);
    const net = Math.floor(total / 1.1);
    const deposit = Math.max(0, Number(els.depositDisplay?.value || 0));
    const method = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';
    const change = method === 'cash' ? Math.max(0, deposit - total) : 0;
    const shortCash = method === 'cash' && total > 0 && deposit < total;

    els.subtotalInput.value = subtotal;
    els.discountInput.value = discount;
    els.depositHidden.value = deposit;
    els.noteHidden.value = els.noteTextarea?.value || '';

    els.subtotalText.textContent = yen(subtotal);
    els.discountText.textContent = yen(discount);
    els.totalText.textContent = yen(total);
    els.netText.textContent = yen(net);
    els.changeText.textContent = yen(change);

    els.depositArea?.classList.toggle('is-short', shortCash);
    if (els.depositError) els.depositError.hidden = !shortCash;

    if (els.checkoutButton) {
      els.checkoutButton.disabled = total <= 0 || shortCash;
    }

    return { subtotal, discount, total, net, deposit, change, method, shortCash };
  }

  function renderCart() {
    if (!els.cartList) return;
    if (!cart.length) {
      els.cartList.innerHTML = '<p class="muted-text">左のメニューをタップしてください。</p>';
    } else {
      els.cartList.innerHTML = cart.map((item, i) => `
        <article class="cart-item">
          <strong>${escapeHtml(item.name)}</strong>
          <span>${yen(item.price)}</span>
          <button type="button" data-remove="${i}">×</button>
        </article>
      `).join('');
    }
    totals();
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (s) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[s]));
  }

  document.querySelectorAll('.menu-item').forEach((btn) => {
    btn.addEventListener('click', () => {
      cart.push({
        id: btn.dataset.id,
        name: btn.dataset.name || 'メニュー',
        price: Number(btn.dataset.price || 0)
      });
      renderCart();
    });
  });

  els.customAdd?.addEventListener('click', () => {
    const name = (els.customName?.value || '').trim() || 'その他';
    const price = Number(els.customPrice?.value || 0);

    if (price <= 0) {
      alert('その他の金額を入力してください。');
      return;
    }

    cart.push({ id: 'custom', name, price });

    if (els.customName) els.customName.value = '';
    if (els.customPrice) els.customPrice.value = '';
    renderCart();
  });

  els.customPrice?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      els.customAdd?.click();
    }
  });

  els.cartList?.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-remove]');
    if (!btn) return;
    cart.splice(Number(btn.dataset.remove), 1);
    renderCart();
  });

  document.querySelectorAll('[data-discount]').forEach((btn) => {
    btn.addEventListener('click', () => {
      els.discountDisplay.value = btn.dataset.discount || '0';
      totals();
    });
  });

  els.discountDisplay?.addEventListener('input', totals);
  els.depositDisplay?.addEventListener('input', totals);
  els.noteTextarea?.addEventListener('input', totals);
  document.querySelectorAll('input[name="payment_method"]').forEach((r) => r.addEventListener('change', totals));

  els.form?.addEventListener('submit', (e) => {
    const t = totals();

    if (t.total <= 0) {
      e.preventDefault();
      alert('メニューを選択してください。');
      return;
    }

    if (t.shortCash) {
      e.preventDefault();
      alert('預り金が合計金額より少ないため、会計登録できません。');
      els.depositDisplay?.focus();
      return;
    }

    if (t.method === 'cash' && t.change > 0) {
      els.toastAmount.textContent = yen(t.change);
      els.toast.hidden = false;
      setTimeout(() => { els.toast.hidden = true; }, 3000);
    }
  });

  renderCart();
})();
