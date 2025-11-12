# API Documentation - EggFlow System

Base URL: `http://localhost:5000/api`

## Authentication

All protected endpoints require a JWT token in the Authorization header:
```
Authorization: Bearer <token>
```

### Register User
**POST** `/auth/register`

**Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "phone": "1234567890",
  "address": "123 Main St"
}
```

**Response:**
```json
{
  "_id": "user_id",
  "name": "John Doe",
  "email": "john@example.com",
  "role": "customer",
  "token": "jwt_token_here"
}
```

### Login
**POST** `/auth/login`

**Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "_id": "user_id",
  "name": "John Doe",
  "email": "john@example.com",
  "role": "customer",
  "token": "jwt_token_here"
}
```

### Get Current User
**GET** `/auth/me` 🔒

**Response:**
```json
{
  "_id": "user_id",
  "name": "John Doe",
  "email": "john@example.com",
  "role": "customer",
  "phone": "1234567890",
  "address": "123 Main St"
}
```

### Update Profile
**PUT** `/auth/profile` 🔒

**Body:**
```json
{
  "name": "John Updated",
  "phone": "9876543210",
  "address": "456 New St",
  "password": "newpassword123"
}
```

## Inventory Management

### Get Inventory
**GET** `/inventory` 🔒

**Response:**
```json
{
  "_id": "inventory_id",
  "totalTrays": 100,
  "eggsPerTray": 30,
  "reservedEggs": 150,
  "totalEggs": 3000,
  "availableEggs": 2850,
  "isLowStock": false,
  "lowStockThreshold": 100,
  "lastUpdated": "2024-01-15T10:30:00Z",
  "updatedBy": {
    "_id": "user_id",
    "name": "Staff User"
  }
}
```

### Add Stock
**POST** `/inventory/add` 🔒 👤 Staff/Manager

**Body:**
```json
{
  "trays": 10
}
```

**Response:**
```json
{
  "message": "Successfully added 10 trays (300 eggs)",
  "inventory": { /* inventory object */ }
}
```

### Remove Stock (Walk-in Sale)
**POST** `/inventory/remove` 🔒 👤 Staff/Manager

**Body:**
```json
{
  "eggs": 60
}
```

**Response:**
```json
{
  "message": "Successfully removed 60 eggs",
  "inventory": { /* inventory object */ }
}
```

### Get Inventory Stats
**GET** `/inventory/stats` 🔒 👤 Manager

**Response:**
```json
{
  "totalTrays": 100,
  "totalEggs": 3000,
  "reservedEggs": 150,
  "availableEggs": 2850,
  "eggsPerTray": 30,
  "lastUpdated": "2024-01-15T10:30:00Z",
  "isLowStock": false
}
```

## Reservations

### Create Reservation
**POST** `/reservations` 🔒 👤 Customer

**Body:**
```json
{
  "numberOfEggs": 150,
  "pricePerEgg": 8,
  "notes": "Delivery preferred"
}
```

**Response:**
```json
{
  "_id": "reservation_id",
  "customer": {
    "_id": "customer_id",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890"
  },
  "numberOfEggs": 150,
  "pricePerEgg": 8,
  "totalAmount": 1200,
  "status": "pending",
  "notes": "Delivery preferred",
  "createdAt": "2024-01-15T10:00:00Z"
}
```

### Get All Reservations
**GET** `/reservations?status=pending` 🔒

**Query Parameters:**
- `status`: pending, approved, paid, completed, cancelled, expired (optional)

**Response:**
```json
[
  {
    "_id": "reservation_id",
    "customer": { /* customer object */ },
    "numberOfEggs": 150,
    "status": "pending",
    "totalAmount": 1200,
    "createdAt": "2024-01-15T10:00:00Z"
  }
]
```

### Get Single Reservation
**GET** `/reservations/:id` 🔒

**Response:**
```json
{
  "_id": "reservation_id",
  "customer": { /* full customer object */ },
  "numberOfEggs": 150,
  "pricePerEgg": 8,
  "totalAmount": 1200,
  "status": "approved",
  "approvedBy": { /* manager object */ },
  "approvedAt": "2024-01-15T11:00:00Z",
  "paymentDeadline": "2024-01-17T11:00:00Z",
  "notes": "Delivery preferred",
  "createdAt": "2024-01-15T10:00:00Z"
}
```

### Approve Reservation
**PUT** `/reservations/:id/approve` 🔒 👤 Manager

**Response:**
```json
{
  "_id": "reservation_id",
  "status": "approved",
  "approvedBy": { /* manager object */ },
  "approvedAt": "2024-01-15T11:00:00Z",
  "paymentDeadline": "2024-01-17T11:00:00Z"
}
```

### Confirm Payment
**PUT** `/reservations/:id/confirm-payment` 🔒 👤 Manager

**Response:**
```json
{
  "_id": "reservation_id",
  "status": "completed",
  "paidAt": "2024-01-16T14:00:00Z",
  "confirmedBy": { /* manager object */ }
}
```

### Cancel Reservation
**PUT** `/reservations/:id/cancel` 🔒

**Body:**
```json
{
  "reason": "Customer cancelled"
}
```

**Response:**
```json
{
  "_id": "reservation_id",
  "status": "cancelled",
  "cancelledAt": "2024-01-15T12:00:00Z",
  "cancelReason": "Customer cancelled"
}
```

## Financial Transactions

### Record Walk-in Sale
**POST** `/transactions/walkin-sale` 🔒 👤 Staff/Manager

**Body:**
```json
{
  "eggs": 30,
  "pricePerEgg": 8,
  "description": "Walk-in customer"
}
```

**Response:**
```json
{
  "_id": "transaction_id",
  "type": "revenue",
  "category": "walkin_sale",
  "amount": 240,
  "description": "Walk-in customer",
  "recordedBy": { /* user object */ },
  "transactionDate": "2024-01-15T15:00:00Z"
}
```

### Record Expense
**POST** `/transactions/expense` 🔒 👤 Manager

**Body:**
```json
{
  "category": "feed",
  "amount": 500,
  "description": "Chicken feed purchase",
  "reference": "Invoice #123"
}
```

**Categories:** feed, labor, utilities, maintenance, other

**Response:**
```json
{
  "_id": "transaction_id",
  "type": "expense",
  "category": "feed",
  "amount": 500,
  "description": "Chicken feed purchase",
  "reference": "Invoice #123",
  "recordedBy": { /* user object */ },
  "transactionDate": "2024-01-15T16:00:00Z"
}
```

### Get All Transactions
**GET** `/transactions?type=revenue&startDate=2024-01-01&endDate=2024-01-31` 🔒 👤 Manager

**Query Parameters:**
- `type`: revenue or expense (optional)
- `category`: transaction category (optional)
- `startDate`: ISO date string (optional)
- `endDate`: ISO date string (optional)

**Response:**
```json
[
  {
    "_id": "transaction_id",
    "type": "revenue",
    "category": "walkin_sale",
    "amount": 240,
    "description": "Walk-in customer",
    "recordedBy": { /* user object */ },
    "transactionDate": "2024-01-15T15:00:00Z"
  }
]
```

### Get Financial Summary
**GET** `/transactions/summary?startDate=2024-01-01&endDate=2024-01-31` 🔒 👤 Manager

**Query Parameters:**
- `startDate`: ISO date string (optional)
- `endDate`: ISO date string (optional)

**Response:**
```json
{
  "revenue": 12000,
  "expense": 3500,
  "profit": 8500,
  "revenueCount": 45,
  "expenseCount": 12,
  "categoryBreakdown": [
    {
      "type": "revenue",
      "category": "reservation_sale",
      "total": 8000,
      "count": 20
    },
    {
      "type": "revenue",
      "category": "walkin_sale",
      "total": 4000,
      "count": 25
    },
    {
      "type": "expense",
      "category": "feed",
      "total": 2000,
      "count": 5
    }
  ]
}
```

## Messages/Chat

### Send Message
**POST** `/messages` 🔒

**Body:**
```json
{
  "recipient": "user_id",
  "message": "Hello, I have a question"
}
```

**Response:**
```json
{
  "_id": "message_id",
  "sender": { /* sender user object */ },
  "recipient": { /* recipient user object */ },
  "message": "Hello, I have a question",
  "isSystemMessage": false,
  "isRead": false,
  "createdAt": "2024-01-15T17:00:00Z"
}
```

### Get Messages
**GET** `/messages?userId=user_id` 🔒

**Query Parameters:**
- `userId`: Filter messages for specific user conversation (optional)

**Response:**
```json
[
  {
    "_id": "message_id",
    "sender": { /* user object */ },
    "recipient": { /* user object */ },
    "message": "Hello, I have a question",
    "isSystemMessage": false,
    "isRead": false,
    "createdAt": "2024-01-15T17:00:00Z"
  }
]
```

### Mark Messages as Read
**PUT** `/messages/read` 🔒

**Body:**
```json
{
  "messageIds": ["msg_id_1", "msg_id_2"]
}
```

**Response:**
```json
{
  "message": "Messages marked as read"
}
```

### Get Unread Count
**GET** `/messages/unread-count` 🔒

**Response:**
```json
{
  "count": 5
}
```

## Error Responses

All endpoints may return these error responses:

**400 Bad Request**
```json
{
  "message": "Error description"
}
```

**401 Unauthorized**
```json
{
  "message": "Not authorized, no token"
}
```

**403 Forbidden**
```json
{
  "message": "User role 'customer' is not authorized to access this route"
}
```

**404 Not Found**
```json
{
  "message": "Resource not found"
}
```

**429 Too Many Requests**
```json
{
  "message": "Too many requests from this IP, please try again later."
}
```

**500 Internal Server Error**
```json
{
  "message": "Server error description"
}
```

## Rate Limits

- **Auth endpoints** (login/register): 5 requests per 15 minutes
- **Reservation creation**: 10 requests per hour
- **Transaction recording**: 30 requests per 15 minutes
- **General API**: 100 requests per 15 minutes

## Icons Legend

- 🔒 = Authentication required
- 👤 = Specific role required (Manager, Staff, Customer)

## Testing with cURL

### Example: Register and Login
```bash
# Register
curl -X POST http://localhost:5000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"test123"}'

# Login
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"test123"}'

# Get inventory (with token)
curl http://localhost:5000/api/inventory \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```
