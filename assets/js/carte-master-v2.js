(() => {
  document.querySelectorAll('.customer-card').forEach((card) => {
    const target = card.querySelector('.press-delete-target');
    const link = card.querySelector('.customer-card-link');
    let timer = null;
    let longPressed = false;

    const clear = () => {
      if (timer) clearTimeout(timer);
      timer = null;
      card.classList.remove('deleting');
    };

    const start = () => {
      longPressed = false;
      card.classList.add('deleting');

      timer = setTimeout(async () => {
        longPressed = true;
        const name = card.dataset.customerName || 'この顧客';
        const ok = confirm(`${name}を削除しますか？\n\n一覧から非表示になります。\n削除してもよろしいですか？`);
        clear();

        if (!ok) return;

        try {
          const res = await fetch('./api/customer-delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_id: card.dataset.customerId })
          });
          const json = await res.json();
          if (!json.ok) throw new Error(json.error || '削除に失敗しました');

          card.remove();
        } catch (err) {
          alert(err.message);
        }
      }, 800);
    };

    const end = () => {
      clear();
      setTimeout(() => { longPressed = false; }, 150);
    };

    target?.addEventListener('mousedown', start);
    window.addEventListener('mouseup', end);
    target?.addEventListener('touchstart', start, { passive: true });
    window.addEventListener('touchend', end);
    window.addEventListener('touchcancel', end);

    link?.addEventListener('click', (e) => {
      if (longPressed) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  });
})();
