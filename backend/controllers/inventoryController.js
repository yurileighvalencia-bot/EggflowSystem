const Inventory = require('../models/Inventory');

// @desc    Get current inventory
// @route   GET /api/inventory
// @access  Private
const getInventory = async (req, res) => {
  try {
    let inventory = await Inventory.findOne().populate('updatedBy', 'name email');
    
    if (!inventory) {
      // Initialize inventory if it doesn't exist
      inventory = await Inventory.create({
        totalTrays: 0,
        reservedEggs: 0
      });
    }

    const lowStockThreshold = parseInt(process.env.LOW_STOCK_THRESHOLD) || 100;
    
    res.json({
      ...inventory.toObject(),
      isLowStock: inventory.isLowStock(lowStockThreshold),
      lowStockThreshold
    });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Add stock (trays)
// @route   POST /api/inventory/add
// @access  Private (Staff, Manager)
const addStock = async (req, res) => {
  try {
    const { trays } = req.body;

    if (!trays || trays <= 0) {
      return res.status(400).json({ message: 'Please provide valid number of trays' });
    }

    let inventory = await Inventory.findOne();
    
    if (!inventory) {
      inventory = await Inventory.create({
        totalTrays: trays,
        updatedBy: req.user._id
      });
    } else {
      inventory.totalTrays += trays;
      inventory.updatedBy = req.user._id;
      inventory.lastUpdated = Date.now();
      await inventory.save();
    }

    res.json({
      message: `Successfully added ${trays} trays (${trays * 30} eggs)`,
      inventory
    });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Remove stock (for walk-in sales)
// @route   POST /api/inventory/remove
// @access  Private (Staff, Manager)
const removeStock = async (req, res) => {
  try {
    const { eggs } = req.body;

    if (!eggs || eggs <= 0) {
      return res.status(400).json({ message: 'Please provide valid number of eggs' });
    }

    let inventory = await Inventory.findOne();
    
    if (!inventory) {
      return res.status(404).json({ message: 'Inventory not found' });
    }

    if (inventory.availableEggs < eggs) {
      return res.status(400).json({ 
        message: `Insufficient stock. Available: ${inventory.availableEggs} eggs` 
      });
    }

    const traysToRemove = eggs / 30;
    inventory.totalTrays -= traysToRemove;
    inventory.updatedBy = req.user._id;
    inventory.lastUpdated = Date.now();
    await inventory.save();

    res.json({
      message: `Successfully removed ${eggs} eggs`,
      inventory
    });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// @desc    Get inventory history/stats
// @route   GET /api/inventory/stats
// @access  Private (Manager)
const getInventoryStats = async (req, res) => {
  try {
    const inventory = await Inventory.findOne();
    
    if (!inventory) {
      return res.status(404).json({ message: 'Inventory not found' });
    }

    res.json({
      totalTrays: inventory.totalTrays,
      totalEggs: inventory.totalEggs,
      reservedEggs: inventory.reservedEggs,
      availableEggs: inventory.availableEggs,
      eggsPerTray: inventory.eggsPerTray,
      lastUpdated: inventory.lastUpdated,
      isLowStock: inventory.isLowStock()
    });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

module.exports = {
  getInventory,
  addStock,
  removeStock,
  getInventoryStats
};
