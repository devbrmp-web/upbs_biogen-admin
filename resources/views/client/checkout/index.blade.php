@extends('layouts.vertical')

@section('title', 'Checkout - UPBS BRMP Biogen')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Checkout</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('client.catalog') }}">Catalog</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('client.cart') }}">Cart</a></li>
                        <li class="breadcrumb-item active">Checkout</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('client.checkout.process') }}" method="POST" id="checkout-form">
        @csrf
        <div class="row">
            <!-- Customer Information -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Customer Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('customer_name') is-invalid @enderror" 
                                           id="customer_name" name="customer_name" value="{{ old('customer_name') }}" 
                                           placeholder="Enter your full name" required>
                                    @error('customer_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control @error('customer_phone') is-invalid @enderror" 
                                           id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" 
                                           placeholder="e.g., +62-XXX-XXXX-XXXX" required>
                                    @error('customer_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('customer_address') is-invalid @enderror" 
                                      id="customer_address" name="customer_address" rows="3" 
                                      placeholder="Enter your complete address" required>{{ old('customer_address') }}</textarea>
                            @error('customer_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="customer_email" class="form-label">Email Address <span class="text-muted">(Optional but recommended)</span></label>
                            <input type="email" class="form-control @error('customer_email') is-invalid @enderror" 
                                   id="customer_email" name="customer_email" value="{{ old('customer_email') }}" 
                                   placeholder="your.email@example.com">
                            <div class="form-text">Email is recommended for order confirmation and tracking notifications.</div>
                            @error('customer_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Shipping Method -->
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Shipping Method</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-check-lg mb-3">
                                    <input class="form-check-input @error('shipping_method') is-invalid @enderror" 
                                           type="radio" name="shipping_method" id="shipping_pickup" 
                                           value="pickup" {{ old('shipping_method', 'pickup') == 'pickup' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="shipping_pickup">
                                        <div class="d-flex align-items-center">
                                            <i class="bx bx-store-alt me-2 text-primary fs-18"></i>
                                            <div>
                                                <strong>Pickup at BRMP</strong>
                                                <div class="text-muted small">Free - Pickup at BRMP Biogen Office</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div id="pickup-info" class="alert alert-info small">
                                    <i class="bx bx-info-circle me-1"></i>
                                    <strong>Office Address:</strong> BRMP Biogen (Lobi)<br>
                                    <strong>Office Hours:</strong> Monday-Friday 08:00-16:00<br>
                                    Please bring a valid ID and your order code.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-check form-check-lg mb-3">
                                    <input class="form-check-input @error('shipping_method') is-invalid @enderror" 
                                           type="radio" name="shipping_method" id="shipping_delivery" 
                                           value="delivery" {{ old('shipping_method') == 'delivery' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="shipping_delivery">
                                        <div class="d-flex align-items-center">
                                            <i class="bx bx-package me-2 text-warning fs-18"></i>
                                            <div>
                                                <strong>Delivery</strong>
                                                <div class="text-muted small">Call Center Coordination Required</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <div id="delivery-info" class="alert alert-warning small" style="display: none;">
                                    <i class="bx bx-phone me-1"></i>
                                    <strong>Available Couriers:</strong><br>
                                    
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="courier_name" id="courier_pos" 
                                                       value="Pos Indonesia" {{ old('courier_name') == 'Pos Indonesia' ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="courier_pos">
                                                    <strong>Pos Indonesia</strong><br>
                                                    <span class="text-muted">Max weight: 10kg</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="courier_name" id="courier_indah" 
                                                       value="Indah Cargo" {{ old('courier_name') == 'Indah Cargo' ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="courier_indah">
                                                    <strong>Indah Cargo</strong><br>
                                                    <span class="text-muted">No weight limit</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-2">
                                    <strong>Contact:</strong> +62-XXX-XXXX-XXXX<br>
                                    <strong>Hours:</strong> Monday-Friday 08:00-16:00<br>
                                    <em>Shipping coordination will be handled via Call Center/WhatsApp.</em>
                                    
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="delivery_coordination_acknowledged" 
                                               name="delivery_coordination_acknowledged" value="1">
                                        <label class="form-check-label small" for="delivery_coordination_acknowledged">
                                            I understand that shipping coordination will be handled via Call Center/WhatsApp
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('shipping_method')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="card">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input @error('terms_accepted') is-invalid @enderror" 
                                   type="checkbox" id="terms_accepted" name="terms_accepted" value="1" 
                                   {{ old('terms_accepted') ? 'checked' : '' }}>
                            <label class="form-check-label" for="terms_accepted">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> <span class="text-danger">*</span>
                            </label>
                            @error('terms_accepted')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Order Summary</h4>
                    </div>
                    <div class="card-body">
                        @if(isset($cartItems) && count($cartItems) > 0)
                            @foreach($cartItems as $index => $item)
                                <input type="hidden" name="items[{{ $index }}][variety_id]" value="{{ $item['variety_id'] }}">
                                <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                                @if(isset($item['seed_lot_id']))
                                    <input type="hidden" name="items[{{ $index }}][seed_lot_id]" value="{{ $item['seed_lot_id'] }}">
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="mb-0">{{ $item['variety_name'] }}</h6>
                                        <small class="text-muted">Qty: {{ $item['quantity'] }}</small>
                                        @if(isset($item['seed_class']))
                                            <span class="badge bg-info ms-1">{{ $item['seed_class'] }}</span>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <strong>Rp {{ number_format($item['total_price'], 0, ',', '.') }}</strong>
                                    </div>
                                </div>
                                @if(!$loop->last)
                                    <hr class="my-2">
                                @endif
                            @endforeach
                            
                            <hr>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Subtotal:</span>
                                <strong>Rp {{ number_format($subtotal, 0, ',', '.') }}</strong>
                            </div>
                            
                            @if(isset($serviceFee) && $serviceFee > 0)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Service Fee (1%):</span>
                                <strong>Rp {{ number_format($serviceFee, 0, ',', '.') }}</strong>
                            </div>
                            @endif
                            
                            @if(isset($appFee) && $appFee > 0)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Application Fee:</span>
                                <strong>Rp {{ number_format($appFee, 0, ',', '.') }}</strong>
                            </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Shipping:</span>
                                <span id="shipping-cost">Free (Pickup)</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Total:</h5>
                                <h5 class="mb-0" id="total-amount">Rp {{ number_format($totalAmount ?? $subtotal, 0, ',', '.') }}</h5>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="bx bx-cart text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted">Your cart is empty</p>
                                <a href="{{ route('client.catalog') }}" class="btn btn-primary">Browse Products</a>
                            </div>
                        @endif
                    </div>
                    
                    @if(isset($cartItems) && count($cartItems) > 0)
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success w-100" id="checkout-btn">
                                <i class="bx bx-check-circle me-1"></i>
                                Place Order
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Order Processing</h6>
                <p>All orders are subject to availability and confirmation. BRMP Biogen reserves the right to refuse or cancel orders at any time.</p>
                
                <h6>Shipping Methods</h6>
                <p><strong>Pickup at BRMP:</strong> Orders can be collected at BRMP Biogen office during business hours (Monday-Friday 08:00-16:00). Please bring valid ID and order code.</p>
                <p><strong>Delivery:</strong> Shipping coordination must be arranged through our Call Center/WhatsApp. No automatic shipping calculation is applied.</p>
                
                <h6>Payment</h6>
                <p>Payment must be completed within the specified time limit. Orders will be automatically cancelled if payment is not received.</p>
                
                <h6>Quality Assurance</h6>
                <p>All seeds are subject to quality control standards. Any quality issues must be reported within 7 days of receipt.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pickupRadio = document.getElementById('shipping_pickup');
    const deliveryRadio = document.getElementById('shipping_delivery');
    const pickupInfo = document.getElementById('pickup-info');
    const deliveryInfo = document.getElementById('delivery-info');
    const shippingCost = document.getElementById('shipping-cost');
    const deliveryAcknowledged = document.getElementById('delivery_coordination_acknowledged');
    const checkoutForm = document.getElementById('checkout-form');
    
    function updateShippingDisplay() {
        if (pickupRadio.checked) {
            pickupInfo.style.display = 'block';
            deliveryInfo.style.display = 'none';
            shippingCost.textContent = 'Free (Pickup)';
        } else if (deliveryRadio.checked) {
            pickupInfo.style.display = 'none';
            deliveryInfo.style.display = 'block';
            shippingCost.textContent = 'Call Center Coordination';
        }
    }
    
    pickupRadio.addEventListener('change', updateShippingDisplay);
    deliveryRadio.addEventListener('change', updateShippingDisplay);
    
    // Initialize display
    updateShippingDisplay();
    
    // Calculate total weight from cart items
    function calculateTotalWeight() {
        let totalWeight = 0;
        @if(isset($cartItems) && count($cartItems) > 0)
            @foreach($cartItems as $item)
                totalWeight += {{ $item['quantity'] }}; // Assuming 1 unit = 1 kg for seeds
            @endforeach
        @endif
        return totalWeight;
    }
    
    // Courier selection validation
    const courierPosRadio = document.getElementById('courier_pos');
    const courierIndahRadio = document.getElementById('courier_indah');
    
    function validateCourierSelection() {
        const totalWeight = calculateTotalWeight();
        
        if (courierPosRadio && courierPosRadio.checked && totalWeight > 10) {
            alert(`Total weight (${totalWeight} kg) exceeds Pos Indonesia limit of 10 kg. Please select Indah Cargo for heavier orders.`);
            courierIndahRadio.checked = true;
            return false;
        }
        return true;
    }
    
    // Add event listeners for courier selection
    if (courierPosRadio) {
        courierPosRadio.addEventListener('change', validateCourierSelection);
    }
    
    // Form validation
    checkoutForm.addEventListener('submit', function(e) {
        if (deliveryRadio.checked && !deliveryAcknowledged.checked) {
            e.preventDefault();
            alert('Please acknowledge that shipping coordination will be handled via Call Center/WhatsApp for delivery orders.');
            deliveryAcknowledged.focus();
            return false;
        }
        
        // Validate courier selection for delivery
        if (deliveryRadio.checked) {
            const courierSelected = document.querySelector('input[name="courier_name"]:checked');
            if (!courierSelected) {
                e.preventDefault();
                alert('Please select a courier for delivery.');
                return false;
            }
            
            if (!validateCourierSelection()) {
                e.preventDefault();
                return false;
            }
        }
        
        // Disable submit button to prevent double submission
        const submitBtn = document.getElementById('checkout-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Processing...';
    });
});
</script>
@endpush