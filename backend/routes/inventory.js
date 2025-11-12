const express = require('express');
const router = express.Router();
const {
  getInventory,
  addStock,
  removeStock,
  getInventoryStats
} = require('../controllers/inventoryController');
const { protect, authorize } = require('../middleware/auth');

router.get('/', protect, getInventory);
router.post('/add', protect, authorize('staff', 'manager'), addStock);
router.post('/remove', protect, authorize('staff', 'manager'), removeStock);
router.get('/stats', protect, authorize('manager'), getInventoryStats);

module.exports = router;
