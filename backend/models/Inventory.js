const mongoose = require('mongoose');

const inventorySchema = new mongoose.Schema({
  totalTrays: {
    type: Number,
    required: true,
    default: 0,
    min: 0
  },
  eggsPerTray: {
    type: Number,
    default: 30,
    immutable: true
  },
  reservedEggs: {
    type: Number,
    default: 0,
    min: 0
  },
  lastUpdated: {
    type: Date,
    default: Date.now
  },
  updatedBy: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User'
  }
}, {
  timestamps: true
});

// Virtual for total eggs
inventorySchema.virtual('totalEggs').get(function() {
  return this.totalTrays * this.eggsPerTray;
});

// Virtual for available eggs
inventorySchema.virtual('availableEggs').get(function() {
  return (this.totalTrays * this.eggsPerTray) - this.reservedEggs;
});

// Method to check if stock is low
inventorySchema.methods.isLowStock = function(threshold = 100) {
  return this.availableEggs < threshold;
};

// Ensure virtuals are included in JSON
inventorySchema.set('toJSON', { virtuals: true });
inventorySchema.set('toObject', { virtuals: true });

const Inventory = mongoose.model('Inventory', inventorySchema);

module.exports = Inventory;
