import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

function Navbar() {
  const { user, logout, isManager, isStaff } = useAuth();

  return (
    <nav className="navbar">
      <div className="navbar-brand">🥚 EggFlow System</div>
      <ul className="navbar-nav">
        <li><Link to="/dashboard">Dashboard</Link></li>
        <li><Link to="/inventory">Inventory</Link></li>
        <li><Link to="/reservations">Reservations</Link></li>
        {isManager && <li><Link to="/financials">Financials</Link></li>}
        <li><Link to="/chat">Chat</Link></li>
        <li>
          <span style={{ marginRight: '10px' }}>
            {user?.name} ({user?.role})
          </span>
          <button onClick={logout} className="btn btn-danger" style={{ padding: '5px 15px' }}>
            Logout
          </button>
        </li>
      </ul>
    </nav>
  );
}

export default Navbar;
