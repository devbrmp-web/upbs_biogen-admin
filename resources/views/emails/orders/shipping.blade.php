<x-mail::message>
@if($isPickup)
# Pickup Instructions
@else
# Delivery Coordination
@endif

Hello {{ $order->customer_name }},

@if($isPickup)
Great news! Your order **{{ $order->order_code }}** is ready for pickup.
@else
Your order **{{ $order->order_code }}** requires delivery coordination through our Call Center.
@endif

## Order Details

**Order Code:** {{ $order->order_code }}  
**Order Date:** {{ $order->created_at->format('F j, Y \a\t g:i A') }}  
**Status:** {{ ucfirst(str_replace('_', ' ', $order->status)) }}  
**Shipping Method:** {{ $order->is_pickup ? 'Pickup at BRMP' : 'Delivery' }}

@if($isPickup)
## Pickup Information

{{ $shippingInstructions }}

### What to Bring:
- Valid ID (KTP, SIM, or Passport)
- Your order code: **{{ $order->order_code }}**
- This email (printed or on your phone)

<x-mail::panel>
**Office Location:**  
BRMP Biogen (Lobi)  
**Office Hours:** Monday-Friday 08:00-16:00  

Please ensure you arrive during office hours. If you cannot pick up during these times, please contact us to arrange an alternative.
</x-mail::panel>

@else
## Delivery Coordination Required

{{ $shippingInstructions }}

### Next Steps:
1. **Wait for Call Center Contact:** Our team will contact you within 1-2 business days
2. **Confirm Details:** Please confirm your delivery address and preferred time
3. **Coordinate Schedule:** Work with our team to arrange a suitable delivery time

<x-mail::panel>
**Call Center Information:**  
**Phone/WhatsApp:** +62-XXX-XXXX-XXXX  
**Office Hours:** Monday-Friday 08:00-16:00  

If you need to contact us first or have specific delivery requirements, please call during office hours.
</x-mail::panel>

### Important Notes:
- No automatic shipping calculation is applied in the system
- All delivery arrangements are coordinated manually
- Delivery fees (if any) will be discussed during coordination
- Please ensure someone is available to receive the delivery
@endif

## Order Summary

**Total Amount:** Rp {{ number_format($order->total_amount, 0, ',', '.') }}  
@if($order->status === 'paid')
**Payment Status:** ✅ Paid
@else
**Payment Status:** {{ ucfirst(str_replace('_', ' ', $order->status)) }}
@endif

<x-mail::button :url="route('client.orders.track', ['order_code' => $order->order_code])">
Track Your Order
</x-mail::button>

@if($isPickup)
We look forward to seeing you at our office!
@else
Our Call Center team will be in touch with you soon to arrange the delivery.
@endif

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
