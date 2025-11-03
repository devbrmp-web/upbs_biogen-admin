<x-mail::message>
# Order Status Update

Hello {{ $order->customer_name }},

Your order **{{ $order->order_code }}** status has been updated.

## Status Change

**Previous Status:** {{ $previousStatus }}  
**New Status:** {{ $newStatus }}  
**Updated:** {{ now()->format('F j, Y \a\t g:i A') }}

@if($notes)
## Update Notes

{{ $notes }}
@endif

## Order Details

**Order Code:** {{ $order->order_code }}  
**Order Date:** {{ $order->created_at->format('F j, Y \a\t g:i A') }}  
**Shipping Method:** {{ $order->is_pickup ? 'Pickup at BRMP' : 'Delivery' }}  
**Total Amount:** Rp {{ number_format($order->total_amount, 0, ',', '.') }}

@if($order->status === 'paid')
<x-mail::panel>
**Payment Confirmed! ✅**

Thank you for your payment. Your order is now being processed and will be prepared for {{ $order->is_pickup ? 'pickup' : 'delivery coordination' }}.
</x-mail::panel>
@endif

@if($order->status === 'pickup_ready')
<x-mail::panel>
**Ready for Pickup! 📦**

Your order is ready for pickup at our office.

**Office Location:** BRMP Biogen (Lobi)  
**Office Hours:** Monday-Friday 08:00-16:00  
**What to Bring:** Valid ID and your order code: {{ $order->order_code }}
</x-mail::panel>
@endif

@if($order->status === 'delivery_coordination')
<x-mail::panel>
**Delivery Coordination Required 🚚**

Our Call Center team will contact you within 1-2 business days to coordinate the delivery.

**Call Center:** +62-XXX-XXXX-XXXX  
**Office Hours:** Monday-Friday 08:00-16:00
</x-mail::panel>
@endif

@if($order->status === 'shipped')
<x-mail::panel>
**Order Shipped! 🚛**

@if($order->tracking_number)
Your order has been shipped with tracking number: **{{ $order->tracking_number }}**
@if($order->courier_name)
via {{ $order->courier_name }}{{ $order->courier_service ? ' (' . $order->courier_service . ')' : '' }}.
@endif
@else
Your order has been shipped and is on its way to you.
@endif
</x-mail::panel>
@endif

@if($order->status === 'completed')
<x-mail::panel>
**Order Completed! ✅**

Your order has been successfully {{ $order->is_pickup ? 'picked up' : 'delivered' }}. Thank you for choosing us!

We hope you're satisfied with your purchase. If you have any feedback or need support, please don't hesitate to contact us.
</x-mail::panel>
@endif

@if($order->status === 'cancelled')
<x-mail::panel>
**Order Cancelled ❌**

Your order has been cancelled. If this was unexpected or if you have any questions, please contact us immediately.

@if($order->paid_at)
**Refund Information:** If you have already paid for this order, a refund will be processed within 3-5 business days.
@endif
</x-mail::panel>
@endif

## What's Next?

@if($order->status === 'awaiting_payment')
- Complete your payment by {{ $order->payment_deadline->format('F j, Y \a\t g:i A') }}
- Your order will be processed once payment is confirmed
@elseif($order->status === 'paid')
- Your order is being prepared
- You will receive another notification when it's ready
@elseif($order->status === 'processing')
- Your order is being prepared
- You will be notified when it's ready for {{ $order->is_pickup ? 'pickup' : 'delivery coordination' }}
@elseif($order->status === 'pickup_ready')
- Visit our office during business hours to collect your order
- Bring a valid ID and your order code
@elseif($order->status === 'delivery_coordination')
- Wait for our Call Center to contact you
- Prepare your delivery address and preferred time
@elseif($order->status === 'shipped')
- Your order is on its way
- You will be notified when it's delivered
@elseif($order->status === 'completed')
- Thank you for your business!
- We hope to serve you again soon
@endif

<x-mail::button :url="$trackingUrl">
Track Your Order
</x-mail::button>

If you have any questions about this update, please don't hesitate to contact us.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
