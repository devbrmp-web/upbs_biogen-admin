# Order Tracking API Documentation

## Overview
The Order Tracking API allows customers to track their orders without requiring user accounts. This endpoint is designed for guest users to check order status using minimal identifying information.

## Endpoint Details

### Track Order
**URL**: `/api/track-order`  
**Method**: `GET`  
**Authentication**: None (Public endpoint)  
**Rate Limit**: 10 requests per minute per IP address

#### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_code` | string | Yes | Unique order identifier (format: ORD-YYYYMMDD-XXXX) |
| `phone` | string | No* | Customer phone number used during checkout |
| `email` | string | No* | Customer email address used during checkout |

*At least one of `phone` or `email` must be provided for verification.

#### Example Requests

**Using Phone Number:**
```http
GET /api/track-order?order_code=ORD-20241225-0001&phone=081234567890
```

**Using Email:**
```http
GET /api/track-order?order_code=ORD-20241225-0001&email=customer@example.com
```

**Using Both (Recommended):**
```http
GET /api/track-order?order_code=ORD-20241225-0001&phone=081234567890&email=customer@example.com
```

### Response Format

#### Successful Response (200 OK)
```json
{
  "success": true,
  "data": {
    "order": {
      "order_code": "ORD-20241225-0001",
      "status": "shipped",
      "status_label": "Shipped",
      "shipping_method": "delivery",
      "shipping_method_label": "Delivery",
      "total_amount": 150000,
      "created_at": "2024-12-25T10:30:00Z",
      "updated_at": "2024-12-26T14:20:00Z",
      "customer": {
        "name": "John Doe",
        "phone": "081234567890",
        "email": "customer@example.com",
        "address": "Jl. Contoh No. 123, Jakarta"
      },
      "items": [
        {
          "variety_name": "Benih Padi IR64",
          "quantity": 2,
          "unit_price": 50000,
          "subtotal": 100000
        },
        {
          "variety_name": "Benih Jagung Hibrida",
          "quantity": 1,
          "unit_price": 30000,
          "subtotal": 30000
        }
      ],
      "payment": {
        "status": "paid",
        "method": "virtual_account",
        "paid_at": "2024-12-25T11:15:00Z",
        "pnbp_receipt_no": "PNBP-2024-001234"
      },
      "shipment": {
        "courier_name": "JNE",
        "service": "REG",
        "tracking_number": "JNE123456789",
        "shipping_cost": 20000,
        "estimated_delivery": "2024-12-28",
        "shipped_at": "2024-12-26T14:20:00Z"
      },
      "timeline": [
        {
          "status": "awaiting_payment",
          "timestamp": "2024-12-25T10:30:00Z",
          "description": "Order created and awaiting payment"
        },
        {
          "status": "paid",
          "timestamp": "2024-12-25T11:15:00Z",
          "description": "Payment confirmed"
        },
        {
          "status": "processing",
          "timestamp": "2024-12-26T09:00:00Z",
          "description": "Order is being processed"
        },
        {
          "status": "shipped",
          "timestamp": "2024-12-26T14:20:00Z",
          "description": "Order has been shipped"
        }
      ]
    }
  }
}
```

#### Error Responses

**Order Not Found (404 Not Found):**
```json
{
  "success": false,
  "error": {
    "code": "ORDER_NOT_FOUND",
    "message": "Order not found or verification failed"
  }
}
```

**Missing Parameters (400 Bad Request):**
```json
{
  "success": false,
  "error": {
    "code": "MISSING_PARAMETERS",
    "message": "Order code and at least one verification method (phone or email) are required"
  }
}
```

**Rate Limit Exceeded (429 Too Many Requests):**
```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests. Please try again later."
  }
}
```

**Validation Error (422 Unprocessable Entity):**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid order code format",
    "details": {
      "order_code": ["The order code format is invalid"]
    }
  }
}
```

## Order Status Values

| Status | Description |
|--------|-------------|
| `awaiting_payment` | Order created, waiting for payment |
| `paid` | Payment confirmed |
| `processing` | Order is being prepared |
| `pickup_ready` | Ready for customer pickup (pickup orders only) |
| `delivery_coordination` | Coordinating delivery (delivery orders only) |
| `shipped` | Order has been shipped (delivery orders only) |
| `picked_up` | Order has been picked up (pickup orders only) |
| `completed` | Order completed successfully |
| `cancelled` | Order cancelled |

## Shipping Methods

| Method | Description |
|--------|-------------|
| `pickup` | Customer pickup at BRMP location |
| `delivery` | Delivery via courier service |

## Security Considerations

### Rate Limiting
- **Limit**: 10 requests per minute per IP address
- **Purpose**: Prevent abuse and protect customer privacy
- **Headers**: Rate limit information included in response headers

### Data Privacy
- Customer data is only returned when proper verification is provided
- Sensitive information (full phone numbers, emails) may be partially masked
- No persistent session or authentication tokens required

### Verification Methods
- **Phone Number**: Must match exactly with order record
- **Email**: Case-insensitive matching
- **Combined**: Both phone and email provide additional security

## Implementation Details

### Controller
**File**: `app/Http/Controllers/Api/OrderTrackingController.php`
**Method**: `track(Request $request)`

### Validation Rules
```php
$rules = [
    'order_code' => 'required|string|regex:/^ORD-\d{8}-\d{4}$/',
    'phone' => 'nullable|string|min:10|max:15',
    'email' => 'nullable|email|max:255',
];
```

### Rate Limiting Middleware
**Middleware**: `throttle:order-tracking,10,1`
**Configuration**: 10 requests per minute per IP

### Database Queries
- Optimized with eager loading for related models
- Indexed on `order_code`, `customer_phone`, and `customer_email`
- Uses database-level filtering for security

## Testing

### Test Coverage
**File**: `tests/Feature/Api/OrderTrackingTest.php`

#### Test Cases
- Valid order tracking with phone number
- Valid order tracking with email
- Valid order tracking with both phone and email
- Order not found scenarios
- Invalid order code format
- Missing verification parameters
- Rate limiting functionality
- Response format validation

### Example Test Request
```php
$response = $this->getJson('/api/track-order?' . http_build_query([
    'order_code' => 'ORD-20241225-0001',
    'phone' => '081234567890'
]));

$response->assertOk()
    ->assertJsonStructure([
        'success',
        'data' => [
            'order' => [
                'order_code',
                'status',
                'customer',
                'items',
                'payment',
                'shipment',
                'timeline'
            ]
        ]
    ]);
```

## Frontend Integration

### JavaScript Example
```javascript
async function trackOrder(orderCode, phone, email) {
    const params = new URLSearchParams({
        order_code: orderCode,
        ...(phone && { phone }),
        ...(email && { email })
    });
    
    try {
        const response = await fetch(`/api/track-order?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayOrderDetails(data.data.order);
        } else {
            displayError(data.error.message);
        }
    } catch (error) {
        displayError('Network error occurred');
    }
}
```

### HTML Form Example
```html
<form id="trackOrderForm">
    <div class="form-group">
        <label for="order_code">Order Code</label>
        <input type="text" id="order_code" name="order_code" 
               placeholder="ORD-20241225-0001" required>
    </div>
    
    <div class="form-group">
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" 
               placeholder="081234567890">
    </div>
    
    <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" 
               placeholder="customer@example.com">
    </div>
    
    <button type="submit">Track Order</button>
</form>
```

## Error Handling

### Client-Side Recommendations
1. **Validate input format** before sending request
2. **Handle rate limiting** with appropriate user feedback
3. **Provide clear error messages** for different error types
4. **Implement retry logic** for network errors

### Server-Side Error Logging
- All tracking attempts are logged for security monitoring
- Failed verification attempts are tracked for abuse detection
- Performance metrics are collected for optimization

## Monitoring and Analytics

### Metrics to Track
- Request volume and patterns
- Success/failure rates
- Response times
- Rate limit hits
- Popular tracking times

### Log Format
```
[2024-12-25 10:30:00] ORDER_TRACKING: order_code=ORD-20241225-0001 ip=192.168.1.1 status=success response_time=150ms
```

## SKPL Compliance

This API implementation satisfies:
- **SKPL-WUB-IN-024**: Order tracking functionality for guest users
- **Security requirements**: Rate limiting and data protection
- **Performance requirements**: Optimized queries and caching
- **Usability requirements**: Simple interface without authentication