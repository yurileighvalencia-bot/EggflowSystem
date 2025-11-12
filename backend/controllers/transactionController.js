const Transaction = require('../models/Transaction');

// @desc    Record walk-in sale
// @route   POST /api/transactions/walkin-sale
// @access  Private (Staff, Manager)
const recordWalkInSale = async (req, res) => {
  try {
    const { eggs, pricePerEgg, description } = req.body;

    if (!eggs || eggs <= 0) {
      return res.status(400).json({ message: 'Please provide valid number of eggs' });
    }

    if (!pricePerEgg || pricePerEgg <= 0) {
      return res.status(400).json({ message: 'Please provide valid price per egg' });
    }

    const amount = eggs * pricePerEgg;

    const transaction = await Transaction.create({
      type: 'revenue',
      category: 'walkin_sale',
      amount,
      description: description || `Walk-in sale - ${eggs} eggs`,
      recordedBy: req.user._id
    });

    await transaction.populate('recordedBy', 'name email');

    res.status(201).json(transaction);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Record expense
// @route   POST /api/transactions/expense
// @access  Private (Manager)
const recordExpense = async (req, res) => {
  try {
    const { category, amount, description, reference } = req.body;

    if (!category || !amount || !description) {
      return res.status(400).json({ message: 'Please provide category, amount, and description' });
    }

    if (amount <= 0) {
      return res.status(400).json({ message: 'Amount must be greater than 0' });
    }

    const validExpenseCategories = ['feed', 'labor', 'utilities', 'maintenance', 'other'];
    if (!validExpenseCategories.includes(category)) {
      return res.status(400).json({ message: 'Invalid expense category' });
    }

    const transaction = await Transaction.create({
      type: 'expense',
      category,
      amount,
      description,
      reference,
      recordedBy: req.user._id
    });

    await transaction.populate('recordedBy', 'name email');

    res.status(201).json(transaction);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Get all transactions
// @route   GET /api/transactions
// @access  Private (Manager)
const getTransactions = async (req, res) => {
  try {
    const { type, category, startDate, endDate } = req.query;
    let query = {};

    if (type) {
      query.type = type;
    }

    if (category) {
      query.category = category;
    }

    if (startDate || endDate) {
      query.transactionDate = {};
      if (startDate) {
        query.transactionDate.$gte = new Date(startDate);
      }
      if (endDate) {
        query.transactionDate.$lte = new Date(endDate);
      }
    }

    const transactions = await Transaction.find(query)
      .populate('recordedBy', 'name email')
      .populate('relatedReservation')
      .sort('-transactionDate');

    res.json(transactions);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Get financial summary
// @route   GET /api/transactions/summary
// @access  Private (Manager)
const getFinancialSummary = async (req, res) => {
  try {
    const { startDate, endDate } = req.query;
    let dateFilter = {};

    if (startDate || endDate) {
      if (startDate) {
        dateFilter.$gte = new Date(startDate);
      }
      if (endDate) {
        dateFilter.$lte = new Date(endDate);
      }
    }

    const pipeline = [
      ...(Object.keys(dateFilter).length > 0 ? [{ $match: { transactionDate: dateFilter } }] : []),
      {
        $group: {
          _id: '$type',
          total: { $sum: '$amount' },
          count: { $sum: 1 }
        }
      }
    ];

    const summary = await Transaction.aggregate(pipeline);

    const result = {
      revenue: 0,
      expense: 0,
      profit: 0,
      revenueCount: 0,
      expenseCount: 0
    };

    summary.forEach(item => {
      if (item._id === 'revenue') {
        result.revenue = item.total;
        result.revenueCount = item.count;
      } else if (item._id === 'expense') {
        result.expense = item.total;
        result.expenseCount = item.count;
      }
    });

    result.profit = result.revenue - result.expense;

    // Get category breakdown
    const categoryPipeline = [
      ...(Object.keys(dateFilter).length > 0 ? [{ $match: { transactionDate: dateFilter } }] : []),
      {
        $group: {
          _id: { type: '$type', category: '$category' },
          total: { $sum: '$amount' },
          count: { $sum: 1 }
        }
      }
    ];

    const categoryBreakdown = await Transaction.aggregate(categoryPipeline);

    result.categoryBreakdown = categoryBreakdown.map(item => ({
      type: item._id.type,
      category: item._id.category,
      total: item.total,
      count: item.count
    }));

    res.json(result);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

module.exports = {
  recordWalkInSale,
  recordExpense,
  getTransactions,
  getFinancialSummary
};
