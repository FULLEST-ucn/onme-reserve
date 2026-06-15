(() => {
  const canvas = document.getElementById('signaturePad');
  const hidden = document.getElementById('signatureData');
  const clearBtn = document.getElementById('clearSignature');
  const form = document.getElementById('consentForm');
  const checkedCount = document.getElementById('checkedCount');
  const signatureStatus = document.getElementById('signatureStatus');

  if (!canvas || !hidden) return;

  const ctx = canvas.getContext('2d');
  let drawing = false;
  let signed = !!hidden.value;
  let last = null;

  function resizeCanvas() {
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    const existing = hidden.value || canvas.dataset.existingSignature || '';
    const old = signed && existing ? existing : null;

    canvas.width = Math.floor(rect.width * ratio);
    canvas.height = Math.floor(rect.height * ratio);
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#111';
    ctx.lineWidth = 3;

    if (old) {
      const img = new Image();
      img.onload = () => ctx.drawImage(img, 0, 0, rect.width, rect.height);
      img.src = old;
    }
  }

  function point(e) {
    const rect = canvas.getBoundingClientRect();
    const p = e.touches ? e.touches[0] : e;
    return {
      x: p.clientX - rect.left,
      y: p.clientY - rect.top
    };
  }

  function start(e) {
    e.preventDefault();
    drawing = true;
    signed = true;
    last = point(e);
    canvas.closest('.signature-section')?.classList.remove('is-invalid');
  }

  function move(e) {
    if (!drawing) return;
    e.preventDefault();
    const p = point(e);
    ctx.beginPath();
    ctx.moveTo(last.x, last.y);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    last = p;
    updateSignature();
  }

  function end(e) {
    if (!drawing) return;
    e.preventDefault();
    drawing = false;
    updateSignature();
  }

  function updateSignature() {
    hidden.value = canvas.toDataURL('image/png');
    if (signatureStatus) signatureStatus.textContent = signed ? '署名済み' : '未署名';
  }

  function clearSignature() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    signed = false;
    hidden.value = '';
    canvas.dataset.existingSignature = '';
    if (signatureStatus) signatureStatus.textContent = '未署名';
  }

  function updateCheckedCount() {
    const checks = form.querySelectorAll('input[type="checkbox"][name="checked[]"]');
    const checked = form.querySelectorAll('input[type="checkbox"][name="checked[]"]:checked');
    if (checkedCount) checkedCount.textContent = `${checked.length} / ${checks.length}`;
  }

  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  canvas.addEventListener('mousedown', start);
  canvas.addEventListener('mousemove', move);
  window.addEventListener('mouseup', end);

  canvas.addEventListener('touchstart', start, { passive: false });
  canvas.addEventListener('touchmove', move, { passive: false });
  canvas.addEventListener('touchend', end, { passive: false });
  canvas.addEventListener('touchcancel', end, { passive: false });

  if (window.PointerEvent) {
    canvas.addEventListener('pointerdown', start);
    canvas.addEventListener('pointermove', move);
    window.addEventListener('pointerup', end);
    window.addEventListener('pointercancel', end);
  }

  clearBtn?.addEventListener('click', clearSignature);

  form.querySelectorAll('input[type="checkbox"][name="checked[]"]').forEach((el) => {
    el.addEventListener('change', () => {
      updateCheckedCount();
      el.closest('.check-row')?.classList.remove('is-invalid-check');
    });
  });
  updateCheckedCount();

  form.querySelectorAll('input, textarea').forEach((el) => {
    el.addEventListener('input', () => {
      el.closest('.is-invalid')?.classList.remove('is-invalid');
    });
    el.addEventListener('change', () => {
      el.closest('.is-invalid')?.classList.remove('is-invalid');
    });
  });

  form.addEventListener('submit', (e) => {
    if (signed) updateSignature();

    let firstInvalid = null;

    const name = form.querySelector('[name="customer_name"]');
    if (name && !name.value.trim()) {
      name.closest('label')?.classList.add('is-invalid');
      firstInvalid = firstInvalid || name;
    }

    form.querySelectorAll('input[type="checkbox"][name="checked[]"]').forEach((el) => {
      if (!el.checked) {
        el.closest('.check-row')?.classList.add('is-invalid-check');
        firstInvalid = firstInvalid || el.closest('.check-row');
      }
    });

    if (!hidden.value) {
      canvas.closest('.signature-section')?.classList.add('is-invalid');
      firstInvalid = firstInvalid || canvas;
    }

    if (firstInvalid) {
      e.preventDefault();
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  if (signed && hidden.value) {
    if (signatureStatus) signatureStatus.textContent = '署名済み';
  }
})();
