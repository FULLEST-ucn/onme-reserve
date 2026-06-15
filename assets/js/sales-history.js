(() => {
  const modal = document.getElementById('saleEditModal');
  const closeBtn = document.getElementById('closeSaleModal');

  document.querySelectorAll('.history-sale-row').forEach((row) => {
    row.addEventListener('click', () => {
      document.getElementById('editSaleId').value = row.dataset.saleId || '';
      document.getElementById('editCustomerId').value = row.dataset.customerId || '0';
      document.getElementById('editStaffId').value = row.dataset.staffId || '0';
      document.getElementById('editSubtotal').value = row.dataset.subtotal || '0';
      document.getElementById('editDiscount').value = row.dataset.discount || '0';
      document.getElementById('editPayment').value = row.dataset.payment || 'cash';
      document.getElementById('editDeposit').value = row.dataset.deposit || '0';
      document.getElementById('editStatus').value = row.dataset.status || 'paid';
      const refundInput = document.getElementById('editRefundAmount');
      if (refundInput) {
        refundInput.value = '0';
        refundInput.disabled = (row.dataset.status || 'paid') === 'void';
      }
      document.getElementById('editNote').value = row.dataset.note || '';
      modal.hidden = false;
    });
  });

  closeBtn?.addEventListener('click', () => modal.hidden = true);
  modal?.addEventListener('click', (e) => {
    if (e.target === modal) modal.hidden = true;
  });
})();


(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('date')) {
    const detail = document.getElementById('detail');
    if (detail && !window.location.hash) {
      setTimeout(() => detail.scrollIntoView({ behavior: 'smooth', block: 'start' }), 150);
    }
  }
})();


(() => {
  const status = document.getElementById('editStatus');
  const refund = document.getElementById('editRefundAmount');
  if (!status || !refund) return;

  status.addEventListener('change', () => {
    if (status.value === 'void') {
      refund.value = '0';
      refund.disabled = true;
    } else {
      refund.disabled = false;
    }
  });
})();
