(() => {
  const reservationSelect = document.getElementById('reservationSelect');
  const customerSelect = document.getElementById('customerSelect');
  const subtotalInput = document.getElementById('subtotalInput');

  reservationSelect?.addEventListener('change', () => {
    const opt = reservationSelect.options[reservationSelect.selectedIndex];
    const customerId = opt?.dataset.customerId || '0';
    const price = opt?.dataset.price || '0';

    if (customerSelect && customerId !== '0') customerSelect.value = customerId;
    if (subtotalInput && price !== '0') subtotalInput.value = price;
  });

  const modal = document.getElementById('editSaleModal');
  const closeBtn = document.getElementById('closeEditModal');

  document.querySelectorAll('.edit-sale-button').forEach((btn) => {
    btn.addEventListener('click', () => {
      document.getElementById('editSaleId').value = btn.dataset.saleId || '';
      document.getElementById('editCustomerId').value = btn.dataset.customerId || '0';
      document.getElementById('editSubtotal').value = btn.dataset.subtotal || '0';
      document.getElementById('editDiscount').value = btn.dataset.discount || '0';
      document.getElementById('editPayment').value = btn.dataset.payment || 'cash';
      document.getElementById('editNote').value = btn.dataset.note || '';
      modal.hidden = false;
    });
  });

  closeBtn?.addEventListener('click', () => {
    modal.hidden = true;
  });

  modal?.addEventListener('click', (e) => {
    if (e.target === modal) modal.hidden = true;
  });
})();
