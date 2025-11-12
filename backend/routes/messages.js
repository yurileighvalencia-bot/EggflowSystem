const express = require('express');
const router = express.Router();
const {
  sendMessage,
  getMessages,
  markAsRead,
  getUnreadCount
} = require('../controllers/messageController');
const { protect } = require('../middleware/auth');

router.post('/', protect, sendMessage);
router.get('/', protect, getMessages);
router.put('/read', protect, markAsRead);
router.get('/unread-count', protect, getUnreadCount);

module.exports = router;
