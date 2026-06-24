import { useState } from 'react';
import { traceUnit } from '../services/api';

export default function TraceUnit() {
  const [unitId, setUnitId] = useState('');
  const [result, setResult] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');
    setResult(null);
    setLoading(true);
    try {
      const { data } = await traceUnit(unitId.trim());
      setResult(data);
    } catch (err) {
      setError(err.response?.data?.error || 'Unit not found or trace failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="stack">
      <section className="card">
        <h1>Trace blood unit</h1>
        <p className="muted">Look up inventory and blockchain records for a unit ID.</p>
        <form className="trace-form" onSubmit={handleSubmit}>
          <input
            value={unitId}
            onChange={(e) => setUnitId(e.target.value)}
            placeholder="Enter unit ID e.g. BBMS-001"
            required
          />
          <button type="submit" className="btn-primary" disabled={loading}>
            {loading ? 'Tracing...' : 'Trace'}
          </button>
        </form>
      </section>

      {error && <div className="alert error">{error}</div>}

      {result && (
        <>
          <section className="card">
            <h2>Inventory record</h2>
            <ul className="status-list">
              <li>Unit ID: {result.inventory.unit_id}</li>
              <li>Blood group: {result.inventory.blood_group}</li>
              <li>Status: {result.inventory.status}</li>
              <li>Collected: {result.inventory.collection_date}</li>
              <li>Expires: {result.inventory.expiry_date}</li>
              <li>Facility: {result.inventory.facility_name}</li>
            </ul>
          </section>

          <section className="card">
            <h2>Blockchain transactions</h2>
            {result.blockchain_transactions.length === 0 ? (
              <p className="muted">No blockchain transactions recorded for this unit.</p>
            ) : (
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Action</th>
                      <th>Transaction hash</th>
                      <th>Block</th>
                      <th>Recorded</th>
                    </tr>
                  </thead>
                  <tbody>
                    {result.blockchain_transactions.map((tx) => (
                      <tr key={tx.transaction_hash}>
                        <td>{tx.action}</td>
                        <td className="mono">{tx.transaction_hash}</td>
                        <td>{tx.block_number}</td>
                        <td>{tx.recorded_at}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </section>

          {result.on_chain && (
            <section className="card">
              <h2>On-chain state</h2>
              <ul className="status-list">
                <li>Status: {result.on_chain.status}</li>
                <li>Recorded by: {result.on_chain.recorded_by}</li>
                <li>Timestamp: {result.on_chain.timestamp}</li>
              </ul>
            </section>
          )}
        </>
      )}
    </div>
  );
}
