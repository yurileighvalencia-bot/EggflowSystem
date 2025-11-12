# Quick Start Guide - EggFlow System

## Prerequisites
- Node.js 14+ installed
- MongoDB installed and running (or MongoDB Atlas account)
- Git installed

## Setup in 5 Minutes

### 1. Clone and Install (2 minutes)

```bash
# Clone the repository
git clone <repository-url>
cd EggflowSystem

# Install backend dependencies
npm install

# Install frontend dependencies
cd frontend
npm install
cd ..
```

### 2. Configure Environment (1 minute)

The `.env` file is already created with default development settings. If MongoDB is running locally, no changes needed!

If using MongoDB Atlas or custom settings, edit `.env`:
```bash
# Optional: Edit .env if needed
nano .env
```

### 3. Start the Application (1 minute)

**Terminal 1 - Start Backend:**
```bash
npm run dev
```
Backend will run on http://localhost:5000

**Terminal 2 - Start Frontend:**
```bash
cd frontend
npm run dev
```
Frontend will run on http://localhost:3000

### 4. Create Your First Users (1 minute)

#### Option A: Using the Web Interface

1. Open http://localhost:3000/register
2. Register as a customer (default role)
3. For Manager/Staff accounts, register first then update role in MongoDB:

```javascript
// In MongoDB shell or MongoDB Compass
use eggflowsystem

// Update to Manager
db.users.updateOne(
  { email: "your-email@example.com" },
  { $set: { role: "manager" } }
)

// Update to Staff
db.users.updateOne(
  { email: "staff@example.com" },
  { $set: { role: "staff" } }
)
```

#### Option B: Using MongoDB Shell Directly

```javascript
use eggflowsystem

// Create Manager
db.users.insertOne({
  name: "Admin Manager",
  email: "manager@eggflow.com",
  password: "$2a$10$YourHashedPasswordHere", // Use registration first
  role: "manager",
  isActive: true,
  createdAt: new Date(),
  updatedAt: new Date()
})
```

**Recommended Test Accounts:**
- Manager: manager@test.com / manager123
- Staff: staff@test.com / staff123
- Customer: customer@test.com / customer123

(Register these via /register, then update roles in MongoDB)

## Quick Feature Test

### Test Inventory Management

1. Login as Manager or Staff
2. Go to Inventory page
3. Click "Add Stock"
4. Add 10 trays (= 300 eggs)
5. Verify inventory updates

### Test Reservation System

1. Login as Customer
2. Go to Reservations page
3. Click "New Reservation"
4. Create reservation for 150 eggs
5. Login as Manager (different browser/incognito)
6. Approve the reservation
7. Confirm payment
8. Check inventory decreased

### Test Financial Tracking

1. Login as Manager
2. Go to Financials page
3. Record a walk-in sale
4. Record an expense
5. View financial summary

### Test Chat

1. Open two browser windows
2. Login as Customer in one, Manager in other
3. Go to Chat in both
4. Send messages back and forth

## Common Commands

```bash
# Start backend in development mode
npm run dev

# Start backend in production mode
npm start

# Build frontend for production
cd frontend
npm run build

# Preview production build
npm run preview

# Check MongoDB status
mongod --version

# Start MongoDB (if not running)
mongod

# View backend logs (if using PM2 in production)
pm2 logs eggflow-backend
```

## Troubleshooting

### "Cannot connect to MongoDB"
**Solution:** Ensure MongoDB is running
```bash
# Start MongoDB
sudo systemctl start mongod
# or
mongod
```

### "Port 5000 already in use"
**Solution:** Change PORT in .env file
```env
PORT=5001
```

### "Port 3000 already in use"
**Solution:** Vite will automatically suggest next available port (3001, 3002, etc.)

### "Module not found"
**Solution:** Reinstall dependencies
```bash
rm -rf node_modules
npm install
cd frontend
rm -rf node_modules
npm install
```

### Rate Limit Errors
**Solution:** Wait for the time window to reset or adjust limits in `backend/middleware/rateLimiter.js`

## Next Steps

1. **Read the full README.md** for detailed feature documentation
2. **Check TESTING.md** for comprehensive testing scenarios
3. **Review DEPLOYMENT.md** for production deployment options
4. **Customize** the application for your specific needs

## Default System Settings

- **Eggs per tray:** 30
- **Low stock threshold:** 100 eggs
- **Payment window:** 48 hours after approval
- **Auto-cancel check:** Every 1 hour
- **Rate limits:**
  - Auth routes: 5 requests per 15 minutes
  - Reservations: 10 per hour
  - Transactions: 30 per 15 minutes
  - General API: 100 per 15 minutes

## Need Help?

1. Check the console logs in both terminals
2. Review error messages carefully
3. Ensure MongoDB is running
4. Verify all environment variables are set
5. Check that all dependencies are installed

## Security Reminder for Production

Before deploying to production:
- [ ] Change JWT_SECRET to a strong random value
- [ ] Use HTTPS (SSL certificate)
- [ ] Use MongoDB Atlas or secure MongoDB instance
- [ ] Update CORS settings for your domain
- [ ] Set NODE_ENV=production
- [ ] Review rate limiting settings
- [ ] Set up regular database backups

Happy egg management! 🥚
