const mongoose = require('mongoose');

const transactionSchema = new mongoose.Schema({
  type: {
    type: String,
    enum: ['revenue', 'expense'],
    required: true
  },
  category: {
    type: String,
    enum: ['reservation_sale', 'walkin_sale', 'feed', 'labor', 'utilities', 'maintenance', 'other'],
    required: true
  },
  amount: {
    type: Number,
    required: true,
    min: 0
  },
  description: {
    type: String,
    required: true,
    trim: true
  },
  reference: {
    type: String,
    trim: true
  },
  relatedReservation: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Reservation'
  },
  recordedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  transactionDate: {
    type: Date,
    default: Date.now
  }
}, {
  timestamps: true
});

// Index for efficient date-range queries
transactionSchema.index({ transactionDate: -1 });
transactionSchema.index({ type: 1, transactionDate: -1 });

const Transaction = mongoose.model('Transaction', transactionSchema);

module.exports = Transaction;
