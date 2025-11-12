import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import axios from 'axios';

function Dashboard() {
  const { user, isManager, isStaff, isCustomer } = useAuth();
  const [stats, setStats] = useState({
    inventory: null,
    reservations: [],
    recentTransactions: [],
    unreadMessages: 0
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      const [inventoryRes, reservationsRes, messagesRes] = await Promise.all([
        axios.get('/api/inventory'),
        axios.get('/api/reservations?status=pending'),
        axios.get('/api/messages/unread-count')
      ]);

      setStats({
        inventory: inventoryRes.data,
        reservations: reservationsRes.data,
        unreadMessages: messagesRes.data.count
      });
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <div className="container"><div className="loading">Loading dashboard...</div></div>;
  }

  return (
    <div className="container">
      <h1>Welcome, {user?.name}!</h1>
      <p style={{ color: '#666', marginBottom: '30px' }}>Role: {user?.role}</p>

      <div className="stats-grid">
        <div className="stat-card">
          <h3>Total Eggs</h3>
          <div className="value">{stats.inventory?.totalEggs || 0}</div>
        </div>
        <div className="stat-card">
          <h3>Available Eggs</h3>
          <div className="value">{stats.inventory?.availableEggs || 0}</div>
        </div>
        <div className="stat-card">
          <h3>Reserved Eggs</h3>
          <div className="value">{stats.inventory?.reservedEggs || 0}</div>
        </div>
        <div className="stat-card">
          <h3>Unread Messages</h3>
          <div className="value">{stats.unreadMessages}</div>
        </div>
      </div>

      {stats.inventory?.isLowStock && (
        <div className="alert alert-warning">
          ⚠️ Low stock alert! Current available eggs ({stats.inventory.availableEggs}) 
          is below threshold ({stats.inventory.lowStockThreshold}).
        </div>
      )}

      {(isManager || isStaff) && stats.reservations.length > 0 && (
        <div className="card">
          <h2>Pending Reservations ({stats.reservations.length})</h2>
          <table className="table">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Eggs</th>
                <th>Amount</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              {stats.reservations.slice(0, 5).map((reservation) => (
                <tr key={reservation._id}>
                  <td>{reservation.customer?.name}</td>
                  <td>{reservation.numberOfEggs}</td>
                  <td>₱{reservation.totalAmount.toFixed(2)}</td>
                  <td>{new Date(reservation.createdAt).toLocaleDateString()}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <div className="card">
        <h2>Quick Actions</h2>
        <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
          {isCustomer && (
            <button 
              className="btn btn-primary"
              onClick={() => window.location.href = '/reservations'}
            >
              New Reservation
            </button>
          )}
          {(isStaff || isManager) && (
            <>
              <button 
                className="btn btn-primary"
                onClick={() => window.location.href = '/inventory'}
              >
                Manage Inventory
              </button>
              <button 
                className="btn btn-secondary"
                onClick={() => window.location.href = '/reservations'}
              >
                View Reservations
              </button>
            </>
          )}
          <button 
            className="btn btn-secondary"
            onClick={() => window.location.href = '/chat'}
          >
            Open Chat
          </button>
        </div>
      </div>
    </div>
  );
}

export default Dashboard;
