(() => {
  const table = document.getElementById('calendarTable');
  const status = document.getElementById('moveStatus');
  const modal = document.getElementById('calendarEditModal');
  const modalClose = document.getElementById('modalClose');
  const editForm = document.getElementById('calendarEditForm');
  if (!table) return;

  const slotHeight = 72;
  const startHour = 8;
  let state = null;
  let clickTimer = null;
  let suppressClick = false;
  const indicator = document.createElement('div');
  indicator.className = 'drop-indicator';
  indicator.style.display = 'none';

  const setStatus = (text) => { if (status) status.textContent = text; };
  const getPoint = (e) => { const p = e.touches ? e.touches[0] : e; return { x: p.clientX, y: p.clientY }; };
  const snapY = (y) => Math.max(0, Math.round(y / slotHeight) * slotHeight);
  const yToTime = (y) => {
    const slot = Math.max(0, Math.round(y / slotHeight));
    const minutes = startHour * 60 + slot * 30;
    return `${String(Math.floor(minutes / 60)).padStart(2,'0')}:${String(minutes % 60).padStart(2,'0')}`;
  };
  const staffColumnFromPoint = (x, y) => {
    const el = document.elementFromPoint(x, y);
    return el ? el.closest('.staff-column') : null;
  };

  function beginDrag(eventEl, e) {
    const p = getPoint(e);
    const rect = eventEl.getBoundingClientRect();
    state = {
      el: eventEl,
      type: eventEl.dataset.type,
      id: eventEl.dataset.id,
      pointerOffsetY: p.y - rect.top,
      originalTransition: eventEl.style.transition || ''
    };
    suppressClick = true;
    eventEl.classList.add('dragging');
    eventEl.style.transition = 'none';
    document.body.style.userSelect = 'none';
    setStatus('移動中：移動先で離してください');
  }

  function moveDrag(e) {
    if (!state) return;
    e.preventDefault();
    const p = getPoint(e);
    const column = staffColumnFromPoint(p.x, p.y);
    document.querySelectorAll('.staff-column.drop-target').forEach(c => c.classList.remove('drop-target'));
    if (!column) { indicator.style.display = 'none'; return; }
    column.classList.add('drop-target');
    if (indicator.parentElement !== column) column.appendChild(indicator);
    const rect = column.getBoundingClientRect();
    const y = snapY(p.y - rect.top + column.scrollTop - state.pointerOffsetY);
    indicator.style.top = `${y}px`;
    indicator.style.display = 'block';
    state.dropColumn = column;
    state.dropY = y;
  }

  async function endDrag() {
    if (!state) return;
    const eventEl = state.el;
    const column = state.dropColumn;
    const y = state.dropY;
    document.querySelectorAll('.staff-column.drop-target').forEach(c => c.classList.remove('drop-target'));
    indicator.style.display = 'none';
    document.body.style.userSelect = '';
    eventEl.classList.remove('dragging', 'drag-ready');
    eventEl.style.transition = state.originalTransition;

    if (!column || y === undefined) {
      setStatus('1タップ編集 / ダブルタップ削除');
      state = null;
      setTimeout(() => suppressClick = false, 300);
      return;
    }

    const time = yToTime(y);
    setStatus('保存中...');
    try {
      const res = await fetch('./api/calendar-move.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
          type:state.type,
          id:state.id,
          staff_id:column.dataset.staffId,
          date:table.dataset.date,
          time
        })
      });
      const json = await res.json();
      if (!json.ok) throw new Error(json.error || '保存に失敗しました');
      location.reload();
    } catch (err) {
      alert(err.message);
      location.reload();
    }
    state = null;
    setTimeout(() => suppressClick = false, 300);
  }

  function openEditModal(eventEl) {
    document.getElementById('editType').value = eventEl.dataset.type;
    document.getElementById('editId').value = eventEl.dataset.id;
    document.getElementById('editDate').value = eventEl.dataset.date;
    document.getElementById('editStart').value = eventEl.dataset.start;
    document.getElementById('editEnd').value = eventEl.dataset.end;
    document.getElementById('editNote').value = eventEl.dataset.note || '';
    document.getElementById('modalTitle').textContent = eventEl.dataset.type === 'availability' ? '空き枠編集' : '予約時間編集';
    document.getElementById('noteLabel').hidden = eventEl.dataset.type !== 'availability';
    modal.hidden = false;
  }

  async function deleteEvent(eventEl) {
    const label = eventEl.dataset.type === 'availability' ? 'この空き枠' : 'この予約';
    if (!confirm(`${label}を削除しますか？`)) return;
    try {
      const res = await fetch('./api/calendar-delete.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({type:eventEl.dataset.type, id:eventEl.dataset.id})
      });
      const json = await res.json();
      if (!json.ok) throw new Error(json.error || '削除に失敗しました');
      location.reload();
    } catch (err) {
      alert(err.message);
    }
  }

  document.querySelectorAll('.draggable-event').forEach((eventEl) => {
    let pressTimer = null;
    let moved = false;
    let startPoint = null;
    const clear = () => {
      if (pressTimer) clearTimeout(pressTimer);
      pressTimer = null;
      eventEl.classList.remove('drag-ready');
    };

    const startPress = (e) => {
      startPoint = getPoint(e);
      moved = false;
      clear();
      pressTimer = setTimeout(() => {
        eventEl.classList.add('drag-ready');
        beginDrag(eventEl, e);
      }, 450);
    };

    const movePress = (e) => {
      const p = getPoint(e);
      if (startPoint && (Math.abs(p.x - startPoint.x) > 8 || Math.abs(p.y - startPoint.y) > 8)) moved = true;
      if (state && state.el === eventEl) moveDrag(e);
      else if (moved) clear();
    };

    const endPress = () => {
      clear();
      if (state && state.el === eventEl) endDrag();
    };

    eventEl.addEventListener('mousedown', startPress);
    window.addEventListener('mousemove', movePress);
    window.addEventListener('mouseup', endPress);
    eventEl.addEventListener('touchstart', startPress, { passive:false });
    window.addEventListener('touchmove', movePress, { passive:false });
    window.addEventListener('touchend', endPress);
    window.addEventListener('touchcancel', endPress);

    eventEl.addEventListener('click', () => {
      if (suppressClick) return;
      if (clickTimer) {
        clearTimeout(clickTimer);
        clickTimer = null;
        deleteEvent(eventEl);
        return;
      }
      clickTimer = setTimeout(() => {
        clickTimer = null;
        openEditModal(eventEl);
      }, 260);
    });
  });

  if (modalClose) modalClose.addEventListener('click', () => modal.hidden = true);
  if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) modal.hidden = true; });

  if (editForm) editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    try {
      const res = await fetch('./api/calendar-update.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
          type:document.getElementById('editType').value,
          id:document.getElementById('editId').value,
          date:document.getElementById('editDate').value,
          start_time:document.getElementById('editStart').value,
          end_time:document.getElementById('editEnd').value,
          note:document.getElementById('editNote').value
        })
      });
      const json = await res.json();
      if (!json.ok) throw new Error(json.error || '保存に失敗しました');
      location.reload();
    } catch (err) {
      alert(err.message);
    }
  });
})();
