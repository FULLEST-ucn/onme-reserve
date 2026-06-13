(() => {
  document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', async () => {
      const fd = new FormData();
      fd.append('id', select.dataset.id);
      fd.append('status', select.value);

      select.disabled = true;
      const res = await fetch('../api/reservation-status.php', {
        method: 'POST',
        body: fd
      });
      select.disabled = false;

      if (!res.ok) {
        alert('ステータス更新に失敗しました。');
      }
    });
  });
})();
