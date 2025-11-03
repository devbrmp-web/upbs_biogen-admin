<x-mail::message>
# Order Confirmation

Hello {{ $order->customer_name }},

Thank you for your order! We have received your order and it is being processed.

## Order Details

**Order Code:** {{ $order->order_code }}  
**Order Date:** {{ $order->created_at->format('F j, Y \a\t g:i A') }}  
**Status:** {{ ucfirst(str_replace('_', ' ', $order->status)) }}

## Customer Information

**Name:** {{ $order->customer_name }}  
**Phone:** {{ $order->customer_phone }}  
@if($order->customer_email)
**Email:** {{ $order->customer_email }}  
@endif
**Address:** {{ $order->customer_address }}

## Order Items

@foreach($orderItems as $item)
- **{{ $item->variety_name }}** ({{ $item->variety_sku }})
  - Quantity: {{ number_format($item->quantity) }} units
  - Unit Price: Rp {{ number_format($item->unit_price, 0, ',', '.') }}
  - Subtotal: Rp {{ number_format($item->total_price, 0, ',', '.') }}

@endforeach

## Order Summary

**Subtotal:** Rp {{ number_format($order->subtotal, 0, ',', '.') }}  
**Shipping:** {{ $order->is_pickup ? 'Free (Pickup)' : 'Call Center Coordination' }}  
**Total Amount:** Rp {{ number_format($order->total_amount, 0, ',', '.') }}

## Shipping Method: {{ $order->is_pickup ? 'Pickup at BRMP' : 'Delivery' }}

{{ $shippingInstructions }}

@if($order->is_delivery)
<x-mail::panel>
**Important for Delivery Orders:**

Our Call Center team will contact you within 1-2 business days to coordinate the delivery schedule and confirm your address details.

**Call Center Contact:** +62-XXX-XXXX-XXXX  
**Office Hours:** Monday-Friday 08:00-16:00
</x-mail::panel>
@endif

## Next Steps

@if($order->status === 'awaiting_payment')
1. **Complete Payment:**
@if($order->payment_deadline)
Please complete your payment by {{ $order->payment_deadline->format('F j, Y \a\t g:i A') }}
@else
Please complete your payment as soon as possible to avoid expiration.
@endif
2. **Payment Confirmation:** Your order will be processed once payment is confirmed
@if($order->is_pickup)
3. **Pickup Notification:** You will receive a notification when your order is ready for pickup at BRMP
@else
3. **Delivery Coordination:** Our Call Center will contact you to arrange delivery
@endif
@endif

<x-mail::button :url="route('client.orders.track', ['order_code' => $order->order_code])">
Track Your Order
</x-mail::button>

If you have any questions about your order, please don't hesitate to contact us.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
