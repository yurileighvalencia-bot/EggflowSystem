# Testing Guide for EggFlow System

## Prerequisites for Testing

1. Ensure MongoDB is running:
   ```bash
   # If using local MongoDB
   mongod
   
   # Or use MongoDB Atlas cloud database
   ```

2. Start the backend server:
   ```bash
   npm run dev
   ```

3. In a separate terminal, start the frontend:
   ```bash
   cd frontend
   npm run dev
   ```

## Manual Testing Steps

### 1. Authentication Testing

#### Register a Customer
1. Navigate to `http://localhost:3000/register`
2. Fill in the registration form:
   - Name: Test Customer
   - Email: customer@test.com
   - Password: customer123
   - Phone: (optional)
   - Address: (optional)
3. Submit the form
4. Verify you're redirected to the dashboard

#### Register Staff/Manager Users
Since new registrations default to "customer" role, you'll need to:

**Option A: Update role via MongoDB directly**
```javascript
// Connect to MongoDB and update user role
use eggflowsystem
db.users.updateOne(
  { email: "staff@test.com" },
  { $set: { role: "staff" } }
)

db.users.updateOne(
  { email: "manager@test.com" },
  { $set: { role: "manager" } }
)
```

**Option B: Create test users via API**
```bash
# Register and then update via MongoDB
curl -X POST http://localhost:5000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Manager",
    "email": "manager@test.com",
    "password": "manager123"
  }'
```

#### Login Testing
1. Navigate to `http://localhost:3000/login`
2. Use credentials from registered users
3. Verify role-based navigation appears correctly

### 2. Inventory Management Testing (Staff/Manager)

#### Add Stock
1. Login as Staff or Manager
2. Navigate to Inventory page
3. Click "Add Stock"
4. Enter number of trays (e.g., 10)
5. Submit
6. Verify:
   - Total trays increases by 10
   - Total eggs increases by 300 (10 × 30)
   - Success message appears

#### Remove Stock (Walk-in Sale)
1. Click "Remove Stock (Walk-in Sale)"
2. Enter number of eggs (e.g., 60)
3. Submit
4. Verify:
   - Available eggs decreases by 60
   - Success message appears

#### Low Stock Alert
1. Remove stock until available eggs < 100
2. Verify warning alert appears on:
   - Dashboard
   - Inventory page

### 3. Reservation System Testing

#### Customer Creates Reservation
1. Login as Customer
2. Navigate to Reservations
3. Click "New Reservation"
4. Fill in:
   - Number of Eggs: 150
   - Price per Egg: 8
   - Notes: "Test reservation"
5. Submit
6. Verify:
   - Reservation appears in table with "pending" status
   - Total amount calculated correctly (150 × 8 = ₱1200)

#### Manager Approves Reservation
1. Login as Manager
2. Navigate to Reservations
3. Find pending reservation
4. Click "Approve"
5. Verify:
   - Status changes to "approved"
   - Payment deadline is set (48 hours from now)
   - Reserved eggs count increases on Inventory page
   - Available eggs decreases

#### Manager Confirms Payment
1. On approved reservation, click "Confirm Payment"
2. Verify:
   - Status changes to "completed"
   - Reserved eggs count decreases
   - Total trays decreases appropriately
   - Transaction is created (check Financials page)

#### Cancel Reservation
1. As Customer: Can cancel only "pending" reservations
2. As Manager: Can cancel "pending" or "approved" reservations
3. Click "Cancel" on a reservation
4. Verify:
   - Status changes to "cancelled"
   - If was "approved", reserved eggs are released

#### Auto-Expiration (Advanced)
1. Approve a reservation
2. Manually update the paymentDeadline in MongoDB to a past date:
   ```javascript
   db.reservations.updateOne(
     { _id: ObjectId("reservation_id") },
     { $set: { paymentDeadline: new Date("2024-01-01") } }
   )
   ```
3. Wait for auto-cancel checker (runs every hour, or restart server to trigger immediately)
4. Verify reservation status changes to "expired"

### 4. Financial Management Testing (Manager Only)

#### Record Walk-in Sale
1. Login as Manager
2. Navigate to Financials
3. Click "Record Walk-in Sale"
4. Fill in:
   - Number of Eggs: 30
   - Price per Egg: 8
   - Description: "Walk-in customer sale"
5. Submit
6. Verify:
   - Transaction appears in "Recent Transactions"
   - Revenue increases by ₱240
   - Category shows "walkin_sale"

#### Record Expense
1. Click "Record Expense"
2. Fill in:
   - Category: Feed
   - Amount: 500
   - Description: "Chicken feed purchase"
   - Reference: (optional)
3. Submit
4. Verify:
   - Transaction appears with type "expense"
   - Total Expenses increases
   - Net Profit decreases

#### Financial Summary
1. Verify stats show:
   - Total Revenue (from all sales)
   - Total Expenses
   - Net Profit (Revenue - Expenses)
   - Category breakdown

#### Date Range Filter
1. Set start and end dates
2. Verify filtered results
3. Click "Clear" to reset

### 5. Chat/Communication Testing

#### Send Messages
1. Login as Customer
2. Navigate to Chat
3. Type a message: "Hello, I have a question about reservations"
4. Click Send
5. Verify message appears in chat window

#### Real-time Chat (Two Browser Windows)
1. Open two browser windows
2. Login as Customer in one, Manager in another
3. Navigate to Chat in both
4. Send message from one
5. Verify it appears in real-time in the other (may require socket connection)

#### System Messages
1. When reservations expire, system messages should appear
2. Check for system messages (styled differently)

### 6. Dashboard Testing

#### Customer Dashboard
1. Login as Customer
2. Verify dashboard shows:
   - Inventory stats (view only)
   - Unread message count
   - Quick action: "New Reservation"

#### Staff Dashboard
1. Login as Staff
2. Verify dashboard shows:
   - Inventory stats
   - Quick actions: "Manage Inventory", "View Reservations"
   - No Financial access

#### Manager Dashboard
1. Login as Manager
2. Verify dashboard shows:
   - All inventory stats
   - Pending reservations list
   - Low stock alerts (if applicable)
   - Quick actions for all features

### 7. Role-Based Access Control Testing

Test that unauthorized access is blocked:

#### Customer Restrictions
- Cannot access `/financials` (should redirect)
- Cannot approve/confirm reservations
- Cannot add/remove inventory

#### Staff Restrictions
- Cannot access `/financials` (should redirect)
- Cannot approve/confirm reservations
- CAN add/remove inventory

#### Manager Access
- Can access all pages
- Can perform all actions

### 8. API Endpoint Testing (Using curl or Postman)

```bash
# Health Check
curl http://localhost:5000/api/health

# Register
curl -X POST http://localhost:5000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"API Test","email":"api@test.com","password":"test123"}'

# Login
curl -X POST http://localhost:5000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"api@test.com","password":"test123"}'

# Get Inventory (requires token)
curl http://localhost:5000/api/inventory \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## Common Issues and Solutions

### MongoDB Connection Error
- Ensure MongoDB is running
- Check MONGODB_URI in .env file
- Verify database permissions

### Port Already in Use
- Backend: Change PORT in .env
- Frontend: Vite will auto-increment port if 3000 is busy

### Socket.io Connection Failed
- Ensure backend server is running
- Check CORS settings
- Verify Socket.io URL in Chat.jsx matches backend URL

### Low Stock Alert Not Showing
- Ensure available eggs < LOW_STOCK_THRESHOLD (default 100)
- Check inventory calculation

### Auto-Cancellation Not Working
- Check AUTO_CANCEL_INTERVAL in .env
- Restart server to trigger initial check
- Verify paymentDeadline is in the past

## Test Checklist

- [ ] User Registration works
- [ ] User Login works
- [ ] Dashboard loads for all roles
- [ ] Inventory - Add stock works
- [ ] Inventory - Remove stock works
- [ ] Inventory - Low stock alert appears
- [ ] Reservation - Customer can create
- [ ] Reservation - Manager can approve
- [ ] Reservation - Manager can confirm payment
- [ ] Reservation - Cancellation works
- [ ] Financials - Walk-in sale recording works
- [ ] Financials - Expense recording works
- [ ] Financials - Summary calculates correctly
- [ ] Chat - Messages can be sent
- [ ] Chat - Messages appear for recipient
- [ ] Role-based access control enforced
- [ ] Responsive UI works on different screen sizes

## Performance Testing

1. Create 100+ reservations to test pagination
2. Send many messages to test chat scrolling
3. Add/remove inventory multiple times to test updates
4. Create many transactions to test financial reports

## Security Testing

1. Verify JWT tokens expire correctly
2. Test password hashing (passwords not stored in plain text)
3. Verify role-based endpoint protection
4. Test input validation on all forms
5. Check for SQL injection prevention (MongoDB uses BSON)
6. Verify CORS settings are appropriate
