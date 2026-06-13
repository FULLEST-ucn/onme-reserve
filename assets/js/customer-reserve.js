(() => {
  const state = {
    menuId: null,
    staffId: null,
    duration: 0,
    price: 0,
    date: document.getElementById('reserveDate')?.value,
    start: null,
    options: new Map(),
  };

  const yen = n => `¥${Number(n || 0).toLocaleString()}`;
  const $ = sel => document.querySelector(sel);
  const $$ = sel => document.querySelectorAll(sel);

  function toast(msg) {
    const el = $('#toast');
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2200);
  }

  function updateSummary() {
    $('#totalDuration').textContent = state.duration ? `${state.duration}分` : '-';
    $('#totalPrice').textContent = state.price ? yen(state.price) : '-';
    $('#formMenuId').value = state.menuId || '';
    $('#formStaffId').value = state.staffId || '';
    $('#formDate').value = state.date || '';
    $('#formStart').value = state.start || '';
    $('#formDuration').value = state.duration || '';
  }

  async function loadSlots() {
    updateSummary();
    const list = $('#slotList');
    if (!state.menuId || !state.staffId || !state.duration) {
      list.innerHTML = '<p class="empty">メニューとスタッフを選択してください。</p>';
      return;
    }

    list.innerHTML = '<p class="empty">空き時間を確認中...</p>';

    const url = `../api/slots.php?date=${encodeURIComponent(state.date)}&staff_id=${state.staffId}&duration=${state.duration}`;
    const res = await fetch(url);
    const slots = await res.json();

    if (!Array.isArray(slots) || slots.length === 0) {
      list.innerHTML = '<p class="empty">この条件で予約可能な時間がありません。</p>';
      return;
    }

    list.innerHTML = slots.map(slot => `
      <button type="button" class="slot-item" data-start="${slot.start_time}">
        <strong>${slot.start_time}</strong>
        <span>${slot.end_time}まで</span>
      </button>
    `).join('');
  }

  $$('.menu-item').forEach(btn => {
    btn.addEventListener('click', () => {
      $$('.menu-item').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const optionDuration = [...state.options.values()].reduce((sum, o) => sum + o.duration, 0);
      const optionPrice = [...state.options.values()].reduce((sum, o) => sum + o.price, 0);
      state.menuId = btn.dataset.id;
      state.duration = Number(btn.dataset.duration) + optionDuration;
      state.price = Number(btn.dataset.price) + optionPrice;
      state.start = null;
      loadSlots();
    });
  });

  $$('.option-item').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      btn.classList.toggle('active');

      if (btn.classList.contains('active')) {
        state.options.set(id, {
          duration: Number(btn.dataset.duration),
          price: Number(btn.dataset.price),
        });
      } else {
        state.options.delete(id);
      }

      const activeMenu = $('.menu-item.active');
      if (activeMenu) {
        const optionDuration = [...state.options.values()].reduce((sum, o) => sum + o.duration, 0);
        const optionPrice = [...state.options.values()].reduce((sum, o) => sum + o.price, 0);
        state.duration = Number(activeMenu.dataset.duration) + optionDuration;
        state.price = Number(activeMenu.dataset.price) + optionPrice;
      }
      state.start = null;
      loadSlots();
    });
  });

  $$('.staff-item').forEach(btn => {
    btn.addEventListener('click', () => {
      $$('.staff-item').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      state.staffId = btn.dataset.id;
      state.start = null;
      loadSlots();
    });
  });

  $('#slotList').addEventListener('click', e => {
    const btn = e.target.closest('.slot-item');
    if (!btn) return;
    $$('.slot-item').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    state.start = btn.dataset.start;
    updateSummary();
  });

  $('#reserveDate').addEventListener('change', e => {
    state.date = e.target.value;
    state.start = null;
    loadSlots();
  });

  $('#prevDate').addEventListener('click', () => {
    const d = new Date(state.date);
    d.setDate(d.getDate() - 1);
    state.date = d.toISOString().slice(0,10);
    $('#reserveDate').value = state.date;
    loadSlots();
  });

  $('#nextDate').addEventListener('click', () => {
    const d = new Date(state.date);
    d.setDate(d.getDate() + 1);
    state.date = d.toISOString().slice(0,10);
    $('#reserveDate').value = state.date;
    loadSlots();
  });

  $('#reserveForm').addEventListener('submit', async e => {
    e.preventDefault();
    if (!state.menuId || !state.staffId || !state.start) {
      toast('メニュー・スタッフ・日時を選択してください。');
      return;
    }
    const fd = new FormData(e.target);
    const res = await fetch('../api/reserve.php', { method: 'POST', body: fd });
    const data = await res.json().catch(() => ({}));
    if (res.ok && data.ok) {
      toast('予約が完了しました。');
      e.target.reset();
    } else {
      toast(data.error || '予約に失敗しました。');
    }
  });

  updateSummary();
})();
