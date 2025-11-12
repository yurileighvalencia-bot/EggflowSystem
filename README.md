# EggflowSystem
Capstone/Thesis Project - Egg Inventory and Reservation Management System

## Overview
EggFlow System is a comprehensive web-based application for managing egg inventory, customer reservations, financial tracking, and communication for an egg farm business.

## Features

### Role-Based Access Control
- **Manager**: Full access to all features
- **Staff**: Inventory management, walk-in sales, view reservations
- **Customer**: Create reservations, view own reservations, chat with managers

### Inventory Management
- Track egg inventory in trays (30 eggs per tray)
- Real-time stock updates
- Low stock alerts (threshold: 100 eggs)
- Separate tracking for available and reserved eggs

### Reservation System
- Customers can create reservations
- Manager approval workflow
- 48-hour payment window after approval
- Automatic cancellation of expired reservations
- Stock deduction upon payment confirmation

### Financial Tracking
- Revenue tracking (reservation sales, walk-in sales)
- Expense management (feed, labor, utilities, maintenance, other)
- Financial reports with date range filtering
- Category-wise breakdown

### Communication
- Real-time chat using Socket.io
- Customer-to-Manager communication
- Staff-to-Manager communication
- Automatic system notifications

## Technology Stack

### Backend
- Node.js & Express.js
- MongoDB with Mongoose
- JWT for authentication
- Socket.io for real-time communication
- bcryptjs for password hashing

### Frontend
- React 18
- React Router for navigation
- Axios for API calls
- Socket.io-client for real-time features
- Vite as build tool

## Installation & Setup

### Prerequisites
- Node.js (v14 or higher)
- MongoDB (local or cloud instance)

### Backend Setup

1. Clone the repository:
```bash
git clone <repository-url>
cd EggflowSystem
```

2. Install backend dependencies:
```bash
npm install
```

3. Create `.env` file in the root directory:
```env
PORT=5000
NODE_ENV=development
MONGODB_URI=mongodb://localhost:27017/eggflowsystem
JWT_SECRET=your_secure_jwt_secret_key
FRONTEND_URL=http://localhost:3000
AUTO_CANCEL_INTERVAL=3600000
LOW_STOCK_THRESHOLD=100
```

4. Start the backend server:
```bash
npm run dev
```

The backend server will run on `http://localhost:5000`

### Frontend Setup

1. Navigate to frontend directory:
```bash
cd frontend
```

2. Install frontend dependencies:
```bash
npm install
```

3. Start the frontend development server:
```bash
npm run dev
```

The frontend will run on `http://localhost:3000`

## Default Users

To create users, use the registration endpoint or create them directly in MongoDB:

### Manager Account (Example)
```javascript
{
  "name": "Manager User",
  "email": "manager@eggflow.com",
  "password": "manager123",
  "role": "manager"
}
```

### Staff Account (Example)
```javascript
{
  "name": "Staff User",
  "email": "staff@eggflow.com",
  "password": "staff123",
  "role": "staff"
}
```

### Customer Account (Example)
```javascript
{
  "name": "Customer User",
  "email": "customer@eggflow.com",
  "password": "customer123",
  "role": "customer"
}
```

Note: To create Manager or Staff accounts, you'll need to directly update the role in MongoDB after registration, or modify the registration controller to allow role assignment (for development only).

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - User login
- `GET /api/auth/me` - Get current user profile
- `PUT /api/auth/profile` - Update user profile

### Inventory
- `GET /api/inventory` - Get current inventory
- `POST /api/inventory/add` - Add stock (Staff/Manager)
- `POST /api/inventory/remove` - Remove stock for walk-in sales (Staff/Manager)
- `GET /api/inventory/stats` - Get inventory statistics (Manager)

### Reservations
- `POST /api/reservations` - Create reservation (Customer)
- `GET /api/reservations` - Get all reservations
- `GET /api/reservations/:id` - Get single reservation
- `PUT /api/reservations/:id/approve` - Approve reservation (Manager)
- `PUT /api/reservations/:id/confirm-payment` - Confirm payment (Manager)
- `PUT /api/reservations/:id/cancel` - Cancel reservation

### Transactions
- `POST /api/transactions/walkin-sale` - Record walk-in sale (Staff/Manager)
- `POST /api/transactions/expense` - Record expense (Manager)
- `GET /api/transactions` - Get all transactions (Manager)
- `GET /api/transactions/summary` - Get financial summary (Manager)

### Messages
- `POST /api/messages` - Send message
- `GET /api/messages` - Get messages
- `PUT /api/messages/read` - Mark messages as read
- `GET /api/messages/unread-count` - Get unread message count

## Key Features Details

### Automatic Reservation Expiration
The system automatically checks for expired reservations every hour (configurable via `AUTO_CANCEL_INTERVAL`). Reservations with payment deadlines that have passed are automatically cancelled, and reserved eggs are released back to available inventory.

### Low Stock Alerts
When available eggs drop below the threshold (default: 100 eggs), the system displays alerts on the dashboard and inventory page.

### Real-Time Updates
- Socket.io enables real-time chat functionality
- Inventory updates can be broadcast to all connected clients
- Reservation status changes can trigger notifications

## Development

### Running Tests
```bash
npm test
```

### Building for Production

Backend:
```bash
npm start
```

Frontend:
```bash
cd frontend
npm run build
```

## Project Structure
```
EggflowSystem/
├── backend/
│   ├── config/         # Database configuration
│   ├── controllers/    # Request handlers
│   ├── middleware/     # Authentication & authorization
│   ├── models/         # MongoDB schemas
│   ├── routes/         # API routes
│   └── utils/          # Utility functions
├── frontend/
│   ├── public/         # Static files
│   └── src/
│       ├── components/ # Reusable components
│       ├── context/    # React context (Auth)
│       ├── pages/      # Page components
│       └── services/   # API services
├── server.js           # Main server file
└── package.json        # Dependencies
```

## License
ISC

## Contributors
Capstone/Thesis Project Team

