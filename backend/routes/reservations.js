const express = require('express');
const router = express.Router();
const {
  createReservation,
  getReservations,
  getReservation,
  approveReservation,
  confirmPayment,
  cancelReservation
} = require('../controllers/reservationController');
const { protect, authorize } = require('../middleware/auth');
const { reservationLimiter } = require('../middleware/rateLimiter');

router.post('/', protect, authorize('customer'), reservationLimiter, createReservation);
router.get('/', protect, getReservations);
router.get('/:id', protect, getReservation);
router.put('/:id/approve', protect, authorize('manager'), approveReservation);
router.put('/:id/confirm-payment', protect, authorize('manager'), confirmPayment);
router.put('/:id/cancel', protect, cancelReservation);

module.exports = router;
