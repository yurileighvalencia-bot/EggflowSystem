import { useState, useEffect } from 'react';
import axios from 'axios';

function Financials() {
  const [summary, setSummary] = useState(null);
  const [transactions, setTransactions] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showExpenseForm, setShowExpenseForm] = useState(false);
  const [showWalkInForm, setShowWalkInForm] = useState(false);
  const [expenseData, setExpenseData] = useState({
    category: 'feed',
    amount: '',
    description: '',
    reference: ''
  });
  const [walkInData, setWalkInData] = useState({
    eggs: '',
    pricePerEgg: '8',
    description: ''
  });
  const [message, setMessage] = useState({ type: '', text: '' });
  const [dateRange, setDateRange] = useState({
    startDate: '',
    endDate: ''
  });

  useEffect(() => {
    fetchFinancialData();
  }, [dateRange]);

  const fetchFinancialData = async () => {
    try {
      const params = {};
      if (dateRange.startDate) params.startDate = dateRange.startDate;
      if (dateRange.endDate) params.endDate = dateRange.endDate;

      const [summaryRes, transactionsRes] = await Promise.all([
        axios.get('/api/transactions/summary', { params }),
        axios.get('/api/transactions', { params })
      ]);

      setSummary(summaryRes.data);
      setTransactions(transactionsRes.data);
    } catch (error) {
      setMessage({ type: 'error', text: 'Failed to load financial data' });
    } finally {
      setLoading(false);
    }
  };

  const handleRecordExpense = async (e) => {
    e.preventDefault();
    try {
      await axios.post('/api/transactions/expense', {
        category: expenseData.category,
        amount: parseFloat(expenseData.amount),
        description: expenseData.description,
        reference: expenseData.reference
      });
      setMessage({ type: 'success', text: 'Expense recorded successfully!' });
      setExpenseData({ category: 'feed', amount: '', description: '', reference: '' });
      setShowExpenseForm(false);
      fetchFinancialData();
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Failed to record expense' 
      });
    }
  };

  const handleRecordWalkIn = async (e) => {
    e.preventDefault();
    try {
      await axios.post('/api/transactions/walkin-sale', {
        eggs: parseInt(walkInData.eggs),
        pricePerEgg: parseFloat(walkInData.pricePerEgg),
        description: walkInData.description
      });
      setMessage({ type: 'success', text: 'Walk-in sale recorded successfully!' });
      setWalkInData({ eggs: '', pricePerEgg: '8', description: '' });
      setShowWalkInForm(false);
      fetchFinancialData();
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Failed to record sale' 
      });
    }
  };

  if (loading) {
    return <div className="container"><div className="loading">Loading financial data...</div></div>;
  }

  return (
    <div className="container">
      <h1>Financial Management</h1>

      {message.text && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      <div className="card">
        <h3>Date Range Filter</h3>
        <div style={{ display: 'flex', gap: '10px', alignItems: 'end' }}>
          <div className="form-group" style={{ marginBottom: 0 }}>
            <label>Start Date</label>
            <input
              type="date"
              value={dateRange.startDate}
              onChange={(e) => setDateRange({ ...dateRange, startDate: e.target.value })}
            />
          </div>
          <div className="form-group" style={{ marginBottom: 0 }}>
            <label>End Date</label>
            <input
              type="date"
              value={dateRange.endDate}
              onChange={(e) => setDateRange({ ...dateRange, endDate: e.target.value })}
            />
          </div>
          <button 
            className="btn btn-secondary"
            onClick={() => setDateRange({ startDate: '', endDate: '' })}
          >
            Clear
          </button>
        </div>
      </div>

      <div className="stats-grid">
        <div className="stat-card">
          <h3>Total Revenue</h3>
          <div className="value" style={{ color: '#4CAF50' }}>
            ₱{summary?.revenue?.toFixed(2) || '0.00'}
          </div>
          <small>{summary?.revenueCount || 0} transactions</small>
        </div>
        <div className="stat-card">
          <h3>Total Expenses</h3>
          <div className="value" style={{ color: '#f44336' }}>
            ₱{summary?.expense?.toFixed(2) || '0.00'}
          </div>
          <small>{summary?.expenseCount || 0} transactions</small>
        </div>
        <div className="stat-card">
          <h3>Net Profit</h3>
          <div className="value" style={{ color: summary?.profit >= 0 ? '#4CAF50' : '#f44336' }}>
            ₱{summary?.profit?.toFixed(2) || '0.00'}
          </div>
        </div>
      </div>

      {summary?.categoryBreakdown && summary.categoryBreakdown.length > 0 && (
        <div className="card">
          <h2>Category Breakdown</h2>
          <table className="table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Transactions</th>
              </tr>
            </thead>
            <tbody>
              {summary.categoryBreakdown.map((item, index) => (
                <tr key={index}>
                  <td>
                    <span className={`badge ${item.type === 'revenue' ? 'badge-success' : 'badge-danger'}`}>
                      {item.type}
                    </span>
                  </td>
                  <td>{item.category}</td>
                  <td>₱{item.total.toFixed(2)}</td>
                  <td>{item.count}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <div className="card">
        <h2>Record Transaction</h2>
        <div style={{ display: 'flex', gap: '10px', marginBottom: '20px' }}>
          <button 
            className="btn btn-primary"
            onClick={() => {
              setShowWalkInForm(!showWalkInForm);
              setShowExpenseForm(false);
            }}
          >
            Record Walk-in Sale
          </button>
          <button 
            className="btn btn-secondary"
            onClick={() => {
              setShowExpenseForm(!showExpenseForm);
              setShowWalkInForm(false);
            }}
          >
            Record Expense
          </button>
        </div>

        {showWalkInForm && (
          <form onSubmit={handleRecordWalkIn}>
            <h3>Walk-in Sale</h3>
            <div className="form-group">
              <label>Number of Eggs</label>
              <input
                type="number"
                min="1"
                value={walkInData.eggs}
                onChange={(e) => setWalkInData({ ...walkInData, eggs: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Price per Egg (₱)</label>
              <input
                type="number"
                step="0.01"
                min="0.01"
                value={walkInData.pricePerEgg}
                onChange={(e) => setWalkInData({ ...walkInData, pricePerEgg: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Description (optional)</label>
              <input
                type="text"
                value={walkInData.description}
                onChange={(e) => setWalkInData({ ...walkInData, description: e.target.value })}
              />
            </div>
            <div className="form-group">
              <strong>Total Amount: ₱{(walkInData.eggs * walkInData.pricePerEgg || 0).toFixed(2)}</strong>
            </div>
            <button type="submit" className="btn btn-primary">Record Sale</button>
            <button 
              type="button" 
              className="btn btn-secondary" 
              onClick={() => setShowWalkInForm(false)}
              style={{ marginLeft: '10px' }}
            >
              Cancel
            </button>
          </form>
        )}

        {showExpenseForm && (
          <form onSubmit={handleRecordExpense}>
            <h3>Record Expense</h3>
            <div className="form-group">
              <label>Category</label>
              <select
                value={expenseData.category}
                onChange={(e) => setExpenseData({ ...expenseData, category: e.target.value })}
                required
              >
                <option value="feed">Feed</option>
                <option value="labor">Labor</option>
                <option value="utilities">Utilities</option>
                <option value="maintenance">Maintenance</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div className="form-group">
              <label>Amount (₱)</label>
              <input
                type="number"
                step="0.01"
                min="0.01"
                value={expenseData.amount}
                onChange={(e) => setExpenseData({ ...expenseData, amount: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Description</label>
              <input
                type="text"
                value={expenseData.description}
                onChange={(e) => setExpenseData({ ...expenseData, description: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Reference (optional)</label>
              <input
                type="text"
                value={expenseData.reference}
                onChange={(e) => setExpenseData({ ...expenseData, reference: e.target.value })}
              />
            </div>
            <button type="submit" className="btn btn-primary">Record Expense</button>
            <button 
              type="button" 
              className="btn btn-secondary" 
              onClick={() => setShowExpenseForm(false)}
              style={{ marginLeft: '10px' }}
            >
              Cancel
            </button>
          </form>
        )}
      </div>

      <div className="card">
        <h2>Recent Transactions</h2>
        {transactions.length === 0 ? (
          <div className="empty-state">No transactions found</div>
        ) : (
          <table className="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Recorded By</th>
              </tr>
            </thead>
            <tbody>
              {transactions.map((transaction) => (
                <tr key={transaction._id}>
                  <td>{new Date(transaction.transactionDate).toLocaleString()}</td>
                  <td>
                    <span className={`badge ${transaction.type === 'revenue' ? 'badge-success' : 'badge-danger'}`}>
                      {transaction.type}
                    </span>
                  </td>
                  <td>{transaction.category}</td>
                  <td>{transaction.description}</td>
                  <td style={{ color: transaction.type === 'revenue' ? '#4CAF50' : '#f44336' }}>
                    {transaction.type === 'revenue' ? '+' : '-'}₱{transaction.amount.toFixed(2)}
                  </td>
                  <td>{transaction.recordedBy?.name}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

export default Financials;
