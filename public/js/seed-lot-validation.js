/**
 * Seed Lot Form Validation
 * Provides real-time validation for seed lot forms based on seed class selection
 */

document.addEventListener('DOMContentLoaded', function() {
    const seedClassSelect = document.getElementById('seed_class_id');
    const unitSelect = document.getElementById('unit');
    const quantityInput = document.getElementById('quantity');
    
    if (!seedClassSelect || !unitSelect || !quantityInput) {
        return;
    }

    function updateUnitOptions() {
        const selectedOption = seedClassSelect.options[seedClassSelect.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            unitSelect.innerHTML = '<option value="">Select Unit</option>';
            return;
        }

        const category = selectedOption.getAttribute('data-category');
        const defaultUnit = selectedOption.getAttribute('data-unit') || 'kg';
        const currentValue = unitSelect.value;
        
        // Clear current options except the first one
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        
        // Build dynamic options
        let options = [];
        if (category === 'weight') {
            options = [
                { value: 'kg', text: 'Kilogram (kg)' },
                { value: 'ton', text: 'Ton' }
            ];
            // If default_unit is something else (like gram), add it
            if (defaultUnit !== 'kg' && defaultUnit !== 'ton') {
                options.push({ value: defaultUnit, text: defaultUnit.charAt(0).toUpperCase() + defaultUnit.slice(1) });
            }
        } else {
            // Unit-based category: allow the default unit
            options = [
                { value: defaultUnit, text: defaultUnit.charAt(0).toUpperCase() + defaultUnit.slice(1) }
            ];
            // Add piece/bottle as fallback options if not already the default
            if (defaultUnit !== 'piece') options.push({ value: 'piece', text: 'Piece' });
            if (defaultUnit !== 'bottle') options.push({ value: 'bottle', text: 'Bottle' });
        }
        
        // Add new options
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.text;
            
            if (option.value === currentValue) {
                optionElement.selected = true;
            }
            unitSelect.appendChild(optionElement);
        });
        
        // If current value is not valid for new seed class, clear selection
        const validValues = options.map(opt => opt.value);
        if (currentValue && !validValues.includes(currentValue)) {
            unitSelect.value = '';
            showUnitValidationMessage(category, defaultUnit);
        } else {
            hideUnitValidationMessage();
        }

        // Update quantity behavior based on seed class
        updateQuantityBehavior(category);
    }

    function showUnitValidationMessage(category, defaultUnit) {
        let message = '';
        if (category === 'weight') {
            message = `Weight-based classes should use kg, ton, or ${defaultUnit}.`;
        } else {
            message = `Unit-based classes should use ${defaultUnit}, piece, or bottle.`;
        }
        
        if (message) {
            hideUnitValidationMessage();
            const messageDiv = document.createElement('div');
            messageDiv.className = 'alert alert-warning mt-2 seed-class-unit-message';
            messageDiv.innerHTML = `<small><i class="bx bx-info-circle"></i> ${message}</small>`;
            unitSelect.parentNode.appendChild(messageDiv);
        }
    }

    function hideUnitValidationMessage() {
        const existingMessage = document.querySelector('.seed-class-unit-message');
        if (existingMessage) existingMessage.remove();
    }

    // Initialize
    seedClassSelect.addEventListener('change', updateUnitOptions);
    
    if (seedClassSelect.value) {
        updateUnitOptions();
    }

    function updateQuantityBehavior(category) {
        if (!quantityInput) return;
        quantityInput.setAttribute('step', '1');
        quantityInput.setAttribute('min', '0');
        showQuantityValidationMessage('Quantity must be an integer (no decimals) for all classes.');
    }

    // Enforce integer input (Integer-Only Policy)
    quantityInput.addEventListener('keydown', function(e) {
        // Block: . , - e
        if (['.', ',', '-', 'e', 'E'].includes(e.key)) {
            e.preventDefault();
        }
    });

    quantityInput.addEventListener('input', function() {
        // Strict normalization: strip anything non-digit and floor if necessary
        let val = this.value.replace(/[^0-9]/g, '');
        if (val !== '') {
            this.value = Math.floor(parseInt(val));
        } else {
            this.value = '';
        }
    });

    function showQuantityValidationMessage(message) {
        hideQuantityValidationMessage();
        const msgDiv = document.createElement('div');
        msgDiv.className = 'alert alert-warning mt-2 seed-class-quantity-message';
        msgDiv.innerText = message;
        quantityInput.parentNode.appendChild(msgDiv);
    }

    function hideQuantityValidationMessage() {
        const existing = document.querySelector('.seed-class-quantity-message');
        if (existing) existing.remove();
    }
});
