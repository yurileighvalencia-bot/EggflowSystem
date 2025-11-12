const Message = require('../models/Message');

// @desc    Send a message
// @route   POST /api/messages
// @access  Private
const sendMessage = async (req, res) => {
  try {
    const { recipient, message } = req.body;

    if (!message || !message.trim()) {
      return res.status(400).json({ message: 'Message cannot be empty' });
    }

    // Customers can only send to managers
    // Staff can send to managers
    // Managers can send to anyone
    if (req.user.role === 'customer' || req.user.role === 'staff') {
      if (!recipient) {
        return res.status(400).json({ message: 'Recipient is required' });
      }
    }

    const newMessage = await Message.create({
      sender: req.user._id,
      recipient: recipient || null,
      message: message.trim(),
      isSystemMessage: false
    });

    await newMessage.populate('sender', 'name email role');
    if (recipient) {
      await newMessage.populate('recipient', 'name email role');
    }

    res.status(201).json(newMessage);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Get messages (conversation)
// @route   GET /api/messages
// @access  Private
const getMessages = async (req, res) => {
  try {
    const { userId } = req.query;
    let query = {};

    if (req.user.role === 'customer') {
      // Customers see messages between them and managers
      query.$or = [
        { sender: req.user._id },
        { recipient: req.user._id }
      ];
    } else if (req.user.role === 'manager') {
      // Managers can filter by userId or see all
      if (userId) {
        query.$or = [
          { sender: userId, recipient: req.user._id },
          { sender: req.user._id, recipient: userId }
        ];
      } else {
        // All messages
        query.$or = [
          { sender: req.user._id },
          { recipient: req.user._id },
          { recipient: null } // Broadcast messages
        ];
      }
    } else if (req.user.role === 'staff') {
      // Staff see messages between them and managers
      query.$or = [
        { sender: req.user._id },
        { recipient: req.user._id }
      ];
    }

    const messages = await Message.find(query)
      .populate('sender', 'name email role')
      .populate('recipient', 'name email role')
      .sort('createdAt');

    res.json(messages);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Mark messages as read
// @route   PUT /api/messages/read
// @access  Private
const markAsRead = async (req, res) => {
  try {
    const { messageIds } = req.body;

    if (!messageIds || !Array.isArray(messageIds)) {
      return res.status(400).json({ message: 'Invalid message IDs' });
    }

    await Message.updateMany(
      {
        _id: { $in: messageIds },
        recipient: req.user._id,
        isRead: false
      },
      {
        isRead: true,
        readAt: Date.now()
      }
    );

    res.json({ message: 'Messages marked as read' });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Get unread message count
// @route   GET /api/messages/unread-count
// @access  Private
const getUnreadCount = async (req, res) => {
  try {
    const count = await Message.countDocuments({
      recipient: req.user._id,
      isRead: false
    });

    res.json({ count });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

module.exports = {
  sendMessage,
  getMessages,
  markAsRead,
  getUnreadCount
};
