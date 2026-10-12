// Stainless Laundry Services - Order Calculation & Validation
document.addEventListener('DOMContentLoaded', function () {
    const orderForm = document.getElementById('orderForm');
    if (!orderForm) return;

    const checkboxes = orderForm.querySelectorAll('.item-checkbox');
    const quantityInputs = orderForm.querySelectorAll('.item-qty');
    const summaryItemsEl = document.getElementById('summaryItems');
    const subtotalEl = document.getElementById('calcSubtotal');
    const pickupFeeEl = document.getElementById('calcPickupFee');
    const grandTotalEl = document.getElementById('calcGrandTotal');
    const submitBtn = orderForm.querySelector('button[type="submit"]');

    const pickupFee = parseFloat(orderForm.getAttribute('data-pickup-fee') || '0');

    function updateOrderTotals() {
        let subtotal = 0;
        let selectedCount = 0;
        let breakdownHtml = '';

        checkboxes.forEach(function (cb) {
            const row = cb.closest('.item-picker-row');
            const qtyInput = row.querySelector('.item-qty');
            const unitPrice = parseFloat(cb.getAttribute('data-price') || '0');
            const itemName = cb.getAttribute('data-name') || '';

            if (cb.checked) {
                row.classList.add('selected');
                qtyInput.removeAttribute('disabled');
                const qty = Math.max(1, parseInt(qtyInput.value || '1', 10));
                const lineTotal = unitPrice * qty;
                subtotal += lineTotal;
                selectedCount++;

                breakdownHtml += `
                    <div class="calc-line">
                        <span>${itemName} &times; ${qty}</span>
                        <strong>KES ${lineTotal.toLocaleString()}</strong>
                    </div>
                `;
            } else {
                row.classList.remove('selected');
                qtyInput.setAttribute('disabled', 'disabled');
            }
        });

        if (summaryItemsEl) {
            summaryItemsEl.innerHTML = breakdownHtml || '<div class="calc-line text-muted"><span>No items selected yet</span><span>KES 0</span></div>';
        }

        const grandTotal = subtotal > 0 ? (subtotal + pickupFee) : 0;

        if (subtotalEl) subtotalEl.textContent = 'KES ' + subtotal.toLocaleString();
        if (pickupFeeEl) pickupFeeEl.textContent = 'KES ' + pickupFee.toLocaleString();
        if (grandTotalEl) grandTotalEl.textContent = 'KES ' + grandTotal.toLocaleString();

        if (submitBtn) {
            if (selectedCount === 0) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Select at least 1 item to proceed';
            } else {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirm & Place Laundry Order';
            }
        }
    }

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', updateOrderTotals);
    });

    quantityInputs.forEach(function (inp) {
        inp.addEventListener('input', updateOrderTotals);
        inp.addEventListener('change', updateOrderTotals);
    });

    // Prevent double form submission
    orderForm.addEventListener('submit', function (e) {
        let anyChecked = false;
        checkboxes.forEach(function (cb) {
            if (cb.checked) anyChecked = true;
        });

        if (!anyChecked) {
            e.preventDefault();
            alert('Please select at least one laundry item before submitting.');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting order... Please wait';
        }
    });

    // Initialize on load
    updateOrderTotals();
});
