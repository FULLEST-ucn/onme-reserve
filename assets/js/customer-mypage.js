(() => {
  const LIFF_ID = '2010388216-TNXdkDdZ';

  function toast(msg) {
    const el = document.getElementById('toast');
    if (!el) return alert(msg);
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2600);
  }

  async function initLiffRedirect() {
    try {
      if (!window.liff) return;
      await liff.init({ liffId: LIFF_ID });

      const url = new URL(location.href);
      if (url.searchParams.get('line_user_id')) return;

      if (liff.isInClient() && !liff.isLoggedIn()) {
        liff.login();
        return;
      }

      if (liff.isLoggedIn()) {
        const profile = await liff.getProfile();
        if (profile.userId) {
          url.searchParams.set('line_user_id', profile.userId);
          history.replaceState(null, '', url.toString());
          location.reload();
        }
      }
    } catch (e) {
      console.log('LIFF mypage skipped:', e);
    }
  }

  document.getElementById('phoneSearchForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const phone = document.getElementById('phoneSearch').value.trim();
    if (!phone) {
      toast('電話番号を入力してください。');
      return;
    }
    const url = new URL(location.href);
    url.searchParams.set('phone', phone);
    location.href = url.toString();
  });

  document.querySelectorAll('[data-cancel-id]').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('予約をキャンセルしますか？')) return;

      const fd = new FormData();
      fd.append('id', btn.dataset.cancelId);
      fd.append('status', 'cancelled');

      const res = await fetch('../api/reservation-status.php', {
        method: 'POST',
        body: fd
      });

      if (res.ok) {
        toast('キャンセルしました。');
        setTimeout(() => location.reload(), 900);
      } else {
        toast('キャンセルに失敗しました。');
      }
    });
  });

  initLiffRedirect();
})();
