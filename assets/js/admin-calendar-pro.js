(() => {
  const API_LIST = '../api/calendar-pro.php';
  const API_AVAILABILITY = '../api/availability.php';
  const calendar = document.getElementById('proCalendar');
  const dateInput = document.getElementById('calendarDate');
  const modal = document.getElementById('availabilityModal');
  const form = document.getElementById('availabilityForm');
  const tooltip = document.getElementById('dragTooltip');
  const toast = document.getElementById('proToast');

  const baseMin = 8 * 60;
  const endMin = 23 * 60;
  const pxPerMin = 2.05;
  const snapMin = 30;

  let staff = JSON.parse(calendar.dataset.staff || '[]');
  let payload = {availability: [], reservations: [], stats: {}};

  const pad = n => String(n).padStart(2, '0');
  const timeToMin = t => {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
  };
  const minToTime = m => `${pad(Math.floor(m / 60))}:${pad(m % 60)}`;
  const snap = min => Math.round(min / snapMin) * snapMin;
  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

  function showToast(message) {
    toast.textContent = message;
    toast.hidden = false;
    setTimeout(() => toast.hidden = true, 2600);
  }

  function showTooltip(x, y, text) {
    tooltip.textContent = text;
    tooltip.style.left = `${x + 12}px`;
    tooltip.style.top = `${y + 12}px`;
    tooltip.hidden = false;
  }

  function hideTooltip() {
    tooltip.hidden = true;
  }

  function currentColumnFromX(x) {
    const cols = [...document.querySelectorAll('.pro-staff-col')];
    const found = cols.find(col => {
      const r = col.getBoundingClientRect();
      return x >= r.left && x <= r.right;
    });
    return found ? Number(found.dataset.staffId) : null;
  }

  function openModal(staffId = 1, start = '10:00', end = '18:00') {
    document.getElementById('modalId').value = '';
    document.getElementById('modalStaff').value = staffId;
    document.getElementById('modalDate').value = dateInput.value;
    document.getElementById('modalStart').value = start;
    document.getElementById('modalEnd').value = end;
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    modal.setAttribute('aria-hidden', 'true');
  }

  async function load() {
    const res = await fetch(`${API_LIST}?date=${encodeURIComponent(dateInput.value)}`);
    payload = await res.json();
    render();
    renderNowLine();
  }

  function renderNowLine() {
    document.querySelectorAll('.now-line').forEach(line => {
      const today = new Date().toISOString().slice(0,10);
      if (today !== dateInput.value) {
        line.hidden = true;
        return;
      }
      const now = new Date();
      const min = now.getHours() * 60 + now.getMinutes();
      if (min < baseMin || min > endMin) {
        line.hidden = true;
        return;
      }
      line.hidden = false;
      line.style.top = `${(min - baseMin) * pxPerMin}px`;
    });
  }

  function render() {
    document.querySelectorAll('.pro-block').forEach(el => el.remove());
    document.getElementById('mobileProList').innerHTML = '';

    document.getElementById('kpiReservations').textContent = payload.stats?.reservation_count ?? 0;
    document.getElementById('kpiAvailable').textContent = payload.stats?.availability_count ?? 0;
    document.getElementById('kpiDate').textContent = dateInput.value.slice(5).replace('-', '/');

    const items = [
      ...(payload.availability || []),
      ...(payload.reservations || [])
    ];

    items.forEach(item => {
      const col = document.querySelector(`.pro-staff-col[data-staff-id="${item.staff_id}"] .pro-grid`);
      const start = timeToMin(item.start_time);
      const end = timeToMin(item.end_time);
      const top = (start - baseMin) * pxPerMin;
      const height = Math.max(42, (end - start) * pxPerMin);

      if (col) {
        const block = document.createElement('div');
        block.className = `pro-block ${item.kind}`;
        block.dataset.id = item.id;
        block.dataset.kind = item.kind;
        block.dataset.staffId = item.staff_id;
        block.style.top = `${top}px`;
        block.style.height = `${height}px`;
        block.innerHTML = `
          <strong>${item.label}</strong>
          <span>${item.start_time}〜${item.end_time}</span>
          <i></i>
        `;
        col.appendChild(block);
        dragBlock(block);
      }
    });

    renderMobile(items);
  }

  function renderMobile(items) {
    const root = document.getElementById('mobileProList');
    root.innerHTML = staff.map(s => {
      const rows = items.filter(i => String(i.staff_id) === String(s.id));
      return `
        <section class="mobile-pro-card">
          <div class="mobile-pro-head">
            <h2>${s.name}</h2>
            <button type="button" data-add="${s.id}">＋追加</button>
          </div>
          ${rows.length ? rows.map(i => `
            <article class="mobile-pro-item ${i.kind}">
              <strong>${i.label}</strong>
              <span>${i.start_time}〜${i.end_time}</span>
            </article>
          `).join('') : '<p class="mobile-empty">予定なし</p>'}
        </section>
      `;
    }).join('');
  }

  function dragBlock(block) {
    let mode = null;
    let startY = 0;
    let startTop = 0;
    let startHeight = 0;
    let latestX = 0;

    block.addEventListener('pointerdown', e => {
      mode = e.target.tagName === 'I' ? 'resize' : 'move';
      startY = e.clientY;
      latestX = e.clientX;
      startTop = parseFloat(block.style.top);
      startHeight = parseFloat(block.style.height);
      block.setPointerCapture(e.pointerId);
      block.classList.add('dragging');
    });

    block.addEventListener('pointermove', e => {
      if (!mode) return;
      latestX = e.clientX;
      const dy = e.clientY - startY;

      if (mode === 'resize') {
        const h = clamp(startHeight + dy, 30 * pxPerMin, (endMin - baseMin) * pxPerMin);
        block.style.height = `${h}px`;
      } else {
        const t = clamp(startTop + dy, 0, (endMin - baseMin) * pxPerMin);
        block.style.top = `${t}px`;
      }

      const top = parseFloat(block.style.top);
      const height = parseFloat(block.style.height);
      const start = snap(baseMin + top / pxPerMin);
      const end = snap(start + height / pxPerMin);
      showTooltip(e.clientX, e.clientY, `${minToTime(start)}〜${minToTime(end)}`);
    });

    block.addEventListener('pointerup', async () => {
      if (!mode) return;
      const newStaffId = currentColumnFromX(latestX) || Number(block.dataset.staffId);
      const top = parseFloat(block.style.top);
      const height = parseFloat(block.style.height);
      const start = snap(baseMin + top / pxPerMin);
      const end = snap(start + height / pxPerMin);

      block.classList.remove('dragging');
      hideTooltip();
      mode = null;

      const res = await fetch(API_LIST, {
        method: 'PATCH',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          id: block.dataset.id,
          kind: block.dataset.kind,
          staff_id: newStaffId,
          date: dateInput.value,
          start_time: minToTime(start),
          end_time: minToTime(end),
        })
      });

      if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        showToast(data.error || '更新できませんでした');
      }

      await load();
    });
  }

  document.getElementById('addAvailability').addEventListener('click', () => openModal());
  document.getElementById('closeModal').addEventListener('click', closeModal);
  document.getElementById('mobileProList').addEventListener('click', e => {
    const btn = e.target.closest('[data-add]');
    if (btn) openModal(btn.dataset.add);
  });

  document.querySelectorAll('.pro-cell').forEach(cell => {
    cell.addEventListener('dblclick', () => {
      const col = cell.closest('.pro-staff-col');
      const staffId = col.dataset.staffId;
      const start = baseMin + Number(cell.dataset.index) * 30;
      openModal(staffId, minToTime(start), minToTime(start + 120));
    });
  });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(form);
    await fetch(API_AVAILABILITY, {method: 'POST', body: fd});
    closeModal();
    await load();
  });

  document.getElementById('prevDay').addEventListener('click', () => {
    const d = new Date(dateInput.value);
    d.setDate(d.getDate() - 1);
    dateInput.value = d.toISOString().slice(0,10);
    load();
  });

  document.getElementById('nextDay').addEventListener('click', () => {
    const d = new Date(dateInput.value);
    d.setDate(d.getDate() + 1);
    dateInput.value = d.toISOString().slice(0,10);
    load();
  });

  dateInput.addEventListener('change', load);
  setInterval(renderNowLine, 60000);
  load();
})();
