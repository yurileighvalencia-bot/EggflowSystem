import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import axios from 'axios';

function Inventory() {
  const { isManager, isStaff } = useAuth();
  const [inventory, setInventory] = useState(null);
  const [loading, setLoading] = useState(true);
  const [showAddForm, setShowAddForm] = useState(false);
  const [showRemoveForm, setShowRemoveForm] = useState(false);
  const [trays, setTrays] = useState('');
  const [eggs, setEggs] = useState('');
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    fetchInventory();
  }, []);

  const fetchInventory = async () => {
    try {
      const { data } = await axios.get('/api/inventory');
      setInventory(data);
    } catch (error) {
      setMessage({ type: 'error', text: 'Failed to load inventory' });
    } finally {
      setLoading(false);
    }
  };

  const handleAddStock = async (e) => {
    e.preventDefault();
    try {
      const { data } = await axios.post('/api/inventory/add', { 
        trays: parseInt(trays) 
      });
      setMessage({ type: 'success', text: data.message });
      setTrays('');
      setShowAddForm(false);
      fetchInventory();
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Failed to add stock' 
      });
    }
  };

  const handleRemoveStock = async (e) => {
    e.preventDefault();
    try {
      const { data } = await axios.post('/api/inventory/remove', { 
        eggs: parseInt(eggs) 
      });
      setMessage({ type: 'success', text: data.message });
      setEggs('');
      setShowRemoveForm(false);
      fetchInventory();
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Failed to remove stock' 
      });
    }
  };

  if (loading) {
    return <div className="container"><div className="loading">Loading inventory...</div></div>;
  }

  return (
    <div className="container">
      <h1>Inventory Management</h1>

      {message.text && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      {inventory?.isLowStock && (
        <div className="alert alert-warning">
          ⚠️ Low stock alert! Available eggs ({inventory.availableEggs}) 
          is below threshold ({inventory.lowStockThreshold}).
        </div>
      )}

      <div className="stats-grid">
        <div className="stat-card">
          <h3>Total Trays</h3>
          <div className="value">{inventory?.totalTrays || 0}</div>
          <small>{inventory?.eggsPerTray} eggs per tray</small>
        </div>
        <div className="stat-card">
          <h3>Total Eggs</h3>
          <div className="value">{inventory?.totalEggs || 0}</div>
        </div>
        <div className="stat-card">
          <h3>Reserved Eggs</h3>
          <div className="value">{inventory?.reservedEggs || 0}</div>
        </div>
        <div className="stat-card">
          <h3>Available Eggs</h3>
          <div className="value" style={{ color: inventory?.isLowStock ? '#f44336' : '#4CAF50' }}>
            {inventory?.availableEggs || 0}
          </div>
        </div>
      </div>

      {(isStaff || isManager) && (
        <div className="card">
          <h2>Stock Management</h2>
          <div style={{ display: 'flex', gap: '10px', marginBottom: '20px' }}>
            <button 
              className="btn btn-primary"
              onClick={() => {
                setShowAddForm(!showAddForm);
                setShowRemoveForm(false);
              }}
            >
              Add Stock
            </button>
            <button 
              className="btn btn-secondary"
              onClick={() => {
                setShowRemoveForm(!showRemoveForm);
                setShowAddForm(false);
              }}
            >
              Remove Stock (Walk-in Sale)
            </button>
          </div>

          {showAddForm && (
            <form onSubmit={handleAddStock}>
              <div className="form-group">
                <label>Number of Trays to Add</label>
                <input
                  type="number"
                  min="1"
                  value={trays}
                  onChange={(e) => setTrays(e.target.value)}
                  required
                />
                <small>Each tray contains {inventory?.eggsPerTray} eggs</small>
              </div>
              <button type="submit" className="btn btn-primary">Add Trays</button>
              <button 
                type="button" 
                className="btn btn-secondary" 
                onClick={() => setShowAddForm(false)}
                style={{ marginLeft: '10px' }}
              >
                Cancel
              </button>
            </form>
          )}

          {showRemoveForm && (
            <form onSubmit={handleRemoveStock}>
              <div className="form-group">
                <label>Number of Eggs to Remove</label>
                <input
                  type="number"
                  min="1"
                  max={inventory?.availableEggs}
                  value={eggs}
                  onChange={(e) => setEggs(e.target.value)}
                  required
                />
                <small>Available: {inventory?.availableEggs} eggs</small>
              </div>
              <button type="submit" className="btn btn-secondary">Remove Eggs</button>
              <button 
                type="button" 
                className="btn btn-secondary" 
                onClick={() => setShowRemoveForm(false)}
                style={{ marginLeft: '10px' }}
              >
                Cancel
              </button>
            </form>
          )}
        </div>
      )}

      <div className="card">
        <h3>Last Updated</h3>
        <p>{inventory?.lastUpdated ? new Date(inventory.lastUpdated).toLocaleString() : 'N/A'}</p>
        {inventory?.updatedBy && (
          <p>Updated by: {inventory.updatedBy.name}</p>
        )}
      </div>
    </div>
  );
}

export default Inventory;
