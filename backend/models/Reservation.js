const mongoose = require('mongoose');

const reservationSchema = new mongoose.Schema({
  customer: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  numberOfEggs: {
    type: Number,
    required: true,
    min: 1
  },
  status: {
    type: String,
    enum: ['pending', 'approved', 'paid', 'completed', 'cancelled', 'expired'],
    default: 'pending'
  },
  approvedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User'
  },
  approvedAt: {
    type: Date
  },
  paymentDeadline: {
    type: Date
  },
  paidAt: {
    type: Date
  },
  confirmedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User'
  },
  totalAmount: {
    type: Number,
    required: true,
    min: 0
  },
  pricePerEgg: {
    type: Number,
    required: true,
    min: 0
  },
  notes: {
    type: String,
    trim: true
  },
  cancelledAt: {
    type: Date
  },
  cancelReason: {
    type: String,
    trim: true
  }
}, {
  timestamps: true
});

// Check if payment window has expired
reservationSchema.methods.isPaymentExpired = function() {
  if (!this.paymentDeadline) return false;
  return new Date() > this.paymentDeadline && this.status === 'approved';
};

// Calculate payment deadline (48 hours from approval)
reservationSchema.methods.setPaymentDeadline = function() {
  if (this.approvedAt) {
    const deadline = new Date(this.approvedAt);
    deadline.setHours(deadline.getHours() + 48);
    this.paymentDeadline = deadline;
  }
};

const Reservation = mongoose.model('Reservation', reservationSchema);

module.exports = Reservation;
