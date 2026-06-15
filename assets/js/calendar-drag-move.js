(() => {
  const table = document.getElementById('calendarTable');
  const status = document.getElementById('moveStatus');
  if (!table) return;

  const slotHeight = 72;
  const startHour = 8;
  let state = null;
  let indicator = document.createElement('div');
  indicator.className = 'drop-indicator';
  indicator.style.display = 'none';

  function setStatus(text) {
    if (status) status.textContent = text;
  }

  function yToTime(y) {
    const slot = Math.max(0, Math.round(y / slotHeight));
    const minutes = startHour * 60 + slot * 30;
    const hh = String(Math.floor(minutes / 60)).padStart(2, '0');
    const mm = String(minutes % 60).padStart(2, '0');
    return `${hh}:${mm}`;
  }

  function snapY(y) {
    return Math.max(0, Math.round(y / slotHeight) * slotHeight);
  }

  function getPoint(e) {
    const p = e.touches ? e.touches[0] : e;
    return { x: p.clientX, y: p.clientY };
  }

  function staffColumnFromPoint(x, y) {
    const el = document.elementFromPoint(x, y);
    return el ? el.closest('.staff-column') : null;
  }

  function beginDrag(eventEl, e) {
    const p = getPoint(e);
    const rect = eventEl.getBoundingClientRect();

    state = {
      el: eventEl,
      type: eventEl.dataset.type,
      id: eventEl.dataset.id,
      pointerOffsetY: p.y - rect.top,
      originalTransition: eventEl.style.transition || '',
      currentColumn: eventEl.closest('.staff-column')
    };

    eventEl.classList.add('dragging');
    eventEl.style.transition = 'none';
    document.body.style.userSelect = 'none';
    setStatus('移動中：移動先で離してください');

    if (state.currentColumn && !indicator.parentElement) {
      state.currentColumn.appendChild(indicator);
    }
  }

  function moveDrag(e) {
    if (!state) return;
    e.preventDefault();

    const p = getPoint(e);
    const column = staffColumnFromPoint(p.x, p.y);
    document.querySelectorAll('.staff-column.drop-target').forEach(c => c.classList.remove('drop-target'));

    if (!column) {
      indicator.style.display = 'none';
      return;
    }

    column.classList.add('drop-target');
    if (indicator.parentElement !== column) {
      column.appendChild(indicator);
    }

    const colRect = column.getBoundingClientRect();
    const y = snapY(p.y - colRect.top + column.scrollTop - state.pointerOffsetY);
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
      setStatus('長押しで移動');
      state = null;
      return;
    }

    const staffId = column.dataset.staffId;
    const time = yToTime(y);
    const date = table.dataset.date;

    eventEl.style.top = `${y}px`;
    if (eventEl.parentElement !== column) {
      column.appendChild(eventEl);
    }

    setStatus('保存中...');

    try {
      const res = await fetch('./api/calendar-move.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: state.type,
          id: state.id,
          staff_id: staffId,
          date,
          time
        })
      });

      const json = await res.json();
      if (!json.ok) throw new Error(json.error || '保存に失敗しました');

      setStatus(`保存しました：${time}`);
      setTimeout(() => location.reload(), 350);
    } catch (err) {
      alert(err.message);
      location.reload();
    }

    state = null;
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
      if (startPoint && (Math.abs(p.x - startPoint.x) > 8 || Math.abs(p.y - startPoint.y) > 8)) {
        moved = true;
      }

      if (state && state.el === eventEl) {
        moveDrag(e);
      } else if (moved) {
        clear();
      }
    };

    const endPress = () => {
      clear();
      if (state && state.el === eventEl) endDrag();
    };

    eventEl.addEventListener('mousedown', startPress);
    window.addEventListener('mousemove', movePress);
    window.addEventListener('mouseup', endPress);

    eventEl.addEventListener('touchstart', startPress, { passive: false });
    window.addEventListener('touchmove', movePress, { passive: false });
    window.addEventListener('touchend', endPress);
    window.addEventListener('touchcancel', endPress);
  });
})();
