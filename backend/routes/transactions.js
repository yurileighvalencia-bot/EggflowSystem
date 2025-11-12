const express = require('express');
const router = express.Router();
const {
  recordWalkInSale,
  recordExpense,
  getTransactions,
  getFinancialSummary
} = require('../controllers/transactionController');
const { protect, authorize } = require('../middleware/auth');

router.post('/walkin-sale', protect, authorize('staff', 'manager'), recordWalkInSale);
router.post('/expense', protect, authorize('manager'), recordExpense);
router.get('/', protect, authorize('manager'), getTransactions);
router.get('/summary', protect, authorize('manager'), getFinancialSummary);

module.exports = router;
