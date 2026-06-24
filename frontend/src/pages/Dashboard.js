import { useEffect, useState } from 'react';
import { healthCheck } from '../services/api';
import { useAuth } from '../context/AuthContext';

export default function Dashboard() {
  const { user } = useAuth();
  const [health, setHealth] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    healthCheck()
      .then(({ data }) => setHealth(data))
      .catch(() => setError('Could not reach backend API. Start Flask on port 5000.'));
  }, []);

  return (
    <div className="stack">
      <section className="hero card">
        <h1>Welcome, {user?.name}</h1>
        <p className="muted">
          Role: <strong>{user?.role}</strong>. Monitor system health, inventory, and blood requests.
        </p>
      </section>

      <section className="grid two">
        <div className="card">
          <h2>System health</h2>
          {error && <div className="alert error">{error}</div>}
          {!health && !error && <p className="muted">Checking services...</p>}
          {health && (
            <ul className="status-list">
              <li>
                API status:{' '}
                <span className={health.status === 'ok' ? 'badge ok' : 'badge warn'}>
                  {health.status}
                </span>
              </li>
              <li>
                Database:{' '}
                <span className={health.database.connected ? 'badge ok' : 'badge bad'}>
                  {health.database.connected ? 'connected' : health.database.message}
                </span>
              </li>
              <li>
                Blockchain:{' '}
                <span className={health.blockchain.connected ? 'badge ok' : 'badge bad'}>
                  {health.blockchain.connected ? 'connected' : 'offline'}
                </span>
              </li>
              <li>RPC URL: {health.blockchain.rpc_url}</li>
              <li>
                Contract:{' '}
                {health.blockchain.contract_configured
                  ? health.blockchain.contract_address
                  : 'not configured'}
              </li>
            </ul>
          )}
        </div>

        <div className="card">
          <h2>Quick actions</h2>
          <ul className="status-list">
            {user?.role === 'hospital' && <li>Submit blood requests from the Requests page.</li>}
            {(user?.role === 'admin' || user?.role === 'lab') && (
              <li>Add collected blood units from the Inventory page.</li>
            )}
            <li>Trace any blood unit using its unique ID on the Trace page.</li>
            <li>Ensure MySQL is running and Ganache is started for full blockchain traceability.</li>
          </ul>
        </div>
      </section>
    </div>
  );
}
