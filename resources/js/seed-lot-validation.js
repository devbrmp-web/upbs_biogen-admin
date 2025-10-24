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

    // Unit options for different seed classes
    const unitOptions = {
        'BS': [
            { value: 'kg', text: 'Kilogram (kg)' },
            { value: 'gram', text: 'Gram (g)' },
            { value: 'ton', text: 'Ton' }
        ],
        'FS': [
            { value: 'kg', text: 'Kilogram (kg)' },
            { value: 'gram', text: 'Gram (g)' },
            { value: 'ton', text: 'Ton' }
        ],
        'PL': [
            { value: 'bottle', text: 'Bottle' },
            { value: 'piece', text: 'Piece' }
        ],
        'default': [
            { value: 'kg', text: 'Kilogram (kg)' },
            { value: 'gram', text: 'Gram (g)' },
            { value: 'ton', text: 'Ton' },
            { value: 'piece', text: 'Piece' },
            { value: 'bottle', text: 'Bottle' }
        ]
    };

    function updateUnitOptions() {
        const selectedOption = seedClassSelect.options[seedClassSelect.selectedIndex];
        const seedClassCode = selectedOption.getAttribute('data-code');
        const currentValue = unitSelect.value;
        
        // Clear current options except the first one (placeholder)
        while (unitSelect.children.length > 1) {
            unitSelect.removeChild(unitSelect.lastChild);
        }
        
        // Get appropriate unit options
        const options = unitOptions[seedClassCode] || unitOptions['default'];
        
        // Add new options
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.text;
            
            // Restore previous selection if valid
            if (option.value === currentValue) {
                optionElement.selected = true;
            }
            
            unitSelect.appendChild(optionElement);
        });
        
        // If current value is not valid for new seed class, clear selection
        const validValues = options.map(opt => opt.value);
        if (currentValue && !validValues.includes(currentValue)) {
            unitSelect.value = '';
            
            // Show validation message
            showUnitValidationMessage(seedClassCode);
        } else {
            hideUnitValidationMessage();
        }

        // Update quantity behavior based on seed class
        updateQuantityBehavior(seedClassCode);
    }

    function showUnitValidationMessage(seedClassCode) {
        let message = '';
        
        switch (seedClassCode) {
            case 'BS':
            case 'FS':
                message = 'Basic Seed (BS) and Foundation Seed (FS) must use weight-based units (kg, gram, ton).';
                break;
            case 'PL':
                message = 'Planlet (PL) must use bottle or piece units.';
                break;
        }
        
        if (message) {
            // Remove existing message
            hideUnitValidationMessage();
            
            // Create new message
            const messageDiv = document.createElement('div');
            messageDiv.className = 'alert alert-warning mt-2 seed-class-unit-message';
            messageDiv.innerHTML = `<small><i class="bx bx-info-circle"></i> ${message}</small>`;
            
            unitSelect.parentNode.appendChild(messageDiv);
        }
    }

    function hideUnitValidationMessage() {
        const existingMessage = document.querySelector('.seed-class-unit-message');
        if (existingMessage) {
            existingMessage.remove();
        }
    }

    // Update seed class options to include data-code attribute
    function initializeSeedClassOptions() {
        const seedClassOptions = seedClassSelect.querySelectorAll('option[value]');
        
        seedClassOptions.forEach(option => {
            const text = option.textContent;
            
            // Extract code from text like "Basic Seed (BS)" -> "BS"
            const codeMatch = text.match(/\(([^)]+)\)$/);
            if (codeMatch) {
                option.setAttribute('data-code', codeMatch[1]);
            }
        });
    }

    // Initialize
    initializeSeedClassOptions();
    
    // Listen for seed class changes
    seedClassSelect.addEventListener('change', updateUnitOptions);
    
    // Initial update if seed class is already selected
    if (seedClassSelect.value) {
        updateUnitOptions();
    }

    /**
     * Update quantity input step and validation behavior
     * - PL (Planlet): integer only, step=1
     * - Others (BS, FS, default): numeric with decimals, step=0.01
     */
    function updateQuantityBehavior(seedClassCode) {
        if (!quantityInput) return;
        // Enforce integer-only for ALL seed classes
        quantityInput.setAttribute('step', '1');
        quantityInput.setAttribute('min', '0');
        showQuantityValidationMessage('Quantity harus bilangan bulat (tanpa desimal) untuk semua kelas.');
    }

    quantityInput.addEventListener('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        // Block any non-digit input (including '.')
        if (!/[0-9]/.test(char)) {
            e.preventDefault();
        }
    });

    quantityInput.addEventListener('input', function() {
        // Strip non-digits to enforce integer-only
        this.value = this.value.replace(/[^0-9]/g, '');
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