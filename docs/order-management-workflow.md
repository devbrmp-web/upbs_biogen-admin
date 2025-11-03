# Order Management Workflow Documentation

## Overview
This document describes the complete order management workflow for the Website UPBS BRMP Biogen (WUB) system, including guest checkout, payment processing, order fulfillment, and administrative features.

## System Architecture

### Core Models
- **Order**: Main order entity with customer information and status tracking
- **OrderItem**: Individual items within an order with quantity and pricing
- **Payment**: Payment information and status tracking
- **Shipment**: Shipping information and tracking details
- **AuditLog**: Comprehensive audit trail for all order-related actions

### Database Relationships
```
Order (1) -> (many) OrderItem
Order (1) -> (1) Payment
Order (1) -> (0..1) Shipment
Order (many) -> (many) AuditLog
```

## Order Lifecycle

### 1. Guest Checkout Process (SKPL-WUB-IN-020)
**Route**: `POST /checkout`
**Controller**: `App\Http\Controllers\Client\CheckoutController@store`

#### Required Customer Data
- Name (required)
- Phone number (required)
- Address (required)
- Email (optional, recommended for notifications)

#### Process Flow
1. Validate customer data and cart items
2. Calculate shipping costs via API integration
3. Create order with `awaiting_payment` status
4. Generate unique order code (format: ORD-YYYYMMDD-XXXX)
5. Create order items with snapshot pricing
6. Create payment record with gateway integration
7. Send order confirmation email (if email provided)
8. Redirect to payment gateway

### 2. Payment Processing (SKPL-WUB-PR-022)
**Webhook Route**: `POST /webhook/payment`
**Controller**: `App\Http\Controllers\WebhookController@handlePayment`

#### Payment Methods Supported
- Virtual Account (VA)
- QRIS
- Bank Transfer

#### Webhook Processing
1. Verify webhook signature for security
2. Update payment status based on gateway response
3. Store PNBP receipt number
4. Update order status to `paid`
5. Send payment confirmation email
6. Log all payment events for audit

### 3. Order Fulfillment (SKPL-WUB-PR-023)

#### Admin Order Management
**Route**: `/admin/orders`
**Controller**: `App\Http\Controllers\Admin\OrderController`

#### Status Transitions
```
awaiting_payment -> paid -> processing -> shipped/pickup_ready -> completed
                          \-> cancelled (admin action)
```

#### Shipping Methods
- **Delivery**: Standard shipping with courier
- **Pickup**: Customer pickup at BRMP location

#### Admin Actions & UX (AJAX)
- View order details and customer information
- Update order status with validation
- Process shipments and add tracking numbers
- Generate shipping documents (PDF)
- Cancel orders with stock restoration
- Export order data to CSV
- Dynamic filters via AJAX with 300ms debounce (Search, Shipping Method, Status, Date From/To)
- Sorting on column headers (Order Date, Customer, Status, Total)
- Clean URLs using history.pushState without ajax param; popstate restores view
- Bulk actions: Cancel Selected (eligible statuses only)
- Row actions: View, Update Status (modal), Cancel (confirm), Copy order code

### 4. Order Tracking (SKPL-WUB-IN-024)
**Route**: `GET /track-order`
**Controller**: `App\Http\Controllers\Client\OrderTrackingController`

#### Tracking Methods
- Order code + phone number
- Order code + email (if provided)
- Rate limited to prevent abuse

## Email Notifications (SKPL-WUB-OUT-026)

### Automated Email Events
1. **Order Confirmation**: Sent after successful checkout
2. **Payment Confirmation**: Sent after payment webhook confirmation
3. **Order Status Updates**: Sent when admin updates order status
4. **Shipping Notification**: Sent when order is shipped with tracking info
5. **Order Completion**: Sent when order is marked as completed

### Email Templates
- `emails.order-confirmation`: Order details and payment instructions
- `emails.order-status-update`: Status change notifications
- `emails.order-cancelled`: Cancellation notifications

## Security Features

### Data Protection
- Customer data stored per order (no persistent accounts)
- Sensitive information pseudonymized in logs
- CSRF protection on all forms
- Rate limiting on public endpoints

### Payment Security
- Webhook signature verification
- Idempotent payment processing
- Secure API key management
- Payment status source of truth from gateway only

### Audit Trail
All significant actions are logged including:
- Order creation and modifications
- Status changes with user attribution
- Payment events and webhook calls
- Stock adjustments and cancellations

## API Integration

### Shipping Cost Calculation (SKPL-WUB-PR-021)
**Service**: `App\Services\ShippingService`
- Integration with RajaOngkir or similar API
- Real-time cost calculation during checkout
- Snapshot shipping costs stored in order

### Payment Gateway Integration
**Service**: `App\Services\PaymentService`
- Midtrans or similar payment gateway
- Server-to-server webhook handling
- Retry mechanism for failed webhooks
- Comprehensive error logging

## Document Generation (SKPL-WUB-OUT-025)

### PDF Documents
- **Invoice**: Generated after payment confirmation
- **Shipping Label**: Generated for delivery orders
- **Pickup Receipt**: Generated for pickup orders

### CSV Export (SKPL-WUB-OUT-027)
- Date range filtering
- Status-based filtering
- UTF-8 encoding with BOM for Excel compatibility
- Streaming response for large datasets

## Testing Coverage

### Feature Tests
- Complete checkout flow testing
- Payment webhook processing
- Order status transitions
- Email notification delivery
- Admin UI functionality
- Order tracking system

### Test Files
- `tests/Feature/Admin/OrderManagementTest.php`: Admin UI and functionality
- `tests/Feature/Client/CheckoutTest.php`: Guest checkout process
- `tests/Feature/WebhookTest.php`: Payment webhook handling

## Configuration

### Environment Variables
```env
# Payment Gateway
PAYMENT_GATEWAY_KEY=your_payment_key
PAYMENT_GATEWAY_SECRET=your_payment_secret
PAYMENT_WEBHOOK_URL=https://yourdomain.com/webhook/payment

# Shipping API
SHIPPING_API_KEY=your_shipping_key
SHIPPING_API_URL=https://api.rajaongkir.com/starter

# Email Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
```

### Rate Limiting
- Order tracking: 10 requests per minute per IP
- Checkout: 5 requests per minute per IP
- Webhook: 100 requests per minute per IP

## Monitoring and Maintenance

### Log Files
- `storage/logs/orders.log`: Order-specific events
- `storage/logs/payments.log`: Payment processing events
- `storage/logs/webhooks.log`: Webhook processing logs

### Performance Considerations
- Database indexing on order_code, customer_phone, customer_email
- Eager loading for order relationships
- Caching for frequently accessed data
- Queue processing for email notifications

## Troubleshooting

### Common Issues
1. **Payment webhook failures**: Check signature verification and network connectivity
2. **Email delivery issues**: Verify SMTP configuration and queue processing
3. **Order tracking not working**: Check rate limiting and database indexes
4. **PDF generation errors**: Verify dompdf configuration and file permissions

### Debug Commands
```bash
# Check queue status
php artisan queue:work

# Test email configuration
php artisan tinker
Mail::raw('Test', function($msg) { $msg->to('test@example.com'); });

# Clear application cache
php artisan cache:clear
php artisan config:clear
```

## SKPL Compliance

This implementation satisfies the following SKPL requirements:
- **SKPL-WUB-IN-020**: Guest checkout functionality
- **SKPL-WUB-PR-021**: Shipping cost calculation
- **SKPL-WUB-PR-022**: Payment gateway integration
- **SKPL-WUB-PR-023**: Order fulfillment workflow
- **SKPL-WUB-IN-024**: Order tracking system
- **SKPL-WUB-OUT-025**: Document generation
- **SKPL-WUB-OUT-026**: Email notifications
- **SKPL-WUB-OUT-027**: CSV export functionality

## Future Enhancements

### Planned Features
- SMS notifications for order updates
- Advanced reporting and analytics
- Bulk order processing tools
- Integration with additional payment gateways
- Mobile app API endpoints

### Performance Optimizations
- Redis caching for order data
- Database query optimization
- CDN integration for static assets
- Background job processing for heavy operations
