const Reservation = require('../models/Reservation');
const Inventory = require('../models/Inventory');
const Transaction = require('../models/Transaction');

// @desc    Create new reservation
// @route   POST /api/reservations
// @access  Private (Customer)
const createReservation = async (req, res) => {
  try {
    const { numberOfEggs, pricePerEgg, notes } = req.body;

    if (!numberOfEggs || numberOfEggs <= 0) {
      return res.status(400).json({ message: 'Please provide valid number of eggs' });
    }

    if (!pricePerEgg || pricePerEgg <= 0) {
      return res.status(400).json({ message: 'Please provide valid price per egg' });
    }

    // Check inventory availability
    const inventory = await Inventory.findOne();
    if (!inventory || inventory.availableEggs < numberOfEggs) {
      return res.status(400).json({ 
        message: 'Insufficient stock available',
        available: inventory ? inventory.availableEggs : 0
      });
    }

    const totalAmount = numberOfEggs * pricePerEgg;

    const reservation = await Reservation.create({
      customer: req.user._id,
      numberOfEggs,
      pricePerEgg,
      totalAmount,
      notes,
      status: 'pending'
    });

    await reservation.populate('customer', 'name email phone');

    res.status(201).json(reservation);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Get all reservations
// @route   GET /api/reservations
// @access  Private
const getReservations = async (req, res) => {
  try {
    const { status } = req.query;
    let query = {};

    // Customers can only see their own reservations
    if (req.user.role === 'customer') {
      query.customer = req.user._id;
    }

    if (status) {
      query.status = status;
    }

    const reservations = await Reservation.find(query)
      .populate('customer', 'name email phone')
      .populate('approvedBy', 'name email')
      .populate('confirmedBy', 'name email')
      .sort('-createdAt');

    res.json(reservations);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Get single reservation
// @route   GET /api/reservations/:id
// @access  Private
const getReservation = async (req, res) => {
  try {
    const reservation = await Reservation.findById(req.params.id)
      .populate('customer', 'name email phone address')
      .populate('approvedBy', 'name email')
      .populate('confirmedBy', 'name email');

    if (!reservation) {
      return res.status(404).json({ message: 'Reservation not found' });
    }

    // Customers can only view their own reservations
    if (req.user.role === 'customer' && reservation.customer._id.toString() !== req.user._id.toString()) {
      return res.status(403).json({ message: 'Not authorized to view this reservation' });
    }

    res.json(reservation);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Approve reservation (Manager only)
// @route   PUT /api/reservations/:id/approve
// @access  Private (Manager)
const approveReservation = async (req, res) => {
  try {
    const reservation = await Reservation.findById(req.params.id);

    if (!reservation) {
      return res.status(404).json({ message: 'Reservation not found' });
    }

    if (reservation.status !== 'pending') {
      return res.status(400).json({ message: 'Only pending reservations can be approved' });
    }

    // Check inventory availability
    const inventory = await Inventory.findOne();
    if (!inventory || inventory.availableEggs < reservation.numberOfEggs) {
      return res.status(400).json({ message: 'Insufficient stock available' });
    }

    // Reserve the eggs
    inventory.reservedEggs += reservation.numberOfEggs;
    await inventory.save();

    // Update reservation
    reservation.status = 'approved';
    reservation.approvedBy = req.user._id;
    reservation.approvedAt = Date.now();
    reservation.setPaymentDeadline();
    await reservation.save();

    await reservation.populate('customer', 'name email phone');
    await reservation.populate('approvedBy', 'name email');

    res.json(reservation);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Confirm payment (Manager only)
// @route   PUT /api/reservations/:id/confirm-payment
// @access  Private (Manager)
const confirmPayment = async (req, res) => {
  try {
    const reservation = await Reservation.findById(req.params.id);

    if (!reservation) {
      return res.status(404).json({ message: 'Reservation not found' });
    }

    if (reservation.status !== 'approved') {
      return res.status(400).json({ message: 'Only approved reservations can have payment confirmed' });
    }

    // Update reservation
    reservation.status = 'paid';
    reservation.paidAt = Date.now();
    reservation.confirmedBy = req.user._id;
    await reservation.save();

    // Deduct from inventory
    const inventory = await Inventory.findOne();
    if (inventory) {
      const traysToRemove = reservation.numberOfEggs / 30;
      inventory.totalTrays -= traysToRemove;
      inventory.reservedEggs -= reservation.numberOfEggs;
      await inventory.save();
    }

    // Record transaction
    await Transaction.create({
      type: 'revenue',
      category: 'reservation_sale',
      amount: reservation.totalAmount,
      description: `Reservation #${reservation._id} - ${reservation.numberOfEggs} eggs`,
      relatedReservation: reservation._id,
      recordedBy: req.user._id,
      transactionDate: Date.now()
    });

    // Mark as completed
    reservation.status = 'completed';
    await reservation.save();

    await reservation.populate('customer', 'name email phone');
    await reservation.populate('confirmedBy', 'name email');

    res.json(reservation);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Cancel reservation
// @route   PUT /api/reservations/:id/cancel
// @access  Private
const cancelReservation = async (req, res) => {
  try {
    const { reason } = req.body;
    const reservation = await Reservation.findById(req.params.id);

    if (!reservation) {
      return res.status(404).json({ message: 'Reservation not found' });
    }

    // Customers can only cancel their own pending reservations
    if (req.user.role === 'customer') {
      if (reservation.customer.toString() !== req.user._id.toString()) {
        return res.status(403).json({ message: 'Not authorized' });
      }
      if (reservation.status !== 'pending') {
        return res.status(400).json({ message: 'Only pending reservations can be cancelled' });
      }
    }

    // If approved, release reserved eggs
    if (reservation.status === 'approved') {
      const inventory = await Inventory.findOne();
      if (inventory) {
        inventory.reservedEggs -= reservation.numberOfEggs;
        await inventory.save();
      }
    }

    reservation.status = 'cancelled';
    reservation.cancelledAt = Date.now();
    reservation.cancelReason = reason || 'Cancelled by user';
    await reservation.save();

    res.json(reservation);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

module.exports = {
  createReservation,
  getReservations,
  getReservation,
  approveReservation,
  confirmPayment,
  cancelReservation
};
