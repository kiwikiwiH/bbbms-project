import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Layout() {
  const { user, logout } = useAuth();

  return (
    <div className="layout">
      <header className="topbar">
        <div className="brand">
          <span className="brand-mark">🩸</span>
          <div>
            <strong>BBBMS</strong>
            <small>Blood Bank Management</small>
          </div>
        </div>
        <nav className="nav-links">
          <NavLink to="/">Dashboard</NavLink>
          <NavLink to="/inventory">Inventory</NavLink>
          <NavLink to="/requests">Requests</NavLink>
          <NavLink to="/trace">Trace Unit</NavLink>
        </nav>
        <div className="user-box">
          <span>{user?.name}</span>
          <small>{user?.role}</small>
          <button type="button" className="btn-secondary" onClick={logout}>
            Logout
          </button>
        </div>
      </header>
      <main className="page-content">
        <Outlet />
      </main>
      <footer className="footer">
        Blockchain-Based Blood Bank Management System
      </footer>
    </div>
  );
}
