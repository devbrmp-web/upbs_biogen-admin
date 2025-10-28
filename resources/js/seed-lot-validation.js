/**
 * Seed Lot Form Validation
 * Provides real-time validation for seed lot forms based on seed class selection
 */

document.addEventListener('DOMContentLoaded', function() {
    const seedClassSelect = document.getElementById('seed_class_id');
    const unitSelect = document.getElementById('unit');
    const quantityInput = document.getElementById('quantity');
    const unitHint = document.getElementById('unit-hint');
    
    if (!seedClassSelect || !unitSelect || !quantityInput) {
        return;
    }

    // Store all unit options for restoration
    const allUnitOptions = [
        { value: 'kg', text: 'Kilogram (kg)' },
        { value: 'ton', text: 'Ton' },
        { value: 'piece', text: 'Piece' },
        { value: 'bottle', text: 'Bottle' }
    ];

    // Unit options for different seed classes
    const unitOptions = {
        'BS': [
            { value: 'kg', text: 'Kilogram (kg)' },
            { value: 'ton', text: 'Ton' }
        ],
        'FS': [
            { value: 'kg', text: 'Kilogram (kg)' },
            { value: 'ton', text: 'Ton' }
        ],
        'PL': [
            { value: 'bottle', text: 'Bottle' },
            { value: 'piece', text: 'Piece' }
        ]
    };

    // Hint messages for different seed classes
    const hintMessages = {
        'BS': 'Use kg or ton. 1 ton = 1000 kg (stored as kg).',
        'FS': 'Use kg or ton. 1 ton = 1000 kg (stored as kg).',
        'PL': 'Use bottle or piece. Planlet not counted in total kg.'
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
        const options = unitOptions[seedClassCode] || allUnitOptions;
        
        // Add new options
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.text;
            unitSelect.appendChild(optionElement);
        });
        
        // Auto-switch unit if current value is not valid for new seed class
        const validValues = options.map(opt => opt.value);
        if (currentValue && !validValues.includes(currentValue)) {
            // Auto-switch to appropriate unit based on seed class
            if (seedClassCode === 'BS' || seedClassCode === 'FS') {
                // If previous was piece/bottle, switch to kg
                if (currentValue === 'piece' || currentValue === 'bottle') {
                    unitSelect.value = 'kg';
                }
            } else if (seedClassCode === 'PL') {
                // If previous was kg/ton, switch to bottle
                if (currentValue === 'kg' || currentValue === 'ton') {
                    unitSelect.value = 'bottle';
                }
            }
        } else if (currentValue && validValues.includes(currentValue)) {
            // Restore previous selection if valid
            unitSelect.value = currentValue;
        }

        // Update hint message
        updateUnitHint(seedClassCode);
    }

    function updateUnitHint(seedClassCode) {
        if (!unitHint) return;
        
        const message = hintMessages[seedClassCode] || '';
        unitHint.textContent = message;
        
        // Add additional note for ton selection
        const selectedUnit = unitSelect.value;
        if (selectedUnit === 'ton' && (seedClassCode === 'BS' || seedClassCode === 'FS')) {
            unitHint.textContent = message + ' Note: When using ton, system stores/calculates in kg.';
        }
    }

    // Listen for unit changes to update hint
    unitSelect.addEventListener('change', function() {
        const selectedOption = seedClassSelect.options[seedClassSelect.selectedIndex];
        const seedClassCode = selectedOption.getAttribute('data-code');
        updateUnitHint(seedClassCode);
    });

    // Listen for seed class changes
    seedClassSelect.addEventListener('change', updateUnitOptions);
    
    // Initial update if seed class is already selected
    if (seedClassSelect.value) {
        updateUnitOptions();
    }

    /**
     * Update quantity input step and validation behavior
     * - Integer-only for ALL classes
     */
    function updateQuantityBehavior() {
        if (!quantityInput) return;
        // Enforce integer-only for ALL seed classes
        quantityInput.setAttribute('step', '1');
        quantityInput.setAttribute('min', '0');
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

    // Initialize quantity behavior
    updateQuantityBehavior();
});