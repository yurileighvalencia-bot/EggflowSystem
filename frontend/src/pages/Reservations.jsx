import { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import axios from 'axios';

function Reservations() {
  const { isManager, isCustomer } = useAuth();
  const [reservations, setReservations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [formData, setFormData] = useState({
    numberOfEggs: '',
    pricePerEgg: '8',
    notes: ''
  });
  const [message, setMessage] = useState({ type: '', text: '' });
  const [filter, setFilter] = useState('all');

  useEffect(() => {
    fetchReservations();
  }, [filter]);

  const fetchReservations = async () => {
    try {
      const params = filter !== 'all' ? { status: filter } : {};
      const { data } = await axios.get('/api/reservations', { params });
      setReservations(data);
    } catch (error) {
      setMessage({ type: 'error', text: 'Failed to load reservations' });
    } finally {
      setLoading(false);
    }
  };

  const handleCreateReservation = async (e) => {
    e.preventDefault();
    try {
      await axios.post('/api/reservations', {
        numberOfEggs: parseInt(formData.numberOfEggs),
        pricePerEgg: parseFloat(formData.pricePerEgg),
        notes: formData.notes
      });
      setMessage({ type: 'success', text: 'Reservation created successfully!' });
      setFormData({ numberOfEggs: '', pricePerEgg: '8', notes: '' });
      setShowCreateForm(false);
      fetchReservations();
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Failed to create reservation' 
      });
    }
  };

  const handleApprove = async (id) => {
    if (!window.confirm('Approve this reservation?')) return;
    
    try {
      await axios.put(`/api/reservations/${id}/approve`);
      setMessage({ type: 'success', text: 'Reservation approved!' });
      fetchReservations();
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Failed to approve' 
      });
    }
  };

  const handleConfirmPayment = async (id) => {
    if (!window.confirm('Confirm payment for this reservation?')) return;
    
    try {
      await axios.put(`/api/reservations/${id}/confirm-payment`);
      setMessage({ type: 'success', text: 'Payment confirmed and stock deducted!' });
      fetchReservations();
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Failed to confirm payment' 
      });
    }
  };

  const handleCancel = async (id) => {
    const reason = window.prompt('Reason for cancellation (optional):');
    if (reason === null) return; // User clicked cancel
    
    try {
      await axios.put(`/api/reservations/${id}/cancel`, { reason });
      setMessage({ type: 'success', text: 'Reservation cancelled!' });
      fetchReservations();
    } catch (error) {
      setMessage({ 
        type: 'error', 
        text: error.response?.data?.message || 'Failed to cancel' 
      });
    }
  };

  const getStatusBadge = (status) => {
    const badges = {
      pending: 'badge-warning',
      approved: 'badge-info',
      paid: 'badge-success',
      completed: 'badge-success',
      cancelled: 'badge-secondary',
      expired: 'badge-danger'
    };
    return `badge ${badges[status] || 'badge-secondary'}`;
  };

  const getTimeRemaining = (deadline) => {
    if (!deadline) return null;
    const now = new Date();
    const end = new Date(deadline);
    const diff = end - now;
    
    if (diff <= 0) return 'Expired';
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    return `${hours}h ${minutes}m remaining`;
  };

  if (loading) {
    return <div className="container"><div className="loading">Loading reservations...</div></div>;
  }

  return (
    <div className="container">
      <h1>Reservations</h1>

      {message.text && (
        <div className={`alert alert-${message.type}`}>
          {message.text}
        </div>
      )}

      {isCustomer && (
        <div style={{ marginBottom: '20px' }}>
          <button 
            className="btn btn-primary"
            onClick={() => setShowCreateForm(!showCreateForm)}
          >
            {showCreateForm ? 'Cancel' : 'New Reservation'}
          </button>
        </div>
      )}

      {showCreateForm && (
        <div className="card">
          <h2>Create New Reservation</h2>
          <form onSubmit={handleCreateReservation}>
            <div className="form-group">
              <label>Number of Eggs</label>
              <input
                type="number"
                min="1"
                value={formData.numberOfEggs}
                onChange={(e) => setFormData({ ...formData, numberOfEggs: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Price per Egg (₱)</label>
              <input
                type="number"
                step="0.01"
                min="0.01"
                value={formData.pricePerEgg}
                onChange={(e) => setFormData({ ...formData, pricePerEgg: e.target.value })}
                required
              />
            </div>
            <div className="form-group">
              <label>Notes (optional)</label>
              <textarea
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
              />
            </div>
            <div className="form-group">
              <strong>Total Amount: ₱{(formData.numberOfEggs * formData.pricePerEgg || 0).toFixed(2)}</strong>
            </div>
            <button type="submit" className="btn btn-primary">Submit Reservation</button>
          </form>
        </div>
      )}

      <div className="card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px' }}>
          <h2>All Reservations</h2>
          <select 
            value={filter} 
            onChange={(e) => setFilter(e.target.value)}
            style={{ padding: '8px', borderRadius: '4px', border: '1px solid #ddd' }}
          >
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="paid">Paid</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
            <option value="expired">Expired</option>
          </select>
        </div>

        {reservations.length === 0 ? (
          <div className="empty-state">No reservations found</div>
        ) : (
          <table className="table">
            <thead>
              <tr>
                {!isCustomer && <th>Customer</th>}
                <th>Eggs</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Created</th>
                <th>Payment Deadline</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {reservations.map((reservation) => (
                <tr key={reservation._id}>
                  {!isCustomer && <td>{reservation.customer?.name}</td>}
                  <td>{reservation.numberOfEggs}</td>
                  <td>₱{reservation.totalAmount.toFixed(2)}</td>
                  <td>
                    <span className={getStatusBadge(reservation.status)}>
                      {reservation.status}
                    </span>
                  </td>
                  <td>{new Date(reservation.createdAt).toLocaleDateString()}</td>
                  <td>
                    {reservation.paymentDeadline ? (
                      <>
                        {new Date(reservation.paymentDeadline).toLocaleString()}
                        <br />
                        <small style={{ color: '#666' }}>
                          {getTimeRemaining(reservation.paymentDeadline)}
                        </small>
                      </>
                    ) : '-'}
                  </td>
                  <td>
                    <div style={{ display: 'flex', gap: '5px', flexWrap: 'wrap' }}>
                      {isManager && reservation.status === 'pending' && (
                        <button 
                          className="btn btn-primary" 
                          style={{ padding: '5px 10px', fontSize: '12px' }}
                          onClick={() => handleApprove(reservation._id)}
                        >
                          Approve
                        </button>
                      )}
                      {isManager && reservation.status === 'approved' && (
                        <button 
                          className="btn btn-primary" 
                          style={{ padding: '5px 10px', fontSize: '12px' }}
                          onClick={() => handleConfirmPayment(reservation._id)}
                        >
                          Confirm Payment
                        </button>
                      )}
                      {((isCustomer && reservation.status === 'pending') || 
                        (isManager && ['pending', 'approved'].includes(reservation.status))) && (
                        <button 
                          className="btn btn-danger" 
                          style={{ padding: '5px 10px', fontSize: '12px' }}
                          onClick={() => handleCancel(reservation._id)}
                        >
                          Cancel
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}

export default Reservations;
