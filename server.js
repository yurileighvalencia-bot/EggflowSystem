require('dotenv').config();
const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
const connectDB = require('./backend/config/db');
const { startExpirationChecker } = require('./backend/utils/expirationChecker');

// Connect to database
connectDB();

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: process.env.FRONTEND_URL || 'http://localhost:3000',
    methods: ['GET', 'POST']
  }
});

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: false }));

// Routes
app.use('/api/auth', require('./backend/routes/auth'));
app.use('/api/inventory', require('./backend/routes/inventory'));
app.use('/api/reservations', require('./backend/routes/reservations'));
app.use('/api/transactions', require('./backend/routes/transactions'));
app.use('/api/messages', require('./backend/routes/messages'));

// Health check
app.get('/api/health', (req, res) => {
  res.json({ status: 'OK', message: 'EggFlow System API is running' });
});

// Socket.io for real-time features
io.on('connection', (socket) => {
  console.log('New client connected:', socket.id);

  // Join room by user ID
  socket.on('join', (userId) => {
    socket.join(userId);
    console.log(`User ${userId} joined their room`);
  });

  // Send message
  socket.on('sendMessage', (data) => {
    const { recipientId, message } = data;
    io.to(recipientId).emit('newMessage', message);
  });

  // Broadcast inventory update
  socket.on('inventoryUpdate', (data) => {
    io.emit('inventoryUpdated', data);
  });

  // Broadcast reservation update
  socket.on('reservationUpdate', (data) => {
    io.emit('reservationUpdated', data);
  });

  // Low stock alert
  socket.on('lowStockAlert', (data) => {
    // Broadcast to all managers
    io.emit('lowStockAlert', data);
  });

  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

// Start expiration checker
startExpirationChecker();

const PORT = process.env.PORT || 5000;

server.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});

module.exports = { app, io };
