(() => {
  const API = '../api/availability.php';
  const calendar = document.getElementById('desktopCalendar');
  const mobile = document.getElementById('mobileTimeline');
  const dateInput = document.getElementById('calendarDate');
  const modal = document.getElementById('availabilityModal');
  const form = document.getElementById('availabilityForm');
  const modalDate = document.getElementById('modalDate');
  const modalStaff = document.getElementById('modalStaff');
  const modalStart = document.getElementById('modalStart');
  const modalEnd = document.getElementById('modalEnd');
  let items = [];

  const pad = n => String(n).padStart(2, '0');
  const minToTime = m => `${pad(Math.floor(m / 60))}:${pad(m % 60)}`;
  const timeToMin = t => {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
  };
  const clamp = (v, min, max) => Math.max(min, Math.min(max, v));
  const baseMin = 9 * 60;
  const pxPerMin = 2.2;
  const gridMinutes = 30;
  const snap = min => Math.round(min / gridMinutes) * gridMinutes;

  function openModal(staffId = 1, start = '10:00', end = '18:00') {
    modalStaff.value = staffId;
    modalDate.value = dateInput.value;
    modalStart.value = start;
    modalEnd.value = end;
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    modal.setAttribute('aria-hidden', 'true');
  }

  async function load() {
    const date = dateInput.value;
    const res = await fetch(`${API}?date=${encodeURIComponent(date)}`);
    items = await res.json();
    render();
  }

  function render() {
    document.querySelectorAll('.availability-block').forEach(el => el.remove());
    document.querySelectorAll('.mobile-items').forEach(el => el.innerHTML = '');

    items.forEach(item => {
      const startMin = timeToMin(item.start_time);
      const endMin = timeToMin(item.end_time);
      const top = (startMin - baseMin) * pxPerMin;
      const height = Math.max(44, (endMin - startMin) * pxPerMin);

      const col = document.querySelector(`.staff-column[data-staff-id="${item.staff_id}"] .slot-grid`);
      if (col) {
        const block = document.createElement('div');
        block.className = `availability-block ${item.type || 'available'}`;
        block.style.top = `${top}px`;
        block.style.height = `${height}px`;
        block.dataset.id = item.id;
        block.dataset.staffId = item.staff_id;
        block.innerHTML = `
          <strong>${item.label || '受付可能'}</strong>
          <span>${item.start_time}〜${item.end_time}</span>
          <i class="resize-handle"></i>
        `;
        col.appendChild(block);
        makeDraggable(block);
      }

      const list = document.getElementById(`mobileStaff${item.staff_id}`);
      if (list) {
        const card = document.createElement('article');
        card.className = `mobile-schedule-item ${item.type || 'available'}`;
        card.innerHTML = `
          <div>
            <strong>${item.label || '受付可能'}</strong>
            <span>${item.start_time}〜${item.end_time}</span>
          </div>
          <button type="button" data-edit-id="${item.id}">編集</button>
        `;
        list.appendChild(card);
      }
    });
  }

  function makeDraggable(block) {
    let mode = null;
    let startY = 0;
    let startTop = 0;
    let startHeight = 0;

    block.addEventListener('pointerdown', (e) => {
      if (e.target.classList.contains('resize-handle')) mode = 'resize';
      else mode = 'move';
      startY = e.clientY;
      startTop = parseFloat(block.style.top);
      startHeight = parseFloat(block.style.height);
      block.setPointerCapture(e.pointerId);
      block.classList.add('dragging');
    });

    block.addEventListener('pointermove', (e) => {
      if (!mode) return;
      const dy = e.clientY - startY;
      if (mode === 'move') {
        const newTop = clamp(startTop + dy, 0, 28 * 30 * pxPerMin);
        block.style.top = `${newTop}px`;
      } else {
        const newHeight = clamp(startHeight + dy, 30 * pxPerMin, 12 * 60 * pxPerMin);
        block.style.height = `${newHeight}px`;
      }
    });

    block.addEventListener('pointerup', async () => {
      if (!mode) return;
      mode = null;
      block.classList.remove('dragging');
      const id = block.dataset.id;
      const top = parseFloat(block.style.top);
      const height = parseFloat(block.style.height);
      const start = snap(baseMin + top / pxPerMin);
      const end = snap(start + height / pxPerMin);
      await fetch(API, {
        method: 'PATCH',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          id,
          date: dateInput.value,
          start_time: minToTime(start),
          end_time: minToTime(end)
        })
      });
      await load();
    });

    block.addEventListener('dblclick', async () => {
      if (!confirm('この受付可能時間を削除しますか？')) return;
      await fetch(API, {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: block.dataset.id })
      });
      await load();
    });
  }

  document.getElementById('addAvailability')?.addEventListener('click', () => openModal());
  document.getElementById('closeModal')?.addEventListener('click', closeModal);
  document.querySelectorAll('.mini-add').forEach(btn => {
    btn.addEventListener('click', () => openModal(btn.dataset.staffId));
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    await fetch(API, {
      method: 'POST',
      body: fd
    });
    closeModal();
    await load();
  });

  dateInput?.addEventListener('change', () => {
    modalDate.value = dateInput.value;
    load();
  });

  document.getElementById('prevDay')?.addEventListener('click', () => {
    const d = new Date(dateInput.value);
    d.setDate(d.getDate() - 1);
    dateInput.value = d.toISOString().slice(0,10);
    load();
  });

  document.getElementById('nextDay')?.addEventListener('click', () => {
    const d = new Date(dateInput.value);
    d.setDate(d.getDate() + 1);
    dateInput.value = d.toISOString().slice(0,10);
    load();
  });

  document.addEventListener('click', (e) => {
    const edit = e.target.closest('[data-edit-id]');
    if (!edit) return;
    const item = items.find(x => String(x.id) === String(edit.dataset.editId));
    if (item) openModal(item.staff_id, item.start_time, item.end_time);
  });

  load();
})();
