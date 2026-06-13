(() => {
  const LIFF_ID = '2010388216-TNXdkDdZ';
  const today = new Date();
  today.setHours(0,0,0,0);

  const state = {
    menuId: null,
    staffId: null,
    duration: 0,
    price: 0,
    date: document.getElementById('reserveDate')?.value,
    calendarMonth: null,
    start: null,
    options: new Map(),
    lineUserId: '',
  };

  state.calendarMonth = new Date(state.date);
  state.calendarMonth.setDate(1);

  const yen = n => `¥${Number(n || 0).toLocaleString()}`;
  const $ = sel => document.querySelector(sel);
  const $$ = sel => document.querySelectorAll(sel);
  const stepCard = n => document.querySelector(`[data-step="${n}"]`);

  function toast(msg) {
    const el = $('#toast');
    el.textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2600);
  }

  function setProgress(step) {
    $$('.progress-dot').forEach(btn => {
      btn.classList.toggle('active', Number(btn.dataset.jump) <= step);
    });
  }

  function jump(step) {
    setProgress(step);
    stepCard(step)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  function daysInMonth(date) {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
  }

  function renderDateStrip() {
    const strip = $('#dateStrip');
    if (!strip) return;

    const year = state.calendarMonth.getFullYear();
    const month = state.calendarMonth.getMonth();
    const monthTitle = $('#monthTitle');
    if (monthTitle) {
      monthTitle.textContent = `${year}年 ${month + 1}月`;
    }

    const last = daysInMonth(state.calendarMonth);
    const html = [];

    for (let day = 1; day <= last; day++) {
      const d = new Date(year, month, day);
      d.setHours(0,0,0,0);
      const iso = formatDate(d);
      const week = ['SUN','MON','TUE','WED','THU','FRI','SAT'][d.getDay()];
      const disabled = d < today;
      html.push(`
        <button type="button"
          class="date-chip ${iso === state.date ? 'active' : ''} ${disabled ? 'disabled' : ''}"
          data-date="${iso}" ${disabled ? 'disabled' : ''}>
          <small>${week}</small>
          <strong>${day}</strong>
        </button>
      `);
    }

    strip.innerHTML = html.join('');

    setTimeout(() => {
      const active = strip.querySelector('.date-chip.active');
      active?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }, 50);
  }

  async function initLiff() {
    try {
      if (!window.liff) return;
      await liff.init({ liffId: LIFF_ID });
      if (liff.isInClient() && !liff.isLoggedIn()) {
        liff.login();
        return;
      }
      if (liff.isLoggedIn()) {
        const profile = await liff.getProfile();
        state.lineUserId = profile.userId || '';
        $('#formLineUserId').value = state.lineUserId;
        if (profile.displayName) $('#customerName').value = profile.displayName;
        const box = $('#lineProfile');
        if (box) {
          box.hidden = false;
          $('#lineName').textContent = profile.displayName || 'LINE User';
          if (profile.pictureUrl) $('#linePicture').src = profile.pictureUrl;
        }
      }
    } catch (e) {
      console.log('LIFF init skipped:', e);
    }
  }

  function updateSummary() {
    $('#totalDuration').textContent = state.duration ? `${state.duration}分` : '-';
    $('#totalPrice').textContent = state.price ? yen(state.price) : '-';
    $('#formMenuId').value = state.menuId || '';
    $('#formStaffId').value = state.staffId || '';
    $('#formDate').value = state.date || '';
    $('#formStart').value = state.start || '';
    $('#formDuration').value = state.duration || '';
    $('#formLineUserId').value = state.lineUserId || '';
  }

  async function loadSlots() {
    updateSummary();
    renderDateStrip();
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

  $$('.progress-dot').forEach(btn => {
    btn.addEventListener('click', () => jump(Number(btn.dataset.jump)));
  });

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
      jump(2);
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
      jump(4);
    });
  });

  $('#dateStrip')?.addEventListener('click', e => {
    const btn = e.target.closest('.date-chip');
    if (!btn || btn.disabled) return;
    state.date = btn.dataset.date;
    $('#reserveDate').value = state.date;
    state.start = null;
    loadSlots();
  });

  $('#prevMonth')?.addEventListener('click', () => {
    const prev = new Date(state.calendarMonth);
    prev.setMonth(prev.getMonth() - 1);
    state.calendarMonth = prev;
    renderDateStrip();
  });

  $('#nextMonth')?.addEventListener('click', () => {
    const next = new Date(state.calendarMonth);
    next.setMonth(next.getMonth() + 1);
    state.calendarMonth = next;
    renderDateStrip();
  });

  $('#slotList').addEventListener('click', e => {
    const btn = e.target.closest('.slot-item');
    if (!btn) return;
    $$('.slot-item').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    state.start = btn.dataset.start;
    updateSummary();
    jump(5);
  });

  $('#reserveForm').addEventListener('submit', async e => {
    e.preventDefault();
    if (!state.menuId || !state.staffId || !state.start) {
      toast('メニュー・スタッフ・日時を選択してください。');
      return;
    }

    const submitBtn = e.target.querySelector('.reserve-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = '予約中...';

    const fd = new FormData(e.target);
    const res = await fetch('../api/reserve.php', { method: 'POST', body: fd });
    const data = await res.json().catch(() => ({}));

    submitBtn.disabled = false;
    submitBtn.textContent = '予約する';

    if (res.ok && data.ok) {
      location.href = './complete.php';
    } else {
      toast(data.error || '予約に失敗しました。');
      loadSlots();
    }
  });

  initLiff();
  renderDateStrip();
  updateSummary();
})();
