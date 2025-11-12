const Reservation = require('../models/Reservation');
const Inventory = require('../models/Inventory');
const Message = require('../models/Message');

const checkExpiredReservations = async () => {
  try {
    // Find all approved reservations with expired payment deadlines
    const expiredReservations = await Reservation.find({
      status: 'approved',
      paymentDeadline: { $lt: new Date() }
    }).populate('customer', 'name email');

    for (const reservation of expiredReservations) {
      // Release reserved eggs
      const inventory = await Inventory.findOne();
      if (inventory) {
        inventory.reservedEggs -= reservation.numberOfEggs;
        await inventory.save();
      }

      // Update reservation status
      reservation.status = 'expired';
      reservation.cancelledAt = Date.now();
      reservation.cancelReason = 'Payment deadline expired (48 hours)';
      await reservation.save();

      // Send notification to customer
      await Message.create({
        sender: null,
        recipient: reservation.customer._id,
        message: `Your reservation #${reservation._id} has expired due to non-payment within 48 hours. The reserved ${reservation.numberOfEggs} eggs have been released back to inventory.`,
        isSystemMessage: true
      });

      console.log(`Auto-cancelled expired reservation: ${reservation._id}`);
    }

    if (expiredReservations.length > 0) {
      console.log(`Auto-cancelled ${expiredReservations.length} expired reservations`);
    }
  } catch (error) {
    console.error('Error checking expired reservations:', error);
  }
};

// Run check every hour (or based on env variable)
const startExpirationChecker = () => {
  const interval = parseInt(process.env.AUTO_CANCEL_INTERVAL) || 3600000; // Default 1 hour
  
  console.log(`Starting expiration checker with interval: ${interval}ms`);
  
  // Run immediately on start
  checkExpiredReservations();
  
  // Then run on interval
  setInterval(checkExpiredReservations, interval);
};

module.exports = { checkExpiredReservations, startExpirationChecker };
